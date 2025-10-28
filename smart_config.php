<?php
//version 2.0 dated 6/19/2025
//By Brian Wallace
///////////////////////////////////////////////////
//User Defined Variables
///////////////////////////////////////////////////

$config_file="/var/www/html/config/config_files/smart_logging_config.txt";
$use_login_sessions=true; //set to false if not using user login sessions
$form_submittal_destination="index.php?page=6&config_page=smart_server2"; //set to the destination the HTML form submit should be directed to
$page_title="TrueNAS SMART Logging and Notification Configuration Settings";

///////////////////////////////////////////////////
//Beginning of configuration page
///////////////////////////////////////////////////
if($use_login_sessions){
	if($_SERVER['HTTPS']!="on") {

	$redirect= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

	header("Location:$redirect"); } 

	// Initialize the session
	if(session_status() !== PHP_SESSION_ACTIVE) session_start();
	 
	$current_time=time();

	if(!isset($_SESSION["session_start_time"])){
		$expire_time=$current_time-60;
	}else{
		$expire_time=$_SESSION["session_start_time"]+3600; #un-refreshed session will only be good for 1 hour
	}


	// Check if the user is logged in, if not then redirect him to login page
	if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $current_time > $expire_time || !isset($_SESSION["session_user_id"])){
		// Unset all of the session variables
		$_SESSION = array();
		// Destroy the session.
		session_destroy();
		header("location: ../login.php");
		exit;
	}else{
		$_SESSION["session_start_time"]=$current_time; //refresh session start time
	}
}
error_reporting(E_NOTICE);
include $_SERVER['DOCUMENT_ROOT']."/functions.php";
$SNMP_user_error="";
$capture_interval_error="";
$nas_url_error="";
$ups_group_error="";
$influxdb_host_error="";
$influxdb_port_error="";
$influxdb_name_error="";
$influxdb_user_error="";
$influxdb_pass_error="";
$script_enable_error="";
$AuthPass1_error="";
$PrivPass2_error="";
//$influx_db_version_error="";
$influxdb_org_error="";
$enable_email_notifications_error="";
$email_address_error="";
$paramter_1_name_error="";
$paramter_1_notification_threshold_error="";
$paramter_2_name_error="";
$paramter_2_notification_threshold_error="";
$paramter_3_name_error="";
$paramter_3_notification_threshold_error="";
$paramter_4_name_error="";
$paramter_4_notification_threshold_error="";
$paramter_5_name_error="";
$paramter_5_notification_threshold_error="";
$paramter_6_name_error="";
$paramter_6_notification_threshold_error="";
$paramter_7_name_error="";
$paramter_7_notification_threshold_error="";
$paramter_8_name_error="";
$paramter_8_notification_threshold_error="";
$paramter_9_name_error="";
$paramter_9_notification_threshold_error="";
$paramter_10_name_error="";
$paramter_10_notification_threshold_error="";
$paramter_11_name_error="";
$paramter_11_notification_threshold_error="";
$paramter_12_name_error="";
$paramter_12_notification_threshold_error="";
$paramter_13_name_error="";
$paramter_13_notification_threshold_error="";
$paramter_14_name_error="";
$paramter_14_notification_threshold_error="";
$paramter_15_name_error="";
$paramter_15_notification_threshold_error="";
$paramter_16_name_error="";
$paramter_16_notification_threshold_error="";
$paramter_17_name_error="";
$paramter_17_notification_threshold_error="";
$paramter_18_name_error="";
$paramter_18_notification_threshold_error="";
$paramter_19_name_error="";
$paramter_19_notification_threshold_error="";
$paramter_20_name_error="";
$paramter_20_notification_threshold_error="";

$paramter_21_name_error="";
$paramter_21_notification_threshold_error="";
$paramter_22_name_error="";
$paramter_22_notification_threshold_error="";
$paramter_23_name_error="";
$paramter_23_notification_threshold_error="";
$paramter_24_name_error="";
$paramter_24_notification_threshold_error="";
$paramter_25_name_error="";
$paramter_25_notification_threshold_error="";
$paramter_26_name_error="";
$paramter_26_notification_threshold_error="";
$paramter_27_name_error="";
$paramter_27_notification_threshold_error="";
$paramter_28_name_error="";
$paramter_28_notification_threshold_error="";
$paramter_29_name_error="";
$paramter_29_notification_threshold_error="";
$paramter_30_name_error="";
$paramter_30_notification_threshold_error="";
$from_email_address_error="";
$generic_error="";
$snmp_auth_protocol_error="";
$snmp_privacy_protocol_error="";
//$influxdb_user="user";
		

