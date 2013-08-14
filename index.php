<!DOCTYPE html>
<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1">
<SCRIPT type="text/javascript">
function redirect(url) {
	window.location = url;
}
</script>
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
date_default_timezone_set('UTC');
$cal = new calendars;
?>
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8" />

<title>Newcastle Biomedicine CRP Flexible Working Application</title>
<LINK REL="StyleSheet" HREF="inc/css/normalise.css" TYPE="text/css" MEDIA="screen">
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
<SCRIPT language	="JavaScript" src="inc/js/jquery.spritely.js"></SCRIPT>
</head>
<body>
<?php
	//keep track of the user month selection
	if(!empty($_GET["showMths"])){
		$_SESSION["showMths"] = $_GET["showMths"];
	}
	if(empty($_SESSION["showMths"])) {
		$_SESSION["showMths"]=2;
	}
	if($_SESSION["showMths"]== 4) {
		echo "<div class='calendar_main' style='height:925px;'>";
	}elseif($_SESSION["showMths"] == 6) {
		echo "<div class='calendar_main' style='height:1000px;'>";
	}else{
		echo "<div class='calendar_main'>";
	}

	//a login request has been made
	if($_GET["func"] == "login") { // attempt to login
		$email= strtolower(addslashes($_POST["email_address"]));
		$password=$_POST["password"];
		$check_email = dl::select("flexi_user", "user_email='$email'");
		$check_deleted = dl::select("flexi_deleted", "user_id=".$check_email[0]["user_id"]);
		if(empty($check_deleted)) {
			$check_timesheet = dl::select("flexi_timesheet", "user_id=".$check_email[0]["user_id"]);
			if(!empty($check_email)) {
				foreach($check_email as $ce) {
					//create array listing all user settings when login confirmed transfer to a session variable
					$user_settings=array(secId=>$ce["user_security"],userId=>$ce["user_id"],permissionId=>$ce["user_permission_id"],email=>$ce["user_email"],name=>$ce["user_name"],al=>$ce["user_al_template"],timeTemplate=>$ce["user_time_template"],flexiTemplate=>$ce["user_flexi_template"],timesheet=>$check_timesheet[0]["timesheet_id"]);
				}
				$check_password = dl::select("flexi_security", "security_id=".$user_settings['secId']);
				if(!empty($check_password)) {
					foreach($check_password as $cp) {
						echo MD5(SALT.$password), $cp["security_password"];
						if($cp["security_password"] == MD5(SALT.$password)) {
							$_SESSION["userSettings"]=$user_settings;
							//connect to PERMISSION table and retrieve permission settings
							$getPermissions = dl::select("flexi_permission_template", $_SESSION["userSettings"]["permissionId"]."=permission_template_name_id");
							if(!empty($getPermissions)) {
								foreach($getPermissions as $gp) {
									//add permissions to session array
									$_SESSION["userPermissions"]=array(user=>$gp["permission_user"],templates=>$gp["permission_templates"],teams=>$gp["permission_teams"],team_events=>$gp["permission_team_events"],team_authorise=>$gp["permission_team_authorise"],events=>$gp["permission_events"],event_types=>$gp["permission_event_types"],add_time=>$gp["permission_add_time"],edit_time=>$gp["permission_edit_time"],edit_locked_time=>$gp["permission_edit_locked_time"],add_global=>$gp["permission_add_global"], override_delete=>$gp["permission_override_delete"], edit_flexipot=>$gp["permission_edit_flexipot"], view_user_leave=>$gp["permission_view_leave"], user_messaging=>$gp["permission_messaging"], view_user_timesheet=>$gp["permission_view_timesheet"],view_user_override=>$gp["permission_view_override"], view_reports=>$gp["permission_view_reports"], year_end=>$gp["permission_year_end"], lock_override=>$gp["permission_lock"]);
								}
							}
							//check to see if the application is locked before setting the loggedin session variable
							$locked = dl::select("flexi_locked");
							if($locked[0]["locked"]=="True" or $locked[0]["locked"]=="true") { //the application is locked
								if($_SESSION["userPermissions"]["lock_override"]=="false") {
									echo "<div id='noAccess_dialog' style='display: none;' title='The FWS Application Unavailable'>";
									echo "The FWS application has been locked for maintenance. You will receive a message when the system becomes available. Sorry for the inconvenience!<BR /><BR />";
									echo "</div>";
									
									?>
									 <script>
									$(function() {
										$("#noAccess_dialog").dialog();
									});
									</script>
									<?php
									die();
								}
							}
							//credentials confirmed
							$_SESSION["loggedin"]=true;
							
						}
					}
				}
			}
		}else{
			//message and return to access account
			echo "<SCRIPT language='javascript'>alert(\"Sorry your user account has been disabled. Please contact an Administrator of the flexible working system to reinstate your id.\"); redirect('index.php')</SCRIPT>" ;
		}
	}
	if($_GET["func"] == "forgotPass") { //user has forgotton their password
		$formArr = array(array(type=>"intro", formtitle=>"Reset your password", formintro=>"Enter your email address to receive a reset link."), 
			array(type=>"form", form=>array(action=>"index.php?func=resetPass",method=>"post")),
			array(prompt=>"Email Address", type=>"text", name=>"email_address", length=>20, value=>""), 
			array(type=>"submit", buttontext=>"Reset"), 
			array(type=>'endform'));
		$form = new forms;
		echo $form->create_form($formArr, "120px");
	}elseif($_GET["func"]=="resetPass") {
		reset_pass($_POST["email_address"]);
	}elseif($_GET["func"]=="resetPassword") {
		reset_password($_GET["passcode"]);
	}elseif($_GET["func"]=="changePassword") {
		change_password($_POST, $_GET["passcode"]);	
	}elseif(!isset($_SESSION["loggedin"]) and $_GET["func"] <> "confirmAcc" ){ //check if the session has been lost
		$formArr = array(array(type=>"intro", formtitle=>"Flexible Working System Login", formintro=>"Enter your email address and password to access the CRP Flexible Working web Application."), 
			array(type=>"form", form=>array(action=>"index.php?func=login",method=>"post")),
			array(prompt=>"Email Address", type=>"text", name=>"email_address", length=>20, value=>""), 
			array(prompt=>"Password", type=>"password", name=>"password", length=>20, value=>""),
			array(prompt=>"forgotten password", type=>"link", link=>"index.php?func=forgotPass"),
			array(type=>"submit", buttontext=>"Login"), 
			array(type=>'endform'));
		$form = new forms;
		echo $form->create_form($formArr, "120px");
	}
	// confirm that the user has logged in
	if($_SESSION["loggedin"]) { 
		//now need to get settings to display in left hand info location
		//get annual leave entitlement and used leave
		$used = checkLeaveEntitlement($_SESSION["userSettings"]["userId"]);
		$arrTime = checkWeeklyHours($_SESSION["userSettings"]["userId"]);
		global $entitledTo;
		global $nextYrLeave;
		global $hoursLeave;
		global $hoursTaken;
		global $leaveAccountType;
		//now lets check if they have any additional holidays
		$additional=0;
		$additionalHols=dl::select("flexi_carried_forward_live", "timesheet_id=".$_SESSION["userSettings"]["timesheet"]);
		$additional = $additionalHols[0]["additional_leave"]; 
		if($leaveAccountType=="Parttime") {
			$add = dl::select("flexi_template", "template_id=".$_SESSION["userSettings"]["flexiTemplate"]);
			$additional = $additional * $add[0]["max_surplus"]; //this calculates the hours from the average hours worked per day.
		}
		?>
		<div class='header_left'>
			<div class='header_text'>
			Calendar
			</div>
			
		</div>
	
		<?php 
		display_calendar($_SESSION["showMths"], -1)?>
        <div class='header_left'>
			<div class='header_text'>
			Start Time Reminder
			</div>
		</div> 
		<div class='left_body'><div class='left_body_spacer'>
		<?php
		$reminderTime = dl::select("flexi_time_reminder", "timesheet_id = ".$_SESSION["userSettings"]["timesheet"]);
        $formArr = array(array(type=>"intro", formtitle=>"", formintro=>"Enter your start time reminder."), 
			array(type=>"form", form=>array(action=>"index.php?func=reminder",method=>"post")),
			array(prompt=>"Start Time", type=>"time", name=>"rem_time",starttime=>"0000", endtime=>"2300", interval=>1, selected=>$reminderTime[0]["reminder"], clear=>true),
			array(type=>"submit", buttontext=>"Set reminder"), 
			array(type=>'endform'));
		$form = new forms;
		echo $form->create_form($formArr);
		?>
        </div></div>
		<div class='header_left'>
			<div class='header_text'>
			Outstanding Requests
			</div>
		</div>
		<?php
		$message = "<span class='request_pad'>There are currently no outstanding requests</span>";
		$css = "request_view";
		$userId = $_SESSION["userSettings"]["userId"];		
		$sql = "select * from flexi_user as u 
		join flexi_permission_template as p on (u.user_permission_id=p.permission_template_name_id) 
		where u.user_id = ".$userId." and permission_team_authorise = 'true'";
		$manager = dl::getQuery($sql);
		//loop here for multiple teams
		$teamWhere = "";
		$team = dl::select("flexi_team_user", "user_id=".$userId);
		foreach($team as $t) {
			$teamWhere .= "tu.team_id = ".$t["team_id"]. " or ";
			//get current users home team
			$homeTeam = dl::select("flexi_team_local", "team_user_id=".$t["team_user_id"]);
			if(!empty($homeTeam)) {
				$homeTeamId = $homeTeam[0]["team_user_id"];
			}
		}
		$teamWhere = "(".substr($teamWhere,0,strlen($teamWhere)-4).")";
		if(!empty($manager)) {
			//An approver
			$sql = "select distinct(event_id), u.user_id from flexi_requests as r 
			join flexi_event as e on (r.request_event_id=e.event_id) 
			join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) 
			join flexi_user as u on (t.user_id=u.user_id) 
			join flexi_team_user as tu on (tu.user_id=u.user_id) 
			where u.user_id <> ".$_SESSION["userSettings"]["userId"]." and r.request_approved = '' and ".$teamWhere;
			$count = dl::getQuery($sql);
			$requests=0;
			foreach($count as $c) {
				//has returned all of the request in the member teams and not returned your own requests
				//now need to check if in your home team and ignore if managers in home team
				$sql = "select * from flexi_event as e
				join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) 
				join flexi_user as u on (t.user_id=u.user_id) 
				join flexi_permission_template as pt on (u.user_permission_id=pt.permission_template_name_id) 
				where event_id = ".$c["event_id"];
				$check_permission = dl::getQuery($sql);
				//now need to find out the users home team
				$user_teams = dl::select("flexi_team_user", "user_id=".$c["user_id"]);
				foreach($user_teams as $ut) {
					$user_homeTeam = dl::select("flexi_team_local", "team_user_id=".$ut["team_user_id"]);
					if(!empty($user_homeTeam)){
						$user_homeTeamId = $user_homeTeam[0]["team_user_id"];
					}
				}
				if($check_permission[0]["permission_team_authorise"]=="false") { //the permission of the requestor
					$requests++;	
				}else{ //the requestor is a manager
					//need to check if the current user is in the requestors home team
					$inTeam = dl::select("flexi_team_user", "user_id = ".$c["user_id"]." and team_user_id = ".$user_homeTeamId);
					if(!empty($inTeam)) { //confirmed that the manager is responsible for the approval
						//check that the users team is managed by the manager
						$managed = dl::select("flexi_team_user", "user_id = ".$_SESSION["userSettings"]["userId"]." and team_id = ".$inTeam[0]["team_id"]);
						if(!empty($managed)){
							if($user_homeTeamId <> $homeTeamId) {
								$requests++;
							}
						}
					}
				}
			}
		}
		$sql = "select COUNT(request_id) as cRequest from flexi_requests as r 
		join flexi_event as e on (r.request_event_id=e.event_id) 
		join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) 
		join flexi_user as u on (t.user_id=u.user_id) 
		where r.request_approved = '' and u.user_id =".$userId;
		$count = dl::getQuery($sql);
		if($count[0]["cRequest"]>0) {
			$css = "request_message";
			if($message == "<span class='request_pad'>There are currently no outstanding requests</span>"){
				$message="";
			}
			$message .= "<span class='request_pad'>You have made ".$count[0]["cRequest"]." request(s). <a href='index.php?func=yourrequests'>View your requests</a></span>";
		}

		if(!empty($manager) and $requests>0) {
			$css = "request_message";
			if($message == "<span class='request_pad'>There are currently no outstanding requests</span>"){
				$message="";
			}
			$message .= "<div class='request_pad'>You have ".$requests." outstanding request(s). <a href='index.php?func=approveleave'>View requests</a></div>";
		}
		echo "<div class='$css'>$message</div>";
		?>
		<div class='header_left'>
			<div class='header_text'>
			Team Information
			</div>
		</div>
		<div class='left_body'><div class='left_body_spacer'>
		<?php
		$sql = "select COUNT(user_id) as cTeams from flexi_team_user where user_id = ".$userId;
		$teams=dl::getQuery($sql);
		$cTeams = $teams[0]["cTeams"];
		if(!empty($manager)) { //An approver
			//get team names and add to drop down list if count is > 1
			echo "You're responsible for the requests from ".$cTeams." team(s)<BR>";
		}else{
			echo "You're a member of ".$cTeams." team(s)<BR><BR>";
		}
		$sql = "select * from flexi_team_user as u join flexi_team as t on (u.team_id=t.team_id) where u.user_id = ".$userId." order by t.team_name";
		$teams = dl::getQuery($sql);
		if($cTeams > 1) {
			foreach($teams as $tm) {
				$teamNames[] = $tm["team_name"];
			}
			$formArr = array(array(type=>"form", form=>array(action=>"index.php?func=viewteam",method=>"post")),	
				array(prompt=>"Membership:", type=>"selection", name=>"team_name", listarr=>$teamNames, selected=>"", value=>"", clear=>true),
				array(type=>"submit", buttontext=>"View Team", clear=>false), 
				array(type=>'endform'));
				$form = new forms;
				$form->create_form($formArr, "90px");
		}else{
			//show a link instead of a drop down box
			echo "<div class='left_header'>Membership:</div>";
			echo "<div class='left_text'>".$teams[0]["team_name"]." <a href='index.php?func=viewteam&team=".$teams[0]["team_id"]."'>View Team</a></div>";	
		}
		?>
		</div></div>
		<div class='header_left'>
			<div class='header_text'>
			User Information
			</div>
		</div>
		<div class='left_body'><div class='left_body_spacer'>
        	<div class='left_clear'></div>
			<div class='left_header'>
			User Name:
			</div>
			<div class='left_text'>
			<?php echo $_SESSION["userSettings"]["name"]?>
			</div>
            <div class='left_clear'></div>
			<div class='left_header'>
			Email:
			</div>
			<div class='left_text'>
			<a href="mailto:<?php echo $_SESSION["userSettings"]["email"]?>"><?php echo $_SESSION["userSettings"]["email"]?></a>
			</div>
            <div class='left_clear'></div>
			<div class='left_header'><u>
			LEAVE</u>
			</div>
			<div class='left_text'>&nbsp;</div>
            <div class='left_clear'></div>
			<div class='left_header'>
			Entitlement:
			</div>
			<?php if($leaveAccountType == "Fulltime") {?>
				<div class='left_text'>
				<?php echo $entitledTo?> days
				</div>
				<div class='left_clear'></div>
				<div class='left_header'>
				Additional:
				</div>
				<div class='left_text'>
				<?php echo $additional?> days
				</div>
				<div class='left_clear'></div>
				<div class='left_header'>
				Allocated:
				</div>
				<div class='left_text'>
				<?php echo $used?> day(s)
				</div>
				<?php if($nextYrLeave > 0) { ?>
					<div class='left_clear'></div>
					<div class='left_header'>
					Next year:
					</div>
					<div class='left_text'>
					<?php echo $nextYrLeave?> day(s)
					</div>
				<?php } ?>
				<div class='left_clear'></div>
				<div class='left_header'>
				Remaining:
				</div>
				<div class='left_text'>
				<?php $remaining= $entitledTo+$additional-$used;
				echo $remaining;
				?> day(s)
				</div>
			<?php }else{?>
				<div class='left_text'>
				<?php echo $entitledTo?> hrs
				</div>
				<div class='left_clear'></div>
				<div class='left_header'>
				Additional:
				</div>
				<div class='left_text'>
				<?php echo $additional?> hrs
				</div>
				<div class='left_clear'></div>
			
				<div class='left_header'>
				Used Hours:
				</div>
				<div class='left_text'>
				<?php 
				echo $hoursTaken;
				?> hrs
				</div>
				<div class='left_header'>
				Remaining:
				</div>
				<div class='left_text'>
				<?php 
				$remaining = $entitledTo+$additional-$hoursTaken;
				echo $remaining;
				?> hrs
				</div>
		<?php }?>
		</div></div>
		<?php

	}else{
		if($_GET["func"]=="confirmAcc"){
			$formArr = array(array(type=>"intro", formtitle=>"Access User Account", formintro=>"Enter your email address and a password for the flexible working system"), 
			array(type=>"form", form=>array(action=>"index.php?func=accessAcc&passcode=".$_GET["passcode"],method=>"post")),
			array(prompt=>"Email Address", type=>"text", name=>"email_address", length=>20, value=>"", clear=>true), 
			array(prompt=>"Password", type=>"password", name=>"password", length=>20, value=>"", clear=>true),
			array(prompt=>"Confirm Password", type=>"password", name=>"compare", field=>"password", message=>"Your passwords do not match. Please retype!", length=>20, value=>"", clear=>true),
			array(type=>"submit", buttontext=>"Access account", clear=>true), 
			array(type=>'endform'));
			$form = new forms;
			echo $form->create_form($formArr);
		}elseif($_GET["func"]=="accessAcc") {	
			//check the passcode and the email address and the passwords are the same
			if($_POST["password"] == $_POST["compare"]) {
				// check passcode and email address
				if($_GET["passcode"]==MD5(SALT.$_POST["email_address"])) { //everything confirmed
					// now need to locate user account and add password to security table and update user table
					$user = dl::select("flexi_user", "user_email='".addslashes($_POST["email_address"])."'");
					$user_id=$user[0]["user_id"];
					$security = dl::insert("flexi_security", array(security_password=>MD5(SALT.$_POST["password"]), user_id=>$user_id));
					$sec = dl::select("flexi_security", "user_id=".$user_id);
					$sec_id = $sec[0]["security_id"];
					dl::update("flexi_user", array(user_security=>$sec_id), "user_id=".$user_id);
					//all updates completed might as well sign the user into the system.
					$password=$_POST["password"];
					$email = $_POST["email_address"];
					$check_email = dl::select("flexi_user", "user_email='".addslashes($email)."'");
					$check_timesheet = dl::select("flexi_timesheet", "user_id=".$check_email[0]["user_id"]);
					if(!empty($check_email)) {
						foreach($check_email as $ce) {
							//create array listing all user settings when login confirmed transfer to a session variable
							$user_settings=array(secId=>$ce["user_security"],userId=>$ce["user_id"],permissionId=>$ce["user_permission_id"],email=>$ce["user_email"],name=>$ce["user_name"],al=>$ce["user_al_template"],timeTemplate=>$ce["user_time_template"],flexiTemplate=>$ce["user_flexi_template"],timesheet=>$check_timesheet[0]["timesheet_id"]);
						}
						$check_password = dl::select("flexi_security", "security_id=".$user_settings['secId']);
						if(!empty($check_password)) {
							foreach($check_password as $cp) {
								if($cp["security_password"] == MD5(SALT.$password)) {
									//credentials confirmed get all other details
									$_SESSION["loggedin"]=true;
									$_SESSION["userSettings"]=$user_settings;
									//connect to PERMISSION table and retrieve permission settings
									$getPermissions = dl::select("flexi_permission_template", $_SESSION["userSettings"]["permissionId"]."=permission_template_name_id");
									if(!empty($getPermissions)) {
										foreach($getPermissions as $gp) {
											//add permissions to session array
											$_SESSION["userPermissions"]=array(user=>$gp["permission_user"],templates=>$gp["permission_templates"],teams=>$gp["permission_teams"],team_events=>$gp["permission_team_events"],team_authorise=>$gp["permission_team_authorise"],events=>$gp["permission_events"],event_types=>$gp["permission_event_types"],add_time=>$gp["permission_add_time"],edit_time=>$gp["permission_edit_time"],edit_locked_time=>$gp["permission_edit_locked_time"],add_global=>$gp["permission_add_global"], override_delete=>$gp["permission_override_delete"], edit_flexipot=>$gp["permission_edit_flexipot"], view_user_leave=>$gp["permission_view_leave"], user_messaging=>$gp["permission_messaging"], view_user_timesheet=>$gp["permission_view_timesheet"],view_user_override=>$gp["permission_view_override"], view_reports=>$gp["permission_view_reports"], year_end=>$gp["permission_year_end"]);
										}
									}
									$subject = $email_1_subject;
									$bodyText = $email_1_content;
									$bodyText = str_replace("%%whoto%%", $user[0]["user_name"], $bodyText);
									$recipients = array($email);	
									//send the email confirmation
									$recips=explode(", ", $recipients);
									$m = new Mail();
									$m->From( "FWS <fws@ncl.ac.uk>" ); 
									$m->autoCheck(false);
									$m->To( $recipients );
									$m->Subject( $subject );
									$m->Body( $bodyText );
									$m->Priority(3);
									$m->Send();
								}
							}
						}
						echo "<SCRIPT language='javascript'>alert(\"Thank you for entering your user credentials. You have been automatically logged in.\"); redirect('index.php?choice=View&subchoice=timesheet')</SCRIPT>" ;
					}
				}else{
					//message and return to access account
					echo "<SCRIPT language='javascript'>alert(\"Sorry you have an incorrect passcode!!!\"); redirect('index.php?func=login')</SCRIPT>" ;
				}
			}else{ // message and return to access account
				echo "<SCRIPT language='javascript'>alert(\"Passwords do not match, please retype them!\"); redirect('index.php?func=confirmAcc&passcode=".$_GET["passcode"]."')</SCRIPT>" ;
			}
		}
	}
	?>

