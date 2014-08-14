<?php 
session_start();
error_reporting("E_ALL & ~E_NOTICE");
//error_reporting("~E_NOTICE");
require('inc/mysqli_datalayer.php');
require('inc/connection.inc');
include('inc/functions.php');
include('inc/email_messages.inc');
require('inc/classes/calendar_class.php');
require('inc/classes/form_class.php');
require('inc/classes/libmail.inc');
require('inc/classes/tc_calendar.php');
require('inc/classes/showteamleave_class.php');
date_default_timezone_set('UTC');
$cal = new calendars;


if($_POST["func"] == "check_width") {
	$_SESSION["screenResolution"] = $_POST["width"];
	$width = array("width"=>$_POST["width"]);
	echo json_encode($width);
}

if($_POST["func"] == "get_event_type") {
	$typePos = strpos($_POST["id"], "-");
	$type = substr($_POST["id"], 0, $typePos);
	$etypes = dl::select("flexi_event_type", "event_type_id = ".$type);
	$returnType = array("type"=>$etypes[0]["event_type_name"]);
	echo json_encode($returnType);
}

if($_POST["func"] == "get_field_list") {
	echo "<div class='dropdown-panel' style='min-height: 300px;'>";
	echo "<h2>".$_POST["desc"]."</h2>";
	
	switch ($_POST["desc"]) {
		case "Working Day": 
			echo "<form id='workingdayform' action='index.php?func=saveevent&user=".$_POST["user"]."' method='post'>";
			echo "<input type=\"text\" name=\"time_start_entry\" id=\"starttimeentry\" size=\"10\" placeholder=\"Start Time\" value=\"".$_POST["time"]."\"/><BR /><BR />";
			echo "<input type=\"hidden\" name=\"time_start\" id=\"starttime\" size=\"10\" placeholder=\"Start Time\" />";
			echo "<input type=\"hidden\" name=\"time_start_mins\" id=\"starttimemins\" size=\"10\" placeholder=\"Start Time\" />";
			
			echo "<input type=\"text\" name=\"time_end_entry\" id=\"endtimeentry\" size=\"10\" placeholder=\"End Time\"  value=\"".$_POST["start"]."\"/><BR /><BR />";
			echo "<input type=\"hidden\" name=\"time_end\" id=\"endtime\" size=\"10\" placeholder=\"End Time\" />";
			echo "<input type=\"hidden\" name=\"time_end_mins\" id=\"endtimemins\" size=\"10\" placeholder=\"End Time\" />";
			
			echo "<input type=\"checkbox\" id=\"extended_lunch\" value='Yes' name='extended_lunch' /> Extended Lunch<BR /><BR />";
			
			echo "<input type=\"text\" name=\"lunch_time_start_entry\" id=\"lunchstartentry\" size=\"12\" placeholder=\"Lunch Start Time\" /><BR /><BR />";
			echo "<input type=\"hidden\" name=\"lunch_time_start\" id=\"lunchstart\" size=\"12\" placeholder=\"Lunch Start Time\" />";
			echo "<input type=\"hidden\" name=\"lunch_time_start_mins\" id=\"lunchstartmins\" size=\"12\" placeholder=\"Lunch Start Time\" />";
			
			echo "<input type=\"text\" name=\"lunch_time_end_entry\" id=\"lunchendentry\" size=\"12\" placeholder=\"Lunch End Time\" /><BR /><BR />";
			echo "<input type=\"hidden\" name=\"lunch_time_end\" id=\"lunchend\" size=\"12\" />";
			echo "<input type=\"hidden\" name=\"lunch_time_end_mins\" id=\"lunchendmins\" size=\"12\" />";
			
			echo "<input type=\"hidden\" name=\"date_name\" id=\"date_name\" size=\"12\" />";
			echo "<input type=\"hidden\" name=\"event_type\" id=\"event_type\" size=\"12\" />";
			echo "<button id='submit_button'>Add Event</button>";
			echo "</form>";
			$formName = 'workingdayform';
			break;
		case "Annual Leave":
		case "Maternity/Paternity Leave":
		case "Flexi Leave":
		case "Emergency Leave":	
		case "Unpaid leave":
			$div = str_replace(array(" ", "/"), "", $_POST["desc"]);
			echo "<form id='leaveform' action='index.php?func=saveevent&user=".$_POST["user"]."' method='post'>";		
			echo "<input type=\"text\" name=\"duration_time_entry\" id=\"durationentrytime\" size=\"10\" placeholder=\"Duration Start\" value=\"".$_POST["time"]."\" /><BR /><BR />";
			echo "<input type=\"hidden\" name=\"duration_time_start\" id=\"durationstarttime\" />";
			echo "<input type=\"hidden\" name=\"duration_time_start_mins\" id=\"durationstarttimemins\" />";
			echo "<div id='$div'>";
			echo "<input type='radio' id='".$div."1' name='duration' value='Full day'><label for='".$div."1'>Full day</label>";
			echo "<input type='radio' id='".$div."2' name='duration' value='Half day'><label for='".$div."2'>Half day</label>";
			echo "<input type='radio' id='".$div."3' name='duration' value='Remainder' checked='checked'><label for='".$div."3'>Remainder</label>";
			echo "</div><BR><BR>";
			echo "<input type=\"hidden\" name=\"date_name\" id=\"date_name\" size=\"12\" />";
			echo "<input type=\"hidden\" name=\"date_name2\" id=\"date_name2\" size=\"12\" />";
			echo "<input type=\"hidden\" name=\"event_type\" id=\"event_type\" size=\"12\" />";
			echo "<button id='submit_button'>Add Event</button>";
			echo "</form>";
			$formName = 'leaveform';
			break;
		case "Offsite Meeting":
		case "Sickness":
		case "Training":
        case "Carers Leave":
		case "Bereavement Leave":
		case "Hospital":
			$div = str_replace(" ", "", $_POST["desc"]);
			echo "<form id='meetingform' action='index.php?func=saveevent&user=".$_POST["user"]."' method='post'>";
			echo "<fieldset><h3>Duration</H3>";
			echo "<input type=\"text\" name=\"duration_time_entry\" id=\"durationentrytime\" size=\"10\" placeholder=\"Duration Start\" /> (Can be Blank) <BR /><BR />";
			echo "<input type=\"hidden\" name=\"duration_time_start\" id=\"durationstarttime\" />";
			echo "<input type=\"hidden\" name=\"duration_time_start_mins\" id=\"durationstarttimemins\" />";
			echo "<div id='$div'>";
			echo "<input type='radio' id='".$div."1' name='duration' value='Full day'><label for='".$div."1'>Full day</label>";
			echo "<input type='radio' id='".$div."2' name='duration' value='Half day'><label for='".$div."2'>Half day</label>";
			echo "<input type='radio' id='".$div."3' name='duration' value='Remainder'><label for='".$div."3'>Remainder</label>";
			echo "</div><BR><BR>";
			echo "</fieldset><BR>";
			echo "<fieldset><h3>Specific Time</h3>";			
			echo "<input type=\"text\" name=\"time_start_entry\" id=\"starttimeentry\" size=\"10\" placeholder=\"Start Time\" value=\"".$_POST["time"]."\"/><BR /><BR />";
			echo "<input type=\"hidden\" name=\"time_start\" id=\"starttime\" size=\"10\" placeholder=\"Start Time\" />";
			echo "<input type=\"hidden\" name=\"time_start_mins\" id=\"starttimemins\" size=\"10\" placeholder=\"Start Time\" />";
			
			echo "<input type=\"text\" name=\"time_end_entry\" id=\"endtimeentry\" size=\"10\" placeholder=\"End Time\" value=\"".$_POST["start"]."\"/><BR /><BR />";
			echo "<input type=\"hidden\" name=\"time_end\" id=\"endtime\" size=\"10\" placeholder=\"End Time\" />";
			echo "<input type=\"hidden\" name=\"time_end_mins\" id=\"endtimemins\" size=\"10\" placeholder=\"End Time\" />";
			echo "</fieldset><BR><BR>";
			echo "<input type=\"hidden\" name=\"date_name\" id=\"date_name\" size=\"12\" />";
			echo "<input type=\"hidden\" name=\"date_name2\" id=\"date_name2\" size=\"12\" />";
			echo "<input type=\"hidden\" name=\"event_type\" id=\"event_type\" size=\"12\" />";
			echo "<button id='submit_button'>Add Event</button>";
			echo "</form>";
			$formName = 'meetingform';
			break;
		case "TOIL":
			echo "<form id='toilform' action='index.php?func=saveevent&user=".$_POST["user"]."' method='post'>";
			echo "<fieldset><h3>Specific Time</h3>";			
			echo "<input type=\"text\" name=\"time_start_entry\" id=\"starttimeentry\" size=\"10\" placeholder=\"Start Time\" value=\"".$_POST["time"]."\"/><BR /><BR />";
			echo "<input type=\"hidden\" name=\"time_start\" id=\"starttime\" size=\"10\" placeholder=\"Start Time\" />";
			echo "<input type=\"hidden\" name=\"time_start_mins\" id=\"starttimemins\" size=\"10\" placeholder=\"Start Time\" />";
			
			echo "<input type=\"text\" name=\"time_end_entry\" id=\"endtimeentry\" size=\"10\" placeholder=\"End Time\"  value=\"".$_POST["start"]."\"/><BR /><BR />";
			echo "<input type=\"hidden\" name=\"time_end\" id=\"endtime\" size=\"10\" placeholder=\"End Time\" />";
			echo "<input type=\"hidden\" name=\"time_end_mins\" id=\"endtimemins\" size=\"10\" placeholder=\"End Time\" />";
			echo "</fieldset><BR><BR>";
			echo "<input type=\"hidden\" name=\"date_name\" id=\"date_name\" size=\"12\" /><BR /><BR />";
			echo "<input type=\"hidden\" name=\"event_type\" id=\"event_type\" size=\"12\" /><BR /><BR />";
			echo "<button id='submit_button'>Add Event</button>";
			echo "</form>";
			$formName = 'toilform';
			break;
	}
	echo "</div>";
//TODO: Working on the submission of the data to the save_event function in function.php
	?>
	<script>
	var fieldNames = new Array("#starttimeentry", "#endtimeentry", "#lunchstartentry", "#lunchendentry", "#durationentrytime");
	for(var field in fieldNames) {
		$(fieldNames[field]).timepicker({
			defaultTime: '<?php echo $_POST["time"]?>',
			showPeriodLabels: false,
			hours: { starts: 7, ends: 23},
			rows: 10,
			minutes: { interval: 1}
		});
		
	}
	$(function(){
		$("#<?php echo $div?>").buttonset();
		$("#submit_button").button()
		.click(function( event ){
			$("#valDisp").hide();
			$("#arrow-left").hide();
			event.preventDefault();
			/*
			 * check the submit values and submit to save_event function
			 */
			<?php
			switch($_POST["desc"]){
				case "Working Day":
					echo "$('#starttime').val( $('#starttimeentry').val().substring(0,2) );";
					echo "$('#starttimemins').val( $('#starttimeentry').val().substring(3) );";
					echo "$('#endtime').val( $('#endtimeentry').val().substring(0,2) );";
					echo "$('#endtimemins').val( $('#endtimeentry').val().substring(3) );";
					echo "$('#lunchend').val( $('#lunchendentry').val().substring(0,2) );";
					echo "$('#lunchendmins').val( $('#lunchendentry').val().substring(3) );";
					echo "$('#lunchstart').val( $('#lunchstartentry').val().substring(0,2) );";
					echo "$('#lunchstartmins').val( $('#lunchstartentry').val().substring(3) );";
					break;
				case "Annual Leave":
				case "Maternity/Paternity Leave":
				case "Flexi Leave":
				case "Carers Leave":
				case "Emergency Leave":	
				case "Unpaid leave":
					echo "$('#durationstarttime').val( $('#durationentrytime').val().substring(0,2) );";
					echo "$('#durationstarttimemins').val( $('#durationentrytime').val().substring(3) );";
				break;
				case "TOIL":
					echo "$('#starttime').val( $('#starttimeentry').val().substring(0,2) );";
					echo "$('#starttimemins').val( $('#starttimeentry').val().substring(3) );";
					echo "$('#endtime').val( $('#endtimeentry').val().substring(0,2) );";
					echo "$('#endtimemins').val( $('#endtimeentry').val().substring(3) );";
				break;
				default:
					echo "$('#starttime').val( $('#starttimeentry').val().substring(0,2) );";
					echo "$('#starttimemins').val( $('#starttimeentry').val().substring(3) );";
					echo "$('#endtime').val( $('#endtimeentry').val().substring(0,2) );";
					echo "$('#endtimemins').val( $('#endtimeentry').val().substring(3) );";
					echo "$('#durationstarttime').val( $('#durationentrytime').val().substring(0,2) );";
					echo "$('#durationstarttimemins').val( $('#durationentrytime').val().substring(3) );";
			}
			?>
			$("#date_name").val("<?php echo $_POST["date"]?>");
			$("#date_name2").val("<?php echo $_POST["date"]?>");
			$("#event_type").val("<?php echo $_POST["desc"]?>");
			$("#<?php echo $formName?>").submit();
		});
	});
	</script>
	<?php
}

