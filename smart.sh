#!/bin/bash
#Version 2/17/2026
#By Brian Wallace
#########################################################


#check that the script is running as root or some of the commands required will not work
if [[ $( whoami ) != "root" ]]; then
	echo -e "ERROR - Script requires ROOT permissions, exiting script"
	exit 1
fi

#########################################################
#USER ADJUSABLE SCRIPT VARIABLES
email_contents="/volume1/web/logging/notifications/SMART_Logging_email_contents.txt"					#when email notifications are sent, the contents of the email and a log entry if the email successfully sent is saved to this file
lock_file_location="/volume1/web/logging/notifications/SMART_Logging.lock"								#file created while the script is running and deleted when the script is done. this is to prevent more than one copy of the script from running at a time
email_last_sent="/volume1/web/logging/notifications/${0##*/}_SMART_Logging_last_message_sent.txt"		#some emails are to be sent only every 60 minutes (like config file missing/corrupt messages) to prevent your inbox from being spammed
debug=0																									#if set to "1" the script will display all of the collected data being sent to InfluxDB
#config_file_location="/volume1/web/config"																#where the configuration file will be saved. do not change value unless the value is also changed in the smart_config.php file
#config_file_name="smart_logging_config.txt"															#name of config file, do not change value unless the value is also changed in the smart_config.php file
#measurement="Dosk_SMART"																				#influxDB measurement name
#nas_name="Server_Name"																					#name of server the SMART data is being collected from
#use_sendmail=1																							#set to "1" to use "sendmail" command, set to "0" to use the "ssmtp" command when sending email notifications

#########################################################
#EMAIL SETTINGS USED IF CONFIGURATION FILE IS UNAVAILABLE
#These variables will be overwritten with new corrected data if the configuration file loads properly. 
#If the config file does not load properly, then the script will still be able to send alert emails informing the user the config file is missing/corrupted
email_address="email@email.com"
from_email_address="email@email.com"
#########################################################


#for my personal use as i have multiple Synology systems, these lines can be deleted and the variables above can be un-commented
######################################################################################
sever_type=1 #1=server2, 2=serverNVR, 3=serverplex

if [[ $sever_type == 1 ]]; then
	config_file_location="/volume1/web/config/config_files/config_files_local"
	config_file_name="smart_logging_config.txt"
	measurement="synology_SMART_status2"
	nas_name="Server2"
	use_sendmail=1
fi

if [[ $sever_type == 2 ]]; then
	config_file_location="/volume1/web/logging"
	config_file_name="smart_logging_config.txt"
	measurement="synology_SMART_status2"
	nas_name="Server_NVR"
	use_sendmail=1
fi

if [[ $sever_type == 3 ]]; then
	config_file_location="/volume1/web/config/config_files/config_files_local"
	config_file_name="smart_logging_config.txt"
	measurement="synology_SMART_status2"
	nas_name="Server-Plex"
	use_sendmail=1
fi

######################################################################################

#create a lock file in the configuration directory to prevent more than one instance of this script from executing  at once
if ! mkdir "$lock_file_location"; then
	echo "Failed to acquire lock.\n" >&2
	exit 1
fi
trap 'rm -rf "$lock_file_location"' EXIT #remove the lockdir on exit

