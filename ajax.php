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

if($_POST["func"] == "show_user_leave") {
	$leaveType = array("Full", "Half");
	$sql = "select * from flexi_user as u join flexi_timesheet as t on (u.user_id=t.user_id)
			join flexi_event as e on (t.timesheet_id=e.timesheet_id) where u.user_name = '".$_POST["user"]."' 
			and event_type_id = 3 order by event_startdate_time ASC"; //all of the users leave
	$users_leave = $dl->getQuery($sql);
	echo "SELECTED USER : ".$_POST["user"]."<BR><BR>";
	echo "<div style='margin-left: 2em; width: 10em; float: left; font-size: 1.25em'>Event ID</div><div style='width: 15em;  float: left;  font-size: 1.25em'>Start Time</div><div style='width: 15em;  float: left;  font-size: 1.25em'>End Time</div><div style='width: 7em;  float: left;  font-size: 1.25em'>Time</div><div style='width: 11em;  text-align: center; float: left;  font-size: 1.25em'>Half\Full</div><BR><BR>";
	echo "<div style='padding:1em; height: 40em; overflow: auto; '>"; //background-color:#E5EBCC;
	$colorCount = 0;
	foreach($users_leave as $ul) {
		//check if eventID is already in the leave count table
		$leave_count = $dl->select("flexi_leave_count", "flc_event_id =".$ul["event_id"]);
		if( $colorCount == 0) {
			$bgColor = "#E5EBCC";
			$colorCount++;
		}elseif($colorCount == 1 ) {
			$bgColor = "#D5D8AB";
			$colorCount--;
		}
		if(empty($leave_count)) {
			$start = strtotime($ul["event_startdate_time"]);
			$end = strtotime($ul["event_enddate_time"]);
			$time = $end - $start;
			if($time !== $lastTime and !empty($lastTime)) {
				$color = "#999";
			}else{
				$color = "#333";
			}
			$lastTime = $time;
			echo "<div style='margin-left: 2em; width: 10em; float: left; font-size: 1em; color: ".$color."; padding: 4px; height: 2.5em; background-color: ".$bgColor.";'>".$ul["event_id"]."</div><div style='width: 18em;  float: left;  font-size: 1em; color: ".$color."; padding: 4px; height: 2.5em; background-color: ".$bgColor.";'>".$ul["event_startdate_time"]."</div><div style='width: 19em;  float: left;  font-size: 1em; color: ".$color."; padding: 4px; height: 2.5em; background-color: ".$bgColor.";'>".$ul["event_enddate_time"]."</div><div style='width: 11em;  float: left;  font-size: 1em; color: ".$color."; padding: 4px; height: 2.5em; background-color: ".$bgColor.";'>".date("h:i:s", $time)."</div>";
			echo "<div style='width: 10em;  float: left;  font-size: 1em; padding: 4px; height: 2.5em; background-color: ".$bgColor.";'>";
				echo "<div id='fullorhalf".$ul["event_id"]."' style='float: left;'>";
					echo "<input class='leave' type='radio' name='button".$ul["event_id"]."' id='halfbutton".$ul["event_id"]."' value='half".$ul["event_id"]."'><label for='halfbutton".$ul["event_id"]."'>Half</label>";
					echo "<input class='leave' type='radio' name='button".$ul["event_id"]."' id='fullbutton".$ul["event_id"]."' value='full".$ul["event_id"]."' checked><label for='fullbutton".$ul["event_id"]."'>Full</label>";
				echo "</div>";
			echo "</div><BR><BR>";
			?>
			<script>
			 $(function() {
				$( "#fullorhalf<?php echo $ul["event_id"]?>" ).buttonset();
			});
			</script>
			<?php
		}
	}
	
	echo "</div>";
	echo "<input type='button' id='save_count' value='Save Count'>";
	echo "<div id='count_saved'></div>";
	?>
			<script>
			 $(function() {
				$( "#save_count" ).click(function(){
					
					var btnActive = [];
					$(".leave:checked").each(function(){
						btnActive.push($(this).val());
					})
					var func = "save_user_leave";
					$.post(
						"ajax.php",
						{ func: func,
							leave: btnActive
						},
						function (data)
						{
							$('#count_saved').html(data);
							$("#count_saved").fadeOut("slow");
							$("#count_saved").show();
							$("#display_leave").delay(1000).slideUp("slow");
							$("#display_leave").show();
					});
				});
			});
			</script>
			<?php
}

if($_POST["func"] == "save_user_leave") {
	global $dl;
	foreach($_POST["leave"] as $leave) {
		switch(substr($leave,0,4)) {
			case "full":
				$dl->insert("flexi_leave_count", array("flc_fullorhalf"=>1, "flc_event_id"=>substr($leave,4, strlen($leave))));
			break;
			case "half":
				$dl->insert("flexi_leave_count", array("flc_fullorhalf"=>0.5, "flc_event_id"=>substr($leave,4, strlen($leave))));
			break;
		}
	}
	echo "Updated Leave";
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

if($_POST["func"]=="toggle_lock") {
	$locked = $dl->select("flexi_locked");
	if($locked[0]["locked"] == "true") {
		$dl->update("flexi_locked", array("locked"=>"false"));
	}elseif($locked[0]["locked"]== "false"){
		$dl->update("flexi_locked", array("locked"=>"true"));
	}
	$locked = $dl->select("flexi_locked");
	echo json_encode(array("lock"=>$locked[0]["locked"]));
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

if($_POST["func"] == "edit_template") {
	save_flexi_days_template_edit();
}

?>