if($_POST["func"]=="days_changed") {
	$days = dl::select("flexi_weekdays");
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
			join flexi_event as e on (t.timesheet_id=e.timesheet_id) where u.user_name = \"".$_POST["user"]["name"]."\"
			and event_type_id = 3 order by event_startdate_time ASC"; //all of the users leave
	echo $sql;
	$users_leave = dl::getQuery($sql);
	echo "SELECTED USER : ".$_POST["user"]["name"]."<BR><BR>";
	echo "<div style='margin-left: 2em; width: 10em; float: left; font-size: 1.25em'>Event ID</div><div style='width: 15em;  float: left;  font-size: 1.25em'>Start Time</div><div style='width: 15em;  float: left;  font-size: 1.25em'>End Time</div><div style='width: 7em;  float: left;  font-size: 1.25em'>Time</div><div style='width: 11em;  text-align: center; float: left;  font-size: 1.25em'>Half\Full</div><BR><BR>";
	echo "<div style='padding:1em; height: 40em; overflow: auto; '>"; //background-color:#E5EBCC;
	$colorCount = 0;
	foreach($users_leave as $ul) {
		//check if eventID is already in the leave count table
		$leave_count = dl::select("flexi_leave_count", "flc_event_id =".$ul["event_id"]);
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
				dl::insert("flexi_leave_count", array("flc_fullorhalf"=>1, "flc_event_id"=>substr($leave,4, strlen($leave))));
			break;
			case "half":
				dl::insert("flexi_leave_count", array("flc_fullorhalf"=>0.5, "flc_event_id"=>substr($leave,4, strlen($leave))));
			break;
		}
	}
	echo "Updated Leave";
}