</div>
<?php
if($_SESSION["showMths"]== 4) {
		echo "<div class='working_space' style='height:925px;'>";
	}elseif($_SESSION["showMths"] == 6) {
		echo "<div class='working_space' style='height:1000px;'>";
	}else{
		echo "<div class='working_space'>";
	}
//has the user logged in
		if($_SESSION["loggedin"]) { 
			$button_choice=$_GET['choice'];
			if($button_choice=="") {
				$button_choice="View";
				$submenu_choice="timesheet";
			}
    		show_topMenu($button_choice);
			show_subMenu($button_choice);
			//update the templates period if over the period end date
			$flexi_template = dl::select("flexi_template");
			foreach($flexi_template as $ft) {
				if(date("Y-m-d") > date("Y-m-d", strtotime($ft["end_period"]))) { //todays date is after the period end so need to update the period
					$endPeriod = date("Y-m-d", strtotime($ft["end_period"]));
					$period_start_date = add_date(strtotime($ft["end_period"]),1);
					$period_end_date = add_date(strtotime($ft["end_period"]),28);
					//now update the templates
					dl::update("flexi_template", array(start_period=>date("Y-m-d", strtotime($period_start_date)), end_period=>date("Y-m-d", strtotime($period_end_date))), "template_id=".$ft["template_id"]);
					//end of period so need to move over the flexi to the pot
					$sql = "select * from flexi_user as u join 
					flexi_timesheet as t on (u.user_id=t.user_id) 
					left outer join flexi_deleted as d on (u.user_id=d.user_id)					
					where user_flexi_template=".$ft["template_id"]." and date_deleted IS NULL";
					$users = dl::getQuery($sql);
					foreach($users as $user) {
						//need to get the managers' email address
						//get approvers email and name
						//find the team the user is a local member of
						$sql = "select * from flexi_team as ft 
						join flexi_team_user as ftu on (ftu.team_id=ft.team_id)
						join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id) 
						where ftu.user_id=".$user["user_id"];
						$teams = dl::getQuery($sql);
						$team_id = $teams[0]["team_id"];
						//now need to see if this user is a manager/approver within this team
						//this determines if the manager in this team receives the approval request or the none local team member approver/manager
						$sql = "select * from flexi_permission_template as fpt 
						join flexi_user as fu on (fu.user_permission_id=fpt.permission_template_name_id) 
						join flexi_team_user as ftu on (ftu.user_id=fu.user_id)
						left outer join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id)
						left outer join flexi_deleted as d on (fu.user_id=d.user_id) 
						join flexi_team as ft on (ft.team_id=ftu.team_id) 
						where ft.team_id = ".$team_id." and fpt.permission_team_authorise = 'true' and date_deleted IS NULL and tl.team_user_id IS NOT NULL";
						$localManager = dl::getQuery($sql);
						if($localManager["user_id"] == $user["user_id"]) { // this is a request from the local team manager so the request should go to the non-local manager	
							$sql = "select * from flexi_permission_template as fpt 
							join flexi_user as fu on (fu.user_permission_id=fpt.permission_template_name_id) 
							join flexi_team_user as ftu on (ftu.user_id=fu.user_id)
							left outer join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id)
							left outer join flexi_deleted as d on (fu.user_id=d.user_id) 
							join flexi_team as ft on (ft.team_id=ftu.team_id) 
							where ft.team_id = ".$team_id." and fpt.permission_team_authorise = 'true' and date_deleted IS NULL and tl.team_user_id IS NULL";
							$manager = dl::getQuery($sql);
							foreach($manager as $m) {
								//create an array of the managers who can approve the event
								$recipients[]=$m["user_email"];
							}
						}else{
							foreach($localManager as $lm) {
								//create an array of the managers who can approve the event
								$recipients[]=$lm["user_email"];
							}
						}
						$cf = dl::select("flexi_carried_forward_live", "timesheet_id=".$user["timesheet_id"]);
						if(!empty($cf)) { //need to move the flexi
							if($cf[0]["current_flexi"] > $ft["max_deficit"]) { 
								//need to get the managers' email address
								//get approvers email and name
								// dl::$debug=true;
								//find the team the user is a local member of
								$sql = "select * from flexi_team as ft 
								join flexi_team_user as ftu on (ftu.team_id=ft.team_id)
								join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id) 
								where ftu.user_id=".$user["user_id"];
								$teams = dl::getQuery($sql);
								$team_id = $teams[0]["team_id"];
								//now need to see if this user is a manager/approver within this team
								//this determines if the manager in this team receives the approval request or the none local team member approver/manager
								$sql = "select * from flexi_permission_template as fpt 
								join flexi_user as fu on (fu.user_permission_id=fpt.permission_template_name_id) 
								join flexi_team_user as ftu on (ftu.user_id=fu.user_id)
								left outer join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id)
								left outer join flexi_deleted as d on (fu.user_id=d.user_id) 
								join flexi_team as ft on (ft.team_id=ftu.team_id) 
								where ft.team_id = ".$team_id." and fpt.permission_team_authorise = 'true' and date_deleted IS NULL and tl.team_user_id IS NOT NULL";
								$localManager = dl::getQuery($sql);
								if($localManager["user_id"] == $user["user_id"]) { // this is a request from the local team manager so the request should go to the non-local manager	
									$sql = "select * from flexi_permission_template as fpt 
									join flexi_user as fu on (fu.user_permission_id=fpt.permission_template_name_id) 
									join flexi_team_user as ftu on (ftu.user_id=fu.user_id)
									left outer join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id)
									left outer join flexi_deleted as d on (fu.user_id=d.user_id) 
									join flexi_team as ft on (ft.team_id=ftu.team_id) 
									where ft.team_id = ".$team_id." and fpt.permission_team_authorise = 'true' and date_deleted IS NULL and tl.team_user_id IS NULL";
									$manager = dl::getQuery($sql);
									foreach($manager as $m) {
										//create an array of the managers who can approve the event
										$recipients[]=$m["user_email"];
									}
								}else{
									foreach($localManager as $lm) {
										//create an array of the managers who can approve the event
										$recipients[]=$lm["user_email"];
									}
								}
								//the current flexi calculation
								$currentFlexi = $cf[0]["current_flexi"];
								$timeCarried = $currentFlexi;
								//check the max surplus
								$max_surplus = $ft["max_surplus"];
								//now lets create the flexitime record for this period
								if($timeCarried > $max_surplus) {
									$timeSaved = $max_surplus;
								}else{
									$timeSaved = $timeCarried;
								}
								//check if $timesaved is positive or negative
								if($timeSaved>0) {
									$carriedSign = "+";
								}else{
									$carriedSign = "-";
									$timeSaved = $timeSaved * -1;
								}
								$fieldList = array("timesheet_id", "sign", "flexitime","period_date");
								$valuesArr = array($user["timesheet_id"], $cf[0]["sign"], $cf[0]["flexi_time_carried_forward"], date("Y-m-d", strtotime($endPeriod)));
								$writeArr = array_combine($fieldList,$valuesArr);
								dl::insert("flexi_carried_forward",$writeArr); //this has recorded the flexitime for the user for the current period. Will be used when examining the timesheet in each period.
								if($timeCarried > $max_surplus) {
									$lostTime = $timeCarried - $max_surplus;
									$lostTime = $lostTime * 60 * 60;
									//send email to user notifying of lost time copy manager
									$subject = $email_2_subject;
									$bodyText = $email_2_content;
									$bodyText = str_replace("%%whoto%%", $user["user_name"], $bodyText);
									$bodyText = str_replace("%%INSERT%%", date("H:i", $lostTime), $bodyText);
									$recipients = array($user["user_email"]);	
									//send the email confirmation
									$recips=explode(", ", $recipients);
									$m = new Mail();
									$m->From( "FWS <fws@ncl.ac.uk>" ); // the first address in the recipients list is used as the from email contact and will receive emails in response to the registration request.
									$m->autoCheck(false);
									$m->To( $recipients );
									$m->Subject( $subject );
									$m->Body( $bodyText );
									$m->Cc($authEmail);
									$m->Priority(3);
									$m->Send();
									//update the flexipot
									dl::update("flexi_carried_forward_live", array(sign=>$carriedSign, flexi_time_carried_forward=>$timeSaved), "timesheet_id=".$user["timesheet_id"]);
								}else{
									dl::update("flexi_carried_forward_live", array(sign=>$carriedSign, flexi_time_carried_forward=>$timeSaved), "timesheet_id=".$user["timesheet_id"]);
								}
							}else{
								//the current flexi calculation
								$currentFlexi = $cf[0]["current_flexi"];
								$timeCarried = $currentFlexi;
								//check the max surplus
								$max_surplus = $ft["max_surplus"];
								//now lets create the flexitime record for this period
								if($timeCarried > $max_surplus) {
									$timeSaved = $max_surplus;
								}else{
									$timeSaved = $timeCarried;
								}
								//check if $timesaved is positive or negative
								if($timeSaved>0) {
									$carriedSign = "+";
								}else{
									$carriedSign = "-";
									$timeSaved = $timeSaved * -1;
								}
								$fieldList = array("timesheet_id", "sign", "flexitime","period_date");
								$valuesArr = array($user["timesheet_id"], $carriedSign, $timeSaved, date("Y-m-d", strtotime($endPeriod)));
								$writeArr = array_combine($fieldList,$valuesArr);
								dl::insert("flexi_carried_forward",$writeArr); //this has recorded the flexitime for the user for the current period. Will be used when examining the timesheet in each period.
								//send email to manager copy superAdmin
								$subject = $email_3_subject;
								$bodyText = $email_3_content;
								$bodyText = str_replace("%%whoto%%", $user["user_name"], $bodyText);
								$recipients = array($user["user_email"]);	
								//send the email confirmation
								$recips=explode(", ", $recipients);
								$m = new Mail();
								$m->From( "FWS <fws@ncl.ac.uk>" ); // the first address in the recipients list is used as the from email contact and will receive emails in response to the registration request.
								$m->autoCheck(false);
								$m->To( $recipients );
								$m->Subject( $subject );
								$m->Body( $bodyText );
								$m->Cc($authEmail);
								$m->Priority(3);
								$m->Send();
								//update the flexipot
								dl::update("flexi_carried_forward_live", array(sign=>$carriedSign, flexi_time_carried_forward=>$timeSaved), "timesheet_id=".$user["timesheet_id"]);
							}
						}
					}
					//lastly lets check if any deleted users should be completly removed from the system
					$deletions = check_for_deletions();
					if(!empty($deletions)) {
						//delete all of the table entries for the id's in the $deletions array
						foreach($deletions as $del) {
							/*delete from 	flexi_user
											flexi_team_user
											flexi_security
											flexi_timesheet */
							dl::delete("flexi_user", "user_id=".$del);
							dl::delete("flexi_team_user", "user_id=".$del);
							dl::delete("flexi_security", "user_id=".$del);
							dl::delete("flexi_timesheet", "user_id=".$del);
						}
					}
				}
			}
			if($_GET["func"] == "login" ) {
				echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=timesheet')</SCRIPT>" ;
			}
			if($_GET["choice"] == "Add") {
				if($_GET["subchoice"] == "addtime") {
					echo "<div class='left_body'>";
					add_time("Add Time", "Select the Time then add the start and end times for the chosen date.");
					echo "</div>";
				}
				if($_GET["subchoice"] == "addevent") {
					echo "<div class='left_body'>";
					add_event("Add Event", "Select the Event Type then add the start and end times for the chosen date.", $_GET["userid"]);
					echo "</div>";
				}
				if($_GET["subchoice"] == "addeventtype") {
					echo "<div class='left_body'>";
					add_event_type();
					echo "</div>";
				}
				if($_GET["subchoice"] == "addeventduration") {
					echo "<div class='left_body'>";
					add_event_duration();
					echo "</div>";
				}
				if($_GET["subchoice"] == "addteam") {
					echo "<div class='left_body'>";
					add_team();
					echo "</div>";
				}
				if($_GET["subchoice"] == "adduser") {
					echo "<div class='left_body'>";
					add_user("ADD A USER", "Add a new user, link the user to entered templates and the user will be emailed after setup completes");
					echo "</div>";
				}
			}elseif($_GET["choice"] == "View") {
				if($_GET["subchoice"] == "leaveDates") {
					echo "<div class='left_body'>";
					leave_dates($_SESSION["userSettings"]["userId"]);
					echo "</div>";
				}
				if($_GET["subchoice"] == "timesheet") {
					echo "<div class='left_body'>";
					view_timesheet($_SESSION["userSettings"]["userId"]);
					echo "</div>";
				}
				if($_GET["subchoice"] == "EventTypes") {
					echo "<div class='left_body'>";
					view_event_types();
					echo "</div>";
				}
				if($_GET["subchoice"] == "EventDurations") {
					echo "<div class='left_body'>";
					view_event_durations();
					echo "</div>";
				}
				if($_GET["subchoice"] == "Teams") {
					echo "<div class='left_body'>";
					view_teams();
					echo "</div>";
				}
			}elseif($_GET["choice"] == "Edit") {
				if($_GET["subchoice"] == "editeventtype") {
					select_event_types();	
				}
				if($_GET["subchoice"] == "editteam") {
					select_teams();	
				}
				if($_GET["subchoice"] == "selecteventdurations") {
					select_event_durations();	
				}
				if($_GET["subchoice"] == "edituser") {
					if(empty($_GET["page"])) {
						$page=0;
					}else{
						$page=$_GET["page"];
					}
					view_user($page);	
				}
				if($_GET["subchoice"] == "editevent" or $_GET["subchoice"]== "edittime") {
					view_events();	
				}		
			}elseif($_GET["choice"] == "Templates") {
				if($_GET["subchoice"] == "permissiontemplate") {
					echo "<div class='left_body'>";
					add_permissions();
					echo "</div>";
				}
				if($_GET["subchoice"] == "timetemplate") {
					echo "<div class='left_body'>";
					add_time_template();
					echo "</div>";
				}
				if($_GET["subchoice"] == "leavetemplate") {
					echo "<div class='left_body'>";
					add_leave_template();
					echo "</div>";
				}
				if($_GET["subchoice"] == "flexitemplate") {
					echo "<div class='left_body'>";
					add_flexi_template();
					echo "</div>";
				}
				if($_GET["subchoice"] == "flexidaystemplate") {
					echo "<div class='left_body'>";
					add_flexi_days_template();
					echo "</div>";
				}
				if($_GET["subchoice"] == "lockApplication") {
					echo "<div class='left_body'>";
					lock_application();
					echo "</div>";
				}
			}elseif($_GET["choice"] == "Reports") {
				if($_GET["subchoice"] == "sicknessReport") {
					echo "<div class='left_body'>";
					echo "<SCRIPT>redirect('reports.php?func=Sickness');</SCRIPT>" ;
					echo "</div>";
				}
				if($_GET["subchoice"] == "leaveManagementReport") {
					echo "<div class='left_body'>";
					echo "<SCRIPT>redirect('reports.php?func=leaveManagement');</SCRIPT>" ;
					echo "</div>";
				}
				if($_GET["subchoice"] == "leaveResetReport") {
					echo "<div class='left_body'>";
					echo "<SCRIPT>redirect('reports.php?func=leaveReset');</SCRIPT>" ;
					echo "</div>";
				}
			}elseif($_GET["choice"] == "Messaging") {
				if($_GET["subchoice"] == "sendMessage") {
					echo "<div class='left_body'>";
					send_message();
					echo "</div>";
				}
			}

			if($_GET["func"]=="logoff") {
				clear_login();
			}
			if($_GET["func"]=="sendMessage") {
				deliver_message(addslashes($_POST["subject"]), addslashes($_POST["message"]));
			}
			if($_GET["func"] == "reminder" ) {
				add_reminder();
			}
			if($_GET["func"]=="set_leave_count") {
				set_leave_count();
			}
			if($_GET["func"]=="view_users_templates") {
				view_users_templates();
			}
			if($_GET["func"]=="showCFNotes") {
				show_notes($_GET["timesheet"]);
			}
			if($_GET["func"]=="viewteam") {
				view_team_members();
			}
			if($_GET["func"]=="approveleave") {
				approve_leave();
			}
			if($_GET["func"]=="confirmapproval") {
				confirm_approval();
			}
			if($_GET["func"]=="yourrequests") {
				view_your_requests();
			}
			if($_GET["func"]=="showwk") {
				show_week();
			}
			if($_GET["func"]=="saveuser") {
				save_user();
			}
			if($_GET["func"]=="savetime") {
				savetime($_POST);
			}
			if($_GET["func"]=="saveteam") {
				save_team($_POST);
			}
			if($_GET["func"] == "savepermissions") {
				save_permissions();
			}
			if($_GET["func"] == "saveeventtype") {
				save_event_type();
			}
			if($_GET["func"] == "saveeventduration") {
				save_event_duration();
			}
			if($_GET["func"] == "saveevent") {
				save_event($_GET["user"]);
			}
			if($_GET["func"] == "deleteevent") {
				delete_events($_GET["id"],$_GET["confirmation"], $_GET["deltype"]);
			}
			if($_GET["func"] == "editevent") {
				edit_event($_GET["id"]);
			}
			if($_GET["func"] == "editeventtype") {
				edit_event_types();
			}
			if($_GET["func"] == "editeventdurations") {
				edit_event_durations();
			}
			if($_GET["func"] == "deleteeventtype") {
				delete_event_type($_GET["id"]);
			}
			if($_GET["func"] == "deleteeventdurations") {
				delete_event_durations($_GET["id"]);
			}
			if($_GET["func"] == "saveeventtypeedit") {
				save_event_type_edit();
			}
			if($_GET["func"] == "saveeventdurationedit") {
				save_event_duration_edit($_GET["id"]);
			}
			if($_GET["func"] == "saveeventtypeedit") {
				save_event_type_edit();
			}
			if($_GET["func"] == "editteam") {
				edit_teams();
			}
			if($_GET["func"] == "saveteamedit") {
				save_team_edit();
			}
			if($_GET["func"] == "deleteteam") {
				delete_teams();
			}
			if($_GET["func"] == "showuserleave") {
					leave_dates($_GET["userid"], $_GET["year"]);
			}
			if($_GET["func"] == "viewuserstimesheet") {
				view_timesheet($_GET["userid"]);
			}
			if($_GET["func"] == "previousperiod") {
				view_timesheet($_GET["userid"],$_GET["start"],$_GET["end"]);
			}
			if($_GET["func"] == "nextperiod") {
				view_timesheet($_GET["userid"],$_GET["start"],$_GET["end"]);
			}
			if($_GET["func"] == "adduserevent") {
				echo "<div class='left_body'>";
				add_event("Add user Event", "Select the Event Type then add the start and end times for the chosen date.<BR>This will add an event to another users timesheet", $_GET["userid"]);
				echo "</div>";
			}
			// Templates
			if($_GET["func"] == "savetimetemplate") {
				save_time_template();
			}
			if($_GET["func"] == "savetimetemplateedit") {
				save_time_template_edit();
			}
			if($_GET["func"] == "saveleavetemplate") {
				save_leave_template();
			}
			if($_GET["func"] == "saveflexitemplate") {
				save_flexi_template();
			}
			if($_GET["func"] == "saveflexitemplateedit") {
				save_flexi_template_edit();
			}
			if($_GET["func"] == "saveflexidaystemplate") {
				save_flexi_days_template();
			}
			if($_GET["func"] == "edittimetemplate") {
				edit_time_template();
			}
			if($_GET["func"] == "deletetimetemplate") {
				delete_time_template($_GET["id"]);
			}
			if($_GET["func"] == "editflexitemplate") {
				edit_flexi_template();
			}
			if($_GET["func"] == "deleteflexitemplate") {
				delete_flexi_template($_GET["id"]);
			}
			if($_GET["func"] == "editdaystemplate") {
				edit_flexi_days_template();
			}
			if($_GET["func"] == "saveflexidaystemplateedit") {
				save_flexi_days_template_edit();
			}
			if($_GET["func"] == "deleteflexidaystemplate") {
				delete_flexi_days_template($_GET["id"]);
			}
			if($_GET["func"] == "editleavetemplate") {
				edit_leave_template();
			}
			if($_GET["func"] == "saveleavetemplateedit") {
				save_leave_template_edit();
			}
			if($_GET["func"] == "deleteleavetemplate") {
				delete_leave_template($_GET["id"]);
			}
			if($_GET["func"] == "editpermissiontemplate") {
				edit_permissions();
			}
			if($_GET["func"] == "savepermissiontemplateedit") {
				save_permissions_edit();
			}
			if($_GET["func"] == "deletepermissiontemplate") {
				delete_permissions($_GET["id"]);
			}
			if($_GET["func"] == "edituserevents") {
				view_events($_GET["userid"],$_GET["page"]);
			}
			if($_GET["func"] == "edituser") {
				edit_user();
			}
			if($_GET["func"] == "deleteuser") {
				delete_user($_GET["id"]);
			}
			if($_GET["func"] == "saveuseredit") {
				save_user_edit();
			} 
			if($_GET["func"] == "editflexipot") {
				flexi_pot_edit($_GET["timesheet"]);
			}
			if($_GET["func"] == "saveflexipot") {
				flexi_pot_save($_GET["timesheet"]);
			} 
			if($_GET["func"] == "correctGlobals") {
				correct_globals();
			}
			if($_GET["func"] == "addAdditional") {
				additional_leave();
			}
			if($_GET["func"] == "addLeave") {
				addLeave($_GET["timesheet_id"]);
			}
			if($_GET["func"] == "noAdd") {
				noAdd();
			}
			
	}else{ //show main screen
			echo "<div class='main_header'>CRP Flexible Working Application</div>";
			echo "<div class='main_left'><img src='inc/css/images/flexitimeJuggler.gif'></div><div class='main_right'>";
			echo "<p>Welcome to the CRP Flexible Working Application</p>";
			echo "Your ID should have already been created, so type your email and password to enter the CRP Flexible Working system.";
			echo "If your email address is not recognised please contact ";
			echo "<a href='mailto:mandy.jarvis@ncl.ac.uk'>Mandy Jarvis</a> or CRP IT to add your email address to the system.</div>";
	}?>
	<script>
	$(document).ready(function(){
		checkWidth();
	})
	
function checkWidth(){
	var width = $(window).width();
	$.post(
		"ajax.php",
		{ 
			func: 'check_width',
			width: width
		},
		function (data)
			{
				var json = $.parseJSON(data);
			}
	);
	setTimeout(checkWidth, 5000);
}
		
</script>
</div>
</body>
</html>
