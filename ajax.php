<?php 
session_start();
error_reporting("E_ALL & ~E_NOTICE");
//error_reporting("~E_NOTICE");
require('inc/Datalayer.inc');
require('inc/connection.inc');
global $dl;
include('inc/functions.php');
include('inc/email_messages.inc');
require('inc/classes/calendar_class.php');
require('inc/classes/form_class.php');
require('inc/classes/libmail.inc');
require('inc/classes/tc_calendar.php');
date_default_timezone_set('UTC');
$cal = new calendars;



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Newcastle Biomedicine CRP Flexible Working Application</title>
<LINK REL="StyleSheet" HREF="inc/css/css.css" TYPE="text/css" MEDIA="screen">
<LINK REL="StyleSheet" HREF="inc/css/form.css" TYPE="text/css" MEDIA="screen">
<LINK REL="StyleSheet" HREF="inc/css/timesheet.css" TYPE="text/css" MEDIA="screen">
<LINK REL="StyleSheet" HREF="inc/css/report.css" TYPE="text/css" MEDIA="screen">
<LINK REL="StyleSheet" HREF="inc/js/jquery-ui-1.10.3.custom/css/redmond/jquery-ui-1.10.3.custom.css" TYPE="text/css" MEDIA="screen">
<link REL="SHORTCUT ICON" HREF="inc/images/favicon.ico">
<link href="inc/css/calendar.css" rel="stylesheet" type="text/css" />
<script language="javascript" src="inc/classes/calendar.js"></script>
<script language="javascript" src="inc/js/jquery-ui-1.10.3.custom/js/jquery-1.9.1.js"></script>
<script language="javascript" src="inc/js/jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.js"></script>
</head>
<body>
<?php
if($_POST["func"]=="days_changed") {
	$days = $dl->select("flexi_weekdays");
	foreach($days as $day){
		$week_days[] = $day["fw_weekday"];
	}
	$formArr = array(array(type=>"intro", formtitle=>"Additional Time Details", formintro=>"Fill out the fields below to add additional information"), 
			array(prompt=>"Weekday", type=>"selection", name=>"week_day", listarr=>$week_days, selected=>"All", value=>"", clear=>true),
			array(prompt=>"Time", type=>"time", name=>"weekday_time", starttime=>"0000", endtime=>"0900", interval=>1, value=>"Enter leave period", clear=>true));	

			$form = new forms;
			$form->create_form($formArr);
	echo "<div id='show_time_total'></div>";		
	
	?>
	<script>
	$(document).ready(function(){
		$("#week_day").change(function() { 
			var func = "calc_hours";
			$.post(
				"ajax.php",
				{ func: func,
					days_per_week: <?php echo $_POST["days_per_week"]?>,
					hours: $("#weekday_time").val(),
					weekday: $("#week_day").val(),
					mins: $("#weekday_time_mins").val()
				},
				function (data)
				{
					$('#add_daysandtime').html(data);
			});
		});
		$("#weekday_time").change(function() { 
			var func = "calc_time";
			$.post(
				"ajax.php",
				{ func: func,
					option: "multiply",
					days_per_week: <?php echo $_POST["days_per_week"]?>
				},
				function (data)
				{
					$('#show_time_total').html(data);
			});
			$("#add_template").removeAttr("disabled");
		});
		$("#weekday_time_mins").change(function() { 
			var func = "calc_time";
			$.post(
				"ajax.php",
				{ func: func,
					option: "multiply",
					days_per_week: <?php echo $_POST["days_per_week"]?>
				},
				function (data)
				{
					$('#show_time_total').html(data);
			});
			$("#add_template").removeAttr("disabled");
		});
	});
	</script>
<?php
}

if($_POST["func"] == "calc_hours") {
	$days = $dl->select("flexi_weekdays");
	$continue = false;
	foreach($days as $day){
		if($_POST["weekday"] == $day["fw_weekday"] or $continue) {
			$week_days[] = $day["fw_weekday"];
			$continue = true;
		}
	}
	$formArr = array(array(type=>"intro", formtitle=>"Additional Time Details", formintro=>"Fill out the fields below to add additional information"));
	for($i=1; $i<=$_POST["days_per_week"]; $i++) {	 
			$formArr[] = array(prompt=>"Weekday", type=>"selection", name=>"week_day".$i, listarr=>$week_days, selected=>$week_days[$i-1], value=>"", clear=>true);
			$formArr[] = array(prompt=>"Time", type=>"time", name=>"weekday_time".$i, starttime=>"0000", endtime=>"0900", interval=>1, value=>"Enter leave period", clear=>true);	
			?>
			<script>
			$(document).ready(function(){
				$("#weekday_time<?php echo $i ?>").change(function() { 
					$("#add_template").removeAttr("disabled");
					var func = "calc_time";
					$.post(
						"ajax.php",
						{ func: func,
							days_per_week: <?php echo $_POST["days_per_week"]?>
						},
						function (data)
						{
							$('#show_time_total').html(data);
					});
				});
				$("#weekday_time<?php echo $i ?>_mins").change(function() { 
					$("#add_template").removeAttr("disabled");
					var func = "calc_time";
					$.post(
						"ajax.php",
						{ func: func,
							days_per_week: <?php echo $_POST["days_per_week"]?>
						},
						function (data)
						{
							$('#show_time_total').html(data);
					});
				});
			});
			</script>
			<?php
}
	$form = new forms;
	$form->create_form($formArr);
	echo "<div id='show_time_total'></div>";
	
}

if($_POST["func"] == "calc_time") {
	if($_POST["option"] != "multiply"){
		?>
		<script>
		var count = <?php echo $_POST["days_per_week"]?>;
		var hours = 0;
		var mins = 0;
		for(i=1; i<=count; i++) {
			hours = hours + Number($("#weekday_time"+i).val());
			mins = mins + Number($("#weekday_time"+i+"_mins").val());
			if(mins >= 60) {
				hours = hours + 1;
				mins = mins - 60;
			}
		}
		if(mins < 10){
			mins = "0"+mins;
		}
		if(hours < 10){
			hours = "0"+hours;
		}
		$("#show_time_total").html("Total (Hours:Mins) = "+hours+":"+mins);
		</script>
		<?php
	}else{
		?>
		<script>
		var count = <?php echo $_POST["days_per_week"]?>;
		var hours = 0;
		var mins = 0;
		mins = $("#weekday_time_mins").val() * count;
		hours =  $("#weekday_time").val() * count;
		while (mins >=60){
			mins = mins - 60;
			hours = hours + 1;
		}
		if(mins < 10){
			mins = "0"+mins;
		}
		if(hours < 10){
			hours = "0"+hours;
		}
		$("#show_time_total").html("Total (Hours:Mins) = "+hours+":"+mins);
		</script>
		<?php
	}
}

if($_POST["func"] == "save_template") {
	save_flexi_days_template();
}

?>

</body>