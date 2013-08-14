<?php
session_start();
//error_reporting("E_ALL & ~E_NOTICE");
//error_reporting("~E_NOTICE");
require('inc/mysqli_datalayer.php');
require('inc/connection.inc');
global $dl;
include("inc/classes/form_class.php");
include("inc/classes/tc_calendar.php");
include("inc/classes/leave_report_class.php");
include('inc/functions.php');
date_default_timezone_set('UTC');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Newcastle Biomedicine CRP Flexible Working Application</title>
<LINK REL="StyleSheet" HREF="inc/css/css.css" TYPE="text/css" MEDIA="screen">
<LINK REL="StyleSheet" HREF="inc/css/form.css" TYPE="text/css" MEDIA="screen">
<LINK REL="StyleSheet" HREF="inc/css/report.css" TYPE="text/css" MEDIA="screen">
<LINK REL="StyleSheet" HREF="inc/css/report_print.css" TYPE="text/css" MEDIA="print">
<link REL="SHORTCUT ICON" HREF="inc/images/favicon.ico">
<script language="javascript" src="inc/classes/calendar.js"></script>
</head>
<body>
<?php
	if($_GET["func"]== "Sickness") {
		show_sickness_report();
	}
	if($_GET["func"]== "leaveManagement") {
		show_leave_report();
	}
	if($_GET["func"]== "leaveReset") {
		reset_leave_report();
	}
	if( $_GET["func"] == "saveLeave") {
		save_additional_leave();
	}
	
?>