if(isset($_POST['submit_server_PDU'])){
	if (file_exists("".$config_file."")) {
		$data = file_get_contents("".$config_file."");
		$pieces = explode(",", $data);
	}
		   
	[$SNMP_user, $SNMP_user_error] = test_input_processing($_POST['SNMP_user'], $pieces[0], "name", 0, 0);
	
	if ($_POST['capture_interval']==10 || $_POST['capture_interval']==15 || $_POST['capture_interval']==30 || $_POST['capture_interval']==60){
		$capture_interval=htmlspecialchars($_POST['capture_interval']);
	}else{
		$capture_interval=$pieces[1];
	}
	
	[$nas_url, $nas_url_error] = test_input_processing($_POST['nas_url'], $pieces[2], "ip", 0, 0);
	
	[$influxdb_host, $influxdb_host_error] = test_input_processing($_POST['influxdb_host'], $pieces[5], "ip", 0, 0);
	
	[$influxdb_port, $influxdb_port_error] = test_input_processing($_POST['influxdb_port'], $pieces[6], "numeric", 0, 65000);	
	
	[$influxdb_name, $influxdb_name_error] = test_input_processing($_POST['influxdb_name'], $pieces[7], "name", 0, 0);

	//[$influxdb_user, $influxdb_user_error] = test_input_processing($_POST['influxdb_user'], $pieces[8], "name", 0, 0);		

	[$influxdb_pass, $influxdb_pass_error] = test_input_processing($_POST['influxdb_pass'], $pieces[9], "password", 0, 0);	
	
	[$script_enable, $generic_error] = test_input_processing($_POST['script_enable'], "", "checkbox", 0, 0);
	
	[$AuthPass1, $AuthPass1_error] = test_input_processing($_POST['AuthPass1'], $pieces[11], "password", 0, 0);
	
	[$PrivPass2, $PrivPass2_error] = test_input_processing($_POST['PrivPass2'], $pieces[12], "password", 0, 0);
	
	//[$influx_db_version, $influx_db_version_error] = test_input_processing($_POST['influx_db_version'], $pieces[13], "numeric", 1, 2);
	
	[$influxdb_org, $influxdb_org_error] = test_input_processing($_POST['influxdb_org'], $pieces[14], "name", 0, 0);
	
	[$enable_email_notifications, $enable_email_notifications_error] = test_input_processing($_POST['enable_email_notifications'], "", "checkbox", 0, 0);
	
	[$email_address, $email_address_error] = test_input_processing($_POST['email_address'], $pieces[16], "email", 0, 0);
	
	[$paramter_1_name, $paramter_1_name_error] = test_input_processing($_POST['paramter_1_name'], $pieces[17], "name", 0, 0);
	
	[$paramter_1_notification_threshold, $paramter_1_notification_threshold_error] = test_input_processing($_POST['paramter_1_notification_threshold'], $pieces[18], "numeric", 0, 100000);
	
	[$paramter_2_name, $paramter_2_name_error] = test_input_processing($_POST['paramter_2_name'], $pieces[19], "name", 0, 0);
	
	[$paramter_2_notification_threshold, $paramter_2_notification_threshold_error] = test_input_processing($_POST['paramter_2_notification_threshold'], $pieces[20], "numeric", 0, 100000);
	
	[$paramter_3_name, $paramter_3_name_error] = test_input_processing($_POST['paramter_3_name'], $pieces[21], "name", 0, 0);
	
	[$paramter_3_notification_threshold, $paramter_3_notification_threshold_error] = test_input_processing($_POST['paramter_3_notification_threshold'], $pieces[22], "numeric", 0, 100000);
	
	[$paramter_4_name, $paramter_4_name_error] = test_input_processing($_POST['paramter_4_name'], $pieces[23], "name", 0, 0);
	
	[$paramter_4_notification_threshold, $paramter_4_notification_threshold_error] = test_input_processing($_POST['paramter_4_notification_threshold'], $pieces[24], "numeric", 0, 100000);
	
	[$paramter_5_name, $paramter_5_name_error] = test_input_processing($_POST['paramter_5_name'], $pieces[25], "name", 0, 0);
	
	[$paramter_5_notification_threshold, $paramter_5_notification_threshold_error] = test_input_processing($_POST['paramter_5_notification_threshold'], $pieces[26], "numeric", 0, 100000);
	
	[$from_email_address, $from_email_address_error] = test_input_processing($_POST['from_email_address'], $pieces[27], "email", 0, 0);
	
	[$snmp_auth_protocol, $snmp_auth_protocol_error] = test_input_processing($_POST['snmp_auth_protocol'], $pieces[28], "name", 0, 0);
	
	[$snmp_privacy_protocol, $snmp_privacy_protocol_error] = test_input_processing($_POST['snmp_privacy_protocol'], $pieces[29], "name", 0, 0);
	
	if ($_POST['paramter_1_type']==">" || $_POST['paramter_1_type']=="=" || $_POST['paramter_1_type']=="<"){
		$paramter_1_type=($_POST['paramter_1_type']);
	}else{
		$paramter_1_type=$pieces[30];
	}
	
	if ($_POST['paramter_2_type']==">" || $_POST['paramter_2_type']=="=" || $_POST['paramter_2_type']=="<"){
		$paramter_2_type=($_POST['paramter_2_type']);
	}else{
		$paramter_2_type=$pieces[31];
	}
	
	if ($_POST['paramter_3_type']==">" || $_POST['paramter_3_type']=="=" || $_POST['paramter_3_type']=="<"){
		$paramter_3_type=($_POST['paramter_3_type']);
	}else{
		$paramter_3_type=$pieces[32];
	}
	
	if ($_POST['paramter_4_type']==">" || $_POST['paramter_4_type']=="=" || $_POST['paramter_4_type']=="<"){
		$paramter_4_type=($_POST['paramter_4_type']);
	}else{
		$paramter_4_type=$pieces[33];
	}
	
	if ($_POST['paramter_5_type']==">" || $_POST['paramter_5_type']=="=" || $_POST['paramter_5_type']=="<"){
		$paramter_5_type=($_POST['paramter_5_type']);
	}else{
		$paramter_5_type=$pieces[34];
	}
	
	if ($_POST['paramter_6_type']==">" || $_POST['paramter_6_type']=="=" || $_POST['paramter_6_type']=="<"){
		$paramter_6_type=($_POST['paramter_6_type']);
	}else{
		$paramter_6_type=$pieces[35];
	}
	
	if ($_POST['paramter_7_type']==">" || $_POST['paramter_7_type']=="=" || $_POST['paramter_7_type']=="<"){
		$paramter_7_type=($_POST['paramter_7_type']);
	}else{
		$paramter_7_type=$pieces[36];
	}
	
	if ($_POST['paramter_8_type']==">" || $_POST['paramter_8_type']=="=" || $_POST['paramter_8_type']=="<"){
		$paramter_8_type=($_POST['paramter_8_type']);
	}else{
		$paramter_8_type=$pieces[37];
	}
	
	if ($_POST['paramter_9_type']==">" || $_POST['paramter_9_type']=="=" || $_POST['paramter_9_type']=="<"){
		$paramter_9_type=($_POST['paramter_9_type']);
	}else{
		$paramter_9_type=$pieces[38];
	}
	
	if ($_POST['paramter_10_type']==">" || $_POST['paramter_10_type']=="=" || $_POST['paramter_10_type']=="<"){
		$paramter_10_type=($_POST['paramter_10_type']);
	}else{
		$paramter_10_type=$pieces[39];
	}
	
	
	if ($_POST['paramter_11_type']==">" || $_POST['paramter_11_type']=="=" || $_POST['paramter_11_type']=="<"){
		$paramter_11_type=($_POST['paramter_11_type']);
	}else{
		$paramter_11_type=$pieces[40];
	}
	
	if ($_POST['paramter_12_type']==">" || $_POST['paramter_12_type']=="=" || $_POST['paramter_12_type']=="<"){
		$paramter_12_type=($_POST['paramter_12_type']);
	}else{
		$paramter_12_type=$pieces[41];
	}
	
	if ($_POST['paramter_13_type']==">" || $_POST['paramter_13_type']=="=" || $_POST['paramter_13_type']=="<"){
		$paramter_13_type=($_POST['paramter_13_type']);
	}else{
		$paramter_13_type=$pieces[42];
	}
	
	if ($_POST['paramter_14_type']==">" || $_POST['paramter_14_type']=="=" || $_POST['paramter_14_type']=="<"){
		$paramter_14_type=($_POST['paramter_14_type']);
	}else{
		$paramter_14_type=$pieces[43];
	}
	
	if ($_POST['paramter_15_type']==">" || $_POST['paramter_15_type']=="=" || $_POST['paramter_15_type']=="<"){
		$paramter_15_type=($_POST['paramter_15_type']);
	}else{
		$paramter_15_type=$pieces[44];
	}
	
	if ($_POST['paramter_16_type']==">" || $_POST['paramter_16_type']=="=" || $_POST['paramter_16_type']=="<"){
		$paramter_16_type=($_POST['paramter_16_type']);
	}else{
		$paramter_16_type=$pieces[45];
	}
	
	if ($_POST['paramter_17_type']==">" || $_POST['paramter_17_type']=="=" || $_POST['paramter_17_type']=="<"){
		$paramter_17_type=($_POST['paramter_17_type']);
	}else{
		$paramter_17_type=$pieces[46];
	}
	
	if ($_POST['paramter_18_type']==">" || $_POST['paramter_18_type']=="=" || $_POST['paramter_18_type']=="<"){
		$paramter_18_type=($_POST['paramter_18_type']);
	}else{
		$paramter_18_type=$pieces[47];
	}
	
	if ($_POST['paramter_19_type']==">" || $_POST['paramter_19_type']=="=" || $_POST['paramter_19_type']=="<"){
		$paramter_19_type=($_POST['paramter_19_type']);
	}else{
		$paramter_19_type=$pieces[48];
	}
	
	if ($_POST['paramter_20_type']==">" || $_POST['paramter_20_type']=="=" || $_POST['paramter_20_type']=="<"){
		$paramter_20_type=($_POST['paramter_20_type']);
	}else{
		$paramter_20_type=$pieces[49];
	}
	
	[$paramter_6_name, $paramter_6_name_error] = test_input_processing($_POST['paramter_6_name'], $pieces[50], "name", 0, 0);
	
	[$paramter_6_notification_threshold, $paramter_6_notification_threshold_error] = test_input_processing($_POST['paramter_6_notification_threshold'], $pieces[51], "numeric", 0, 100000);
	
	[$paramter_7_name, $paramter_7_name_error] = test_input_processing($_POST['paramter_7_name'], $pieces[52], "name", 0, 0);
	
	[$paramter_7_notification_threshold, $paramter_7_notification_threshold_error] = test_input_processing($_POST['paramter_7_notification_threshold'], $pieces[53], "numeric", 0, 100000);
	
	[$paramter_8_name, $paramter_8_name_error] = test_input_processing($_POST['paramter_8_name'], $pieces[54], "name", 0, 0);
	
	[$paramter_8_notification_threshold, $paramter_8_notification_threshold_error] = test_input_processing($_POST['paramter_8_notification_threshold'], $pieces[55], "numeric", 0, 100000);
	
	[$paramter_9_name, $paramter_9_name_error] = test_input_processing($_POST['paramter_9_name'], $pieces[56], "name", 0, 0);
	
	[$paramter_9_notification_threshold, $paramter_9_notification_threshold_error] = test_input_processing($_POST['paramter_9_notification_threshold'], $pieces[57], "numeric", 0, 100000);
	
	[$paramter_10_name, $paramter_10_name_error] = test_input_processing($_POST['paramter_10_name'], $pieces[58], "name", 0, 0);
	
	[$paramter_10_notification_threshold, $paramter_10_notification_threshold_error] = test_input_processing($_POST['paramter_10_notification_threshold'], $pieces[59], "numeric", 0, 100000);
	
	[$paramter_11_name, $paramter_11_name_error] = test_input_processing($_POST['paramter_11_name'], $pieces[60], "name", 0, 0);
	
	[$paramter_11_notification_threshold, $paramter_11_notification_threshold_error] = test_input_processing($_POST['paramter_11_notification_threshold'], $pieces[61], "numeric", 0, 100000);
	
	[$paramter_12_name, $paramter_12_name_error] = test_input_processing($_POST['paramter_12_name'], $pieces[62], "name", 0, 0);
	
	[$paramter_12_notification_threshold, $paramter_12_notification_threshold_error] = test_input_processing($_POST['paramter_12_notification_threshold'], $pieces[63], "numeric", 0, 100000);
	
	[$paramter_13_name, $paramter_13_name_error] = test_input_processing($_POST['paramter_13_name'], $pieces[64], "name", 0, 0);
	
	[$paramter_13_notification_threshold, $paramter_13_notification_threshold_error] = test_input_processing($_POST['paramter_13_notification_threshold'], $pieces[65], "numeric", 0, 100000);
	
	[$paramter_14_name, $paramter_14_name_error] = test_input_processing($_POST['paramter_14_name'], $pieces[66], "name", 0, 0);
	
	[$paramter_14_notification_threshold, $paramter_14_notification_threshold_error] = test_input_processing($_POST['paramter_14_notification_threshold'], $pieces[67], "numeric", 0, 100000);
	
	[$paramter_15_name, $paramter_15_name_error] = test_input_processing($_POST['paramter_15_name'], $pieces[68], "name", 0, 0);
	
	[$paramter_15_notification_threshold, $paramter_15_notification_threshold_error] = test_input_processing($_POST['paramter_15_notification_threshold'], $pieces[69], "numeric", 0, 100000);
	
	[$paramter_16_name, $paramter_16_name_error] = test_input_processing($_POST['paramter_16_name'], $pieces[70], "name", 0, 0);
	
	[$paramter_16_notification_threshold, $paramter_16_notification_threshold_error] = test_input_processing($_POST['paramter_16_notification_threshold'], $pieces[71], "numeric", 0, 100000);
	
	[$paramter_17_name, $paramter_17_name_error] = test_input_processing($_POST['paramter_17_name'], $pieces[72], "name", 0, 0);
	
	[$paramter_17_notification_threshold, $paramter_17_notification_threshold_error] = test_input_processing($_POST['paramter_17_notification_threshold'], $pieces[73], "numeric", 0, 100000);
	
	[$paramter_18_name, $paramter_18_name_error] = test_input_processing($_POST['paramter_18_name'], $pieces[74], "name", 0, 0);
	
	[$paramter_18_notification_threshold, $paramter_18_notification_threshold_error] = test_input_processing($_POST['paramter_18_notification_threshold'], $pieces[75], "numeric", 0, 100000);
	
	[$paramter_19_name, $paramter_19_name_error] = test_input_processing($_POST['paramter_19_name'], $pieces[76], "name", 0, 0);
	
	[$paramter_19_notification_threshold, $paramter_19_notification_threshold_error] = test_input_processing($_POST['paramter_19_notification_threshold'], $pieces[77], "numeric", 0, 100000);
	
	[$paramter_20_name, $paramter_20_name_error] = test_input_processing($_POST['paramter_20_name'], $pieces[78], "name", 0, 0);
	
	[$paramter_20_notification_threshold, $paramter_20_notification_threshold_error] = test_input_processing($_POST['paramter_20_notification_threshold'], $pieces[79], "numeric", 0, 100000);
	
	
	
	
	[$paramter_21_name, $paramter_21_name_error] = test_input_processing($_POST['paramter_21_name'], $pieces[69], "name", 0, 0);
	[$paramter_21_notification_threshold, $paramter_21_notification_threshold_error] = test_input_processing($_POST['paramter_21_notification_threshold'], $pieces[70], "numeric", 0, 100000);
	if ($_POST['paramter_21_type']==">" || $_POST['paramter_21_type']=="=" || $_POST['paramter_21_type']=="<"){
		$paramter_21_type=($_POST['paramter_21_type']);
	}else{
		$paramter_21_type=$pieces[71];
	}
	
	[$paramter_22_name, $paramter_22_name_error] = test_input_processing($_POST['paramter_22_name'], $pieces[72], "name", 0, 0);
	[$paramter_22_notification_threshold, $paramter_22_notification_threshold_error] = test_input_processing($_POST['paramter_22_notification_threshold'], $pieces[73], "numeric", 0, 100000);
	if ($_POST['paramter_22_type']==">" || $_POST['paramter_22_type']=="=" || $_POST['paramter_22_type']=="<"){
		$paramter_22_type=($_POST['paramter_22_type']);
	}else{
		$paramter_22_type=$pieces[74];
	}
	
	[$paramter_23_name, $paramter_23_name_error] = test_input_processing($_POST['paramter_23_name'], $pieces[75], "name", 0, 0);
	[$paramter_23_notification_threshold, $paramter_23_notification_threshold_error] = test_input_processing($_POST['paramter_23_notification_threshold'], $pieces[76], "numeric", 0, 100000);
	if ($_POST['paramter_23_type']==">" || $_POST['paramter_23_type']=="=" || $_POST['paramter_23_type']=="<"){
		$paramter_23_type=($_POST['paramter_23_type']);
	}else{
		$paramter_23_type=$pieces[77];
	}
	
	[$paramter_24_name, $paramter_24_name_error] = test_input_processing($_POST['paramter_24_name'], $pieces[78], "name", 0, 0);
	[$paramter_24_notification_threshold, $paramter_24_notification_threshold_error] = test_input_processing($_POST['paramter_24_notification_threshold'], $pieces[79], "numeric", 0, 100000);
	if ($_POST['paramter_24_type']==">" || $_POST['paramter_24_type']=="=" || $_POST['paramter_24_type']=="<"){
		$paramter_24_type=($_POST['paramter_24_type']);
	}else{
		$paramter_24_type=$pieces[80];
	}
	
	[$paramter_25_name, $paramter_25_name_error] = test_input_processing($_POST['paramter_25_name'], $pieces[81], "name", 0, 0);
	[$paramter_25_notification_threshold, $paramter_25_notification_threshold_error] = test_input_processing($_POST['paramter_25_notification_threshold'], $pieces[82], "numeric", 0, 100000);
	if ($_POST['paramter_25_type']==">" || $_POST['paramter_25_type']=="=" || $_POST['paramter_25_type']=="<"){
		$paramter_25_type=($_POST['paramter_25_type']);
	}else{
		$paramter_25_type=$pieces[83];
	}
	
	[$paramter_26_name, $paramter_26_name_error] = test_input_processing($_POST['paramter_26_name'], $pieces[84], "name", 0, 0);
	[$paramter_26_notification_threshold, $paramter_26_notification_threshold_error] = test_input_processing($_POST['paramter_26_notification_threshold'], $pieces[85], "numeric", 0, 100000);
	if ($_POST['paramter_26_type']==">" || $_POST['paramter_26_type']=="=" || $_POST['paramter_26_type']=="<"){
		$paramter_26_type=($_POST['paramter_26_type']);
	}else{
		$paramter_26_type=$pieces[86];
	}
	
	[$paramter_27_name, $paramter_27_name_error] = test_input_processing($_POST['paramter_27_name'], $pieces[87], "name", 0, 0);
	[$paramter_27_notification_threshold, $paramter_27_notification_threshold_error] = test_input_processing($_POST['paramter_27_notification_threshold'], $pieces[88], "numeric", 0, 100000);
	if ($_POST['paramter_27_type']==">" || $_POST['paramter_27_type']=="=" || $_POST['paramter_27_type']=="<"){
		$paramter_27_type=($_POST['paramter_27_type']);
	}else{
		$paramter_27_type=$pieces[89];
	}
	
	[$paramter_28_name, $paramter_28_name_error] = test_input_processing($_POST['paramter_28_name'], $pieces[90], "name", 0, 0);
	[$paramter_28_notification_threshold, $paramter_28_notification_threshold_error] = test_input_processing($_POST['paramter_28_notification_threshold'], $pieces[91], "numeric", 0, 100000);
	if ($_POST['paramter_28_type']==">" || $_POST['paramter_28_type']=="=" || $_POST['paramter_28_type']=="<"){
		$paramter_28_type=($_POST['paramter_28_type']);
	}else{
		$paramter_28_type=$pieces[92];
	}
	
	[$paramter_29_name, $paramter_29_name_error] = test_input_processing($_POST['paramter_29_name'], $pieces[93], "name", 0, 0);
	[$paramter_29_notification_threshold, $paramter_29_notification_threshold_error] = test_input_processing($_POST['paramter_29_notification_threshold'], $pieces[94], "numeric", 0, 100000);
	if ($_POST['paramter_29_type']==">" || $_POST['paramter_29_type']=="=" || $_POST['paramter_29_type']=="<"){
		$paramter_29_type=($_POST['paramter_29_type']);
	}else{
		$paramter_29_type=$pieces[95];
	}
	
	[$paramter_30_name, $paramter_30_name_error] = test_input_processing($_POST['paramter_30_name'], $pieces[96], "name", 0, 0);
	[$paramter_30_notification_threshold, $paramter_30_notification_threshold_error] = test_input_processing($_POST['paramter_30_notification_threshold'], $pieces[97], "numeric", 0, 100000);
	if ($_POST['paramter_30_type']==">" || $_POST['paramter_30_type']=="=" || $_POST['paramter_30_type']=="<"){
		$paramter_30_type=($_POST['paramter_30_type']);
	}else{
		$paramter_30_type=$pieces[98];
	}

	$put_contents_string="".$influxdb_host.",".$influxdb_port.",".$influxdb_name.",".$influxdb_pass.",".$script_enable.",".$influxdb_org.",".$enable_email_notifications.",".$email_address.",".$paramter_1_name.",".$paramter_1_notification_threshold.",".$paramter_2_name.",".$paramter_2_notification_threshold.",".$paramter_3_name.",".$paramter_3_notification_threshold.",".$paramter_4_name.",".$paramter_4_notification_threshold.",".$paramter_5_name.",".$paramter_5_notification_threshold.",".$from_email_address.",".$paramter_1_type.",".$paramter_2_type.",".$paramter_3_type.",".$paramter_4_type.",".$paramter_5_type.",".$paramter_6_type.",".$paramter_7_type.",".$paramter_8_type.",".$paramter_9_type.",".$paramter_10_type.",".$paramter_11_type.",".$paramter_12_type.",".$paramter_13_type.",".$paramter_14_type.",".$paramter_15_type.",".$paramter_16_type.",".$paramter_17_type.",".$paramter_18_type.",".$paramter_19_type.",".$paramter_20_type.",".$paramter_6_name.",".$paramter_6_notification_threshold.",".$paramter_7_name.",".$paramter_7_notification_threshold.",".$paramter_8_name.",".$paramter_8_notification_threshold.",".$paramter_9_name.",".$paramter_9_notification_threshold.",".$paramter_10_name.",".$paramter_10_notification_threshold.",".$paramter_11_name.",".$paramter_11_notification_threshold.",".$paramter_12_name.",".$paramter_12_notification_threshold.",".$paramter_13_name.",".$paramter_13_notification_threshold.",".$paramter_14_name.",".$paramter_14_notification_threshold.",".$paramter_15_name.",".$paramter_15_notification_threshold.",".$paramter_16_name.",".$paramter_16_notification_threshold.",".$paramter_17_name.",".$paramter_17_notification_threshold.",".$paramter_18_name.",".$paramter_18_notification_threshold.",".$paramter_19_name.",".$paramter_19_notification_threshold.",".$paramter_20_name.",".$paramter_20_notification_threshold.",".$paramter_21_name.",".$paramter_21_notification_threshold.",".$paramter_21_type.",".$paramter_22_name.",".$paramter_22_notification_threshold.",".$paramter_22_type.",".$paramter_23_name.",".$paramter_23_notification_threshold.",".$paramter_23_type.",".$paramter_24_name.",".$paramter_24_notification_threshold.",".$paramter_24_type.",".$paramter_25_name.",".$paramter_25_notification_threshold.",".$paramter_25_type.",".$paramter_26_name.",".$paramter_26_notification_threshold.",".$paramter_26_type.",".$paramter_27_name.",".$paramter_27_notification_threshold.",".$paramter_27_type.",".$paramter_28_name.",".$paramter_28_notification_threshold.",".$paramter_28_type.",".$paramter_29_name.",".$paramter_29_notification_threshold.",".$paramter_29_type.",".$paramter_30_name.",".$paramter_30_notification_threshold.",".$paramter_30_type."";
		  
	
	if (file_put_contents("".$config_file."",$put_contents_string )==FALSE){
		print "<font color=\"red\">Error - could not save configuration</font>";
	}
		  
}else{
	if (file_exists("".$config_file."")) {
		$data = file_get_contents("".$config_file."");
		$pieces = explode(",", $data);
		//$SNMP_user=$pieces[0];
		//$capture_interval=$pieces[1];
		//$nas_url=$pieces[2];
		//$nas_name=$pieces[3];
		//$ups_group=$pieces[4];
		$influxdb_host=$pieces[0];
		$influxdb_port=$pieces[1];
		$influxdb_name=$pieces[2];
		//$influxdb_user=$pieces[8];
		$influxdb_pass=$pieces[3];
		$script_enable=$pieces[4];
		//$AuthPass1=$pieces[11];
		//$PrivPass2=$pieces[12];
		//$influx_db_version=$pieces[13];
		$influxdb_org=$pieces[5];
		$enable_email_notifications=$pieces[6];
		$email_address=$pieces[7];
		$paramter_1_name=$pieces[8];
		$paramter_1_notification_threshold=$pieces[9];
		$paramter_2_name=$pieces[10];
		$paramter_2_notification_threshold=$pieces[11];
		$paramter_3_name=$pieces[12];
		$paramter_3_notification_threshold=$pieces[13];
		$paramter_4_name=$pieces[14];
		$paramter_4_notification_threshold=$pieces[15];
		$paramter_5_name=$pieces[16];
		$paramter_5_notification_threshold=$pieces[17];
		$from_email_address=$pieces[18];
		//$snmp_auth_protocol=$pieces[28];
		//$snmp_privacy_protocol=$pieces[29];
		$paramter_1_type=$pieces[19];
		$paramter_2_type=$pieces[20];
		$paramter_3_type=$pieces[21];
		$paramter_4_type=$pieces[22];
		$paramter_5_type=$pieces[23];
		$paramter_6_type=$pieces[24];
		$paramter_7_type=$pieces[25];
		$paramter_8_type=$pieces[26];
		$paramter_9_type=$pieces[27];
		$paramter_10_type=$pieces[28];
		$paramter_11_type=$pieces[29];
		$paramter_12_type=$pieces[30];
		$paramter_13_type=$pieces[31];
		$paramter_14_type=$pieces[32];
		$paramter_15_type=$pieces[33];
		$paramter_16_type=$pieces[34];
		$paramter_17_type=$pieces[35];
		$paramter_18_type=$pieces[36];
		$paramter_19_type=$pieces[37];
		$paramter_20_type=$pieces[38];
		$paramter_6_name=$pieces[39];
		$paramter_6_notification_threshold=$pieces[40];
		$paramter_7_name=$pieces[41];
		$paramter_7_notification_threshold=$pieces[42];
		$paramter_8_name=$pieces[43];
		$paramter_8_notification_threshold=$pieces[44];
		$paramter_9_name=$pieces[45];
		$paramter_9_notification_threshold=$pieces[46];
		$paramter_10_name=$pieces[47];
		$paramter_10_notification_threshold=$pieces[48];
		$paramter_11_name=$pieces[49];
		$paramter_11_notification_threshold=$pieces[50];
		$paramter_12_name=$pieces[51];
		$paramter_12_notification_threshold=$pieces[52];
		$paramter_13_name=$pieces[53];
		$paramter_13_notification_threshold=$pieces[54];
		$paramter_14_name=$pieces[55];
		$paramter_14_notification_threshold=$pieces[56];
		$paramter_15_name=$pieces[57];
		$paramter_15_notification_threshold=$pieces[58];
		$paramter_16_name=$pieces[59];
		$paramter_16_notification_threshold=$pieces[60];
		$paramter_17_name=$pieces[61];
		$paramter_17_notification_threshold=$pieces[62];
		$paramter_18_name=$pieces[63];
		$paramter_18_notification_threshold=$pieces[64];
		$paramter_19_name=$pieces[65];
		$paramter_19_notification_threshold=$pieces[66];
		$paramter_20_name=$pieces[67];
		$paramter_20_notification_threshold=$pieces[68];
		
		
		$paramter_21_name=$pieces[69];
		$paramter_21_notification_threshold=$pieces[70];
		$paramter_21_type=$pieces[71];
		
		$paramter_22_name=$pieces[72];
		$paramter_22_notification_threshold=$pieces[73];
		$paramter_22_type=$pieces[74];
		
		$paramter_23_name=$pieces[75];
		$paramter_23_notification_threshold=$pieces[76];
		$paramter_23_type=$pieces[77];
		
		$paramter_24_name=$pieces[78];
		$paramter_24_notification_threshold=$pieces[79];
		$paramter_24_type=$pieces[80];
		
		$paramter_25_name=$pieces[81];
		$paramter_25_notification_threshold=$pieces[82];
		$paramter_25_type=$pieces[83];
		
		$paramter_26_name=$pieces[84];
		$paramter_26_notification_threshold=$pieces[85];
		$paramter_26_type=$pieces[86];
		
		$paramter_27_name=$pieces[87];
		$paramter_27_notification_threshold=$pieces[88];
		$paramter_27_type=$pieces[89];
		
		$paramter_28_name=$pieces[90];
		$paramter_28_notification_threshold=$pieces[91];
		$paramter_28_type=$pieces[92];
		
		$paramter_29_name=$pieces[93];
		$paramter_29_notification_threshold=$pieces[94];
		$paramter_29_type=$pieces[95];
		
		$paramter_30_name=$pieces[96];
		$paramter_30_notification_threshold=$pieces[97];
		$paramter_30_type=$pieces[98];
		
		
	}else{
		$put_contents_string="localhost,8086,influxdb_name,influxdb_pass,0,influxdb_org,0,email_address,paramter_1_name,0,paramter_2_name,0,paramter_3_name,0,paramter_4_name,0,paramter_5_name,0,from_email_address,>,>,>,>,>,>,>,>,>,>,>,>,>,>,>,>,>,>,>,>,paramter_6_name,0,paramter_7_name,0,paramter_8_name,0,paramter_9_name,0,paramter_10_name,0,paramter_11_name,0,paramter_12_name,0,paramter_13_name,0,paramter_14_name,0,paramter_15_name,0,paramter_16_name,0,paramter_17_name,0,paramter_18_name,0,paramter_19_name,0,paramter_20_name,0,paramter_21_name,0,>,paramter_22_name,0,>,paramter_23_name,0,>,paramter_24_name,0,>,paramter_25_name,0,>,paramter_26_name,0,>,paramter_27_name,0,>,paramter_28_name,0,>,paramter_29_name,0,>,paramter_30_name,0,>";
			  
		if (file_put_contents("".$config_file."",$put_contents_string )==FALSE){
			print "<font color=\"red\">Error - could not save configuration</font>";
		}
	}
}
	   
	   print "