#########################################################
#this function pings google.com to confirm internet access is working prior to sending email notifications 
#########################################################
check_internet() {
ping -c1 "google.com" > /dev/null #ping google.com									
	local status=$?
	if ! (exit $status); then
		false
	else
		true
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
		local current_time=$( date +%s )
		if [ -r "${1}" ]; then #file is available and readable 
			read message_tracker < "${1}"
			time_diff=$((( $current_time - $message_tracker ) / 60 ))
		else
			echo -n "$current_time" > "${1}"
			time_diff=$(( ${6} + 1 ))
		fi
				
		if [ $time_diff -ge ${6} ]; then
			local now=$(date +"%T")
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
				local email_response=$(sendmail -t < "${4}"  2>&1)
				if [[ "$email_response" == "" ]]; then
					echo "" |& tee -a "${4}"
					echo -e "Email to \"$email_address\" Sent Successfully\n" |& tee -a "${4}"
					message_tracker=$current_time
					time_diff=0
					echo -n "$message_tracker" > "${1}"
				else
					echo -e "Warning, an error occurred while sending the ${5} notification email. the error was: $email_response\n" |& tee -a "${4}"
				fi
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
				address_explode=(`echo $email_address | sed 's/;/\n/g'`) #explode on the semicolon separating the different possible addresses
				local xx=0
				for xx in "${!address_explode[@]}"; do
					local email_response=$(ssmtp ${address_explode[$xx]} < "${4}"  2>&1)
					if [[ "$email_response" == "" ]]; then
						echo "" |& tee -a "${4}"
						echo -e "Email to \"${address_explode[$xx]}\" Sent Successfully\n" |& tee -a "${4}"
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
	
	#read in file
	read input_read < "$config_file_location/$config_file_name"
	#explode the configuration into an array with the colon as the delimiter
	explode=(`echo $input_read | sed 's/,/\n/g'`)
	
	#verify the correct number of configuration parameters are in the configuration file
	if [[ ! ${#explode[@]} == 80 ]]; then
		send_mail "$email_last_sent" "WARNING - the configuration file is incorrect or corrupted. It should have 80 parameters, it currently has ${#explode[@]} parameters." "Warning NAS \"$nas_name\" SNMP Monitoring Failed for script \"${0##*/}\" - Configuration file is incorrect" "$email_contents" "Config File Error" 60 $use_sendmail
		exit 1
	fi	
	paramter_name=()
	paramter_notification_threshold=()
	paramter_type=()
	
	#save the parameter values into the respective variable and remove the quotes
	nas_url=${explode[2]}
	influxdb_host=${explode[5]}
	influxdb_port=${explode[6]}
	influxdb_name=${explode[7]}
	influxdb_user=${explode[8]}
	influxdb_pass=${explode[9]}
	script_enable=${explode[10]}
	influx_db_version=${explode[13]}
	influxdb_org=${explode[14]}
	enable_email_notifications=${explode[15]}
	email_address=${explode[16]}
	paramter_name+=(${explode[17]})
	paramter_notification_threshold+=(${explode[18]})
	paramter_name+=(${explode[19]})
	paramter_notification_threshold+=(${explode[20]})
	paramter_name+=(${explode[21]})
	paramter_notification_threshold+=(${explode[22]})
	paramter_name+=(${explode[23]})
	paramter_notification_threshold+=(${explode[24]})
	paramter_name+=(${explode[25]})
	paramter_notification_threshold+=(${explode[26]})
	from_email_address=${explode[27]}
	paramter_type+=(${explode[30]})
	paramter_type+=(${explode[31]})
	paramter_type+=(${explode[32]})
	paramter_type+=(${explode[33]})
	paramter_type+=(${explode[34]})
	paramter_type+=(${explode[35]})
	paramter_type+=(${explode[36]})
	paramter_type+=(${explode[37]})
	paramter_type+=(${explode[38]})
	paramter_type+=(${explode[39]})
	paramter_type+=(${explode[40]})
	paramter_type+=(${explode[41]})
	paramter_type+=(${explode[42]})
	paramter_type+=(${explode[43]})
	paramter_type+=(${explode[44]})
	paramter_type+=(${explode[45]})
	paramter_type+=(${explode[46]})
	paramter_type+=(${explode[47]})
	paramter_type+=(${explode[48]})
	paramter_type+=(${explode[49]})
	paramter_name+=(${explode[50]})
	paramter_notification_threshold+=(${explode[51]})
	paramter_name+=(${explode[52]})
	paramter_notification_threshold+=(${explode[53]})
	paramter_name+=(${explode[54]})
	paramter_notification_threshold+=(${explode[55]})
	paramter_name+=(${explode[56]})
	paramter_notification_threshold+=(${explode[57]})
	paramter_name+=(${explode[58]})
	paramter_notification_threshold+=(${explode[59]})
	paramter_name+=(${explode[60]})
	paramter_notification_threshold+=(${explode[61]})
	paramter_name+=(${explode[62]})
	paramter_notification_threshold+=(${explode[63]})
	paramter_name+=(${explode[64]})
	paramter_notification_threshold+=(${explode[65]})
	paramter_name+=(${explode[66]})
	paramter_notification_threshold+=(${explode[67]})
	paramter_name+=(${explode[68]})
	paramter_notification_threshold+=(${explode[69]})
	paramter_name+=(${explode[70]})
	paramter_notification_threshold+=(${explode[71]})
	paramter_name+=(${explode[72]})
	paramter_notification_threshold+=(${explode[73]})
	paramter_name+=(${explode[74]})
	paramter_notification_threshold+=(${explode[75]})
	paramter_name+=(${explode[76]})
	paramter_notification_threshold+=(${explode[77]})
	paramter_name+=(${explode[78]})
	paramter_notification_threshold+=(${explode[79]})

	if [ $script_enable -eq 1 ]; then
		post_url=""

		disk_list1=$(fdisk -l | grep "Disk /dev/sata*[0-9]:")   #some systems have drives listed as /stata1, /sata2 etc
		disk_list2=$(fdisk -l | grep "Disk /dev/sd")			#some systems have drives listed as /sda, /sdb etc

		IFS=$'\n' read -rd '' -a disk_list1_exploded <<<"$disk_list1"	#create an array of the dev/sata results if they exist

		IFS=$'\n' read -rd '' -a disk_list2_exploded <<<"$disk_list2"	#create an array of the dev/sda results if they exist


		#we will need to loop through the disks to get all of the SMART data we are after, but we need to determine which disk naming convention is being used by the system
		if [[ ${#disk_list1_exploded[@]} > 0 ]]; then #if there are any /dev/sata named drives, loop through them
			valid_array=("${disk_list1_exploded[@]}") 
		elif [[ ${#disk_list2_exploded[@]} > 0 ]]; then #if there are any /dev/sda named drives, loop through them
			valid_array=("${disk_list2_exploded[@]}")
		else
			echo "No Valid SATA Disks Found, Skipping Script"
			valid_array=() #making empty array so we do not collect any data for SATA drives and try NVME drives next
		fi

		#now we can loop through all the available disks
		xx=0
		for xx in "${!valid_array[@]}"; do

			#extract just the "/dev/sata1" or just the "/dev/sda" parts of the results, get rid of everything else
			disk="${valid_array[$xx]}"
			disk=$(echo "${disk##*Disk }") 		#get rid of "Disk " at the beginning of the string
			disk=$(echo "${disk%:*}") 			#get rid of everything after the first colon which is after the name of the disk such as "/dev/sata1:"
			
			raw_data=$(smartctl -a -d ata $disk) #get all of the SMART data for the disk
			
			echo -e "\n\n"
			if [[ "$(echo "$raw_data" | grep "synodrivedb")" != "" ]]; then
				if [[ $enable_email_notifications == 1 ]]; then
					send_mail "$email_last_sent" "\"smartctl\" command non-functional due to corrupt Synology drive database.\n\nThe Error Received was:\n\n $raw_data" "\"smartctl\" command non-functional due to corrupt Synology drive database" "$email_contents" "SMART Alert" 60 $use_sendmail
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
				send_mail "$email_last_sent" "Warning SMART disk $disk on $nas_name has either reported an error, or did not pass the last SMART test, review the latest SMART data for more details" "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 $use_sendmail
			fi

			#explode out the different items, separated by \n
			IFS=$'\n' read -rd '' -a exploded_data <<<"$data"
				
			#we now need to loop through all of the different parameters this particular disk's SMART data returns	
			xx=0
			for xx in "${!exploded_data[@]}"; do
				#explode out the different items, separated by " "
				IFS=$' ' read -rd '' -a exploded_data2 <<<"${exploded_data[$xx]}"

				#have to remove the new line at the end of the last entry of the array
				name=${exploded_data2[$(( ${#exploded_data2[@]} - 1 ))]} 					#get the last value of the last entry in the array
				exploded_data2[$(( ${#exploded_data2[@]} - 1 ))]="${name//[$'\t\r\n']}"		#remove any new line breaks from the entry and save back into the array

				if [[ "${exploded_data2[1]}" != "ATTRIBUTE_NAME" ]]; then #filtering out the table header information since we do not want that
					post_url=$post_url"$measurement,nas_name=$nas_name,disk_path=$disk,smart_attribute=${exploded_data2[1]} disk_model=\""$disk_model"\",disk_serial=\""$disk_serial"\",ID=${exploded_data2[0]},current_value=${exploded_data2[3]},worst_value=${exploded_data2[4]},threshold_value=${exploded_data2[5]},RAW_value=${exploded_data2[9]},disk_status=$disk_status
"
				fi
					
				#are email notifications enabled?
				if [[ $enable_email_notifications == 1 ]]; then
					for attribute_counter in "${!paramter_name[@]}" 
					do
						if [[ "${exploded_data2[1]}" == "${paramter_name[$attribute_counter]}" ]]; then
							if [[ ${paramter_type[$attribute_counter]} == ">" ]]; then
								if [ ${exploded_data2[9]} -gt ${paramter_notification_threshold[$attribute_counter]} ]; then
									send_mail "$email_last_sent" "Warning SMART Attribute \"${exploded_data2[1]}\" on disk $disk on $nas_name has exceeded the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of ${exploded_data2[9]}." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 $use_sendmail
								fi
							elif [[ ${paramter_type[$attribute_counter]} == "=" ]]; then
								if [ ${exploded_data2[9]} -eq ${paramter_notification_threshold[$attribute_counter]} ]; then
									send_mail "$email_last_sent" "Warning SMART Attribute \"${exploded_data2[1]}\" on disk $disk on $nas_name is equal to the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of ${exploded_data2[9]}." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 $use_sendmail
								fi
							elif [[ ${paramter_type[$attribute_counter]} == "<" ]]; then
								if [ ${exploded_data2[9]} -lt ${paramter_notification_threshold[$attribute_counter]} ]; then
									send_mail "$email_last_sent" "Warning SMART Attribute \"${exploded_data2[1]}\" on disk $disk on $nas_name is less than the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of ${exploded_data2[9]}." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 $use_sendmail
								fi
							fi
						fi
					done
				fi
			done
		done
		
		
		#get NVME drive details. as this is not available from SNMP, we will pull it using the nvme command. 
		nvme_number_installed=$(nvme list | wc -l)
		nvme_number_installed=$(( ( $nvme_number_installed - 2 ) / 2 )) #remove the first two lines as they are just table header information, and two entries are listed per drive
		if [[ $debug == 1 ]]; then
			echo "nvme_number_installed is $nvme_number_installed"
		fi

		if [[ $nvme_number_installed < 1 ]]; then
			echo "no NVME drives installed, skipping NVME capture"
		else
			for (( c=0; c<$nvme_number_installed; c++ ))
			do 
				post_url=$post_url"$measurement,nas_name=$nas_name,disk_path=/dev/nvme${c}n1 "
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
					
						post_url=$post_url"$disk_SMART_attribute_name=$disk_SMART_attribute_raw,"
							
							
						#are email notifications enabled?
						if [[ $enable_email_notifications == 1 ]]; then
							for attribute_counter in "${!paramter_name[@]}" 
							do
								if [[ $disk_SMART_attribute_name == ${paramter_name[$attribute_counter]} ]]; then
									if [[ ${paramter_type[$attribute_counter]} == ">" ]]; then
										if [ $disk_SMART_attribute_raw -gt ${paramter_notification_threshold[$attribute_counter]} ]; then
											send_mail "$email_last_sent" "Warning SMART Attribute \"disk_SMART_attribute_name\" on disk /dev/nvme${c}n1 on $nas_name is greater than the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of $disk_SMART_attribute_raw." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 $use_sendmail
										fi
									elif [[ ${paramter_type[$attribute_counter]} == "=" ]]; then
										if [ $disk_SMART_attribute_raw -eq ${paramter_notification_threshold[$attribute_counter]} ]; then
											send_mail "$email_last_sent" "Warning SMART Attribute \"disk_SMART_attribute_name\" on disk /dev/nvme${c}n1 on $nas_name is equal to the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of $disk_SMART_attribute_raw." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 $use_sendmail
										fi
									elif [[ ${paramter_type[$attribute_counter]} == "<" ]]; then
										if [ $disk_SMART_attribute_raw -lt ${paramter_notification_threshold[$attribute_counter]} ]; then
											send_mail "$email_last_sent" "Warning SMART Attribute \"disk_SMART_attribute_name\" on disk /dev/nvme${c}n1 on $nas_name is less than the threshold value of ${paramter_notification_threshold[$attribute_counter]}. It currently is reporting a value of $disk_SMART_attribute_raw." "$disk SMART ALERT for $nas_name" "$email_contents" "SMART Alert" 0 $use_sendmail
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
			
		#Post to influxdb
		if [[ $influx_db_version == 1 ]]; then
			echo "saving using influx version 1"
			curl -i -XPOST "http://$influxdb_host:$influxdb_port/write?u=$influxdb_user&p=$influxdb_pass&db=$influxdb_name" --data-binary "$post_url"
		else
			curl -XPOST "http://$influxdb_host:$influxdb_port/api/v2/write?bucket=$influxdb_name&org=$influxdb_org" -H "Authorization: Token $influxdb_pass" --data-raw "$post_url"
		fi
		
		if [[ $debug == 1 ]]; then
			echo "$post_url"
		fi
	else
		echo "script is disabled"
	fi
else
	if [[ "$email_address" == "" || "$from_email_address" == "" || $(echo "$email_address" | grep "@") == "" || $(echo "$from_email_address" | grep "@") == "" || $(echo "$from_email_address" | grep ".") == "" || $(echo "$email_address" | grep ".") == "" ]];then
		echo -e "\n\nNo email address information is configured, Cannot send an email indicating script \"${0##*/}\" config file is missing and script will not run"
	else
		send_mail "$email_last_sent" "Warning NAS \"$nas_name\" SMART Logging Failed for script \"${0##*/}\" - Configuration file is missing" "Warning NAS \"$nas_name_error\" SMART Data Collection Failed for script \"${0##*/}\" - Configuration file is missing" "$email_contents" "Config File Missing Alert" 60 $use_sendmail
	fi
	exit 1
fi