if($_POST["func"] == "calc_hours") {
	$days = dl::select("flexi_weekdays");
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

if($_POST["func"]=="check_delete") {
	$id = substr($_POST["dropped"],4, strlen($_POST["dropped"]));
	if(check_permission("Events")) {
		global $dl;
		include("inc/email_messages.inc");
		//get Team id
		$sql = "select * from flexi_team as ft 
			join flexi_team_user as ftu on (ftu.team_id=ft.team_id)
			join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id) 
			join flexi_timesheet as t on (t.user_id=ftu.user_id) 
			join flexi_event as e on (e.timesheet_id=t.timesheet_id) 
			join flexi_event_type as et on (et.event_type_id=e.event_type_id) 
			where e.event_id=$id";
		$team = dl::getQuery($sql);
		$teamId=$team[0]["team_id"];
		$userId = $team[0]["user_id"];
		$u_name=dl::select("flexi_user", "user_id=".$userId);
		$user_name=$u_name[0]["user_name"];
		$eventDate = substr($team[0]["event_startdate_time"],0,10);
		$eventStartTime = substr($team[0]["event_startdate_time"],11,5);
		$eventEndTime = substr($team[0]["event_enddate_time"],11,5);
		$eventTypeName = $team[0]["event_type_name"];
		$eventTypeId = $team[0]["event_type_id"];
		$eventAuthorisation = $team[0]["event_authorisation"];
		$eventWork = $team[0]["event_work"];
		$eventGlobal = $team[0]["event_global"];
		$eventAnnualLeave = $team[0]["event_al"];
		$eventFlexiLeave = $team[0]["event_flexi"];
		$eventDelete = $team[0]["event_delete"];
		// need to check the event type and inform the team manager if needs be.
		if($eventDelete == "Yes" or $_SESSION["userPermissions"]["override_delete"]=="true") {
			$allowDelete = true; //user can delete the event or the override permission is possessed
		}else{
			$allowDelete = false;
		}
		$highLevel=false;
		//now need to see if this user is a manager/approver within this team
		//this determines if the manager in this team receives the approval request or the none local team member approver/manager
		$sql = "select fu.user_id, fu.user_email, fu.user_name from flexi_permission_template as fpt 
		join flexi_user as fu on (fu.user_permission_id=fpt.permission_template_name_id) 
		join flexi_team_user as ftu on (ftu.user_id=fu.user_id)
		left outer join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id)
		left outer join flexi_deleted as d on (fu.user_id=d.user_id) 
		join flexi_team as ft on (ft.team_id=ftu.team_id) 
		where ft.team_id = ".$teamId." and fpt.permission_team_authorise = 'true' and date_deleted IS NULL and tl.team_user_id IS NOT NULL";
		$localManager = dl::getQuery($sql);
		foreach($localManager as $lm) {
			if($lm["user_id"] == $userId) {
				//this is a local manager request therefore needs to be authorised at a higher level
				$highLevel = true;	
			}
			//create an array of the managers who can approve the event
			$recipients[]=$lm["user_email"];
			$names[]=$lm["user_name"];
		}
		if($highLevel == false) {
			foreach($localManager as $lm) {
				if($_SESSION["userSettings"]["userId"] == $lm["user_id"]) { //local user trying to delete another user or manager in the team
					$allowDelete = true;
				}
			}
		}else{ //lets check to see if this manager has the over ride local manager constraint permission so this manager can delete other managers' events in the same team
            $constraint_permission = dl::select("flexi_permission_template", "permission_template_name_id = ".$_SESSION["userSettings"]["permissionId"]);
            if($constraint_permission[0]["permission_LM_constraint"] == 'true') {
                $allowDelete = true;
            }
        }
		if($highLevel or empty($localManager)) { 
			// this is a request from the local team manager so the request should go to the non-local manager or there is no local manager in this group	
			$sql = "select fu.user_id, user_name, user_email from flexi_permission_template as fpt 
			join flexi_user as fu on (fu.user_permission_id=fpt.permission_template_name_id) 
			join flexi_team_user as ftu on (ftu.user_id=fu.user_id)
			left outer join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id)
			left outer join flexi_deleted as d on (fu.user_id=d.user_id) 
			join flexi_team as ft on (ft.team_id=ftu.team_id) 
			where ft.team_id = ".$teamId." and fpt.permission_team_authorise = 'true' and date_deleted IS NULL and tl.team_user_id IS NULL";
			$manager = dl::getQuery($sql);
			foreach($manager as $m) {
				//create an array of the managers who can approve the event
				$recipients[]=$m["user_email"];
				$names[]=$m["user_name"];
				if($_SESSION["userSettings"]["userId"] == $m["user_id"]) { //none local user trying to delete another user or manager in the team
					$allowDelete = true;
				}
			}
		}
		if($eventGlobal=="Yes"){ 
			?>
			<script type="text/javascript">
			<!--
			var choice = prompt ("Type GLOBAL (uppercase) to delete the global event from all user timesheets, leave blank to delete from the individual, 'Cancel' will not carry out the request.")
			if (choice=='GLOBAL') {
				redirect ("index.php?func=deleteevent&id=<?php echo $id?>&confirmation=true&deltype=global");
			}
			else if (choice == '') {
				redirect ("index.php?func=deleteevent&id=<?php echo $id?>&confirmation=true&deltype=individual");
			}
			else {
				redirect ("index.php?func=edituserevents&userid=<?php echo $userId?>&page=0");
			}
			// -->
			</script> 
			<?php
		}
		if($eventWork == "No") {
			//check event type
			$names = implode(", ",$names); 
			$userName=$_SESSION["userSettings"]["name"];
			$userEmail=$_SESSION["userSettings"]["email"];
			$bodyText = $email_7_content;
			$bodyText = str_replace("%%whoto%%", $names, $bodyText);
			$bodyText = str_replace("%%user%%", $userName, $bodyText);
			$bodyText = str_replace("%%eventowner%%", $user_name, $bodyText);
			$bodyText = str_replace("%%eventtype%%", $eventTypeName, $bodyText);
			$bodyText = str_replace("%%EVENTDATE%%", $eventDate, $bodyText);
			$bodyText = str_replace("%%START%%", $eventStartTime, $bodyText);
			$bodyText = str_replace("%%END%%", $eventEndTime, $bodyText);
			
			if($allowDelete) {
				$subject = $email_7_subject;
				$bodyText = str_replace("%%delete%%", "deleted", $bodyText);
				$bodyText = str_replace("%%HAS/HASNOT%%", "HAS", $bodyText);
			}else{
				$subject = $email_7_subject1;
				$bodyText = str_replace("%%delete%%", "attempted to delete", $bodyText);
				$bodyText = str_replace("%%HAS/HASNOT%%", "HAS NOT", $bodyText);
			}
			//send email dependant on the type of event
			$m = new Mail();	
			//send the email confirmation
			$m->From( "fws@ncl.ac.uk" ); // the first address in the recipients list is used as the from email contact and will receive emails in response to the registration request.
			$m->autoCheck(false);
			$m->To( $recipients );
			$m->Subject( $subject );
			$m->Body( $bodyText );
			$m->CC( $userEmail );
			$m->Priority(3);
			$m->Send();
		}
		if($allowDelete) {
			if($eventGlobal=="No") {
				//delete is going ahead. Check flexi_leave_count table to see if event is in the table and delete it too
				dl::delete("flexi_leave_count", "flc_event_id = $id"); // if it doesn't find it no delete happens. This is for Annual Leave
				dl::delete("flexi_event", "event_id=$id");
			}else{
				if($_SESSION["userPermissions"]["add_global"]=="true"){
					if($deltype == "individual") {
						dl::delete("flexi_event", "event_id=$id");
					}elseif($deltype == "global") {
						dl::delete("flexi_event", "event_startdate_time = '".$eventDate." ".$eventStartTime."' and event_type_id = ".$eventTypeId);
						$global_events = dl::select("flexi_global_events", "event_date = '".$eventDate."' and event_type_id = ".$eventTypeId);
						foreach($global_events as $ge) {
							dl::delete("flexi_global_teams", "global_id = ".$ge["global_id"]);
						}
						dl::delete("flexi_global_events", "event_date = '".$eventDate."' and event_type_id = ".$eventTypeId);
					}
					
				}
			}
		}
		if($_POST["pagelocation"] == "nextperiod" or $_POST["pagelocation"] == "previousperiod") {
			echo "<SCRIPT language='javascript'>redirect('index.php?func=".$_POST["pagelocation"]."&start=".$_POST["start"]."&end=".$_POST["end"]."&userid=".$_POST["user"]."')</SCRIPT>" ;
		}elseif($_POST["pagelocation"] == "viewuserstimesheet") {
			echo "<SCRIPT language='javascript'>redirect('index.php?func=".$_POST["pagelocation"]."&userid=".$_POST["user"]."')</SCRIPT>" ;
		}else{
			echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=timesheet')</SCRIPT>" ;
		}
	}
}

if($_POST["func"]=="toggle_lock") {
	$locked = dl::select("flexi_locked");
	if($locked[0]["locked"] == "true") {
		dl::update("flexi_locked", array("locked"=>"false"));
	}elseif($locked[0]["locked"]== "false"){
		dl::update("flexi_locked", array("locked"=>"true"));
	}
	$locked = dl::select("flexi_locked");
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

if($_POST["func"] == "p_period") {
	$start 	= date("Y-m-d", strtotime(sub_date(strtotime($_POST["from"]),28)));
	$end	= date("Y-m-d", strtotime(sub_date(strtotime($_POST["to"]),28)));
	$user_id = $_SESSION["userSettings"]["userId"];
	teamLeaveDisplay($start, $end, $user_id);
}
if($_POST["func"] == "n_period") {
	$start 	= date("Y-m-d", strtotime(add_date(strtotime($_POST["from"]),28)));
	$end	= date("Y-m-d", strtotime(add_date(strtotime($_POST["to"]),28)));
	$user_id = $_SESSION["userSettings"]["userId"];
	teamLeaveDisplay($start, $end, $user_id);
}
?>