<br>
<fieldset>
	<legend>
		<h3>".$page_title."</h3>
	</legend>
	<table border=\"0\">
		<tr>
			<td>";
		if ($script_enable==1){
			print "<font color=\"green\"><h3>Script Status: Active</h3></font>";
		}else{
			print "<font color=\"red\"><h3>Script Status: Inactive</h3></font>";
		}
print "		</td>
		</tr>
		<tr>
			<td align=\"left\">
				<form action=\"".$form_submittal_destination."\" method=\"post\">
					<p><input type=\"checkbox\" name=\"script_enable\" value=\"1\" ";
					   if ($script_enable==1){
							print "checked";
					   }
print "					>Enable Entire Script?
					</p><br>
					
					<input type=\"hidden\" name=\"capture_interval\" value=".$capture_interval.">
					<b>EMAIL SETTINGS</b>
					<p>-><input type=\"checkbox\" name=\"enable_email_notifications\" value=\"1\" ";
					   if ($enable_email_notifications==1){
							print "checked";
					   }
print "					>Enable Email Notifications?
					</p>
					<p>->Recipient Email Address: <input type=\"text\" name=\"email_address\" value=".$email_address."> ".$email_address_error."</p>
					<p>->From Email Address: <input type=\"text\" name=\"from_email_address\" value=".$from_email_address."> ".$from_email_address_error."</p>
					<br>
					<b>INFLUXDB SETTINGS</b>
					<p>->IP of Influx DB: <input type=\"text\" name=\"influxdb_host\" value=".$influxdb_host."> ".$influxdb_host_error."</p>
					<p>->PORT of Influx DB: <input type=\"text\" name=\"influxdb_port\" value=".$influxdb_port."> ".$influxdb_port_error."</p>
					<p>->Database to use within Influx DB: <input type=\"text\" name=\"influxdb_name\" value=".$influxdb_name."> ".$influxdb_name_error."</p>
					<p>->Password of Influx DB: <input type=\"text\" name=\"influxdb_pass\" value=".$influxdb_pass."> ".$influxdb_pass_error."</p>
					<p>->Influx DB Org: <input type=\"text\" name=\"influxdb_org\" value=".$influxdb_org."> ".$influxdb_org_error."</p>
					<br>
					<input type=\"hidden\" name=\"nas_url\" value=".$nas_url.">
					<input type=\"hidden\" name=\"SNMP_user\" value=".$SNMP_user.">
					<input type=\"hidden\" name=\"AuthPass1\" value=".$AuthPass1.">
					<input type=\"hidden\" name=\"PrivPass2\" value=".$PrivPass2.">
					<input type=\"hidden\" name=\"PrivPass2\" value=".$snmp_auth_protocol.">
					<input type=\"hidden\" name=\"PrivPass2\" value=".$snmp_privacy_protocol.">
					<b>SMART NOTIFICATION SETTINGS</b>
					<br><i>NOTE: If not using a particular parameter, leave fields at default values</i>
					<br>
					<p>-><b>Disk Parameter 1</b> <input type=\"text\" name=\"paramter_1_name\" value=".$paramter_1_name."> ".$paramter_1_name_error."
						<select name=\"paramter_1_type\">";
							if ($paramter_1_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_1_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_1_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_1_notification_threshold\" value=".$paramter_1_notification_threshold."> ".$paramter_1_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 2</b> <input type=\"text\" name=\"paramter_2_name\" value=".$paramter_2_name."> ".$paramter_2_name_error."
						<select name=\"paramter_2_type\">";
							if ($paramter_2_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_2_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_2_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_2_notification_threshold\" value=".$paramter_2_notification_threshold."> ".$paramter_2_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 3</b> <input type=\"text\" name=\"paramter_3_name\" value=".$paramter_3_name."> ".$paramter_3_name_error."
						<select name=\"paramter_3_type\">";
							if ($paramter_3_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_3_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_3_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_3_notification_threshold\" value=".$paramter_3_notification_threshold."> ".$paramter_3_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 4</b> <input type=\"text\" name=\"paramter_4_name\" value=".$paramter_4_name."> ".$paramter_4_name_error."
						<select name=\"paramter_4_type\">";
							if ($paramter_4_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_4_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_4_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_4_notification_threshold\" value=".$paramter_4_notification_threshold."> ".$paramter_4_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 5</b> <input type=\"text\" name=\"paramter_5_name\" value=".$paramter_5_name."> ".$paramter_5_name_error."
						<select name=\"paramter_5_type\">";
							if ($paramter_5_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_5_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_5_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_5_notification_threshold\" value=".$paramter_5_notification_threshold."> ".$paramter_5_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 6</b> <input type=\"text\" name=\"paramter_6_name\" value=".$paramter_6_name."> ".$paramter_6_name_error."
						<select name=\"paramter_6_type\">";
							if ($paramter_6_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_6_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_6_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_6_notification_threshold\" value=".$paramter_6_notification_threshold."> ".$paramter_6_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 7</b> <input type=\"text\" name=\"paramter_7_name\" value=".$paramter_7_name."> ".$paramter_7_name_error."
						<select name=\"paramter_7_type\">";
							if ($paramter_7_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_7_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_7_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_7_notification_threshold\" value=".$paramter_7_notification_threshold."> ".$paramter_7_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 8</b> <input type=\"text\" name=\"paramter_8_name\" value=".$paramter_8_name."> ".$paramter_8_name_error."
						<select name=\"paramter_8_type\">";
							if ($paramter_8_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_8_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_8_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_8_notification_threshold\" value=".$paramter_8_notification_threshold."> ".$paramter_8_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 9</b> <input type=\"text\" name=\"paramter_9_name\" value=".$paramter_9_name."> ".$paramter_9_name_error."
						<select name=\"paramter_9_type\">";
							if ($paramter_9_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_9_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_9_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_9_notification_threshold\" value=".$paramter_9_notification_threshold."> ".$paramter_9_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 10</b> <input type=\"text\" name=\"paramter_10_name\" value=".$paramter_10_name."> ".$paramter_10_name_error."
						<select name=\"paramter_10_type\">";
							if ($paramter_10_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_10_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_10_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_10_notification_threshold\" value=".$paramter_10_notification_threshold."> ".$paramter_10_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 11</b> <input type=\"text\" name=\"paramter_11_name\" value=".$paramter_11_name."> ".$paramter_11_name_error."
						<select name=\"paramter_11_type\">";
							if ($paramter_11_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_11_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_11_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_11_notification_threshold\" value=".$paramter_11_notification_threshold."> ".$paramter_11_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 12</b> <input type=\"text\" name=\"paramter_12_name\" value=".$paramter_12_name."> ".$paramter_12_name_error."
						<select name=\"paramter_12_type\">";
							if ($paramter_12_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_12_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_12_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_12_notification_threshold\" value=".$paramter_12_notification_threshold."> ".$paramter_12_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 13</b> <input type=\"text\" name=\"paramter_13_name\" value=".$paramter_13_name."> ".$paramter_13_name_error."
						<select name=\"paramter_13_type\">";
							if ($paramter_13_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_13_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_13_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_13_notification_threshold\" value=".$paramter_13_notification_threshold."> ".$paramter_13_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 14</b> <input type=\"text\" name=\"paramter_14_name\" value=".$paramter_14_name."> ".$paramter_14_name_error."
						<select name=\"paramter_14_type\">";
							if ($paramter_14_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_14_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_14_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_14_notification_threshold\" value=".$paramter_14_notification_threshold."> ".$paramter_14_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 15</b> <input type=\"text\" name=\"paramter_15_name\" value=".$paramter_15_name."> ".$paramter_15_name_error."
						<select name=\"paramter_15_type\">";
							if ($paramter_15_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_15_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_15_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_15_notification_threshold\" value=".$paramter_15_notification_threshold."> ".$paramter_15_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 16</b> <input type=\"text\" name=\"paramter_16_name\" value=".$paramter_16_name."> ".$paramter_16_name_error."
						<select name=\"paramter_16_type\">";
							if ($paramter_16_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_16_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_16_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_16_notification_threshold\" value=".$paramter_16_notification_threshold."> ".$paramter_16_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 17</b> <input type=\"text\" name=\"paramter_17_name\" value=".$paramter_17_name."> ".$paramter_17_name_error."
						<select name=\"paramter_17_type\">";
							if ($paramter_17_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_17_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_17_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_17_notification_threshold\" value=".$paramter_17_notification_threshold."> ".$paramter_17_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 18</b> <input type=\"text\" name=\"paramter_18_name\" value=".$paramter_18_name."> ".$paramter_18_name_error."
						<select name=\"paramter_18_type\">";
							if ($paramter_18_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_18_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_18_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_18_notification_threshold\" value=".$paramter_18_notification_threshold."> ".$paramter_18_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 19</b> <input type=\"text\" name=\"paramter_19_name\" value=".$paramter_19_name."> ".$paramter_19_name_error."
						<select name=\"paramter_19_type\">";
							if ($paramter_19_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_19_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_19_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_19_notification_threshold\" value=".$paramter_19_notification_threshold."> ".$paramter_19_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 20</b> <input type=\"text\" name=\"paramter_20_name\" value=".$paramter_20_name."> ".$paramter_20_name_error."
						<select name=\"paramter_20_type\">";
							if ($paramter_20_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_20_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_20_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_20_notification_threshold\" value=".$paramter_20_notification_threshold."> ".$paramter_20_notification_threshold_error."</p>
					<p>-><b>Disk Parameter 21</b> <input type=\"text\" name=\"paramter_21_name\" value=".$paramter_21_name."> ".$paramter_21_name_error."
						<select name=\"paramter_21_type\">";
							if ($paramter_21_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_21_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_21_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_21_notification_threshold\" value=".$paramter_21_notification_threshold."> ".$paramter_21_notification_threshold_error."</p>
						<p>-><b>Disk Parameter 22</b> <input type=\"text\" name=\"paramter_22_name\" value=".$paramter_22_name."> ".$paramter_22_name_error."
						<select name=\"paramter_22_type\">";
							if ($paramter_22_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_22_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_22_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_22_notification_threshold\" value=".$paramter_22_notification_threshold."> ".$paramter_22_notification_threshold_error."</p>
					
					<p>-><b>Disk Parameter 23</b> <input type=\"text\" name=\"paramter_23_name\" value=".$paramter_23_name."> ".$paramter_23_name_error."
						<select name=\"paramter_23_type\">";
							if ($paramter_23_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_23_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_23_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_23_notification_threshold\" value=".$paramter_23_notification_threshold."> ".$paramter_23_notification_threshold_error."</p>
					
					<p>-><b>Disk Parameter 24</b> <input type=\"text\" name=\"paramter_24_name\" value=".$paramter_24_name."> ".$paramter_24_name_error."
						<select name=\"paramter_24_type\">";
							if ($paramter_24_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_24_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_24_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_24_notification_threshold\" value=".$paramter_24_notification_threshold."> ".$paramter_24_notification_threshold_error."</p>
					
					
					<p>-><b>Disk Parameter 25</b> <input type=\"text\" name=\"paramter_25_name\" value=".$paramter_25_name."> ".$paramter_25_name_error."
						<select name=\"paramter_25_type\">";
							if ($paramter_25_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_25_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_25_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_25_notification_threshold\" value=".$paramter_25_notification_threshold."> ".$paramter_25_notification_threshold_error."</p>
					
					<p>-><b>Disk Parameter 26</b> <input type=\"text\" name=\"paramter_26_name\" value=".$paramter_26_name."> ".$paramter_26_name_error."
						<select name=\"paramter_26_type\">";
							if ($paramter_26_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_26_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_26_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_26_notification_threshold\" value=".$paramter_26_notification_threshold."> ".$paramter_26_notification_threshold_error."</p>
					
					<p>-><b>Disk Parameter 27</b> <input type=\"text\" name=\"paramter_27_name\" value=".$paramter_27_name."> ".$paramter_27_name_error."
						<select name=\"paramter_27_type\">";
							if ($paramter_27_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_27_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_27_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_27_notification_threshold\" value=".$paramter_27_notification_threshold."> ".$paramter_27_notification_threshold_error."</p>
					
					<p>-><b>Disk Parameter 28</b> <input type=\"text\" name=\"paramter_28_name\" value=".$paramter_28_name."> ".$paramter_28_name_error."
						<select name=\"paramter_28_type\">";
							if ($paramter_28_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_28_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_28_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_28_notification_threshold\" value=".$paramter_28_notification_threshold."> ".$paramter_28_notification_threshold_error."</p>
					
					<p>-><b>Disk Parameter 29</b> <input type=\"text\" name=\"paramter_29_name\" value=".$paramter_29_name."> ".$paramter_29_name_error."
						<select name=\"paramter_29_type\">";
							if ($paramter_29_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_29_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_29_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_29_notification_threshold\" value=".$paramter_29_notification_threshold."> ".$paramter_29_notification_threshold_error."</p>
					
					<p>-><b>Disk Parameter 30</b> <input type=\"text\" name=\"paramter_30_name\" value=".$paramter_30_name."> ".$paramter_30_name_error."
						<select name=\"paramter_30_type\">";
							if ($paramter_30_type==">"){
								print "<option value=\">\" selected>></option>
								<option value=\"<\"><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_30_type=="<"){
								print "<option value=\">\">></option>
								<option value=\"<\" selected><</option>
								<option value=\"=\">=</option>";
							}else if ($paramter_30_type=="="){
								print "<option value=\">\">></option>
								<option value=\"<\"><</option>
								<option value=\"=\" selected>=</option>";
							}
					print "</select>
						<input type=\"text\" name=\"paramter_30_notification_threshold\" value=".$paramter_30_notification_threshold."> ".$paramter_30_notification_threshold_error."</p>
					
					
					
					
					
					<center><input type=\"submit\" name=\"submit_server_PDU\" value=\"Submit\" /></center>
				</form>
			</td>
		</tr>
	</table>
</fieldset>";
?>
