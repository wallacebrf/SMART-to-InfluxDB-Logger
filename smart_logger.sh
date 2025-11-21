#!/bin/bash
# shellcheck disable=SC2162,SC2004,SC2129,SC2116,SC2321,SC2027,SC2086,SC2219
#Version 11/19/2025
#made updates to appease shell check
#By Brian Wallace
#########################################################


#check that the script is running as root or some of the commands required will not work
if [[ $( whoami ) != "root" ]]; then
	echo -e "ERROR - Script requires ROOT permissions, exiting script"
	exit 1
fi

if [ -d "/mnt/ramfs" ]; then
	echo "RAM disk \"/mnt/ramfs\" Exists"
else
	echo "creating RAM disk \"/mnt/ramfs\""
	mkdir /mnt/ramfs && mount -t tmpfs -o size=100m ramdisk /mnt/ramfs
fi

#########################################################
#USER ADJUSABLE SCRIPT VARIABLES
email_contents="/mnt/volume1/logging/notifications/SMART_Logging_email_contents.txt"					#when email notifications are sent, the contents of the email and a log entry if the email successfully sent is saved to this file
lock_file_location="/mnt/volume1/logging/notifications/SMART_Logging.lock"								#file created while the script is running and deleted when the script is done. this is to prevent more than one copy of the script from running at a time
email_last_sent="/mnt/volume1/logging/notifications/${0##*/}_SMART_Logging_last_message_sent.txt"		#some emails are to be sent only every 60 minutes (like config file missing/corrupt messages) to prevent your inbox from being spammed
debug=0																									#if set to "1" the script will display all of the collected data being sent to InfluxDB
nas_name_error="TrueNAS"																				#if the config file fails to load, this will ensure the script describes what system the email is from
synology=0																								#set to a value of 1 if using synology															
config_file_location="/mnt/volume1/hosting/web/config/config_files"
config_file_name="smart_logging_config.txt"
measurement="TrueNAS_SMART_status"
nas_name="TrueNAS"
use_sendmail=2  #use TrueNAS																						#set to "1" to use "sendmail" command, set to "0" to use the "ssmtp" command when sending email notifications, set to "2" if using trueNAS

#########################################################
#EMAIL SETTINGS USED IF CONFIGURATION FILE IS UNAVAILABLE
#These variables will be overwritten with new corrected data if the configuration file loads properly. 
#If the config file does not load properly, then the script will still be able to send alert emails informing the user the config file is missing/corrupted
email_address="email@email.com"
from_email_address="email@email.com"
#########################################################


#create a lock file in the configuration directory to prevent more than one instance of this script from executing  at once
if ! mkdir "$lock_file_location"; then
	printf "Failed to acquire lock.\n" >&2
	exit 1
fi
trap 'rm -rf "$lock_file_location"' EXIT #remove the lockdir on exit

#########################################################
#this function pings google.com to confirm internet access is working prior to sending email notifications 
#########################################################
check_internet() {
	if [[ $synology != 0 ]]; then
		ping -c1 "google.com" > /dev/null #ping google.com									
			local status=$?
			if ! (exit $status); then
				false
			else
				true
			fi
	else
		true
	fi
}

##################################################################################################################
#Flash drive check Function
#By Dave Russell "007revad"
##################################################################################################################
# shellcheck disable=SC2005 #don't complain about useless cat in this function
function not_flash_drive(){
	# $1 is /dev/sata1 /dev/usb1 etc
	# Check if drive is flash drive (not supported by smartctl)
	local removable
	local capability
	local device
	device=$(echo "$(basename "$1")" | cut -d":" -f1)
	removable=$(cat "/sys/block/$device/removable")
	capability=$(cat "/sys/block/$device/capability")
	if [[ $removable == "1" ]] && [[ $capability == "51" ]]; then
		return 1
	fi
}

##################################################################################################################
#USB drive check Function
#By Dave Russell "007revad"
##################################################################################################################
function is_usb(){
    # $1 is /dev/sda etc
    if realpath /sys/block/"$(basename "$1")" | grep -q usb; then
        return 0
    else
        return 1
    fi
}
	
#########################################################
#this function is used to send notifications
#########################################################
function send_mail(){
#email_last_sent_log_file=${1}			this file contains the UNIX time stamp of when the email is sent so we can track how long ago an email was last sent
#message_text=${2}						this string of text contains the body of the email message
#email_subject=${3}						this string of text contains the email subject line
#email_contents_file=${4}				this file is where the contents of the email are saved prior to sending and it contains the log of the email transmission, either will indicated email sent successfully or will include the error details
#error_message=${5}						this string of text is only displayed when the script is executed from the CLI, it will be part of the error message if the email is not sent correctly
#email_interval=${6}					this numerical value will control how many minutes must pass before the next email is allowed to be sent
#use_sendmail=${7}						this will control if "sendmail" or "ssmtp" will be used to send emails.
	#check to make sure the email address infomation is not blank
	if [[ $from_email_address == "" || $email_address == "" ]]; then
		echo "From / To email address information is blank, cannot send email notifications"
		return
	fi
	
	#make sure the email address at least contains an "@" symbol and a "." as email addresses must have those
	if [[ $(echo "$email_address" | grep "@") == "" || $(echo "$from_email_address" | grep "@") == "" || $(echo "$from_email_address" | grep ".") == "" || $(echo "$email_address" | grep ".") == "" ]]; then
		echo "From / To email address information is not an email address, cannot send email notifications"
		return
	fi
	
	local message_tracker=""
	local time_diff=0
	echo -e "${2}"
	echo ""
	if check_internet; then
		local current_time
		current_time=$( date +%s )
		if [ -r "${1}" ]; then #file is available and readable 
			read message_tracker < "${1}"
			time_diff=$((( $current_time - $message_tracker ) / 60 ))
		else
			echo -n "$current_time" > "${1}"
			time_diff=$(( ${6} + 1 ))
		fi
				
		if [ "$time_diff" -ge "${6}" ]; then
			local now
			now=$(date +"%T")
			echo "the email has not been sent in over ${6} minutes, re-sending email"
			if [[ ${7} == 1 ]]; then #if this is a value of 1, use "sendmail" command
				#verify the "sendmail" command is installed / available on the system
				if ! command -v sendmail &> /dev/null; then
					echo -e "\"sendmail\" command is not installed / available, unable to send email notification. Try using the \"ssmtp\" command\n" |& tee -a "${4}"
					return
				fi
				echo "from: $from_email_address " > "${4}"
				echo "to: $email_address " >> "${4}"
				echo "subject: ${3}" >> "${4}"
				echo "" >> "${4}"
				echo -e "$now - ${2}" >> "${4}" #adding the mailbody text. 
				local email_response
				email_response=$(sendmail -t < "${4}"  2>&1)
				if [[ "$email_response" == "" ]]; then
					echo "" |& tee -a "${4}"
					echo -e "Email to \"$email_address\" Sent Successfully\n" |& tee -a "${4}"
					message_tracker=$current_time
					time_diff=0
					echo -n "$message_tracker" > "${1}"
				else
					echo -e "Warning, an error occurred while sending the ${5} notification email. the error was: $email_response\n" |& tee -a "${4}"
				fi
			elif [[ ${7} == 2 ]]; then #if this is a value of 1, use TrueNAS commands
				#https://github.com/oxyde1989/standalone-tn-send-email/tree/main
				
				#the command can only take one email address destination at a time. so if there are more than one email addresses in the list, we need to send them one at a time
				address_explode=(`echo "$email_address" | sed 's/;/\n/g'`)
				local bb=0
				for bb in "${!address_explode[@]}"; do
					python3 /mnt/volume1/logging/multireport_sendemail.py --subject "${3}" --to_address "${address_explode[$bb]}" --mail_body_html "$now - ${2}" --override_fromemail "$from_email_address"
				done
			else #since the value is not equal to 1, use ssmtp command
				#verify the "ssmtp" command is installed / available on the system
				if ! command -v ssmtp &> /dev/null; then
					echo -e "\"ssmtp\" command is not installed / available, unable to send email notification. Try using the \"sendmail\" command\n" |& tee -a "${4}"
					return
				fi
				echo "From: $from_email_address " > "${4}"
				echo "Subject: ${3}" >> "${4}"
				echo "" >> "${4}"
				echo -e "\n$now - ${2}\n" >> "${4}" #adding the mailbody text. 
				
				#the "ssmtp" command can only take one email address destination at a time. so if there are more than one email addresses in the list, we need to send them one at a time
				#address_explode=(`echo $email_address | sed 's/;/\n/g'`) #explode on the semicolon separating the different possible addresses
				IFS=$';' read -d '' -r -a address_explode < "$email_address"
				local bb=0
				for bb in "${!address_explode[@]}"; do
					local email_response
					email_response=$(ssmtp "${address_explode[$bb]}" < "${4}"  2>&1)
					if [[ "$email_response" == "" ]]; then
						echo "" |& tee -a "${4}"
						echo -e "Email to \"${address_explode[$bb]}\" Sent Successfully\n" |& tee -a "${4}"
						message_tracker=$current_time
						time_diff=0
						echo -n "$message_tracker" > "${1}"
					else
						echo -e "Warning, an error occurred while sending the ${5} notification email. the error was: $email_response\n" |& tee -a "${4}"
					fi
				done
			fi
		else
			echo -e "Only $time_diff minuets have passed since the last notification, email will be sent every ${6} minutes. $(( ${6} - $time_diff )) Minutes Remaining Until Next Email\n"
		fi
	else
		echo -e "Internet is not available, skipping sending email\n" |& tee -a "${4}"
	fi
}	

if [ -r "$config_file_location"/"$config_file_name" ]; then
	#file is available and readable 
	
	#read in file, explode the configuration into an array with the colon as the delimiter
	IFS=$',' read -d '' -r -a explode < "$config_file_location/$config_file_name"
	
	#verify the correct number of configuration parameters are in the configuration file
	if [[ ! ${#explode[@]} == 99 ]]; then
		send_mail "$email_last_sent" "WARNING - the configuration file is incorrect or corrupted. It should have 99 parameters, it currently has ${#explode[@]} parameters." "Warning NAS \"$nas_name\" SNMP Monitoring Failed for script \"${0##*/}\" - Configuration file is incorrect" "$email_contents" "Config File Error" 60 "$use_sendmail"
		exit 1
	fi	
	paramter_name=()
	paramter_notification_threshold=()
	paramter_type=()
	
	#save the parameter values into the respective variable and remove the quotes
	influxdb_host="${explode[0]}"
	influxdb_port="${explode[1]}"
	influxdb_name="${explode[2]}"
	influxdb_pass="${explode[3]}"
	script_enable="${explode[4]}"
	influxdb_org="${explode[5]}"
	enable_email_notifications="${explode[6]}"
	email_address="${explode[7]}"
	paramter_name+=("${explode[8]}")
	paramter_notification_threshold+=("${explode[9]}")
	paramter_name+=("${explode[10]}")
	paramter_notification_threshold+=("${explode[11]}")
	paramter_name+=("${explode[12]}")
	paramter_notification_threshold+=("${explode[13]}")
	paramter_name+=("${explode[14]}")
	paramter_notification_threshold+=("${explode[15]}")
	paramter_name+=("${explode[16]}")
	paramter_notification_threshold+=("${explode[17]}")
	from_email_address="${explode[18]}"
	paramter_type+=("${explode[19]}")
	paramter_type+=("${explode[20]}")
	paramter_type+=("${explode[21]}")
	paramter_type+=("${explode[22]}")
	paramter_type+=("${explode[23]}")
	paramter_type+=("${explode[24]}")
	paramter_type+=("${explode[25]}")
	paramter_type+=("${explode[26]}")
	paramter_type+=("${explode[27]}")
	paramter_type+=("${explode[28]}")
	paramter_type+=("${explode[29]}")
	paramter_type+=("${explode[30]}")
	paramter_type+=("${explode[31]}")
	paramter_type+=("${explode[32]}")
	paramter_type+=("${explode[33]}")
	paramter_type+=("${explode[34]}")
	paramter_type+=("${explode[35]}")
	paramter_type+=("${explode[36]}")
	paramter_type+=("${explode[37]}")
	paramter_type+=("${explode[38]}")
	paramter_name+=("${explode[39]}")
	paramter_notification_threshold+=("${explode[40]}")
	paramter_name+=("${explode[41]}")
	paramter_notification_threshold+=("${explode[42]}")
	paramter_name+=("${explode[43]}")
	paramter_notification_threshold+=("${explode[44]}")
	paramter_name+=("${explode[45]}")
	paramter_notification_threshold+=("${explode[46]}")
	paramter_name+=("${explode[47]}")
	paramter_notification_threshold+=("${explode[48]}")
	paramter_name+=("${explode[49]}")
	paramter_notification_threshold+=("${explode[50]}")
	paramter_name+=("${explode[51]}")
	paramter_notification_threshold+=("${explode[52]}")
	paramter_name+=("${explode[53]}")
	paramter_notification_threshold+=("${explode[54]}")
	paramter_name+=("${explode[55]}")
	paramter_notification_threshold+=("${explode[56]}")
	paramter_name+=("${explode[57]}")
	paramter_notification_threshold+=("${explode[58]}")
	paramter_name+=("${explode[59]}")
	paramter_notification_threshold+=("${explode[60]}")
	paramter_name+=("${explode[61]}")
	paramter_notification_threshold+=("${explode[62]}")
	paramter_name+=("${explode[63]}")
	paramter_notification_threshold+=("${explode[64]}")
	paramter_name+=("${explode[65]}")
	paramter_notification_threshold+=("${explode[66]}")
	paramter_name+=("${explode[67]}")
	paramter_notification_threshold+=("${explode[68]}")
	
	paramter_name+=("${explode[69]}")
	paramter_notification_threshold+=("${explode[70]}")
	paramter_type+=("${explode[71]}")
	paramter_name+=("${explode[72]}")
	paramter_notification_threshold+=("${explode[73]}")
	paramter_type+=("${explode[74]}")
	paramter_name+=("${explode[75]}")
	paramter_notification_threshold+=("${explode[76]}")
	paramter_type+=("${explode[77]}")
	paramter_name+=("${explode[78]}")
	paramter_notification_threshold+=("${explode[79]}")
	paramter_type+=("${explode[80]}")
	paramter_name+=("${explode[81]}")
	paramter_notification_threshold+=("${explode[82]}")
	paramter_type+=("${explode[83]}")
	paramter_name+=("${explode[84]}")
	paramter_notification_threshold+=("${explode[85]}")
	paramter_type+=("${explode[86]}")
	paramter_name+=("${explode[87]}")
	paramter_notification_threshold+=("${explode[88]}")
	paramter_type+=("${explode[89]}")
	paramter_name+=("${explode[90]}")
	paramter_notification_threshold+=("${explode[91]}")
	paramter_type+=("${explode[92]}")
	paramter_name+=("${explode[93]}")
	paramter_notification_threshold+=("${explode[94]}")
	paramter_type+=("${explode[95]}")
	paramter_name+=("${explode[96]}")
	paramter_notification_threshold+=("${explode[97]}")
	paramter_type+=("${explode[98]}")

	if [ "$script_enable" -eq 1 ]; then
		post_url=""

		##################################################################################################################
		#Get listing of all installed SATA drives in the system
		##################################################################################################################
		disk_list1=$(fdisk -l 2>/dev/null | grep -E "Disk /dev/(sata|sas)*[0-9]:")	#some systems have drives listed as /stata1, /sata2, /sas1 etc
		disk_list2=$(fdisk -l 2>/dev/null | grep -E "Disk /dev/(sd|hd)")			#some systems have drives listed as /sda, /sdb, /hda etc
		disk_list3=$(fdisk -l 2>/dev/null | grep "Disk /dev/usb")					#usb drives

		IFS=$'\n' read -rd '' -a disk_list1_exploded <<<"$disk_list1"	#create an array of the dev/sata results if they exist

		IFS=$'\n' read -rd '' -a tmp_disk_list2_exploded <<<"$disk_list2"	#create an array of the dev/sda results if they exist

		IFS=$'\n' read -rd '' -a disk_list3_exploded <<<"$disk_list3"	#create an array of the dev/usb results if they exist

		
		#add usb drives to disk_list1_exploded or disk_list2_exploded
		if [[ ${#disk_list1_exploded[@]} -gt "0" ]]; then		#/dev/sata* and /dev/sas*
			for usb_disk in "${disk_list3_exploded[@]}"; do
				if not_flash_drive "$usb_disk"; then			#skip flash drives
					disk_list1_exploded+=("$usb_disk")
				fi
			done
		elif [[ ${#tmp_disk_list2_exploded[@]} -gt "0" ]]; then		#/dev/sd* and /dev/hd*
			for tmp_disk in "${tmp_disk_list2_exploded[@]}"; do
				tmp="$(echo "$(basename "$tmp_disk")" | cut -d":" -f1)"
				if is_usb "$tmp"; then		#add USB drives except flash drives
					if not_flash_drive "$tmp"; then
						disk_list2_exploded+=("$tmp_disk")
					fi
				else
					disk_list2_exploded+=("$tmp_disk")				#add all other drives
				fi
			done
		fi

		#we will need to loop through the disks to get all of the SMART data we are after, but we need to determine which disk naming convention is being used by the system
		if [[ ${#disk_list1_exploded[@]} -gt 0 ]]; then #if there are any /dev/sata named drives, loop through them
			valid_array=("${disk_list1_exploded[@]}") 
		elif [[ ${#disk_list2_exploded[@]} -gt 0 ]]; then #if there are any /dev/sda named drives, loop through them
			valid_array=("${disk_list2_exploded[@]}")
		else
			echo "No Valid SATA or USB Disks Found, Skipping Script"
			valid_array=() #making empty array so we do not collect any data for SATA drives and try NVME drives next
			exit 1
		fi

		#now we can loop through all the available disks
		xx=0
		for xx in "${!valid_array[@]}"; do
			
			#extract just the "/dev/sata1" or just the "/dev/sda" parts of the results, get rid of everything else
			disk="${valid_array[$xx]}"
			disk=$(echo "${disk##*Disk }") 		#get rid of "Disk " at the beginning of the string
			disk=$(echo "${disk%:*}") 			#get rid of everything after the first colon which is after the name of the disk such as "/dev/sata1:"
			
			raw_data=$(smartctl -x "$disk") #get all of the SMART data for the disk
			
			if [[ "$(echo "$raw_data" | grep "synodrivedb")" != "" ]]; then
				echo -e "\n\n"
				if [[ $enable_email_notifications == 1 ]]; then
					send_mail "$email_last_sent" "\"smartctl\" command non-functional due to corrupt Synology drive database.\n\nThe Error Received was:\n\n $raw_data" "\"smartctl\" command non-functional due to corrupt Synology drive database" "$email_contents" "SMART Alert" 60 "$use_sendmail"
				else
					echo -e "\"smartctl\" command non-functional due to corrupt Synology drive database.\n\nThe Error Received was:\n\n $raw_data"
				fi
				exit 1
			fi

			data=$(echo "$raw_data" | awk '/ID# ATTRIBUTE_NAME/,/^$/') #list the table of attributes and their values, don't list anything else
				
			#let's extract the serial number of the drive
			disk_serial=$(echo "$raw_data" | grep "Serial Number:")
			disk_serial=$(echo "${disk_serial##* }") #remove leading spaces
				
			#let's extract the Model number of the drive
			disk_model=$(echo "$raw_data" | grep "Device Model:")
			disk_model=$(echo "${disk_model##* }") #remove leading spaces
			
			#let's extract the PASSED/FAILED status from SMART
			status=$(echo "$raw_data" | grep "SMART overall-health self-assessment test result: ")
			if [[ "$status" == "SMART overall-health self-assessment test result: PASSED" ]]; then
				disk_status=1
			else
				disk_status=0
				send_mail "$email_last_sent" "Warning SMART disk $disk on $nas_name has either reported an error, or did not pass the last SMART test, review the latest SMART data for more details" "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 "$use_sendmail"
			fi			

			#explode out the different items, separated by \n
			IFS=$'\n' read -rd '' -a exploded_data <<<"$data"
				
			#we now need to loop through all of the different parameters this particular disk's SMART data returns	
			yy=0
			for yy in "${!exploded_data[@]}"; do
				if [[ "${exploded_data[$yy]}" != *"|"* ]] then
					#echo "skipping row"
				#else
					#explode out the different items, separated by " "
					IFS=$' ' read -rd '' -a exploded_data2 <<<"${exploded_data[$yy]}"

					#have to remove the new line at the end of the last entry of the array
					name=${exploded_data2[$(( ${#exploded_data2[@]} - 1 ))]} 					#get the last value of the last entry in the array
					exploded_data2[$(( ${#exploded_data2[@]} - 1 ))]="${name//[$'\t\r\n']}"		#remove any new line breaks from the entry and save back into the array

					if [[ "${exploded_data2[1]}" != "ATTRIBUTE_NAME" ]]; then #filtering out the table header information since we do not want that
						post_url=$post_url"$measurement,nas_name=$nas_name,disk_serial=$disk_serial,smart_attribute=${exploded_data2[1]} disk_path=\""$disk"\",disk_model=\""$disk_model"\",ID=${exploded_data2[0]},current_value=${exploded_data2[3]},worst_value=${exploded_data2[4]},threshold_value=${exploded_data2[5]},RAW_value=${exploded_data2[7]},disk_status=$disk_status
	"
					fi
						
					#are email notifications enabled?
					if [[ $enable_email_notifications == 1 ]]; then
						for attribute_counter in "${!paramter_name[@]}" 
						do
							if [[ "${exploded_data2[1]}" == "${paramter_name[$attribute_counter]}" ]]; then
								if [[ "${paramter_type[$attribute_counter]}" == ">" ]]; then
									if [ "${exploded_data2[7]}" -gt "${paramter_notification_threshold[$attribute_counter]}" ]; then
										send_mail "$email_last_sent" "Warning SMART Attribute \"${exploded_data2[1]}\" on disk $disk on $nas_name has exceeded the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of ${exploded_data2[7]}." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 "$use_sendmail"
									fi
								elif [[ "${paramter_type[$attribute_counter]}" == "=" ]]; then
									if [ "${exploded_data2[7]}" -eq "${paramter_notification_threshold[$attribute_counter]}" ]; then
										send_mail "$email_last_sent" "Warning SMART Attribute \"${exploded_data2[1]}\" on disk $disk on $nas_name is equal to the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of ${exploded_data2[7]}." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 "$use_sendmail"
									fi
								elif [[ "${paramter_type[$attribute_counter]}" == "<" ]]; then
									if [ "${exploded_data2[7]}" -lt "${paramter_notification_threshold[$attribute_counter]}" ]; then
										send_mail "$email_last_sent" "Warning SMART Attribute \"${exploded_data2[1]}\" on disk $disk on $nas_name is less than the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of ${exploded_data2[7]}." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 "$use_sendmail"
									fi
								fi
							fi
						done
					fi
				fi
			done
			
			LifetimePowerOnResets=$(echo "$raw_data" | grep "Lifetime Power-On Resets")
			IFS=$' ' read -rd '' -a LifetimePowerOnResets <<<"$LifetimePowerOnResets"
			if [[ "${LifetimePowerOnResets[3]}" == "" ]]; then
				LifetimePowerOnResets[3]=-1
			fi
			
			PoweronHours=$(echo "$raw_data" | grep "Power-on Hours")
			IFS=$' ' read -rd '' -a PoweronHours <<<"$PoweronHours"
			if [[ "${PoweronHours[3]}" == "" ]]; then
				PoweronHours[3]=-1
			fi
			
			LogicalSectorsWritten=$(echo "$raw_data" | grep "Logical Sectors Written")
			IFS=$' ' read -rd '' -a LogicalSectorsWritten <<<"$LogicalSectorsWritten"
			if [[ "${LogicalSectorsWritten[3]}" == "" ]]; then
				LogicalSectorsWritten[3]=-1
			fi
			
			LogicalSectorsRead=$(echo "$raw_data" | grep "Logical Sectors Read")
			IFS=$' ' read -rd '' -a LogicalSectorsRead <<<"$LogicalSectorsRead"
			if [[ "${LogicalSectorsRead[3]}" == "" ]]; then
				LogicalSectorsRead[3]=-1
			fi
			
			NumberofReadCommands=$(echo "$raw_data" | grep "Number of Read Commands")
			IFS=$' ' read -rd '' -a NumberofReadCommands <<<"$NumberofReadCommands"
			if [[ "${NumberofReadCommands[3]}" == "" ]]; then
				NumberofReadCommands[3]=-1
			fi
			
			SpindleMotorPoweronHours=$(echo "$raw_data" | grep "Spindle Motor Power-on Hours")
			IFS=$' ' read -rd '' -a SpindleMotorPoweronHours <<<"$SpindleMotorPoweronHours"
			if [[ "${SpindleMotorPoweronHours[3]}" == "" ]]; then
				SpindleMotorPoweronHours[3]=-1
			fi
			
			HeadFlyingHours=$(echo "$raw_data" | grep "Head Flying Hours")
			IFS=$' ' read -rd '' -a HeadFlyingHours <<<"$HeadFlyingHours"
			if [[ "${HeadFlyingHours[3]}" == "" ]]; then
				HeadFlyingHours[3]=-1
			fi
			
			HeadLoadEvents=$(echo "$raw_data" | grep "Head Load Events")
			IFS=$' ' read -rd '' -a HeadLoadEvents <<<"$HeadLoadEvents"
			if [[ "${HeadLoadEvents[3]}" == "" ]]; then
				HeadLoadEvents[3]=-1
			fi
			
			NumberofReallocatedLogicalSectors=$(echo "$raw_data" | grep "Number of Reallocated Logical Sectors")
			IFS=$' ' read -rd '' -a NumberofReallocatedLogicalSectors <<<"$NumberofReallocatedLogicalSectors"
			if [[ "${NumberofReallocatedLogicalSectors[3]}" == "" ]]; then
				NumberofReallocatedLogicalSectors[3]=-1
			fi
			
			ReadRecoveryAttempts=$(echo "$raw_data" | grep "Read Recovery Attempts")
			IFS=$' ' read -rd '' -a ReadRecoveryAttempts <<<"$ReadRecoveryAttempts"
			if [[ "${ReadRecoveryAttempts[3]}" == "" ]]; then
				ReadRecoveryAttempts[3]=-1
			fi
			
			NumberofMechanicalStartFailures=$(echo "$raw_data" | grep "Number of Mechanical Start Failures")
			IFS=$' ' read -rd '' -a NumberofMechanicalStartFailures <<<"$NumberofMechanicalStartFailures"
			if [[ "${NumberofMechanicalStartFailures[3]}" == "" ]]; then
				NumberofMechanicalStartFailures[3]=-1
			fi
			
			NumberofReportedUncorrectableErrors=$(echo "$raw_data" | grep "Number of Reported Uncorrectable Errors")
			IFS=$' ' read -rd '' -a NumberofReportedUncorrectableErrors <<<"$NumberofReportedUncorrectableErrors"
			if [[ "${NumberofReportedUncorrectableErrors[3]}" == "" ]]; then
				NumberofReportedUncorrectableErrors[3]=-1
			fi
			
			ResetsBetweenCmdAcceptanceandCompletion=$(echo "$raw_data" | grep "Resets Between Cmd Acceptance and Completion")
			IFS=$' ' read -rd '' -a ResetsBetweenCmdAcceptanceandCompletion <<<"$ResetsBetweenCmdAcceptanceandCompletion"
			if [[ "${ResetsBetweenCmdAcceptanceandCompletion[3]}" == "" ]]; then
				ResetsBetweenCmdAcceptanceandCompletion[3]=-1
			fi
			
			NumberofHardwareResets=$(echo "$raw_data" | grep "Number of Hardware Resets")
			IFS=$' ' read -rd '' -a NumberofHardwareResets <<<"$NumberofHardwareResets"
			if [[ "${NumberofHardwareResets[3]}" == "" ]]; then
				NumberofHardwareResets[3]=-1
			fi
			
			NumberofASREvents=$(echo "$raw_data" | grep "Number of ASR Events")
			IFS=$' ' read -rd '' -a NumberofASREvents <<<"$NumberofASREvents"
			if [[ "${NumberofASREvents[3]}" == "" ]]; then
				NumberofASREvents[3]=-1
			fi
			
			NumberofInterfaceCRCErrors=$(echo "$raw_data" | grep "Number of Interface CRC Errors")
			IFS=$' ' read -rd '' -a NumberofInterfaceCRCErrors <<<"$NumberofInterfaceCRCErrors"
			if [[ "${NumberofInterfaceCRCErrors[3]}" == "" ]]; then
				NumberofInterfaceCRCErrors[3]=-1
			fi

			post_url=$post_url"$measurement,nas_name=$nas_name,disk_serial=$disk_serial,smart_attribute=extended_attribute LifetimePowerOnResets=${LifetimePowerOnResets[3]},PoweronHours=${PoweronHours[3]},LogicalSectorsWritten=${LogicalSectorsWritten[3]},LogicalSectorsRead=${LogicalSectorsRead[3]},NumberofReadCommands=${NumberofReadCommands[3]},SpindleMotorPoweronHours=${SpindleMotorPoweronHours[3]},HeadFlyingHours=${HeadFlyingHours[3]},HeadLoadEvents=${HeadLoadEvents[3]},NumberofReallocatedLogicalSectors=${NumberofReallocatedLogicalSectors[3]},ReadRecoveryAttempts=${ReadRecoveryAttempts[3]},NumberofMechanicalStartFailures=${NumberofMechanicalStartFailures[3]},NumberofReportedUncorrectableErrors=${NumberofReportedUncorrectableErrors[3]},ResetsBetweenCmdAcceptanceandCompletion=${ResetsBetweenCmdAcceptanceandCompletion[3]},NumberofHardwareResets=${NumberofHardwareResets[3]},NumberofInterfaceCRCErrors=${NumberofInterfaceCRCErrors[3]},NumberofASREvents=${NumberofASREvents[3]}
"
		done
		
		
		#get NVME drive details. as this is not available from SNMP, we will pull it using the nvme command. 
		nvme_number_installed=$(nvme list | grep "/dev" | wc -l)
		if [[ $synology -eq 1 ]]; then
			nvme_number_installed=$(( $nvme_number_installed / 2 )) #two entries are listed per drive for synology, one the disk, one the partiotion on the disk
		fi
		if [[ $debug == 1 ]]; then
			echo "nvme_number_installed is $nvme_number_installed"
		fi

		if [[ $nvme_number_installed -lt 1 ]]; then
			echo "no NVME drives installed, skipping NVME capture"
		else
			for (( c=0; c<$nvme_number_installed; c++ ))
			do 
				disk_serial=$(cat /sys/block/nvme${c}n1/device/serial)
				post_url=$post_url"$measurement,nas_name=$nas_name,disk_serial=$disk_serial disk_path=\""/dev/nvme${c}n1"\","
				line_num=0
				while IFS= read -r line; do
									
					if [[ $line_num != 0 ]]; then
						disk_SMART_attribute_name=$(echo ${line%:*} | xargs)
							
						secondString="_"
						disk_SMART_attribute_name=${disk_SMART_attribute_name//\ /$secondString} #replace all white space with underscore
							
							
						disk_SMART_attribute_raw=$(echo ${line##*:} | xargs)
					
						#cleanup the data to make all returned values numerical numbers and not strings
						secondString=""
						disk_SMART_attribute_raw=${disk_SMART_attribute_raw//\ /$secondString} #remove all white space
						disk_SMART_attribute_raw=${disk_SMART_attribute_raw//\,/$secondString} #remove the commas from numerical values so it is just a plain number and not a string
						disk_SMART_attribute_raw=${disk_SMART_attribute_raw//\%/$secondString} #remove the % symbol from items containing it so it is just a plain number and not a string
						disk_SMART_attribute_raw=${disk_SMART_attribute_raw//\C/$secondString} #remove the "C" from temperature values so it is just a plain number and not a string
						disk_SMART_attribute_raw=$(echo "${disk_SMART_attribute_raw%°*}") #remove "°(xxKelvin)" from temperatures
						disk_SMART_attribute_raw=$(echo "${disk_SMART_attribute_raw%(*}") #remove from data written "(xxTB)"

					
						post_url=$post_url"$disk_SMART_attribute_name=$disk_SMART_attribute_raw,"
							
						#are email notifications enabled?
						if [[ $enable_email_notifications == 1 ]]; then
							for attribute_counter in "${!paramter_name[@]}" 
							do
								if [[ "$disk_SMART_attribute_name" == "${paramter_name[$attribute_counter]}" ]]; then
									if [[ "${paramter_type[$attribute_counter]}" == ">" ]]; then
										if [ "$disk_SMART_attribute_raw" -gt "${paramter_notification_threshold[$attribute_counter]}" ]; then
											send_mail "$email_last_sent" "Warning SMART Attribute \"$disk_SMART_attribute_name\" on disk /dev/nvme${c}n1 on $nas_name is greater than the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of $disk_SMART_attribute_raw." "/dev/nvme${c}n1 SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 "$use_sendmail"
										fi
									elif [[ "${paramter_type[$attribute_counter]}" == "=" ]]; then
										if [ "$disk_SMART_attribute_raw" -eq "${paramter_notification_threshold[$attribute_counter]}" ]; then
											send_mail "$email_last_sent" "Warning SMART Attribute \"$disk_SMART_attribute_name\" on disk /dev/nvme${c}n1 on $nas_name is equal to the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of $disk_SMART_attribute_raw." "/dev/nvme${c}n1 SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 "$use_sendmail"
										fi
									elif [[ "${paramter_type[$attribute_counter]}" == "<" ]]; then
										if [ "$disk_SMART_attribute_raw" -lt "${paramter_notification_threshold[$attribute_counter]}" ]; then
											send_mail "$email_last_sent" "Warning SMART Attribute \"$disk_SMART_attribute_name\" on disk /dev/nvme${c}n1 on $nas_name is less than the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of $disk_SMART_attribute_raw." "/dev/nvme${c}n1 SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 "$use_sendmail"
										fi
									fi
								fi
							done
						fi
					fi
					
					let line_num=line_num+1
				done < <(nvme smart-log /dev/nvme${c}n1)
				
				let line_num=line_num-1
				post_url=$post_url"num_paramters=$line_num
"
			done
		fi
		if [[ $debug -eq 1 ]]; then
			echo "$post_url"
		fi
				
		echo "$post_url" > "/mnt/ramfs/smart_data.txt"
			
		curl -XPOST "http://$influxdb_host:$influxdb_port/api/v2/write?bucket=$influxdb_name&org=$influxdb_org" -H "Authorization: Token $influxdb_pass" --data-binary "@/mnt/ramfs/smart_data.txt"
	else
		echo "script is disabled"
	fi
else
	if [[ "$email_address" == "" || "$from_email_address" == "" || $(echo "$email_address" | grep "@") == "" || $(echo "$from_email_address" | grep "@") == "" || $(echo "$from_email_address" | grep ".") == "" || $(echo "$email_address" | grep ".") == "" ]];then
		echo -e "\n\nNo email address information is configured, Cannot send an email indicating script \"${0##*/}\" config file is missing and script will not run"
	else
		send_mail "$email_last_sent" "Warning NAS \"$nas_name\" SMART Logging Failed for script \"${0##*/}\" - Configuration file is missing" "Warning NAS \"$nas_name_error\" SMART Data Collection Failed for script \"${0##*/}\" - Configuration file is missing" "$email_contents" "Config File Missing Alert" 60 "$use_sendmail"
	fi
	exit 1
fi

