<?php
function additional_leave() {
	if( empty($_SESSION["allUsers"] ) ) {
		$sql = "select * from flexi_user as u 
				join flexi_timesheet as ft on (u.user_id=ft.user_id) 
				join flexi_carried_forward_live as fl on (fl.timesheet_id=ft.timesheet_id)
				left outer join flexi_deleted as d on (u.user_id = d.user_id) 
				where date_deleted is NULL";
		$allUsers = dl::getQuery($sql);
		//print_r($allUsers);
		$user = array_shift($allUsers);
		$_SESSION["allUsers"] = $allUsers;
	}else{
		$user = array_shift($_SESSION["allUsers"]);
	}
	?>
	<script type="text/javascript">
		<!--

		var answer = confirm ("Do you want to add an additional Leave? \n\n Username = <?php echo $user["user_name"] ?>.")
		if (answer)
			redirect ("index.php?func=addLeave&timesheet_id=<?php echo $user["timesheet_id"]?>");
		else
			redirect ("index.php?func=noAdd");
		// -->
	</script>
    <?php
    die();
}

function addLeave($timesheet) {
	echo "Adding...123";
	dl::$debug=true;
	$addLeave = dl::select("flexi_carried_forward_live", "timesheet_id = ".$timesheet);
	print_r($addLeave);
	echo "<BR><BR>";
	$leaveVal = $addLeave[0]["additional_leave"];
	$leaveVal += 1;
	dl::update("flexi_carried_forward_live", array("additional_leave"=>$leaveVal), "timesheet_id = ".$timesheet);
	dl::$debug=false;
	additional_leave();		
}

function noAdd() {
	echo "No Add";
	additional_leave();	
}

function correct_globals() {
	dl::$debug=true;
	//event_type of shutdown is 17
	//event type of Bank Holiday is 5
	$shutdown =	dl::select("flexi_event", "event_type_id=17", "timesheet_id");
	foreach($shutdown as $sd) {
		$sql = "select * from flexi_user as u 
		join flexi_timesheet as ts on (u.user_id=ts.user_id)
		where timesheet_id = ".$sd["timesheet_id"];
		$user = dl::getQuery($sql);
		if( ! empty( $user ) ) {
			$sql = "select * from flexi_user as u 
			join flexi_template_name as tn on (u.user_flexi_template = tn.flexi_template_name_id)
			join flexi_template_days as td on (tn.flexi_template_name_id = td.template_name_id)
			join flexi_template_days_settings as tds on (td.flexi_template_days_id = tds.template_days_id)
			where u.user_id = ".$user[0]["user_id"];
			$duration = dl::getQuery($sql);
			$fullDay = $duration[0]["normal_day_duration"]; //******* Not used anymore *********
			$endTime = $sd["event_startdate_time"];
			$time = substr(date("Y-m-d H:i:s", strtotime($endTime) + strtotime($fullDay)),11,8);
			if( $time <> substr($sd["event_enddate_time"],11,8) ) {
				echo " do summat ".$user[0]["user_name"];
				$left = substr($sd["event_enddate_time"],0,10);
				echo $left."<BR>";
				$replace = $left." ".$time;
				echo $replace."<BR>";
				dl::update("flexi_event", array(event_enddate_time=>$replace), "event_id=".$sd["event_id"]." LIMIT 1");
			}
		}
	}
	die();
}

function set_leave_count() {
	$sql = "select * from flexi_user as u left outer join flexi_deleted as d on (d.user_id=u.user_id)
		where date_deleted is NULL order by user_name";
	$users = dl::getQuery( $sql );
	$userNames[] = "";
	foreach($users as $user) {
		$userNames[] = $user["user_name"];
	}
	echo "<div class='timesheet_workspace' style='min-height: 40em;'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Annual Leave Count", "formintro"=>"Setup the annual leave count for all users"), 	
			array("prompt"=>"Users", "type"=>"selection", "name"=>"username", "listarr"=>$userNames, "selected"=>"", "value"=>"", "clear"=>true));
			$form = new forms;
			$form->create_form($formArr);
	echo "<div id='leave_view'  style=' width: 80em; margin: 0.5em; height: 53em; background-color: #E5EBEF; border: 1px solid #888'>";
	echo "<div id='display_leave' style='padding:1em;'></div>";
	echo "</div>";
	?>
	<SCRIPT>
	$(document).ready(function(){
		$("#username").change(function() { 
			$('#display_leave').slideDown();
			var func = "show_user_leave";
			$.post(
				"ajax.php",
				{ func: func,
					user: { "name" : $("#username").val() }
				},
				function (data)
				{
					$('#display_leave').html(data);
			});
		});
	});
	
	</SCRIPT>
	<?php 
}

function view_users_templates() {
	$sql = "select * from flexi_user as u left outer join flexi_deleted as d on (d.user_id=u.user_id)
		where date_deleted is NULL order by user_name";
	$users = dl::getQuery( $sql );
	$userNames[] = "";
	echo "<div class='timesheet_workspace' style='min-height: 40em;'>";
	echo "<div id='leave_view'  style='overflow: scroll; margin: 0.5em; height: 55em; background-color: #E5EBEF; border: 1px solid #888'>";
	echo "<div id='display_leave' style='padding:1em; margin-top: 4em;'>";
	echo "<div class='report-header'><div class='report-line'>Name</div><div class='report-line'>Flexi Template</div><div class='report-line'>Flexi Days Template</div></div>";
	foreach($users as $user) {
		$template_name_id = dl::select("flexi_template", "template_name_id = ".$user["user_flexi_template"]);
		$template_name = dl::select("flexi_template_name", "flexi_template_name_id = ".$template_name_id[0]["template_name_id"]);
		$template_days_name = dl::select("flexi_template_days", "template_name_id = ".$template_name[0]["flexi_template_name_id"]);
		echo "<div class='report-line'>".$user["user_name"]."</div><div class='report-line'>".$template_name[0]["description"]."</div><div class='report-line'>".$template_days_name[0]["template_days_name"]."</div><BR><BR>";
	}
	
	echo "</div></div>";
}

function check_permission($value) {
	switch($value) {
		case "User":
			if($_SESSION["userPermissions"]["user"]== "true") {
				return true;
			}
		break;
		case "Templates":
			if($_SESSION["userPermissions"]["templates"]== "true") {
				return true;
			}
		break;	
		case "Teams":
			if($_SESSION["userPermissions"]["teams"]== "true") {
				return true;
			}
		break;
		case "Team Events":
			if($_SESSION["userPermissions"]["team_events"]== "true") {
				return true;
			}else{
				return false;
			}
		break;
		case "Team Authorise":
			if($_SESSION["userPermissions"]["team_authorise"]== "true") {
				return true;
			}
		break;
		case "Events":
			if($_SESSION["userPermissions"]["events"]== "true") {
				return true;
			}
		break;
		case "Event Types":
			if($_SESSION["userPermissions"]["event_types"]== "true") {
				return true;
			}
		break;
		case "Add Time":
			if($_SESSION["userPermissions"]["add_time"]== "true") {
				return true;
			}
		break;
		case "Edit Time":
			if($_SESSION["userPermissions"]["edit_time"]== "true") {
				return true;
			}
		break;
		case "Edit Locked Time":
			if($_SESSION["userPermissions"]["edit_locked_time"]== "true") {
				return true;
			}
		break;
		case "Edit Flexipot":
			if($_SESSION["userPermissions"]["edit_flexipot"]== "true") {
				return true;
			}
		break;
		case "User Messaging":
			if($_SESSION["userPermissions"]["user_messaging"]== "true") {
				return true;
			}
		break;
		case "View User Leave":
			if($_SESSION["userPermissions"]["view_user_leave"]== "true") {
				return true;
			}
		break;
		case "View User Timesheet":
			if($_SESSION["userPermissions"]["view_user_timesheet"]== "true") {
				return true;
			}
		break;
		case "View Reports":
			if($_SESSION["userPermissions"]["view_reports"]== "true") {
				return true;
			}
		break;
		case "Year End":
			if($_SESSION["userPermissions"]["year_end"]== "true") {
				return true;
			}
		break;
	}
	return false;
}

function display_cal_headers() {
	$dayHeaders=array("Mo","Tu","We","Th","Fr","Sa","Su");
	foreach($dayHeaders as $dh){
		echo "<div class='calendar_day_name'>$dh</div>";
	}	
}

function display_cal_days($month, $firstDay, $year) {
	global $cal;
	$monthDays=array("dummy","31","28","31","30","31","30","31","31","30","31","30","31");
	$dayCount=1;
	if($month >12) {
		$month = $month - 12;
	}
	switch($firstDay){
		case "Monday":
			$startDay=1;
			break;
		case "Tuesday":
			$startDay=2;
			break;
		case "Wednesday":
			$startDay=3;
			break;
		case "Thursday":
			$startDay=4;
			break;
		case "Friday":
			$startDay=5;
			break;
		case "Saturday":
			$startDay=6;
			break;
		case "Sunday":
			$startDay=7;
	}
	$daysinMonth = $monthDays[$month];
	while($dayCount++ <> $startDay){
		echo "<div class='calendar_day'>&nbsp;</div>";
	}
	for($x=1; $x<=$daysinMonth; $x++) {
		if($x == $cal->get_todays_day() && $month == $cal->get_todays_month_num() && date("Y")==$cal->get_todays_year() ) {
			echo "<div class='calendar_today'><a href=\"index.php?choice=Add&subchoice=addevent&type=Working Day&userid=".$_SESSION["userSettings"]["userId"]."&date=".date("Y-m-d", mktime(0,0,0,$month, $x, $year))."\">$x</a></div>";
		}else{
			echo "<div class='calendar_day'><a href=\"index.php?choice=Add&subchoice=addevent&type=Working Day&userid=".$_SESSION["userSettings"]["userId"]."&date=".date("Y-m-d", mktime(0,0,0,$month, $x, $year))."\">$x</a></div>";
		}
	}
}

function display_calendar($numMonth, $month_offset=0) {
	global $cal;
	$numMonth=$numMonth/2;
	$thisMonth=$cal->get_todays_month($month_offset);
	$firstDay=$cal->get_first_day();
	$monthCount=$month_offset;
	$year=$cal->get_todays_year();
	$thisMonthNum = $cal->get_todays_month_num()+$month_offset;
	if($thisMonthNum > 12) {
		$year++;
		$_SESSION["Year"] = $year;
	}
	if($thisMonthNum <= 0) {
		$year--;
		$thisMonthNum = 13+$monthCount;
		$_SESSION["Year"] = $year;
	}
	$yearUpdated=false;
	for($x=1; $x<=$numMonth; $x++) { //loop through the number of months
		echo "<div class='calendar_holder'><div class='calendar_header'>".$cal->get_todays_month($monthCount)." ".$year."</div>";
		if($cal->get_todays_month($monthCount)=="December"){
			$year++;
			$yearUpdated=true;
			$_SESSION["Year"] = $year;
		}
		echo "<div class='calendar_header'>".$cal->get_todays_month($monthCount+1)." ".$year."</div></div>";
		echo "<div class='calendar_holder'>";
			echo "<div class='calendar_body'>";
				display_cal_headers();
				display_cal_days($thisMonthNum++, $cal->get_first_day($monthCount++), $year);
				if($thisMonthNum == 13  && $yearUpdated==false) { //if month goes over year end add 1 to the year counter
					$year++;
					$_SESSION["Year"]=$year;
				}
			echo "</div>";
			echo "<div class='calendar_body'>";
				display_cal_headers();
				display_cal_days($thisMonthNum++, $cal->get_first_day($monthCount++), $year);
				if($thisMonthNum == 13  && $yearUpdated==false) { //if month goes over year end add 1 to the year counter
					$year++;
					$_SESSION["Year"]=$year;
				}
			echo "</div>";	
		echo "</div>";
	}
	echo "<div class='calendar_view'><CENTER>&nbsp;Calendar View  | <a href='".$_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=".$_GET["subchoice"]."&showMths=2'><span class='calendar_pic'><img src='inc/images/2xiconCalendar.gif' border='0' /></span></a> | <a href='".$_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=".$_GET["subchoice"]."&showMths=4'><span class='calendar_pic'><img src='inc/images/4xiconCalendar.gif' border='0' /></span></a> | <a href='".$_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=".$_GET["subchoice"]."&showMths=6'><span class='calendar_pic'><img src='inc/images/6xiconCalendar.gif' border='0' /></span></a> |</CENTER></div>";
}

function show_topMenu($button_choice) {
	echo"<span class='button_link'><div class='top_menu_space'><div class='logoff'><a href='index.php?func=logoff'>Log off</a></div>";
	if($button_choice == "View") {
		echo "<div class='button_choice'>".show_menuLink($_SERVER['PHP_SELF']."?choice=View", 'View')."</a></div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Add", 'Add')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Edit", 'Edit')."</div>";
		if($_SESSION["userPermissions"]["templates"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Templates", 'Template')."</div>";
		}
		if($_SESSION["userPermissions"]["view_reports"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Reports", 'Report')."</div>";
		}
		if($_SESSION["userPermissions"]["user_messaging"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Messaging", 'Messaging')."</div>";
		}
	}elseif($button_choice=="Add") {
    	echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=View", 'View')."</div>";
		echo "<div class='button_choice'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Add", 'Add')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Edit", 'Edit')."</div>";
		if($_SESSION["userPermissions"]["templates"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Templates", 'Template')."</a></div>";
		}
		if($_SESSION["userPermissions"]["view_reports"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Reports", 'Report')."</div>";
		}
		if($_SESSION["userPermissions"]["user_messaging"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Messaging", 'Messaging')."</div>";
		}
	}elseif($button_choice=="Edit") {
        echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=View", 'View')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Add", 'Add')."</div>";
		echo "<div class='button_choice'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Edit", 'Edit')."</div>";
		if($_SESSION["userPermissions"]["templates"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Templates", 'Template')."</a></div>";
		}
		if($_SESSION["userPermissions"]["view_reports"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Reports", 'Report')."</div>";
		}
		if($_SESSION["userPermissions"]["user_messaging"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Messaging", 'Messaging')."</div>";
		}
	}elseif($button_choice=="Templates") {
        echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=View", 'View')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Add", 'Add')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Edit", 'Edit')."</div>";
		if($_SESSION["userPermissions"]["templates"]=="true") {
			echo "<div class='button_choice'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Templates", 'Template')."</div>";
		}
		if($_SESSION["userPermissions"]["view_reports"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Reports", 'Report')."</div>";
		}
		if($_SESSION["userPermissions"]["user_messaging"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Messaging", 'Messaging')."</div>";
		}
	}elseif($button_choice=="Reports") {
        echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=View", 'View')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Add", 'Add')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Edit", 'Edit')."</div>";
		if($_SESSION["userPermissions"]["templates"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Templates", 'Template')."</div>";
		}
		if($_SESSION["userPermissions"]["view_reports"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Reports", 'Report')."</div>";
		}
		if($_SESSION["userPermissions"]["user_messaging"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Messaging", 'Messaging')."</div>";
		}
	}elseif($button_choice=="Messaging") {
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=View", 'View')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Add", 'Add')."</div>";
		echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Edit", 'Edit')."</div>";

		if($_SESSION["userPermissions"]["templates"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Templates", 'Template')."</div>";
		}
		if($_SESSION["userPermissions"]["view_reports"]=="true") {
			echo "<div class='button_top'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Reports", 'Report')."</div>";
		}
		if($_SESSION["userPermissions"]["user_messaging"]=="true") {
			echo "<div class='button_choice'>".show_menuLink($_SERVER['PHP_SELF']."?choice=Messaging", 'Messaging')."</div>";
		}
	}
    echo "</div></span>";
}
function show_menuLink($link, $linkText) {
	return( "<a href='$link'>$linkText</a>");
}

function show_subMenu($button_choice) {
	echo "<div class='sub_menu_space'>";
    if($button_choice == "View") {
		echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=timesheet", 'Timesheet')."</div>";
		echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=leaveDates", 'Leave Dates')."</div>";
		echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=EventTypes", 'Event Types')."</div>";
		echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=EventDurations", 'Event Durations')."</div>";
		echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=Teams", 'Teams')."</div>";
		$view_icon = "<div class='show_icon'><img src='inc/images/search-icon.jpg' title='View' /></div>";
	}elseif($button_choice=="Add") {
		if($_SESSION["userPermissions"]["add_time"]=="true") {
    		echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=addtime", 'Time')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=addevent&type=Working Day", 'Event')."</div>";
		}
		if($_SESSION["userPermissions"]["teams"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=addteam", 'Team')."</div>";
		}
		if($_SESSION["userPermissions"]["user"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=adduser", 'User')."</div>";
		}
		if($_SESSION["userPermissions"]["event_types"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=addeventtype", 'Event Type')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=addeventduration", 'Event Duration')."</div>";
		}
		$view_icon = "<div class='show_icon'><img src='inc/images/zoom-in-icon.jpg' title='View' /></div>";
		
	}elseif($button_choice=="Edit") {
		if($_SESSION["userPermissions"]["edit_time"]=="true") {
        	echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=edittime", 'Time')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=editevent", 'Event')."</div>";
		}
		if($_SESSION["userPermissions"]["teams"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=editteam", 'Team')."</div>";
		}
		if($_SESSION["userPermissions"]["user"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=edituser", 'User')."</div>";
		}
		if($_SESSION["userPermissions"]["event_types"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=editeventtype", 'Event Type')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=selecteventdurations", 'Event Durations')."</div>";
		}
		$view_icon = "<div class='show_icon'><img src='inc/images/pen_paper_icon.jpg' title='View' /></div>";
	}elseif($button_choice=="Templates") {
		if($_SESSION["userPermissions"]["templates"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=timetemplate", 'Time Template')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=flexitemplate", 'Flexi Template')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=flexidaystemplate", 'Flexi Days')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=permissiontemplate", 'Permission Template')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=leavetemplate", 'Leave Template')."</div>";
		}
		if($_SESSION["userPermissions"]["lock_override"]) {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=lockApplication", 'Application Lock')."</div>";
		}
		$view_icon = "<div class='show_icon'><img src='inc/images/template.jpg' title='View' /></div>";
	}elseif($button_choice=="Reports") {
		if($_SESSION["userPermissions"]["view_reports"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=fullleaveReport", 'Teams Leave')."</div>";
		}
		if($_SESSION["userPermissions"]["year_end"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=sicknessReport", 'Sickness Analysis')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=leaveManagementReport", 'Leave Management')."</div>";
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=leaveResetReport", 'Leave Year End')."</div>";
		}
		$view_icon = "<div class='show_icon'><img src='inc/images/report.png' title='View' /></div>";
	}elseif($button_choice=="Messaging") {
		if($_SESSION["userPermissions"]["user_messaging"]=="true") {
			echo "<div class='sub_menu_button'>".show_menuLink($_SERVER['PHP_SELF']."?choice=".$_GET["choice"]."&subchoice=sendMessage", 'Send Message to All')."</div>";
		}
		$view_icon = "<div class='show_icon'><img src='inc/images/email_message.png' title='View' /></div>";
	}
    echo "</div>";
	if(isset($_GET["choice"]) and !isset($_GET["subchoice"])) {
		echo $view_icon;
	}
}

function lock_application() {
	$locked = dl::select("flexi_locked");
	echo "<div id='lock_dialog' style='display: none;' title='Lock the FWS Application'>";
	echo "Click the lock button to lock the application. This will disable the user login function.<BR /><BR />";
	if($locked[0]["locked"]=="false") {
		echo "<center><input type='checkbox' id='check' /><label for='check'>Lock</label></center>";
	}else{
		echo "<center><input type='checkbox' id='check' checked/><label for='check'>Unlock</label></center>";
	}
	echo "</div>";
	
	?>
	 <script>
	$(function() {
		$("#lock_dialog").dialog();
		$("#check").button();
		
		$("#check").click(function(){
			$.post(
				"ajax.php",
				{ func: "toggle_lock"
				},
				function(data) {
				var json = $.parseJSON(data);
				lock = json.lock;
				$("#check").attr("checked", lock);
				if(lock == "true") {
					$("#check").button('option', 'label', 'Unlock');
				}else{
					$("#check").button('option', 'label', 'Lock');
				}
			});
			
			
		});
		
	});
	</script>
	<?php
}

function send_message() {
	echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Send a message to all users", "formintro"=>"A form to send a message to all users"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=sendMessage","method"=>"post")),
			array("prompt"=>"Subject", "type"=>"text", "name"=>"subject", "length"=>50, "value"=>"", "clear"=>true),	
			array("prompt"=>"Message", "type"=>"textarea", "name"=>"message", rows=>15, cols=>90, "selected"=>"", "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Send Message", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
}

function deliver_message($subject,$message) {
	if(check_permission("User Messaging")) {

		$subject = stripslashes($subject);
		$message = stripslashes($message);
		$recipients = dl::select("flexi_user");
		foreach( $recipients as $recip ) {
			$deleted = dl::select("flexi_deleted", "user_id=".$recip["user_id"]);
			if(empty($deleted)) {
				$sendTo[] = $recip["user_email"];
			}
		}
		$m = new Mail();		
		//send the email confirmation
		$m->From( $_SESSION["userSettings"]["email"] ); 
		$m->autoCheck(false);
		$m->To( array("FWS@ncl.ac.uk") );
		$m->Subject( $subject );
		$m->Body( $message );
		$m->BCC($sendTo);
		$m->Priority(2);
		$m->Send();
		?>
		<SCRIPT language='javascript'>
		alert("MESSAGE SENT...\n\nYour message has been sent to all of the users of the system \n\n");
		redirect('index.php?choice=Messaging&subchoice=sendMessage');
        </SCRIPT>
		<?php
        die();
	}
}

function flexi_pot_edit($timesheetId) {
	if(check_permission("Edit Flexipot")) {
		if( empty( $_GET["endDate"] ) or strtotime($_GET["endDate"]) > strtotime(date("Y-m-d")) ) {
			//get the carried forward record
			$carried = dl::select("flexi_carried_forward_live", "timesheet_id=".$timesheetId);
			if(empty($carried)) { //no carried forward live record
				$fieldarr = array("timesheet_id", "flexi_time_carried_forward", "current_flexi", "additional_leave");
				$valuearr = array($timesheetId, 0, 0, 0);
				$save = array_combine($fieldarr, $valuearr);
				dl::insert("flexi_carried_forward_live", $save);
				$carried = dl::select("flexi_carried_forward_live", "timesheet_id=".$timesheetId);
			}	
			$sign = $carried[0]["sign"];
			$carriedOverTime = date("H:i", ($carried[0]["flexi_time_carried_forward"]*60*60));		
		}else{
			$carriedOver = dl::select("flexi_carried_forward", "timesheet_id=".$timesheetId." and period_date = '".$_GET["endDate"]."'");
			$carried = dl::select("flexi_carried_forward_live", "timesheet_id=".$timesheetId);
			if(! empty($carriedOver) ) {
				//the endperiod has been found so change the value in - flexi_carried_forward
				$carriedOverTime = date("H:i", ($carriedOver[0]["flexitime"]*60*60));
				$sign = $carriedOver[0]["sign"];
			}else{ //the endperiod has not been found so the change must be in flexi_carried_forward_live
				$carriedOverTime = date("H:i", ($carried[0]["flexi_time_carried_forward"]*60*60));
				$sign = $carried[0]["sign"];
			}
		}
		$sql = "select * from flexi_timesheet as t join flexi_user as u on (t.user_id=u.user_id) where timesheet_id = ".$timesheetId;
		$username = dl::getQuery($sql);
		//get annual leave entitlement and used leave
		$leave 						= new check_leave($username[0]["user_id"]);
		$leaveAccountType 	= $leave->getLeaveAccountType();
		$proRataTime 			= $leave->getProRataTime();
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Carried Forward Time", "formintro"=>"Listed here is the time that has been carried forward. You can edit the time here but also swap time accumulated for supplemental holidays"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveflexipot&timesheet=".$timesheetId."&endDate=".$_GET["endDate"],"method"=>"post")),	
			array("prompt"=>"User Name", "type"=>"label", "name"=>"usersCarriedOver", "length"=>30, "value"=>$username[0]["user_name"]." (".$leaveAccountType.")", "clear"=>true),
			array("prompt"=>"Positive/minus", "type"=>"selection", "name"=>"posneg", "listarr"=>array("+","-"), "selected"=>$sign, "value"=>"", "clear"=>true),
			array("prompt"=>"Carried over time", "type"=>"time", "name"=>"carried_over", "starttime"=>"0000", "endtime"=>"2300", "interval"=>1, "selected"=>$carriedOverTime, "clear"=>true),	
			array("prompt"=>"Additional Leave", "type"=>"label", "name"=>"additionalLeave", "length"=>10, "value"=>$carried[0]["additional_leave"]." (1 day = ".$proRataTime." hours)", "clear"=>true),
			array("prompt"=>"New Leave (+/-) Hours", "type"=>"text", "name"=>"newLeave", "length"=>10, "selected"=>"", "clear"=>true),
			array("prompt"=>"Note", "type"=>"textarea", "name"=>"note", rows=>8, cols=>50, "selected"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Edit", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
			echo "<div style='clear:both; padding:5px;'><a href='index.php?func=showCFNotes&timesheet=$timesheetId'>View</a> all notes for this timesheet</div>";
		echo "</div>";
		
	}
}

function add_reminder() {
	$ts = $_SESSION["userSettings"]["timesheet"];
	$reminder = dl::select("flexi_time_reminder", "timesheet_id = ". $ts);
	if(empty($reminder)) { //first entry for this user
		dl::insert("flexi_time_reminder", array(timesheet_id=>$ts, reminder=>$_POST["rem_time"]));
	}else{
		dl::update("flexi_time_reminder", array(timesheet_id=>$ts, reminder=>$_POST["rem_time"].":".$_POST["rem_time_mins"].":00"), "timesheet_id = ".$ts);
	}
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=timesheet');</SCRIPT>" ;
	die();
}

function flexi_pot_save($timesheetId) {

	if(check_permission("Edit Flexipot")) {
		//dl::$debug=true;
		if( empty( $_GET["endDate"] ) ) {
			$carriedFlexi = dl::select("flexi_carried_forward_live", "timesheet_id=".$timesheetId);
			if(!empty($_POST["newLeave"])) {
				if($_POST["newLeave"] > 0 ) { 
					$addLeave = $carriedFlexi[0]["additional_leave"] + $_POST["newLeave"];
					$remainingLeave = number_format($carriedFlexi[0]["flexi_time_carried_forward"] - ($time/60/60),2);
				}else{ //Just change the leave
					$addLeave = $carriedFlexi[0]["additional_leave"] + $_POST["newLeave"];
				}
			}else{ //get the additional leave already allocated so as not to overwrite it.
				$addLeave = $carriedFlexi[0]["additional_leave"];
			}
			if(empty($remainingLeave)) {
				$remainingLeave = $_POST["carried_over"]*60*60 + $_POST["carried_over_mins"]*60;
				$remainingLeave = number_format($remainingLeave/60/60,2);
			}
			$fieldList = array("sign","flexi_time_carried_forward", "additional_leave");
			$valueArr = array($_POST["posneg"], $remainingLeave, $addLeave);
			$writeArr = array_combine($fieldList, $valueArr);
			dl::update("flexi_carried_forward_live", $writeArr, "timesheet_id=".$timesheetId);
			if($_POST["note"]!="") { //add the note to the carried forward note table
				dl::insert("flexi_carried_forward_notes",array(timesheet_id=>$timesheetId, note=>$_POST["note"]));
			}
		}else{
			if(empty($remainingLeave)) {
				$remainingLeave = $_POST["carried_over"]*60*60 + $_POST["carried_over_mins"]*60;
				$remainingLeave = number_format($remainingLeave/60/60,2);
			}
			//check if the period_date exists if not then this an update of the live carried over time.
			$carriedOver = dl::select("flexi_carried_forward", "timesheet_id=$timesheetId and period_date = '".$_GET["endDate"]."'");
			if( ! empty( $carriedOver ) ) { //found the period update the saved carried forward time
				dl::update("flexi_carried_forward", array(sign=>$_POST["posneg"], flexitime=>$remainingLeave), "timesheet_id=".$timesheetId." and period_date = '".$_GET["endDate"]."'");
			}else{ // update the live carried over time for the current period
				dl::update("flexi_carried_forward_live", array(sign=>$_POST["posneg"], flexi_time_carried_forward=>$remainingLeave), "timesheet_id=".$timesheetId);
			}
			if($_POST["note"]!="") { //add the note to the carried forward note table
				dl::insert("flexi_carried_forward_notes",array(timesheet_id=>$timesheetId, note=>$_POST["note"]));
				if(!empty($_GET["endDate"])) { //update the date when the alteration is for instead of the current data and time.
					$lastId = dl::getId();
					dl::update("flexi_carried_forward_notes", array("note_datetime"=>$_GET["endDate"]." 23:59:59"), "note_id = ".$lastId);
				}
				
				
			}
		}
		echo "<SCRIPT language='javascript'>redirect('index.php?func=editflexipot&timesheet=$timesheetId');</SCRIPT>" ;
		die();
	}
}

function show_notes($timesheetId) {
	echo "<div class='timesheet_header'>Carried Forward Notes</div>";
	echo "<table class='table_view'>";
	echo "<tr><th>Time Stamp</th><th>Note</th></tr>";
	$notes = dl::select("flexi_carried_forward_notes", "timesheet_id=".$timesheetId);
	foreach($notes as $note) {
		echo "<tr><td>".$note["note_datetime"]."</td><td>".$note["note"]."</td></tr>";
	}
	echo "</table>";
}

function clear_login() {
	//clear all of the variables and restart the login procedure.
	session_unset();
	session_destroy();
	echo "<SCRIPT language='javascript'>redirect('index.php')</SCRIPT>" ;
}

function view_timesheet($userId, $pStartDate="", $pEndDate="") {
	if($_GET["func"]=="nextperiod" or $_GET["func"] == "previousperiod" or $_GET["func"] == "viewuserstimesheet") { // this is for the redirection after a draggable deletion
			$_SESSION["pageLocation"] 		= $_GET["func"];
			$_SESSION["startPeriod"] 		= $_GET["start"];
			$_SESSION["endPeriod"] 			= $_GET["end"];
			$_SESSION["user"]				= $_GET["userid"];
		}elseif($_GET["choice"]="View") {//adding to your own spreadsheet
			$_SESSION["pageLocation"]		= "viewuserstimesheet";
			$_SESSION["user"]				= $_SESSION["userSettings"]["userId"];
		}else{
			$_SESSION["pageLocation"]		= "";
			$_SESSION["startPeriod"] 		= "";
			$_SESSION["endPeriod"] 			= "";
			$_SESSION["user"]				= "";
		}
	if($userId 								!= $_SESSION["userSettings"]["userId"]) { //the timesheet request is not from the logged in user
		//need to find the user's details
		//need to check if the logged in user has management credentials
		$users 								= dl::select("flexi_user", "user_id = ".$userId);
		$name 								= $users[0]["user_name"];
		$timeTemplate 						= $users[0]["user_time_template"];
		$flexiTemplate 						= $users[0]["user_flexi_template"];
		$permissionTemplate 				= $users[0]["user_permission_id"];
		$own_timesheet 						= false;
	}else{
		$name								= $_SESSION["userSettings"]["name"];
		$timeTemplate 						= $_SESSION["userSettings"]["timeTemplate"];
		$flexiTemplate 						= $_SESSION["userSettings"]["flexiTemplate"];
		$permissionTemplate 			    = $_SESSION["userSettings"]["permissionId"];
		$own_timesheet 						= true;
	}
	
	//check the time change table to see if there has been any flexi template modifications
	//change the flexi template to show the correct weekly time if there has been changes
	$timesheet 								= dl::select("flexi_timesheet", "user_id=".$_GET["userid"]);
	if(!empty($pEndDate)) {
		$changes 							= dl::select("flexi_time_changes", "change_date > '".$pEndDate."' and timesheet_id = ".$timesheet[0]["timesheet_id"]." order by change_date ASC ");
		if(!empty($changes)) { //changes have been made and therefore need to be applied to this view
			$flexiTemplate 					= $changes[0]["old_template_id"];
			$changeDate 					= $changes[0]["change_date"];
		}
	}
	//check if logged in user is an authoriser and can therefore view the full timesheet if the userId <> logged in user id
	$permissions 							= dl::select("flexi_permission_template", "permission_template_name_id = ".$_SESSION["userSettings"]["permissionId"]);
	if($permissions[0]["permission_team_authorise"]=="true") {
		$authorise 							= true;	
	}else{
		$authorise 							= false;
	}
	//check if the permission permits the viewing of the users timesheet at all
	$viewTimesheet 							= dl::select("flexi_permission_template", "permission_template_name_id = ".$permissionTemplate);
	$viewTimesheetCheck 					= $viewTimesheet[0]["permission_view_timesheet"];
	//now check if the permission overrides the setting on the logged in user.
	$viewOverride 							= $permissions[0]["permission_view_override"];
	// get the Event that is a working Session so as to treat it differently to the other events.
	$types 									= dl::select("flexi_event_type", "event_work = 'Yes'");
	$workingType 							= $types[0]["event_type_id"];
	//now get the template settings
	$sql 									= "select * from flexi_template as t 
	join flexi_template_name as tn on (t.template_name_id=tn.flexi_template_name_id) 
	join flexi_template_days as td on (td.template_name_id=tn.flexi_template_name_id) 
	join flexi_template_days_settings as tds on (tds.template_days_id=td.flexi_template_days_id) 
	where t.template_id = ".$flexiTemplate;
	$flexi_settings 						= dl::getQuery($sql);
	$time_settings 							= dl::select("flexi_time_template", "time_template_name_id =".$timeTemplate);
	//get day settings
	$sql = "select * from flexi_template_name as tn 
	join flexi_template_days as td on (tn.flexi_template_name_id = td.template_name_id) 
	join flexi_template_days_settings as ds on (ds.template_days_id = td.flexi_template_days_id) 
	where tn.flexi_template_name_id = ".$flexi_settings[0]["template_name_id"];
	$day_settings = dl::getQuery($sql);
	//setup all of the template variables
	// Date Format *******************************************************
	$timedateFormat 						= $time_settings[0]["time_template_date_format"];
	if($timedateFormat 						== "dd-mm-yyyy") { 
		$dateFormat							= "d-m-Y";
		$shortDate 							= "d/m";
	}else{
		$dateFormat 						= "m-d-Y";
		$shortDate 							= "m/d";
	}
	//Flexi Settings *****************************************************
	$flexiAccPeriod 						= $flexi_settings[0]["account_period"];
	$flexiMaxSurplus 						= $flexi_settings[0]["max_surplus"];
	$flexiMaxDeficit 						= $flexi_settings[0]["max_deficit"];
	$flexiMaxHoliday 						= $flexi_settings[0]["max_holiday"];
	$flexiLeavePeriod 						= $flexi_settings[0]["leave_period"];
	$flexiStartPeriod 						= $flexi_settings[0]["start_period"];
	$flexiEndPeriod 						= $flexi_settings[0]["end_period"];
	
	// Day settings ******************************************************
	$flexiID								= $day_settings[0]["days_settings_id"];
	$daysTemplateType 						= $day_settings[0]["template_type"];
	$daysPerWeek 							= $day_settings[0]["days_week"];
	$daysDayDuration 						= $day_settings[0]["day_duration"]; //this is the policy day duration ie 7hr 24 mins ******* Not used anymore *********
	$daysNormalDuration 					= $day_settings[0]["normal_day_duration"]; //this the user day duration as entered by them ******* Not used anymore *********
	$daysTimeDifferential 					= strtotime($daysNormalDuration) - strtotime($daysDayDuration); //this is the difference between the day duration and Normal day duration
	$daysHalfDayDuration 					= strtotime($daysDayDuration)/2;
	$daysHalfDayDuration 					= date('H:i:s', $daysHalfDayDuration);
	$daysDayHours 							= substr($daysDayDuration,0,2); //the duration of the day hours (What is required for each day!!)
	$daysDayMins 							= substr($daysDayDuration,3,2); // the duration of the day minutes (What is required for each day!!)
	$daysMinimumLunch						= $day_settings[0]["minimum_lunch"];
	$daysMinimumLunchDuration 				= $day_settings[0]["minimum_lunch_duration"];
	$daysLunchEarliest 						= $day_settings[0]["lunch_earliest_start_time"];
	$daysLunchLatest 						= $day_settings[0]["lunch_latest_end_time"];
	//*********************************************************************
	// get the timesheet id of the user
	$timesheet 								= dl::select("flexi_timesheet", "user_id = ".$userId);
	$timesheetId 							= $timesheet[0]["timesheet_id"]; 
	//get the week hours from the flexi_day_times
	$dayTimes 								= dl::select("flexi_day_times", "fdt_flexi_days_id = ".$flexiID);
	if($dayTimes[0]["fdt_weekday_id"] 		== 6 ) {
		$multiplier = $daysPerWeek;
	}else{
		$multiplier 						= 1;
	}
	foreach($dayTimes as $dt) {
		$hours 								+= substr($dt["fdt_working_time"], 0,2);
		$mins 								+= substr($dt["fdt_working_time"],3,2);
	}
	$hours 									= $hours * $multiplier;
	$mins 									= $mins * $multiplier;
	$mins 									= $mins/60;
	$hours 									= $hours + (int) $mins;
	$mins									= $mins - (int) $mins;
	$hoursPerWeek 							= number_format($hours + $mins,1);
	
	// get the events for the userId specified.
	// check if there has been parameters passed with start and end dates
	if(!empty($pStartDate) and !empty($pEndDate)) {
		$eventStartDate 					= $pStartDate." 00:00:00";
		$eventEndDate 						= $pEndDate." 23:59:59";
	}else{
		$eventStartDate						= $flexiStartPeriod." 00:00:00";
		$eventEndDate 						= $flexiEndPeriod." 23:59:59";
	}
	if($authorise or $own_timesheet) { //can view full timesheet
		if($viewTimesheetCheck				=="true" or $viewOverride=="true"){
			$events 						= dl::select("flexi_event", "timesheet_id=".$timesheetId." and event_startdate_time >= '".$eventStartDate."' and event_enddate_time <= '".$eventEndDate."'", "event_startdate_time ASC");
		}
	}else{ // can just view flexi and annual leave on the timesheet
		//can only do this if the view user timesheet template option is true
		if($viewTimesheetCheck				=="true" or $viewOverride=="true"){
			$sql 							= "select * from flexi_event as e join flexi_event_type as et on (	et.event_type_id=e.event_type_id ) 
													where timesheet_id=".$timesheetId." and event_startdate_time >= '".$eventStartDate."' 
													and event_enddate_time <= '".$eventEndDate."' and (event_work='No') order by event_startdate_time ASC";
			$events 						= dl::getQuery($sql);
		}
	}
	// check which period we are looking at and get the flexi carry over for that period
	$flexi_carry 							= dl::select("flexi_carried_forward", "timesheet_id=".$timesheetId." and period_date = '".substr($eventEndDate,0,10)."'");
	
	//check the flexi carried forward notes for any notes on this account
	$flexi_notes							= dl::select("flexi_carried_forward_notes", "timesheet_id = ".$timesheetId." and note_datetime <= '".$eventEndDate. "' and note_datetime >= '".$eventStartDate."'");

	if(!empty($flexi_carry)) { //we are looking at a timesheet in the past
		$flexiInPot 						= date("H:i", $flexi_carry[0]["flexitime"]*60*60);
		$sign 								= $flexi_carry[0]["sign"];
	}elseif(substr($eventEndDate,0,10)==$flexiEndPeriod){ //the current period
		//get the live/current flexitime in their pot
		$flexiPot 							= dl::select("flexi_carried_forward_live", "timesheet_id=".$timesheetId);
		$flexiInPot 						= date("H:i", $flexiPot[0]["flexi_time_carried_forward"]*60*60);
		$sign 								= $flexiPot[0]["sign"];
	}else{
		$flexiInPot 						= "00:00";
	}	
	echo "<div class='timesheet_header'>TIMESHEET</div>";
    echo "<div class='timesheet_name'>".$name."</div>";
	if(date("H:i", strtotime($flexiInPot)) != "00:00" ) {
		$flexi_carried_over					= "User has ".$sign.date("H:i", strtotime($flexiInPot)). " (hh:mm) flexitime carried over from last period.";
		echo "<div class='timesheet_flexipot'>".$flexi_carried_over."</div>";
	}
	if(check_permission("Edit Flexipot")) { // super Administrator
		//link to edit flexitime in pot
		if( ! empty( $_GET["end"] ) ) {
			echo "<div class='timesheet_flexipot'><a href='index.php?func=editflexipot&timesheet=".$timesheetId."&endDate=".$_GET["end"]."'>Edit Carried over time</a></div>";
		}else{
			echo "<div class='timesheet_flexipot'><a href='index.php?func=editflexipot&timesheet=".$timesheetId."'>Edit Carried over time</a></div>";
		}
	}
	if($own_timesheet or $authorise) {
		if(!empty($flexi_notes)) {
			echo "<div class='left_text_image'><img id='view-notes' src='inc/images/text-edit.png' style='cursor: pointer;' /></div><div class='left_text_small'>There are carried over notes for this period.</div>";
			?>
			<div id="notes-dialog" title="Carried Over Notes" style="display: none; overflow-y: scroll;">
			<div class='leaveNote-title'>Date</div><div class='leaveNote-title'>Note</div><BR /><BR />
				<?php
				foreach($flexi_notes as $ln) {
					echo "<div class='note-date'>".$ln["note_datetime"]."</div><div class='leaveNote-message'>".nl2br($ln["note"])."</div>";
				}
				?>
			</div>
			<script>
			$("#view-notes").click(function(){
				$("#notes-dialog").dialog({
					resizable: true,
	                height:300,
	                width: 500,
	                modal: true,
	                buttons: {
	                    "Close": function() {
	                        $( "#notes-dialog" ).dialog( "close" );
	                    }
	            	}
				});
			});
			</script>
			<?php
		}
	}
	echo "<div class='timesheet_workspace timesheet_workspace_times'>";
		echo "<div class='timesheet_table_header_blank'><div class='timesheet_padding'>W/C</div></div>";
		echo "<div class='timesheet_table_header'><div class='timesheet_padding'>Monday</div></div>";
		echo "<div class='timesheet_table_header'><div class='timesheet_padding'>Tuesday</div></div>";
		echo "<div class='timesheet_table_header'><div class='timesheet_padding'>Wednesday</div></div>";
		echo "<div class='timesheet_table_header'><div class='timesheet_padding'>Thursday</div></div>";
		echo "<div class='timesheet_table_header'><div class='timesheet_padding'>Friday</div></div>";
		echo "<div class='timesheet_table_header_we'><div class='timesheet_padding'>Worked</div></div>";
		echo "<div class='timesheet_table_header_we'><div class='timesheet_padding'>Required</div></div>";
		echo "<div class='timesheet_table_header_we'><div class='timesheet_padding'>Balance</div></div>";
		echo "<div class='timesheet_table_header_we'><div class='timesheet_padding'>Flexi Pot</div></div>";
		$date = $eventStartDate;
		$endDate = $eventEndDate;
		$arrCount=0;
		$emptyDate = 1;
		//loop through the events
		foreach($events as $event) {
			// reset the counter for working Events
			$workingEvent = 0;
			$lunchDeducted = false;
			if($flexiAccPeriod == "4 Weekly") {
				while(date('Y-m-d',strtotime($date)) < date('Y-m-d',strtotime($event["event_startdate_time"]))) {
					if(date('l',strtotime($date))=="Monday") { //start of the 4 weekly period display
						echo "<div class='timesheet_table_header_blank'><div class='timesheet_padding'>".date($dateFormat, strtotime($date))."</div></div>";
					}
					if(date('l',strtotime($date))!="Saturday") {
						echo "<div id='empty".$emptyDate."' data-horizontal-offset='-264' data-vertical-offset='-15' data-dropdown='#emptydropdown".$emptyDate."' class='timesheet_table_header_white' style='cursor: pointer;'><div class='timesheet_padding'>".date($shortDate, strtotime($date))."</div></div>";
						//echo "<div id='empty".$emptyDate."' data-dropdown='emptydropdown".$emptyDate."' class='timesheet_table_header_white' onclick='location.href=\"index.php?choice=Add&subchoice=addevent&type=Working Day&userid=".$userId."&date=".date("Y-m-d", strtotime($date))."\"' style='cursor: pointer;'><div class='timesheet_padding'>".date($shortDate, strtotime($date))."</div></div>";
						echo "<div id='emptydropdown".$emptyDate."' class='dropdown dropdown-tip'>";
						echo "<ul class='dropdown-menu'>";
							//use event types to populate the dropdown list
							$list_types = dl::select("flexi_event_type");
							$ids="";
							$eId = $event["event_id"];						
							foreach($list_types as $lt){
								//lets exclude the types that only Admin can enter
								if($lt["event_type_name"] !== "Bank Holiday" and $lt["event_type_name"] !== "University Shutdown") {
									echo "<li id='".$lt["event_type_id"]."-empty".$emptyDate."'><a href='#1'>".$lt["event_type_name"]."</a><li>";								
									$ids[] = $lt["event_type_id"]."-empty".$emptyDate;
								}
							}
						echo "</ul>";
						echo "</div>";
						foreach($ids as $id) {			
							?>
							<script>
							$("#<?php echo $id?>").click(function() {
								$.post(
									"ajax.php",
									{ 
										func: 'get_event_type',
										id: $(this).prop("id")
									},
									function (data)
										{
											var json = $.parseJSON(data);
											window.location.href = "index.php?choice=Add&subchoice=addevent&type="+json.type+"&userid=<?php echo $userId?>&date=<?php echo date("Y-m-d", strtotime($date))?>";
										}
								);
							});
							</script>
							<?php
						}
						$emptyDate++;
						$date = add_date(strtotime($date),1);
					}else{
						if($own_timesheet or $authorise) {
							//summarise
							//get working week in hrs
							/*$hoursPerWeek = $daysDayHours * $daysPerWeek;
							$minsPerWeek = $daysDayMins * $daysPerWeek;
							$addOnHours = $minsPerWeek/60;
							$hoursPerWeek += $addOnHours;*/
							$extra_time 			= ($extra_time * date("i",$daysTimeDifferential))/60;
							$weekTimeMins 			+=$extra_time;
							$weekHrs 				= $weekTimeMins/60;
							$weekTimeHrs 			+= intval($weekHrs);
							$weekMins 				= (($weekTimeMins - intval($weekHrs)*60)/60);
							$hoursWorked 			= $weekTimeHrs+$weekMins + $extra_time;						
							$workBalance 			= $hoursWorked - $hoursPerWeek;
							$workBalanceTotal 		+= $workBalance;
							//check what's in the flexi pot and subtract
							if($flexiInPot 			!= "00:00:00") {
									$flexiHrs 		= $flexiweekTimeHrs  - date("G", strtotime($flexiInPot));
									$flexiMins 		= $flexiweekTimeMins - date("i", strtotime($flexiInPot));
							}
							$flexiPotHour 			= date("G",strtotime($flexiInPot));
							//sign is whether to add or subtract the flexipot value
							if($sign 				== "+") {
								$flexiPotTotal 		= $workBalanceTotal + $flexiPotHour+number_format((date("i", strtotime($flexiInPot))/60),2);
							}else{
								$flexiPotTotal 		= $workBalanceTotal - $flexiPotHour-number_format((date("i", strtotime($flexiInPot))/60),2);
							}
							$style					="";
							if(number_format($flexiPotTotal,1) < $flexiMaxSurplus and  number_format($flexiPotTotal,2) > 0 ) {
								//in the black and not too much
								$style 				= "background-color:#0C6;";
							}
							if(number_format($flexiPotTotal,2) > $flexiMaxSurplus ) {
								// too much
								$style 				= "background-color:#FF6C00;";
							}
							if(number_format($flexiPotTotal,2) < $flexiMaxDeficit ) {
								//in the red
								$style 				= "background-color:#F00;";
							}
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($hoursWorked,1)." hrs</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($hoursPerWeek,1)." hrs</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($workBalance,1)." hrs</div></div>";
							echo "<div class='timesheet_table_header_time' style='$style'><div class='timesheet_padding'>".number_format($flexiPotTotal,1)." hrs</div></div>";
						}else{
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
						}
						
						// check the carried forward table and sdd the flexi details to it only if within the current period
						if($_GET["choice"]=="View" and $_GET["subchoice"] == "timesheet") {
							$fieldList=array("timesheet_id","current_flexi");
							$flexiMinutes = number_format($flexiPotTotal,1);
							$valueArr = array($timesheetId, $flexiMinutes);
							$writeArr = array_combine($fieldList,$valueArr);
							$cf = dl::select("flexi_carried_forward_live", "timesheet_id = ".$timesheetId);
							if(empty($cf)) { //create a record for this
								$rec = dl::insert("flexi_carried_forward_live", $writeArr);
							}else{ //update the flexi time in the existing records
								$rec = dl::update("flexi_carried_forward_live", $writeArr, "timesheet_id = ".$timesheetId);
							}
						}
						$date 						= add_date(strtotime($date),2);//add 2 to skip weekend
						$weekTimeHrs				= 0;
						$weekTimeMins				= 0;
						$extra_time 				= 0;
					}
				}
				if(date('l',strtotime($date))		=="Monday") { //start of the 4 weekly period display
					echo "<div class='timesheet_table_header_blank'><div class='timesheet_padding'>".date($dateFormat, strtotime($date))."</div></div>";
				}
				if(date('Y-m-d',strtotime($date)) 	== date('Y-m-d',strtotime($event["event_startdate_time"]))) {
					if(date('l',strtotime($date))	!="Saturday") { 
						
						$timeFrom 					= date('H:i:s', strtotime($event["event_startdate_time"]));
						$timeTo 					= date('H:i:s', strtotime($event["event_enddate_time"]));
						$timediff 					= date('H:i:s',strtotime($timeTo) - strtotime($timeFrom));
						$alt 						= date('H:i', strtotime($event["event_startdate_time"]))." - ".date('H:i', strtotime($event["event_enddate_time"]));
						//check if the event Type is Flexi Leave if so then don't add the time but capture the flexi Leave
						$eventType 					= dl::select("flexi_event_type", "event_type_id = ".$event["event_type_id"]);
						//find out if the event has a lunch_deduction
						$lunchDeduction 			= dl::select("flexi_event_settings", "event_typeid = ".$event["event_type_id"]);
						if($lunchDeduction[0]["lunch_deduction"] == "Yes") { //the event requires a lunch deduction to be taken off but only if equal or over 6 hours
							$workingEvent 			+= strtotime($timediff);
						}
						//check if an extended lunch was taken
						if($event["event_lunch"] 	!= "00:00:00") { //extended lunch has been taken
							//check if the extended lunch is greater than minimum lunch
							if (strtotime($event["event_lunch"]) > strtotime($daysMinimumLunchDuration)) { //minimum lunch in seconds
								$timediff 			= date('H:i:s',strtotime($timediff) - strtotime($event["event_lunch"]));
								$extended_lunch		= true;
								$lunchDeducted 		= true;
							}else{
								$extended_lunch		= false;
								//need to check if have to take lunch off
								if($daysMinimumLunch=="Yes") {
									if(date("G", strtotime($timediff)) > 6 ) { //if the time worked is greater than 6 hours then take off lunch
										$timediff 	= date('H:i:s',strtotime($timediff) - strtotime($daysMinimumLunchDuration));
										$lunchDeducted = true;
									}
								}
							}
						}else{
							$extended_lunch			= false;
							//need to check if have to take lunch off
							if($daysMinimumLunch	=="Yes") {
								// capture the time for this event as there may be multiple events on the one day (eg: working session then a training session followed by a working session
								//*************************************************
								if(date("G", strtotime($timediff)) >= 6 ) { //if the time worked is greater than 6 hours then take off lunch
									$timediff 		= date('H:i:s',strtotime($timediff) - strtotime($daysMinimumLunchDuration));
									$lunchDeducted 	= true;
								}
							}
															
						}
						if(date("G", $workingEvent) >= 6 ) {
							if(!$lunchDeducted) {
								$timediff 			= date('H:i:s',strtotime($timediff) - strtotime($daysMinimumLunchDuration));
								$lunchDeducted 		= true;
							}
						}
						
						// lunch has been deducted let's now check if any of the events on this day are remainder events
						// if so then we'll add the minimum lunch back on. Should not apply if there is an extended lunch
						if ($lunchDeducted 			== true) {
							$eventDay = date('Y-m-d',strtotime($date)). " 00:00:00";
							$eventlast 				= date('Y-m-d', strtotime($eventDay)). " 23:59:59";
							$eventsToday 			= dl::select("flexi_event", "event_startdate_time >= '$eventDay' and event_enddate_time <= '$eventlast' and timesheet_id = $timesheetId");
							if(count($eventsToday) > 1){
								foreach ($eventsToday as $etoday) {
									$foundRemainder = dl::select("flexi_remainder", "r_event = ".$etoday["event_id"]);
									if(!empty($foundRemainder)) {
										$dayHr 		= substr(date("H:i:s", strtotime($daysMinimumLunchDuration)),0,2)*60*60;
										$dayMin 	= substr(date("H:i:s", strtotime($daysMinimumLunchDuration)),3,2)*60;
										$daySec 	= substr(date("H:i:s", strtotime($daysMinimumLunchDuration)),5,2);
										$lunchtoAdd = $dayHr + $dayMin + $daySec;
										//add the minimum lunch back onto the $timediff variable
										$timediff 	= date('H:i:s', strtotime($timediff) + $lunchtoAdd);
									}
								}
							}
						}
						// add up the number of hours and mins
						if($eventType[0]["event_flexi"]=="Yes") {
							$flexiTimeHrs 			= $flexiTimeHrs + date("G", strtotime($timediff));
							$flexiTimeMins 			= $flexiTimeMins + date("i", strtotime($timediff));
						}else{
							$weekTimeHrs 			= $weekTimeHrs + date("G", strtotime($timediff));
							$weekTimeMins 			= $weekTimeMins + date("i", strtotime($timediff));
						}
						$hours 						= date("G", strtotime($timediff));
						$mins 						= date("i", strtotime($timediff));
						//need to link pixels to the resolution of the screen
						$screenRes 					= $_SESSION["screenResolution"];
						list($pixelSize, $showDate) = set_pixelSize($screenRes);
						
						if($hours 					!= 12) {
							$pixels 				= $hours * $pixelSize;
						}else{
							$pixels 				= 0;
						}
						$pixels 					+= $mins/5;
						//need to calculate the pixels for the timediff
						$pxWidth 					= $pixels;
						//get the event Type and colour
						if($own_timesheet or $authorise) {
							$eventColour 			= $eventType[0]["event_colour"];
							$eventShortCode 		= $eventType[0]["event_shortcode"];
						}else{
							$eventColour 			= "#CCC";
						}
						//get any notes and attach to the alt check if they are public or private
						if($authorise or $own_timesheet) {
							$sql					="select * from flexi_event_notes as en 
												join flexi_notes as n on (en.note_id=n.notes_id) 
												where event_id=".$event["event_id"];
							$notes 					= dl::getQuery($sql);
						}
                        $note = "";
						if(!empty($notes)) {
							if($notes[0]["notes_type"] == "Public" and $authorise) {
								$note 				= "\n\n".$notes[0]["notes_note"];
							}elseif($notes[0]["notes_type"] == "Private" and $own_timesheet) {
								$note 				= "\n\n".$notes[0]["notes_note"];
							}
						}else{
							$note 					= "";
						}
						$dateTitle 					= date("d/m/Y", strtotime($date));
						$alt 						.= " ".date("H:i", strtotime($timediff)); 
						$alt 						= date($shortDate, strtotime($date))." ".$alt.$note;
						if($_SESSION["userPermissions"]["add_global"] == "true") { //if you have the global add permission then user is considered an Admin
							echo "<div class='timesheet_table_header_white' title='$dateTitle'><div class='timesheet_padding'><div title='$alt' class='connected' id='time".$event["event_id"]."'style='cursor: move; float:left; background-color:".$eventColour."; width:".$pxWidth."px '>";
						}else{
							if( substr($event["event_startdate_time"],0,10) >= $flexiStartPeriod) {
								if($eventType[0]["event_global"] == "No") {
									echo "<div class='timesheet_table_header_white' title='$dateTitle'><div class='timesheet_padding'><div title='$alt' class='connected' id='time".$event["event_id"]."'style='cursor: move; float:left; background-color:".$eventColour."; width:".$pxWidth."px '>";
								}else{
									echo "<div class='timesheet_table_header_white' title='$dateTitle'><div class='timesheet_padding'><div title='$alt' style='float:left; background-color:".$eventColour."; width:".$pxWidth."px '>";
								}
							}else{
								echo "<div class='timesheet_table_header_white' title='$dateTitle'><div class='timesheet_padding'><div title='$alt' style='float:left; background-color:".$eventColour."; width:".$pxWidth."px '>";
							}
						}
						if(date("G", strtotime($timediff)) > $showDate ) { 
							echo date($shortDate, strtotime($date));
						}
						echo " &nbsp;</div></div>";
						$loopCount=$arrCount+1;
						$accumulateTime = strtotime($timediff);
						//now loop through multiple events on the same day
						while(date('Y-m-d',strtotime($date)) == date('Y-m-d',strtotime($events[$loopCount]["event_startdate_time"]))) {
							$timeFrom = date('H:i:s', strtotime($events[$loopCount]["event_startdate_time"]));
							$timeTo = date('H:i:s', strtotime($events[$loopCount]["event_enddate_time"]));
							$alt = date('H:i', strtotime($events[$loopCount]["event_startdate_time"]))." - ".date('H:i', strtotime($events[$loopCount]["event_enddate_time"]));
							$timediff = date('H:i:s',strtotime($timeTo) - strtotime($timeFrom));
							/*****************Annual Leave check **********************/
							if($events[$loopCount]["event_type_id"] != 3 and $events[$loopCount]["event_type_id"] != 2) {
								// if the event is not an Annual or Flexi Leave event then add the time to count for minimum lunch check.
								$accumulateTime += strtotime($timeTo) - strtotime($timeFrom);
							}
							//check if the event Type is Flexi Leave if so then don't add the time but capture the flexi Leave
							$eventType = dl::select("flexi_event_type", "event_type_id = ".$events[$loopCount]["event_type_id"]);
							if(date("G", $accumulateTime) >= 6) {
								if(!$lunchDeducted and !$extendedLunch) {
									if(date('Y-m-d',strtotime($date)) == date('Y-m-d',strtotime($events[$loopCount+1]["event_startdate_time"]))) { //check to see if the next event is still on the same day
										//need to check if the next event for this date is a remainder date if it is then the lunch duration is not to be taken off
										$remainder = dl::select("flexi_remainder", "r_event = ". $events[$loopCount+1]["event_id"]);
										if(empty($remainder)) {
											$timediff = date('H:i:s',strtotime($timediff) - strtotime($daysMinimumLunchDuration));
											$lunchDeducted = true;
										}
									}else{
										//check if this event is a remainder event if it is then don't take the lunch off
										$remainder = dl::select("flexi_remainder", "r_event = ". $events[$loopCount]["event_id"]);
										if(empty($remainder)) {
											$timediff = date('H:i:s',strtotime($timediff) - strtotime($daysMinimumLunchDuration));
											$lunchDeducted = true;
										}
									}
								}
							}
							
							// add up the number of hours and mins
							if($eventType[0]["event_flexi"]=="Yes") {
								$flexiTimeHrs = $flexiTimeHrs + date("G", strtotime($timediff));
								$flexiTimeMins = $flexiTimeMins + date("i", strtotime($timediff));
							}else{
								$weekTimeHrs = $weekTimeHrs + date("G", strtotime($timediff));
								$weekTimeMins = $weekTimeMins + date("i", strtotime($timediff));
							}
							$hours = date("G", strtotime($timediff));
							$mins = date("i", strtotime($timediff));
							//need to link pixels to the resolution of the screen
							$screenRes = $_SESSION["screenResolution"];
							list($pixelSize, $showDate) = set_pixelSize($screenRes);
							
							if($hours != 12) { // if < 1 hour (=0) $hours is set to 12
								$pixels = $hours * $pixelSize;
							}else{
								$pixels = 0;
							}
							$pixels += $mins/5;
							//need to calculate the pixels for the timediff
							$pxWidth = $pixels;
							//get the event Type and colour
							if($own_timesheet or $authorise) {
								$eventColour = $eventType[0]["event_colour"];
								$eventShortCode = $eventType[0]["event_shortcode"];
							}else{
								$eventColour = "#CCC";
							}
							//get any notes and attach to the alt check if they are public or private
							if($authorise or $own_timesheet) {
								$sql="select * from flexi_event_notes as en 
								join flexi_notes as n on (en.note_id=n.notes_id) 
								where event_id=".$events[$loopCount]["event_id"];
								$notes = dl::getQuery($sql);
							}
                            $note = "";
							if(!empty($notes)) {
								if($notes[0]["notes_type"] == "Public" and $authorise) {
									$note = "\n\n".$notes[0]["notes_note"];
								}elseif($notes[0]["notes_type"] == "Private" and $own_timesheet) {
									$note = "\n\n".$notes[0]["notes_note"];
								}
							}
							$alt .= " ".date("H:i", strtotime($timediff)); 
							$alt = date($shortDate, strtotime($date))." ".$alt.$note;
							if($_SESSION["userPermissions"]["add_global"] == "true") { //if you have the global add permission then user is considered an Admin
								echo "<div class='connected' id='time".$events[$loopCount]["event_id"]."' style='cursor: move; float:left; background-color:".$eventColour."; width:".$pxWidth."px ' title='$alt'>";
							}else{
								if( substr($events[$loopCount]["event_startdate_time"],0,10) > $flexiStartPeriod) {
									if($eventType[0]["event_global"] == "No") {
										echo "<div class='connected' id='time".$events[$loopCount]["event_id"]."' style='cursor: move; float:left; background-color:".$eventColour."; width:".$pxWidth."px ' title='$alt'>";
									}else{
										echo "<div style='float:left; background-color:".$eventColour."; width:".$pxWidth."px ' title='$alt'>";
									}
								}else{
									echo "<div style='float:left; background-color:".$eventColour."; width:".$pxWidth."px ' title='$alt'>";
								}
							}
							$loopCount++;
							//echo "<div style='float:left; background-color:".$eventColour."; width:".$pxWidth."px'>";
							if(date("G", strtotime($timediff)) > $showDate ) { 
								echo date($shortDate, strtotime($date));
							}
							echo "&nbsp;</div>";
						}
						echo "<a href=\"index.php?choice=Add&subchoice=addevent&type=Working Day&userid=".$userId."&date=".date("Y-m-d", strtotime($date))."&option=another"."\" title='Add another event on ".date("d/m", strtotime($date))."'><div data-horizontal-offset='-306' data-vertical-offset='-12' data-dropdown='#dropdown-events".$event["event_id"]."' class='timesheet_table_header_addIcon'> </div></a>";
						if($extended_lunch) {
							echo "<img src='inc/images/fork_knife.jpg' title='".$event["event_lunch"]."' />";
						}
						echo "</div>";
						echo "<div id='test'></div>";
						 /* 
						 * create dropdowns here
						 */
						echo "<div id='dropdown-events".$event["event_id"]."' class='dropdown-anchor-right dropdown dropdown-tip' style='z-index: 1'>";
						echo "<ul class='dropdown-menu'>";
							//use event types to populate the dropdown list
							$list_types = dl::select("flexi_event_type");
							$ids="";
							$eId = $event["event_id"];						
							foreach($list_types as $lt){
								//lets exclude the types that only Admin can enter
								if($lt["event_type_name"] !== "Bank Holiday" and $lt["event_type_name"] !== "University Shutdown") {
									echo "<li id='".$lt["event_type_id"]."-".$eId."'><a href='#1'>".$lt["event_type_name"]."</a><li>";								
									$ids[] = $lt["event_type_id"]."-".$eId;
								}
							}
						echo "</ul>";
						echo "</div>";
						$eventsToday = dl::select("flexi_event", "event_startdate_time >= '".substr($date,0,10)." 00:00:00' and event_enddate_time <= '".substr($date,0,10)." 23:59:59' and timesheet_id =".$timesheetId);
						//now lets set the start Duration selected if the starttime is earlier than 10:00 and the option get value = 'another'
						$selectedTime = "09:00";
						$selectedEndTime = "";
						if($eventsToday[0]["event_startdate_time"] <= $date." 10:00:00") {
							//lets set the selected value now
							$hrs = substr($eventsToday[0]["event_enddate_time"], 11,2);
							$mins = substr($eventsToday[0]["event_enddate_time"], 14,2);
							$selectedTime = $hrs.":".$mins;
						}else{
							$hrs = substr($eventsToday[0]["event_startdate_time"], 11,2);
							$mins = substr($eventsToday[0]["event_startdate_time"], 14,2);
							$selectedEndTime = $hrs.":".$mins;
						}
						echo "<div id='valDisp' class='dropdown dropdown-panel' style='z-index: 2;'></div>";
						echo "<div id='arrow-left' class='dropdown dropdown-panel' style='z-index: 100; position: absolute; width:0; height:0; border-top: 5px solid transparent; border-bottom: 5px solid transparent; border-right: 5px solid #fff; display:none;'></div>";
						foreach($ids as $id) {
							//lets check for other events already entered for this day
							?>
							<script>
							$("#<?php echo $id?>").on({
								mouseenter: function () {
									divLeft 		= $("#dropdown-events<?php echo $eId?>").position().left;
									divTop			= $("#dropdown-events<?php echo $eId?>").position().top; 
									divWidth 		= $("#dropdown-events<?php echo $eId?>").width();									
									divPos			= divLeft + divWidth+5;
									$("#valDisp").css({top:divTop+8+'px', left:divPos+'px'});
									$("#valDisp").show();
									$("#arrow-left").css({top:divTop+$(this).position().top+15+'px', left:divPos-4+'px'});
									$("#arrow-left").show();
									$.post(
										"ajax.php",
										{ 
											func: 'get_field_list',
											id: $(this).prop("id"),
											desc: $(this).text(),
											date: '<?php echo substr($date,0,10) ?>',
											time: '<?php echo $selectedTime?>',
											start: '<?php echo $selectedEndTime?>',
											user: '<?php echo $userId?>'
										},
										function (data)
											{
												$("#valDisp").html(data);
											}
									);
								}
							});
							$("#<?php echo $id?>").click(function() {
								$.post(
									"ajax.php",
									{ 
										func: 'get_event_type',
										id: $(this).prop("id")
									},
									function (data)
										{
											var json = $.parseJSON(data);
											window.location.href = "index.php?choice=Add&subchoice=addevent&type="+json.type+"&userid=<?php echo $userId?>&date=<?php echo date("Y-m-d", strtotime($date))?>&option=another";
										}
								);
							});
							</script>
							<?php	
						}
					 
						$date = add_date(strtotime($date),1);
						
					}else{
						if($own_timesheet or $authorise) {
							//summary
							//get working week in hrs
							/*$hoursPerWeek = $daysDayHours * $daysPerWeek;
							$minsPerWeek = $daysDayMins * $daysPerWeek;
							$addOnHours = $minsPerWeek/60;
							$hoursPerWeek += $addOnHours;*/
							$weekHrs = ($weekTimeMins)/60;
							$weekTimeHrs += intval($weekHrs);
							$weekMins = ($weekTimeMins - intval($weekHrs)*60)/60;
							$hoursWorked = $weekTimeHrs+$weekMins;
							$workBalance = $hoursWorked - $hoursPerWeek;
							$workBalanceTotal += $workBalance;
							$flexiPotHour = date("G",strtotime($flexiInPot));
							//sign is whether to add or subtract the flexipot value
							if($sign == "+") {
								$flexiPotTotal = $workBalanceTotal + $flexiPotHour+number_format((date("i", strtotime($flexiInPot))/60),2);
							}else{
								$flexiPotTotal = $workBalanceTotal - $flexiPotHour-number_format((date("i", strtotime($flexiInPot))/60),2);
							}
							$style="";
							if(number_format($flexiPotTotal,1) < $flexiMaxSurplus and  number_format($flexiPotTotal,1) > 0 ) {
								//in the black and not too much
								$style = "background-color:#0C6;";
							}
							if(number_format($flexiPotTotal,1) > $flexiMaxSurplus ) {
								//in the black and not too much
								$style = "background-color:#FF6C00;";
							}
							if(number_format($flexiPotTotal,1) < $flexiMaxDeficit ) {
								//in the black and not too much
								$style = "background-color:#F00;";
							}
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($hoursWorked,1)." hrs</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($hoursPerWeek, 1)." hrs</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($workBalance,1)." hrs</div></div>";
							echo "<div class='timesheet_table_header_time' style='$style'><div class='timesheet_padding'>".number_format($flexiPotTotal,1)." hrs</div></div>";
							$fieldList=array("timesheet_id","current_flexi");
							$flexiMinutes = number_format($flexiPotTotal,1);
							$valueArr = array($timesheetId, $flexiMinutes);
							$writeArr = array_combine($fieldList,$valueArr);
							$cf = dl::select("flexi_carried_forward_live", "timesheet_id = ".$timesheetId);
							if(empty($cf)) { //create a record for this
								$rec = dl::insert("flexi_carried_forward_live", $writeArr);
							}else{ //update the flexi time in the existing records IF IN THE CURRENT PERIOD
								if(substr($eventStartDate,0,10) == $flexiStartPeriod ) {
									$rec = dl::update("flexi_carried_forward_live", $writeArr, "timesheet_id = ".$timesheetId);
								}
							}
						}else{
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
							echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
						}
						$date = add_date(strtotime($date),2);//add 2 to skip weekend
						$weekTimeHrs=0;
						$weekTimeMins=0;
						$extra_time=0;
					}
				}
				$arrCount++;
			}else{ //$flexiAccPeriod == Calendar Month
				echo date('N', strtotime($date));
			}
			?>
			<script>
			$(function() {
				 $( "#time<?php echo $event["event_id"] ?>").draggable({
					connectWith: "#sortable-deleted",
					opacity: 0.5,
					revert: false,
					helper: "clone"
				})
			});
			
			$(document).ready(function(){
				 $( "#sortable-deleted" ).droppable({
					drop: function(event, ui) {
						var func = "check_delete";
						var droppedId = ui.draggable.attr("id");
						$('#poof').show();
						$('#poof').sprite({
							fps: 10,
							no_of_frames: 5,
							rewind: false
						});
						setTimeout("$('#poof').hide()", 500);
						$.post(
						"ajax.php",
						{ func: func,
							dropped: droppedId,
							pagelocation: "<?php echo $_SESSION["pageLocation"]?>",
							start: "<?php echo $_SESSION["startPeriod"]?>",
							end: "<?php echo $_SESSION["endPeriod"]?>",
							user: "<?php echo $_SESSION["user"]?>"
						},
						function (data)
						{
							$("#delConfirm").html(data);
							
						});	
					}
				});
			});
			
			</script>
			<?php 
		}//end of $events foreach
		
		//loop to show full timesheet (empty days)
		while(date('Y-m-d',strtotime($date)) < date('Y-m-d',strtotime($endDate))) {
			if(date('l',strtotime($date))=="Monday") { //start of the 4 weekly period display
				echo "<div class='timesheet_table_header_blank'>".date($dateFormat, strtotime($date))."</div>";
			}
			if(date('l',strtotime($date))!="Saturday") { 
				//echo "<div class='timesheet_table_header_white' onclick='location.href=\"index.php?choice=Add&subchoice=addevent&type=Working Day&userid=".$userId."&date=".date("Y-m-d", strtotime($date))."\"' style='cursor: pointer;'><div class='timesheet_padding'>".date($shortDate, strtotime($date))."</div></div>";
				echo "<div id='empty".$emptyDate."' data-horizontal-offset='-264' data-vertical-offset='-15' data-dropdown='#emptydropdown".$emptyDate."' class='timesheet_table_header_white' style='cursor: pointer;'><div class='timesheet_padding'>".date($shortDate, strtotime($date))."</div></div>";
				//echo "<div id='empty".$emptyDate."' data-dropdown='emptydropdown".$emptyDate."' class='timesheet_table_header_white' onclick='location.href=\"index.php?choice=Add&subchoice=addevent&type=Working Day&userid=".$userId."&date=".date("Y-m-d", strtotime($date))."\"' style='cursor: pointer;'><div class='timesheet_padding'>".date($shortDate, strtotime($date))."</div></div>";
				echo "<div id='emptydropdown".$emptyDate."' class='dropdown dropdown-tip'>";
				echo "<ul class='dropdown-menu'>";
					//use event types to populate the dropdown list
					$list_types = dl::select("flexi_event_type");
					$ids="";
					$eId = $event["event_id"];						
					foreach($list_types as $lt){
						//lets exclude the types that only Admin can enter
						if($lt["event_type_name"] !== "Bank Holiday" and $lt["event_type_name"] !== "University Shutdown") {
							echo "<li id='".$lt["event_type_id"]."-empty".$emptyDate."'><a href='#1'>".$lt["event_type_name"]."</a><li>";								
							$ids[] = $lt["event_type_id"]."-empty".$emptyDate;
						}
					}
				echo "</ul>";
				echo "</div>";

				foreach($ids as $id) {			
				?>
				<script>
				$("#<?php echo $id?>").click(function() {
					$.post(
						"ajax.php",
						{ 
							func: 'get_event_type',
							id: $(this).prop("id")
						},
						function (data)
							{
								var json = $.parseJSON(data);
								window.location.href = "index.php?choice=Add&subchoice=addevent&type="+json.type+"&userid=<?php echo $userId?>&date=<?php echo date("Y-m-d", strtotime($date))?>";
							}
					);
				});
				</script>
				<?php
				}
				$emptyDate++;
				$date = add_date(strtotime($date),1);
			}else{
				if($own_timesheet or $authorise) {
					//summarise
					//get working week in hrs
					/*$hoursPerWeek = $daysDayHours * $daysPerWeek;
					$minsPerWeek = $daysDayMins * $daysPerWeek;
					$addOnHours = $minsPerWeek/60;
					$hoursPerWeek += $addOnHours;*/
					$extra_time = ($extra_time * date("i",$daysTimeDifferential))/60;
					$weekTimeMins +=$extra_time;
					$weekHrs = $weekTimeMins/60;
					$weekTimeHrs += intval($weekHrs);
					$weekMins = ($weekTimeMins - intval($weekHrs)*60)/60;
					$hoursWorked = $weekTimeHrs+$weekMins+$extra_time;
					$workBalance = $hoursWorked - $hoursPerWeek;
					$workBalanceTotal +=$workBalance;
					//check what's in the flexi pot and subtract
					if($flexiInPot != "00:00:00") {
						$flexiHrs = $flexiweekTimeHrs  - date("g", strtotime($flexiInPot));
						$flexiMins = $flexiweekTimeMins - date("i", strtotime($flexiInPot));
					}
					$flexiPotHour = date("G",strtotime($flexiInPot));
					//sign is whether to add or subtract the flexipot value
					if($sign == "+") {
						$flexiPotTotal = $workBalanceTotal + $flexiPotHour+number_format((date("i", strtotime($flexiInPot))/60),2);
					}else{
						$flexiPotTotal = $workBalanceTotal - $flexiPotHour-number_format((date("i", strtotime($flexiInPot))/60),2);
					}
					$style="";
					if(number_format($flexiPotTotal,1) < $flexiMaxSurplus and  number_format($flexiPotTotal,1) > 0 ) {
								//in the black and not too much
								$style = "background-color:#0C6;";
					}
					if(number_format($flexiPotTotal,1) > $flexiMaxSurplus ) {
								//in the black and not too much
								$style = "background-color:#FF6C00;";
							}
					if(number_format($flexiPotTotal,1) < $flexiMaxDeficit ) {
						//in the black and not too much
						$style = "background-color:#F00;";
					}
					echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($hoursWorked,1)." hrs</div></div>";
					echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($hoursPerWeek, 1)." hrs</div></div>";
					echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>".number_format($workBalance,1)." hrs</div></div>";
					echo "<div class='timesheet_table_header_time' style='$style'><div class='timesheet_padding'>".number_format($flexiPotTotal,1)." hrs</div></div>";
					$fieldList=array("timesheet_id","current_flexi");
					$flexiMinutes = number_format($flexiPotTotal,1);
					$valueArr = array($timesheetId, $flexiMinutes);
					$writeArr = array_combine($fieldList,$valueArr);
					$cf = dl::select("flexi_carried_forward_live", "timesheet_id = ".$timesheetId);
					if(empty($cf)) { //create a record for this
						$rec = dl::insert("flexi_carried_forward_live", $writeArr);
					}else{ //update the flexi time in the existing records IF IN THE CURRENT PERIOD
						if(substr($eventStartDate,0,10) == $flexiStartPeriod ) {
							$rec = dl::update("flexi_carried_forward_live", $writeArr, "timesheet_id = ".$timesheetId);
						}
					}
				}else{
					echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
					echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
					echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
					echo "<div class='timesheet_table_header_time'><div class='timesheet_padding'>-</div></div>";
				}
				$date = add_date(strtotime($date),2);//add 2 to skip weekend
				$weekTimeHrs=0;
				$weekTimeMins=0;
				$extra_time=0;
			}
		}
	echo "</div>"; //timesheet_workspace
	if($flexiAccPeriod == "4 Weekly") {
		$pAddStartDate = add_date(strtotime($eventEndDate),1);
		$pAddEndDate = add_date(strtotime($eventEndDate),28);
		$pSubStartDate = sub_date(strtotime($eventStartDate),28);
		$pSubEndDate = sub_date(strtotime($eventStartDate),1);
	}else{ // must be monthly
		$pAddStartDate = add_date(strtotime($eventEndDate),1);
		$pAddEndDate = add_date(strtotime($eventEndDate),0,1);
		$pSubStartDate = sub_date(strtotime($eventStartDate),0,1);
		$pSubEndDate = sub_date(strtotime($eventStartDate),1);
	}
	
	//check if the user has Leave Template notes so the manager can view them here.
	$userALTemplate = dl::select("flexi_user", "user_id = ".$userId);
	$leaveNotes = dl::select("flexi_al_notes", "n_template_id =".$userALTemplate[0]["user_al_template"], "");
	echo "<div class='timesheet_footer'><a href='index.php?func=previousperiod&start=".date("Y-m-d", strtotime($pSubStartDate))."&end=".date("Y-m-d", strtotime($pSubEndDate))."&userid=".$userId."'><img src='inc/images/arrow_left.jpg' border='0' align='middle' /></a> <a href='index.php?func=nextperiod&start=".date("Y-m-d", strtotime($pAddStartDate))."&end=".date("Y-m-d", strtotime($pAddEndDate))."&userid=".$userId."'><img src='inc/images/arrow_right.jpg' border='0' align='middle' /></a> ";
	if($authorise) {
		echo "<span title='Edit User Events' id='userEvents'><a href='index.php?func=edituserevents&userid=".$userId."&page=0'><img src='inc/images/small_pen_paper_icon.jpg' border='0' align='middle' /></a></span> <span id='view_leave' title='View User leave dates'><a href='index.php?func=showuserleave&userid=".$userId."'><img src='inc/images/leave.png' border='0' align='middle' /></a></span>";
		if(!empty($leaveNotes)) {
			echo " <span id='show_notes' style='cursor: pointer;' title='Leave Template Notes'> <img src='inc/images/text-edit.png' border='0' align='middle' /> </span>";
			?>
			<div id="notes-dialog2" title="Leave Template Notes" style="display: none; overflow-y: scroll;">
			<div class='leaveNote-title'>Date</div><div class='leaveNote-title'>Note</div><BR /><BR />
				
				<?php
				foreach($leaveNotes as $ln) {
					echo "<div class='note-date'>".$ln["n_date"]."</div><div class='leaveNote-message'>".nl2br($ln["n_note"])."</div>";
				}
				?>
				
			</div>
			<script>
			$("#show_notes").click(function(){
				$("#notes-dialog2").dialog({
					resizable: true,
	                height:300,
	                width: 500,
	                modal: true,
	                buttons: {
	                    "Close": function() {
	                        $( "#notes-dialog2" ).dialog( "close" );
	                    }
	            	}
				});
			});
			</script>
			<?php
		}
	}
	echo "</div>";
	echo "<div id='delConfirm'></div><div id='sortable-deleted' class='connected' title='Drag time here to delete'></div>";
	echo "<div id='poof'></div>";
	/*This is the portion to show team members leave in the currently displayed period*/
	//dl::$debug=true;
	if($authorise){
		showlocalteamleave::display_leave($userId, $eventStartDate, $eventEndDate);
	}
}

function teamleaveReport() {
	$user_id 				= $_SESSION["userSettings"]["userId"];
	$current_period 		= dl::select("flexi_template");
	$start 					= $current_period[0]["start_period"];
	$end					= $current_period[0]["end_period"];
	echo "<div class='timesheet_header'>TEAM LEAVE REPORT</div>";
	echo "<div id='display_leave'>";
	teamLeaveDisplay($start, $end, $user_id);
	echo "</div>";
}

function teamLeaveDisplay($start, $end, $user_id) {
	echo "<div class='timesheet_subheader'>Period - from [ ".$start." ] To [ ".$end." ]</div>";	
	echo "<div class='timesheet_footer'><img id='previousArr' src='inc/images/arrow_left.jpg' border='0' align='middle' /> | <img id='nextArr' src='inc/images/arrow_right.jpg' border='0' align='middle' /></div>";
	showallteamleave::display_leave($user_id, $start, $end);
	echo "</div>";

?>
<script>
		$("#previousArr").click(function() {
			var func = "p_period";
			var from = "<?php echo $start?>";
			var to   = "<?php echo $end?>";
			$.post(
				"ajax.php",
				{ func: func,
				from: from,
				to: to,
				},
				function (data)
				{
				$('#display_leave').html(data);
			});
		});
		$("#nextArr").click(function() {
			var func = "n_period";
			var from = "<?php echo $start?>";
			var to   = "<?php echo $end?>";
			$.post(
				"ajax.php",
				{ func: func,
				from: from,
				to: to,
				},
				function (data)
				{
				$('#display_leave').html(data);
			});
		});
</script>

<?php
}

function set_pixelSize ( $screenRes ) {
	if($screenRes 					> 1600) {
		$pixelSize 			= 14;
		$showDate 			= 3;
	}elseif($screenRes 			> 1439) {
		$pixelSize 			= 12;
		$showDate 			= 4;
	}elseif($screenRes 			> 1280) {
		$pixelSize 			= 10;
		$showDate 			= 4;
	}elseif($screenRes 			> 1080) {
		$pixelSize 			= 8;
		$showDate 			= 5;
	}elseif($screenRes 			> 1048) {
		$pixelSize 			= 8;
		$showDate 			= 7;
	}elseif($screenRes 			> 768) {
		$pixelSize 			= 6;
		$showDate 			= 8;
	}elseif($screenRes 			> 568) {
		$pixelSize 			= 7;
		$showDate 			= 8;
	}else{
		$pixelSize 			= 5;
		$showDate 			= 7;
	}
	return( array($pixelSize, $showDate) );
}

function add_messages_template() {
	
}

function reset_additional_leave() {
	// note this should only be ran once as it will cause issues if ran more that once. It uses the Additional leave to calculate an hourly value for the additional leave by using the max_surplus value
	// replacing the additional_leave value after multiplying it by the max_surplus value.
	//dl::$debug=true;
	$leave = dl::select("flexi_carried_forward_live");
	foreach( $leave as $l ) {
		$additional_leave = $l["additional_leave"];
		$timesheet = dl::select("flexi_timesheet", "timesheet_id = ". $l["timesheet_id"]);
		$user = dl::select("flexi_user", "user_id=".$timesheet[0]["user_id"]);
		$flexi_template = dl::select("flexi_template", "template_id = ".$user[0]["user_flexi_template"]);
		$value = $additional_leave * $flexi_template[0]["max_surplus"];
		echo $user[0]["user_name"]." ".$l["additional_leave"]." ".$flexi_template[0]["max_surplus"]." ".$value."<BR>";
		dl::update("flexi_carried_forward_live", array("additional_leave"=>$value), "carried_forward_id =".$l["carried_forward_id"]);
	}
}

function view_team_members() {
	//dl::$debug=true;
	if(!empty($_POST)) { //Team is a selection from the drop down list
		$team_name 			= $_POST["team_name"];
		$teamUser 				= dl::select("flexi_team", "team_name = '".$team_name."'");
		$teams 					= dl::select("flexi_team_user", "team_id = ".$teamUser[0]["team_id"]." and user_id <> 0");
	}else{
		$team_id 					= $_GET["team"];
		$team_name 			= dl::select("flexi_team", "team_id = ".$team_id);
		$teams 					= dl::select("flexi_team_user", "team_id = ".$team_id." and user_id <> 0");
	}
	foreach($teams as $tm) {
		$user_name 				= dl::select("flexi_user", "user_id = ".$tm["user_id"]);
		$userNames[] 			= array("user_name"=>$user_name[0]["user_name"], user_id=>$user_name[0]["user_id"]);
		$timesheet				= dl::select("flexi_timesheet", "user_id=".$user_name[0]["user_id"]);
		
		//get annual leave entitlement and used leave
		$leaveCheck 				= new check_leave($tm["user_id"]);
		$entitledTo 				= $leaveCheck->getLeaveEntitledTo();
		$nextYrLeave 			= $leaveCheck->getNextYrLeaveTaken();
		$hoursLeave				= $leaveCheck->getHoursLeave();
		$hoursTaken 			= $leaveCheck->getHoursTaken();
		$leaveAccountType 	= $leaveCheck->getLeaveAccountType();
		$proRataTime 			= $leaveCheck->getProRataTime();
		//now lets check if they have any additional holidays
		$additional				=0;
		$additionalHols			=dl::select("flexi_carried_forward_live", "timesheet_id=".$timesheet[0]["timesheet_id"]);
		$additional 				= $additionalHols[0]["additional_leave"];
		$leave[]						=array("days_taken"=>$used, "entitled_to"=>$entitledTo, "additional"=>$additional, "next_year"=>$nextYrLeave, "hours_leave"=>$hoursLeave, "hours_taken"=>$hoursTaken, "account_type"=>$leaveAccountType, "proRata"=>$proRataTime);
	}
	$leaveCount = 0;
	echo "<div class='timesheet_header'>Team Members</div>";
	echo "<table class='table_view'>";
	if(check_permission("View User Leave")) {
		echo "<tr><th>User Names</th><th>Hours Leave</th><th>Hours Taken</th><th>Additional Hours</th><th>Remaining</th><th>Next Year</th></tr>";
		foreach($userNames as $user) {
			$deleted = dl::select("flexi_deleted", "user_id=".$user["user_id"]);
			$remaining=round($leave[$leaveCount]["hours_leave"]+$leave[$leaveCount]["additional"]-$leave[$leaveCount]["hours_taken"], 2);
			if($leave[$leaveCount]["account_type"] == "Fulltime") {
				$days 				= " ( ".round($leave[$leaveCount]["hours_leave"]/$leave[$leaveCount]["proRata"], 1)." days )";
				$daysTaken  	= " ( ".round($leave[$leaveCount]["hours_taken"]/$leave[$leaveCount]["proRata"], 1)." days )";
				$additionalHours= " ( ".round($leave[$leaveCount]["additional"]/$leave[$leaveCount]["proRata"], 1)." days )";
				$remHours		= " ( ".round($remaining/$leave[$leaveCount]["proRata"],1 )." days )";
			}else{
				$days 				= "";
				$daysTaken  	= "";
				$additionalHours= "";
				$remHours		= "";
			}
			if(empty($deleted)){
				echo "<tr><td><a href='index.php?func=viewuserstimesheet&userid=".$user["user_id"]."'>".$user["user_name"]."</a></td><td>".round($leave[$leaveCount]["hours_leave"], 2).$days."</td><td>".round($leave[$leaveCount]["hours_taken"], 2).$daysTaken."</td><td>".round($leave[$leaveCount]["additional"], 2).$additionalHours."</td><td>".$remaining.$remHours."</td><td>".$leave[$leaveCount]["next_year"]."</td></tr>";
			}
			$leaveCount++;
		}
	}else{
		echo "<tr><th>User Names</th></tr>";
		foreach($userNames as $user) {
			$deleted = dl::select("flexi_deleted", "user_id=".$user["user_id"]);
			if(empty($deleted)){
				echo "<tr><td><a href='index.php?func=viewuserstimesheet&userid=".$user["user_id"]."'>".$user["user_name"]."</a></td></tr>";
			}
		}
	}
	echo "</table>";
}

function checkWeeklyHours($userId) {
	$userSettings = dl::select("flexi_user", "user_id=".$userId);
	$sql = "select * from flexi_template as t join flexi_template_name  as tn on (t.template_name_id=tn.flexi_template_name_id)
	join flexi_template_days as td on (tn.flexi_template_name_id=td.template_name_id)
	join flexi_template_days_settings as tds on (tds.template_days_id=td.flexi_template_days_id)
	where  t.template_id = ".$userSettings[0]["user_flexi_template"];
	$flexi = dl::getQuery($sql);
	$times = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$flexi[0]["days_settings_id"]);
	//now need to loop through the times
	foreach($times as $time) {
		$hours = substr($time["fdt_working_time"],0,2);
		$mins = substr($time["fdt_working_time"],3,2);
		if($time["fdt_weekday_id"] == 6) { // this highlights that all 5 days are this value so need to loop through 5 times and add up the mins and hours
			for($n=1; $n<6; $n++) {
				$weekTimeHrs+= $hours; 
				$weekTimeMins+=$mins;
			}
		}else{ // count the hours and minutes for each day.
			$weekTimeHrs+= $hours;
			$weekTimeMins+=$mins;
		}
	}
	while($weekTimeMins >= 60) {
		$weekTimeMins-=60;
		$weekTimeHrs++;
	}
	return(array("hours"=>$weekTimeHrs, "mins"=>$weekTimeMins));
}

function approve_leave() {
	if(check_permission("Team Authorise")) {

		$userId = $_SESSION["userSettings"]["userId"];
		$team = dl::select("flexi_team_user", "user_id=".$userId);
		$reqArr = "";
		foreach($team as $t) {
			$sql = "select * from flexi_requests as r 
			join flexi_event as e on (r.request_event_id=e.event_id) 
			join flexi_event_type as et on (e.event_type_id=et.event_type_id) 
			join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) 
			join flexi_user as u on (u.user_id=t.user_id)
			join flexi_team_user as tu on (t.user_id=tu.user_id)
			where u.user_id <> ".$_SESSION["userSettings"]["userId"]." and r.request_approved = '' and team_id=".$t["team_id"]." order by e.event_startdate_time DESC";
			$requests = dl::getQuery($sql);
			//get current users home team
			$homeTeam = dl::select("flexi_team_local", "team_user_id=".$t["team_user_id"]);
			if(!empty($homeTeam)) {
				$homeTeamId = $homeTeam[0]["team_user_id"];
			}
			foreach($requests as $request) {
				//now need to find out the users home team
				$user_teams = dl::select("flexi_team_user", "user_id=".$request["user_id"]);
				foreach($user_teams as $ut) {
					$user_homeTeam = dl::select("flexi_team_local", "team_user_id=".$ut["team_user_id"]);
					if(!empty($user_homeTeam)){
						$user_homeTeamId = $user_homeTeam[0]["team_user_id"];
					}
				}
				//now need to get the permission of the user to find out if they are a manager
				$sql = "select * from flexi_event as e
				join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) 
				join flexi_user as u on (t.user_id=u.user_id) 
				join flexi_permission_template as pt on (u.user_permission_id=pt.permission_template_name_id) 
				where event_id = ".$request["event_id"];
				$check_permission = dl::getQuery($sql);
				if($check_permission[0]["permission_team_authorise"]=="false") { //the permission of the requestor
					$reqArr[] = array(id=>$request["event_id"], startdate=>$request["event_startdate_time"], enddate=>$request["event_enddate_time"], timesheetId=>$request["timesheet_id"], "type"=>$request["event_type_name"], user=>$request["user_name"], userId=>$request["user_id"], team=>$request["team_id"]);
				}else{ //the requestor is a manager
					//need to check if the current user is in the requestors home team
					$inTeam = dl::select("flexi_team_user", "user_id = ".$request["user_id"]." and team_user_id = ".$user_homeTeamId);
					if(!empty($inTeam)) { //confirmed that the manager is responsible for the approval
						//check that the users team is managed by the manager
						$managed = dl::select("flexi_team_user", "user_id = ".$_SESSION["userSettings"]["userId"]." and team_id = ".$inTeam[0]["team_id"]);
						if(!empty($managed)){
							if($user_homeTeamId <> $homeTeamId) {
								$reqArr[] = array(id=>$request["event_id"], startdate=>$request["event_startdate_time"], enddate=>$request["event_enddate_time"], timesheetId=>$request["timesheet_id"], "type"=>$request["event_type_name"], user=>$request["user_name"], userId=>$request["user_id"], team=>$request["team_id"]);
							}
						}
					}
				}
			}
		}
		if($_GET["approveall"]=='true') {
			$checked='checked';
		}else{
			$checked='';
		}
		echo "<div class='timesheet_header'>Leave Approval</div>";
		echo "<form name='approve' action='index.php?func=confirmapproval' method='post'>";
		echo "<table class='table_view'>";
		echo "<tr><th>Approve</th><th>Refuse</th><th>Request From</th><th>Type</th><th>Annual Leave</th><th>Start</th><th>End</th><th>Reason for refusal</th><th>View</th></tr>";
		foreach($reqArr as $r) {
			//get annual leave entitlement and used leave
			$leave 						= new check_leave($r["userId"]);
			$entitledTo 				= $leave->getLeaveEntitledTo();
			$nextYrLeave 			= $leave->getNextYrLeaveTaken();
			$hoursLeave				= $leave->getHoursLeave();
			$hoursTaken 			= $leave->getHoursTaken();
			$leaveAccountType 	= $leave->getLeaveAccountType();
			$proRataTime 			= $leave->getProRataTime();
			//check if the holidays are from this years entitlement or next years
			$usersLeave 				= dl::select("flexi_user", "user_id=".$r["userId"]);
			$timesheet				= dl::select("flexi_timesheet", "user_id=".$usersLeave[0]["user_id"]);
			$check_date 			= dl::select("flexi_al_template", "al_template_id = ".$usersLeave[0]["user_al_template"]);
			$today 						= date("Y-m-d");
			$newLeaveDate 		= "1st ".$check_date[0]["al_start_month"]." ".date("Y");
			$remHours				= "";
			if($today < date("Y-m-d", strtotime($newLeaveDate)) and $r["startdate"] > date("Y-m-d", strtotime($newLeaveDate)) ) {
				$remaining = "From Next Year";
			}else{
				//now lets check if they have any additional holidays
				$additional			=0;
				$additionalHols		=dl::select("flexi_carried_forward_live", "timesheet_id=".$timesheet[0]["timesheet_id"]);
				$additional 			= $additionalHols[0]["additional_leave"];
				$remaining			=	$hoursLeave+$additional-$hoursTaken;
				
				if($leaveAccountType == "Fulltime") {
					$day = "days";
					if(round($remaining/$proRataTime,1 ) == 1) {
						$day = "day";
					}
					$remHours		= " ( ".round($remaining/$proRataTime,1 )." $day left )";
				}else{
					$remHours		= " hours";
				}
			}
			if(!in_array($r["id"],$searchArr)){
				echo "<tr><td style='text-align:center'><input type='checkbox' name='approve[]' value='".$r["id"]."' $checked></td><td style='text-align:center'><input type='checkbox' name='refuse[]' value='".$r["id"]."'></td><td>".$r["user"]."</td><td>".$r["type"]."</td><td>".$remaining.$remHours."</td><td>".substr($r["startdate"],0,16)."</td><td>".substr($r["enddate"],0,16)."</td><td><input type='text' size='50' name='message".$r["id"]."'><td style='text-align:center'><a href='index.php?func=showwk&startdate=".$r["startdate"]."&team=".$r["team"]."'><img src='inc/images/exclamation.png' border='0'></a></td></tr>";
			}
			$searchArr[] = $r["id"];
		}
		echo "</table>";
		echo "<br><a href='index.php?func=approveleave&approveall=true'>Approve All</a><p>";		
		echo "<input type='submit' value='Approve/Reject'>";
		echo "</form>";	
	}
}

function confirm_approval() {
	if(check_permission("Team Authorise")) {
		include("inc/email_messages.inc");

		//dl::$debug=true;
		foreach($_POST["approve"] as $approved) {
			$uppApp = dl::update("flexi_requests", array(request_approved=>"Yes"), "request_event_id = ".$approved);
			$sql = "select user_email, user_name, u.user_id, t.timesheet_id, team_id, event_startdate_time 
			from flexi_requests as r 
			join flexi_event as e on (r.request_event_id=e.event_id) 
			join flexi_event_type as et on (e.event_type_id=et.event_type_id) 
			join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) 
			join flexi_user as u on (t.user_id=u.user_id) 
			join flexi_team_user as tu on (u.user_id=tu.user_id) 
			left outer join flexi_team_local as tl on (tu.team_user_id=tl.team_user_id)
			left outer join flexi_deleted as d on (u.user_id=d.user_id) 
			where r.request_event_id = ".$approved." and date_deleted IS NULL and tl.team_user_id IS NOT NULL";
			$emails = dl::getQuery($sql);
			$teamId = $emails[0]["team_id"];
			$app=array(email=>$emails[0]["user_email"],user=>$emails[0]["user_name"], leaveDate=>$emails[0]["event_startdate_time"]);
			//get approvers email and name
			$sql="select * from flexi_team_user as tu  
			join flexi_user as u on (tu.user_id=u.user_id) 
			left outer join flexi_team_local as tl on (tu.team_user_id=tl.team_user_id)
			left outer join flexi_deleted as d on (u.user_id=d.user_id)   
			where tu.team_id=$teamId and date_deleted IS NULL and tl.local_team_id IS NULL";
			$authorise = dl::getQuery($sql);
			foreach($authorise as $auth) {
				$authoriser = dl::select("flexi_permission_template", "permission_template_name_id=".$auth["user_permission_id"]);
				if($authoriser[0]["permission_team_authorise"]=="true") {
					if(!in_array($auth["user_email"], $authEmail)) {
						$authEmail[] = $auth["user_email"];
						$authName[] = $auth["user_name"];
					}
				}
			}
			$m = new Mail();
			$AppSubject = $email_8_subject;
			$AppbodyText = $email_8_content;
			$AppbodyText = str_replace("%%whoto%%", $app["user"], $AppbodyText);
			$AppbodyText = str_replace("%%approver%%", $_SESSION["userSettings"]["name"], $AppbodyText);
			$AppbodyText = str_replace("%%date%%", substr($app["leaveDate"],0,10), $AppbodyText);
			$recipients = array($app["email"]);
			
			//send the email confirmation
			$recips=explode(", ", $recipients);
			$m->From( $_SESSION["userSettings"]["email"] ); // the first address in the recipients list is used as the from email contact and will receive emails in response to the registration request.
			$m->autoCheck(false);
			$m->To( $recipients );
			$m->Subject( $AppSubject );
			$m->Body( $AppbodyText );
			$m->CC($authEmail);
			$m->Priority(3);
			$m->Send();
		}
		$refCount=0;
		foreach($_POST["refuse"] as $refused) {
			$uppApp = dl::update("flexi_requests", array(request_approved=>"No"), "request_event_id = ".$refused);
			$sql = "select user_email, u.user_name, t.timesheet_id, team_id, event_startdate_time from flexi_requests as r 
			join flexi_event as e on (r.request_event_id=e.event_id) 
			join flexi_event_type as et on (e.event_type_id=et.event_type_id) 
			join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) 
			join flexi_user as u on (t.user_id=u.user_id) 
			join flexi_team_user as tu on (tu.user_id=u.user_id) 
			left outer join flexi_team_local as tl on (tu.team_user_id=tl.team_user_id)
			left outer join flexi_deleted as d on (u.user_id=d.user_id)
			where r.request_event_id = ".$refused." and date_deleted IS NULL and tl.team_user_id IS NOT NULL";
			$emails = dl::getQuery($sql);
			$teamId = $emails[0]["team_id"];
			$ref=array(email=>$emails[0]["user_email"],user=>$emails[0]["user_name"],message=>$_POST["message".$refused], leaveDate=>$emails[0]["event_startdate_time"]);
			//get approvers email and name
			$sql="select * from flexi_team_user as tu  
			join flexi_user as u on (tu.user_id=u.user_id) 
			left outer join flexi_team_local as tl on (tu.team_user_id=tl.team_user_id)
			left outer join flexi_deleted as d on (u.user_id=d.user_id)   
			where tu.team_id=$teamId and date_deleted IS NULL and tl.local_team_id IS NULL";
			$authorise = dl::getQuery($sql);
			foreach($authorise as $auth) {
				$authoriser = dl::select("flexi_permission_template", "permission_template_name_id=".$auth["user_permission_id"]);
				if($authoriser[0]["permission_team_authorise"]=="true") {
					if(!in_array($auth["user_email"], $authEmail)) {
						$authEmail[] = $auth["user_email"];
						$authName[] = $auth["user_name"];
					}
				}
			}
			// need to send a message to the user
			$RefSubject = $email_9_subject;
			$RefbodyText = $email_9_content;
			$RefbodyText = str_replace("%%whoto%%", $ref["user"], $RefbodyText);
			$RefbodyText = str_replace("%%MESSAGE%%", $ref["message"], $RefbodyText);
			$RefbodyText = str_replace("%%date%%", substr($ref["leaveDate"],0,10), $RefbodyText);
			$RefbodyText = str_replace("%%approver%%", $_SESSION["userSettings"]["name"], $RefbodyText);
			$recipients = array($ref["email"]);
			//send the email confirmation
			$recips=explode(", ", $recipients);
			$m = new Mail();
			$m->From( $_SESSION["userSettings"]["email"] ); // the first address in the recipients list is used as the from email contact and will receive emails in response to the registration request.
			$m->autoCheck(false);
			$m->To( $recipients );
			$m->Subject( $RefSubject );
			$m->Body( $RefbodyText );
			$m->CC($authEmail);
			$m->Priority(3);
			$m->Send();
			//now delete the refused leave event
			dl::delete("flexi_event", "event_id=".$refused);
		}
	} 
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=timesheet')</SCRIPT>" ;
}

function view_your_requests() {
	$userId = $_SESSION["userSettings"]["userId"];
	;
	$sql = "select * from flexi_requests as r join flexi_event as e on (r.request_event_id=e.event_id) join flexi_event_type as et on (e.event_type_id=et.event_type_id) join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) join flexi_user as u on (t.user_id=u.user_id) where r.request_approved = '' and u.user_id =".$userId." order by e.event_startdate_time DESC";
	$requests = dl::getQuery($sql);
	echo "<div class='timesheet_header'>Your Requests</div>";
	echo "<table class='table_view'>";
	echo "<tr><th>Requester</th><th>Type</th><th>Start</th><th>End</th></tr>";
	foreach($requests as $r) {
		echo "<tr><td>".$r["user_name"]."</td><td>".$r["event_type_name"]."</td><td>".substr($r["event_startdate_time"],0,16)."</td><td>".substr($r["event_enddate_time"],0,16)."</td></tr>";
	}
	echo "</table>";
}

function show_week() {
	//determine week parameters
	$startDate = $_GET["startdate"];
	$teamId = $_GET["team"];
	$weekday = date("l",strtotime($startDate));
	switch($weekday) {
		case "Monday":
			$startWeek = sub_date(strtotime($startDate),1);
			break;
		case "Tuesday":
			$startWeek = sub_date(strtotime($startDate),2);
			break;
		case "Wednesday":
			$startWeek = sub_date(strtotime($startDate),3);
			break;
		case "Thursday":
			$startWeek = sub_date(strtotime($startDate),4);
			break;
		case "Friday":
			$startWeek = sub_date(strtotime($startDate),5);
			break;
		case "Saturday":
			$startWeek = sub_date(strtotime($startDate),6);
			break;
		case "Sunday":
			$startWeek = sub_date(strtotime($startDate),7);
			break;	
	}
	//startWeek is the date of the sunday, the end of last week.
	$endWeek = add_date(strtotime($startWeek),7);
	$sql = "select * from flexi_event as e 
	join flexi_event_type as et on (e.event_type_id=et.event_type_id) 
	join flexi_requests  as r on (request_event_id=e.event_id) 
	join flexi_timesheet as t on (t.timesheet_id=e.timesheet_id) 
	join flexi_user as u on (t.user_id=u.user_id) 
	join flexi_team_user as tu on (tu.user_id=u.user_id) 
	join flexi_team as ft on (tu.team_id=ft.team_id)
	where e.event_startdate_time > '".$startWeek."' and e.event_startdate_time <= '".$endWeek."' and tu.team_id =".$teamId." and event_type_name <> 'Working Session' order by e.event_startdate_time DESC";
	$weekRecs = dl::getQuery($sql);
	echo "<div class='timesheet_header'>Week comparison for ".$weekRecs[0]["team_name"]." team</div>";
	echo "From ".substr(add_date(strtotime($startWeek),1),0,10)." to ".substr($endWeek,0,10)."<br>";
	echo "<table class='table_view'>";
	echo "<tr><th>Start Date</th><th>End Date</th><th>Requested By</th><th>Event Type</th><th>Approved</th></tr>";
	foreach($weekRecs as $wRec) {
		echo "<tr><td>".$wRec["event_startdate_time"]."</td><td>".$wRec["event_enddate_time"]."</td><td>".$wRec["user_name"]."</td><td>".$wRec["event_type_name"]."</td><td>".$wRec["request_approved"]."</td></tr>";
	}
	echo "</table>";
}

function reset_pass($pEmail) {
	include('inc/email_messages.inc');
	//find the username from the email address
	$userName = dl::select("flexi_user","user_email='".$pEmail."'");
	//now need to email the new user and provide a link with an encrypted passcode to reset their password
	$m = new Mail();
	$encrypto = MD5(SALT.$pEmail);
	$subject = $email_4_subject;
	$bodyText = $email_4_content;
	$bodyText = str_replace("%%whoto%%", $userName[0]["user_name"], $bodyText);
	$bodyText = str_replace("%%link%%", $encrypto, $bodyText);
	$recipients = array($pEmail);
	//send the email confirmation
	$recips=explode(", ", $recipients);
	$m->From( "fws@ncl.ac.uk" ); // the first address in the recipients list is used as the from email contact and will receive emails in response to the registration request.
	$m->autoCheck(false);
	$m->To( $recipients );
	$m->Subject( $subject );
	$m->Body( stripslashes($bodyText) );
	$m->Priority(3);
	$m->Send();
	echo "<SCRIPT language='javascript'>redirect('index.php')</SCRIPT>" ;	
}

function reset_password($passcode) {
	$formArr = array(array("type"=>"intro", "formtitle"=>"Reset your Password", "formintro"=>"Enter your email address and password to change your password."), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=changePassword&passcode=".$_GET["passcode"],"method"=>"post")),
			array("prompt"=>"Email Address", "type"=>"text", "name"=>"email_address", "length"=>20, "value"=>""), 
			array("prompt"=>"Password", "type"=>"password", "name"=>"password", "length"=>20, "value"=>""),
			array("prompt"=>"retype Password", "type"=>"password", "name"=>"password2", "length"=>20, "value"=>""),
			array("type"=>"submit", "buttontext"=>"Change Password"), 
			array("type"=>'endform'));
		$form = new forms;
		echo $form->create_form($formArr, "120px");
}

function change_password($post, $passcode) {
	//firstly check the password are the same
	if($post["password"]!=$post["password2"]) {
		?>
		<SCRIPT language="javascript">
		alert("PASSWORD ERROR!!!\n\nThe passwords you entered do not match! \n\nPlease reclick the link in the security email that was sent to you to retry the change of password you require. \n\n");
		redirect("index.php?func=resetPass");
		</SCRIPT>
		<?php die();
	}
	if($passcode==MD5(SALT.$post["email_address"])) { //everything confirmed
		// now need to locate user account and add password to security table and update user table
		$user = dl::select("flexi_user", "user_email='".$post["email_address"]."'");
		$user_id=$user[0]["user_id"];
		$security = dl::update("flexi_security", array(security_password=>MD5(SALT.$post["password"])), "user_id=".$user_id);
	}
	echo "<SCRIPT language='javascript'>redirect('index.php')</SCRIPT>" ;
}

function add_user($title, $intro) {
	if(check_permission("User")) {

		//add permission template names to provide drop down selection
		$permissions = dl::select("flexi_permission_template_name");
		foreach($permissions as $perms) {
			$aPermission[]=$perms["permission_template_name"];
		}
		//add Flexi template names to provide drop down selection
		$flexi = dl::select("flexi_template_name");
		foreach($flexi as $flex) {
			$aFlexi[]=$flex["description"];
		}
		//add time template names to provide drop down selection
		$times = dl::select("flexi_time_template_name");
		foreach($times as $time) {
			$aTime[]=$time["time_template_name"];
		}
		//add teams to provide multiple selection
		$teams = dl::select("flexi_team", "", "team_name");
		foreach($teams as $team) {
			$aTeams[]=$team["team_name"];
		}
		//add annual leave template names to provide drop down selection
		$altemp = dl::select("flexi_al_template");
		foreach($altemp as $al) {
			$aAl[]=$al["al_description"];
		}
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>$title, "formintro"=>$intro), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveuser","method"=>"post")),	
			array("prompt"=>"Name", "type"=>"text", "name"=>"name", "length"=>20, "value"=>"Enter the users name", "clear"=>true),	
			array("prompt"=>"Email Address", "type"=>"text", "name"=>"email", "length"=>30, "value"=>"Enter the users email address", "clear"=>true),
			array("prompt"=>"Email Compare", "type"=>"textcompare", field=>"email", "name"=>"compare", message=>"The email addresses are different. Please Check!", "length"=>30, "value"=>"retype email address", "clear"=>true),
			array("prompt"=>"User Type", "type"=>"selection", "name"=>"permission", "listarr"=>$aPermission, "selected"=>"User", "value"=>"", "clear"=>true),
			array("prompt"=>"Flexi Template", "type"=>"selection", "name"=>"flexitemp", "listarr"=>$aFlexi, "selected"=>"Standard Flexi Template", "value"=>"", "clear"=>true),
			array("prompt"=>"Time Template", "type"=>"selection", "name"=>"timetemp", "listarr"=>$aTime, "selected"=>"Standard Time Template", "value"=>"", "clear"=>true),
			array("prompt"=>"Select Teams", "type"=>"selection", "name"=>"teams[]", "listarr"=>$aTeams, multiple=>true, "selected"=>"", "value"=>"", "clear"=>true),
			array("prompt"=>"Select Local Team", "type"=>"selection", "name"=>"localteam", "listarr"=>$aTeams, "selected"=>"", "value"=>"", "clear"=>true),
			array("prompt"=>"Annual Leave", "type"=>"selection", "name"=>"al", "listarr"=>$aAl, "selected"=>"", "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Add user", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
	}
}


function save_user() {
	include("inc/email_messages.inc");
	;
	//dl::$debug=true;
	//check the templates to get the id's
	$flexi = dl::select("flexi_template_name", "description='".$_POST["flexitemp"]."'");
	$time = dl::select("flexi_time_template_name", "time_template_name='".$_POST["timetemp"]."'");
	$al = dl::select("flexi_al_template", "al_description='".$_POST["al"]."'");
	$perms = dl::select("flexi_permission_template_name","permission_template_name='".$_POST["permission"]."'");
	$al_id = $al[0]["al_template_id"];
	$flexi_id = $flexi[0]["flexi_template_name_id"];
	$time_id = $time[0]["time_template_name_id"];
	$perm_id =$perms[0]["permission_id"];
	$fieldarr = array("user_name", "user_email", "user_permission_id","user_al_template","user_time_template","user_flexi_template");
	$postarr = array($_POST["name"],$_POST["email"],$perm_id, $al_id, $time_id, $flexi_id);
	$save = array_combine($fieldarr, $postarr);
	dl::insert("flexi_user", $save);
	//saved the template name now need to get the user id
	$get_id = dl::select("flexi_user", "user_email = '".$_POST['email']."'");
	foreach($get_id as $id) {
		$fieldId = $id["user_id"];
	}
	//now create timesheet location
	$fieldarr= array("user_id");
	$postarr= array($fieldId);
	$save=array_combine($fieldarr, $postarr);
	dl::insert("flexi_timesheet", $save);
	//need timesheet id to add global events to the new user's timesheet
	$timesheetId = dl::select("flexi_timesheet", "user_id=".$fieldId);
	// check the post for all of the teams associated with this user
	$teams = $_POST["teams"];
	foreach($teams as $team) {
		//find the team id's
		$teamId = dl::select("flexi_team", "team_name='".$team."'");
		$fieldarr = array("user_id", "team_id");
		$postarr = array($fieldId, $teamId[0]["team_id"]);
		$aTeam = array_combine($fieldarr, $postarr);
		dl::insert("flexi_team_user", $aTeam);
		//check the global events belonging to the team and add to the users timesheet
		$global_teams = dl::select("flexi_global_teams", "team_id=".$teamId[0]["team_id"]);
		foreach($global_teams as $gt) {
			$global_events = dl::select("flexi_global_events", "global_id=".$gt["global_id"]);
			if(!empty($global_events)) { //create the global event if it doesn't already exist
				$date = $global_events[0]["event_date"]; //format YYYY-mm-dd
				$eventType = $global_events[0]["event_type_id"];
				//now get the full day duration from the flexi_template
				$sql = "select * from flexi_user as u 
				join flexi_template as t on (u.user_flexi_template=t.template_id) 
				join flexi_template_name as tn on (t.template_name_id=tn.flexi_template_name_id)
				join flexi_template_days as td on (td.template_name_id=tn.flexi_template_name_id) 
				join flexi_template_days_settings as tds on (td.flexi_template_days_id=tds.template_days_id) where u.user_id = ".$fieldId;
				$templates = dl::getQuery($sql);
				//now check flexi_day_times for the user
				//get day_settings template id
				$settingsID = $templates[0]["days_settings_id"];
				$dayNumber = date("N", strtotime($date)); //this returns the day number 1=Monday 5=Friday
				//check the flexi_day_times table for the users template settings
				$matchTemplate = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$settingsID, "fdt_weekday_id ASC");
				$fullDay = "";
				if($matchTemplate[0]["fdt_weekday_id"] == 6 ) { //then the template applies to every day
					$fullDay = $matchTemplate[0]["fdt_working_time"];
				}else{
					foreach($matchTemplate as $mt){ //check all of the returned times to see if the user template matches the date of the global event
						if( $mt["fdt_weekday_id"] == $dayNumber ) { //day has been matched
							$fullDay = $mt["fdt_working_time"];
						}
					}
				}
				if(!empty($fullDay)) { //if matched then add the day else skip to the next global event
					$startTime = "09:00:00";
					$startTimeSecs = substr($startTime,0,2) * 60 * 60 + substr($startTime,3,2) * 60 + substr($startTime,6,2) * 60;
					$endTimeSecs = substr($fullDay,0,2) * 60 * 60 + substr($fullDay,3,2) * 60 + substr($fullDay,6,2) * 60;
					if($templates[0]["minimum_lunch"] == "Yes"){
						$lunch = $templates[0]["minimum_lunch_duration"];
						$minLunchSecs = substr($lunch,0,2) * 60 * 60 + substr($lunch,3,2) * 60 + substr($lunch,6,2) * 60;
					}else{
						$minLunchSecs = 0;
					}
					$endTime = date("H:i:s", $startTimeSecs + $endTimeSecs+$minLunchSecs);
					$startDateTime = $date." ".$startTime;
					$endDateTime = $date." ".$endTime;
					//check to see if the event has already been entered and does not overlap any other time/leave etc.
					$checkEntered = dl::select("flexi_event", "(substr(event_startdate_time,1,10) = '".substr($startDateTime,0,10)."' and substr(event_enddate_time,1,10) = '".substr($startDateTime,0,10)."') and timesheet_id=".$timesheetId[0]["timesheet_id"]);
					if(empty($checkEntered)) {
						//all information extracted and record doesn't already exist just write the record
						$fieldArr = array("timesheet_id", "event_startdate_time", "event_enddate_time", "event_type_id");
						$valuesArr = array($timesheetId[0]["timesheet_id"], $startDateTime, $endDateTime, $eventType);
						$writeArr = array_combine($fieldArr, $valuesArr);
						dl::insert("flexi_event",$writeArr);	
					}
				}
			}
		}
	}
	//set the local team for the new user.
	$teamId = dl::select("flexi_team", "team_name='".$_POST["localteam"]."'");
	$teamUser = dl::select("flexi_team_user", "team_id=".$teamId[0]["team_id"]." and user_id=".$fieldId);
	$fieldarr= array("team_user_id");
	$postarr= array($teamUser[0]["team_user_id"]);
	$aTeamUser = array_combine($fieldarr, $postarr);
	dl::insert("flexi_team_local", $aTeamUser);
	//now need to email the new user and provide a link with an encrypted passcode
	$m = new Mail();
	$encrypto = MD5(SALT.$_POST["email"]);
	$subject = $email_5_subject;
	$bodyText = $email_5_content;
	$bodyText = str_replace("%%whoto%%", $_POST["name"], $bodyText);
	$bodyText = str_replace("%%link%%", $encrypto, $bodyText);
	$recipients = array($_POST["email"]);
	
	//send the email confirmation
	$recips=explode(", ", $recipients);
	$m->From( "fws@ncl.ac.uk" ); // the first address in the recipients list is used as the from email contact and will receive emails in response to the registration request.
	$m->autoCheck(false);
	$m->To( $recipients );
	$m->Subject( $subject );
	$m->Body( $bodyText );
	$m->Priority(3);
	$m->Send();
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Edit&subchoice=edituser')</SCRIPT>" ;
}

function view_user($page=0) {
	$rows = 28;
	echo "<div class='timesheet_header'>Edit Users</div>";
	$sql = "select u.user_id, user_name, date_deleted from flexi_user as u left outer join flexi_deleted as d on (u.user_id = d.user_id) where date_deleted is NULL ORDER BY user_name ASC LIMIT $page, $rows ";
	$users = dl::getQuery($sql);
	$sql = "select count(u.user_id) as num from flexi_user as u left outer join flexi_deleted as d on (u.user_id = d.user_id) where date_deleted is NULL";
	$numRows = dl::getQuery($sql);
	$rowCount = $numRows[0]["num"];
	echo "<table class='table_view'>";
	echo "<tr><th>User Names</th><th>Delete</th><th>Edit</th></tr>";
	foreach($users as $user) {
		echo "<tr><td>".$user["user_name"]."</td><td><a href='index.php?func=deleteuser&id=".$user["user_id"]."'>delete</a></td><td><a href='index.php?func=edituser&id=".$user["user_id"]."'>edit</a></td></tr>";
	}
	echo "</table>";
	$page+=28;
	if($page > 28 ){
		$prevPage = $page - 56;
		$previous = true;
	}
	if($page < $rowCount) {
		echo "<BR>&nbsp;<a href='index.php?choice=Edit&subchoice=edituser&page=$page'>Next Page</a>";
	}else{
		echo "<br>";
	}
	if($previous){
		echo "&nbsp;<a href='index.php?choice=Edit&subchoice=edituser&page=$prevPage'>Previous Page</a>";
	}
}

function edit_user() {
	if(check_permission("User")) {

		//dl::$debug=true;
		//add permission template names to provide drop down selection
		$permissions = dl::select("flexi_permission_template_name");
		foreach($permissions as $perms) {
			$aPermission[]=$perms["permission_template_name"];
		}
		//add Flexi template names to provide drop down selection
		$flexi = dl::select("flexi_template_name");
		foreach($flexi as $flex) {
			$aFlexi[]=$flex["description"];
		}
		//add time template names to provide drop down selection
		$times = dl::select("flexi_time_template_name");
		foreach($times as $time) {
			$aTime[]=$time["time_template_name"];
		}
		//add teams to provide multiple selection
		$teams = dl::select("flexi_team", "", "team_name");
		foreach($teams as $team) {
			$aTeams[]=$team["team_name"];
		}
		//add annual leave template names to provide drop down selection
		$altemp = dl::select("flexi_al_template");
		foreach($altemp as $al) {
			$aAl[]=$al["al_description"];
		}
		$users = dl::select("flexi_user", "user_id=".$_GET["id"]);
		$permissions = dl::select("flexi_permission_template_name", "permission_id=".$users[0]["user_permission_id"]);
		$annualLeave = dl::select("flexi_al_template", "al_template_id=".$users[0]["user_al_template"]);
		$flexiTemplate = dl::select("flexi_template_name", "flexi_template_name_id=".$users[0]["user_flexi_template"]);
		$timeTemplate = dl::select("flexi_time_template_name", "time_template_name_id=".$users[0]["user_time_template"]);
		$teams = dl::select("flexi_team_user", "user_id=".$_GET["id"]);
		
		foreach($teams as $team) {
			$teamname = dl::select("flexi_team", "team_id=".$team["team_id"]);
			$teamNames[]= $teamname[0]["team_name"];
		}
		$sql = "select * from flexi_team_user as tu
		join flexi_team_local as tl on(tl.team_user_id=tu.team_user_id)
		where tu.user_id = ".$_GET["id"]." and tl.team_user_id = tu.team_user_id";
		$localTeam = dl::getQuery($sql);
		$localTeamName = dl::select("flexi_team", "team_id=".$localTeam[0]["team_id"]);
		$annualLeave[0]["al_description"];
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>$title, "formintro"=>$intro), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveuseredit&id=".$_GET["id"],"method"=>"post")),	
			array("prompt"=>"Name", "type"=>"text", "name"=>"name", "length"=>20, "value"=>$users[0]["user_name"], "clear"=>true),	
			array("prompt"=>"Email Address", "type"=>"text", "name"=>"email", "length"=>30, "value"=>$users[0]["user_email"], "clear"=>true),
			array("prompt"=>"Email Compare", "type"=>"textcompare", field=>"email", "name"=>"compare", message=>"The email addresses are different. Please Check!", "length"=>30, "value"=>$users[0]["user_email"], "clear"=>true),
			array("prompt"=>"User Type", "type"=>"selection", "name"=>"permission", "listarr"=>$aPermission, "selected"=>$permissions[0]["permission_template_name"], "value"=>"", "clear"=>true),
			array("prompt"=>"Flexi Template", "type"=>"selection", "name"=>"flexitemp", "listarr"=>$aFlexi, "selected"=>$flexiTemplate[0]["description"], "value"=>"", "clear"=>true),
			array("prompt"=>"Change Applies on", "type"=>"date", "name"=>"date_change", "length"=>20, "value"=>"", "clear"=>true),
			array("prompt"=>"Time Template", "type"=>"selection", "name"=>"timetemp", "listarr"=>$aTime, "selected"=>$timeTemplate[0]["time_template_name"], "value"=>"", "clear"=>true),
			array("prompt"=>"Select Teams", "type"=>"selection", "name"=>"teams[]", "listarr"=>$aTeams, multiple=>true, "selected"=>$teamNames, "value"=>"", "clear"=>true),
			array("prompt"=>"Select Local Team", "type"=>"selection", "name"=>"localteam", "listarr"=>$aTeams, "selected"=>$localTeamName[0]["team_name"], "value"=>"", "clear"=>true),
			array("prompt"=>"Annual Leave", "type"=>"selection", "name"=>"al", "listarr"=>$aAl, "selected"=>$annualLeave[0]["al_description"], "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Edit user", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
	}
}

function save_user_edit() {
	$user_details = dl::select("flexi_user", "user_id=".$_GET["id"]);
	$timesheet = dl::select("flexi_timesheet", "user_id=".$_GET["id"]);
	//check the templates to get the id's
	$flexi = dl::select("flexi_template_name", "description='".$_POST["flexitemp"]."'");
	$time = dl::select("flexi_time_template_name", "time_template_name='".$_POST["timetemp"]."'");
	$al = dl::select("flexi_al_template", "al_description='".$_POST["al"]."'");
	$perms = dl::select("flexi_permission_template_name","permission_template_name='".$_POST["permission"]."'");
	$al_id = $al[0]["al_template_id"];
	$flexi_id = $flexi[0]["flexi_template_name_id"];
	$time_id = $time[0]["time_template_name_id"];
	$perm_id =$perms[0]["permission_id"];
	//lets check to see if the flexi template has changed. If it has then the day_duration may have changed meaning that all of the future events for this user need updating.
	if($flexi_id <> $user_details[0]["user_flexi_template"]) { //confirmed a change has happened to the flexi template
		$changeDate = $_POST["date_change"]." ".date("H:i:s", mktime(0,0,0,0,0,0));
		$dates2Change = dl::select("flexi_event", "timesheet_id=".$timesheet[0]["timesheet_id"]." and event_startdate_time > '$changeDate'");
		if(!empty($dates2Change)) {
			foreach($dates2Change as $dchange) {
				//get the days settings template details
				$sql = "select * from flexi_template_name 
				join flexi_template_days on (template_name_id=flexi_template_name_id)
				join flexi_template_days_settings on (flexi_template_days_id=template_days_id)
				where description='".$_POST["flexitemp"]."'";
				echo "<BR>".$sql;
				$settings = dl::getQuery($sql);
				$times = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$settings[0]["days_settings_id"]);
				$dayFound = false; //check if the day has been found in flexi_day_times
				if($times[0]["fdt_weekday_id"] == 6) { //this means it applies to all events
					$dayDuration = $times[0]["fdt_working_time"];
					$dayFound = true;
				}else{ //this means we have to find the day and time for the particular day
					//find out what day the event is on
					$dayNumber = date("N", strtotime($dchange["event_startdate_time"]));
					echo "<BR>Day Number = ".$dayNumber;
					$findTime = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$settings[0]["days_settings_id"]." and fdt_weekday_id =".$dayNumber);
					if(!empty($findTime)) {
						$dayDuration = $findTime[0]["fdt_working_time"];
						$dayFound = true;
					}else{
						$dayFound = false; //this is an event that has not been matched within the new template therefore it should be removed
					}
				}
				echo "<BR>day duration = $dayDuration<BR>";
				$fullorhalf =  dl::select("flexi_leave_count", "flc_event_id = ". $dchange["event_id"]);
				if(empty($fullorhalf)) { //some other leave than annual leave which still needs to be changed
					$forh = 1;
				}else{
					$forh = $fullorhalf[0]["flc_fullorhalf"];
				}
				if($dayFound) { // all is good update the event
					$endHour = substr($dayDuration,0,2);
					$endMin = substr($dayDuration,3,2);
					$endSec = substr($dayDuration,6,2);
					$lunch_secs = 0;
					if($endHour >=6) {
						//need to add the minimum lunch
						if($settings[0]["minimum_lunch"] == "Yes") {
							$lunch_duration = $settings[0]["minimum_lunch_duration"];
							$lunchHour = substr($lunch_duration,0,2);
							$lunchMin = substr($lunch_duration,3,2);
							$lunchSec = substr($lunch_duration,6,2);
							$lunch_secs = $lunchHour*60*60 + $lunchMin*60 + $lunchSec;
						}
					}
					if(!empty($endHour)) { //looks like all is well make the change
						$endDateTime = strtotime($dchange["event_startdate_time"]) + ($endHour*60*60+$endMin*60+$endSec) * $forh + $lunch_secs;
						$endDateTime = date("Y-m-d H:i:s",$endDateTime);
						$updArr = array("event_enddate_time");
						$valueArr = array($endDateTime);
						$writeArr = array_combine($updArr,$valueArr);
						//now need to check the event type is not a working event
						$eventType = dl::select("flexi_event_type", "event_type_id = ".$dchange["event_type_id"]);
						if( $eventType[0]["event_work"] == "No" ) {
							dl::update("flexi_event", $writeArr, "event_id=".$dchange["event_id"]);
						}
					}
				}else{ //no time record has been found in flexi_day-times so this particular day has been removed from the template and as such so should the event
					dl::delete("flexi_event", "event_id = ".$dchange["event_id"]);
					dl::delete("flexi_leave_count", "flc_event_id = ". $dchange["event_id"]);
				}
			}
			echo "<SCRIPT language='javascript'>alert('You have made changes to this users working hours. All leave and global events have been changed to the new working contract. If this is a backdated change and the user has worked part of their new contract as their old contract then you must check that all events are correct. No working events will have been changed but other types of event not previously mentioned will need checking.');</SCRIPT>" ;
			/*need to record this change so as to be able to report on past flexi periods accurately keeping the old day duration and weekly hours so as to keep the 
			flexi carried over information correct. This will be used by the timesheet view to display the correct weekly hours from the old template*/
			$updArr = array("old_template_id", "change_date", "timesheet_id");
			$valueArr = array($user_details[0]["user_flexi_template"], $changeDate, $timesheet[0]["timesheet_id"]);
			$writeArr = array_combine($updArr, $valueArr);
			dl::insert("flexi_time_changes", $writeArr);
		}
	}
	$fieldarr = array("user_name", "user_email", "user_permission_id","user_al_template","user_time_template","user_flexi_template");
	$postarr = array($_POST["name"],$_POST["email"],$perm_id, $al_id, $time_id, $flexi_id);
	$save = array_combine($fieldarr, $postarr);
	dl::update("flexi_user", $save, "user_id=".$_GET["id"]);
	//saved the template name now need to get the user id
	$get_id = dl::select("flexi_user", "user_email = '".addslashes($_POST['email'])."'");
	foreach($get_id as $id) {
		$fieldId = $id["user_id"];
	}
	// check the post for all of the teams associated with this user
	$teams = $_POST["teams"];
	//delete also the local team as this may have changed
	$localTeam = dl::select("flexi_team_user", "user_id=".$fieldId);
	foreach($localTeam as $lt) {
		$found=dl::select("flexi_team_local", "team_user_id=".$lt["team_user_id"]);
		if(!empty($found)) {
			dl::delete("flexi_team_local", "team_user_id=".$lt["team_user_id"]);	
		}
	}
	//now need to delete all of the teams associated and then add the new ones
	dl::delete("flexi_team_user", "user_id=".$fieldId);
	foreach($teams as $team) {
		//find the team id's
		$teamId = dl::select("flexi_team", "team_name='".$team."'");
		$fieldarr = array("user_id", "team_id");
		$postarr = array($fieldId, $teamId[0]["team_id"]);
		$aTeam = array_combine($fieldarr, $postarr);
		dl::insert("flexi_team_user", $aTeam);
	}
	//now add the new local team
	//set the local team for the new user.
	$teamId = dl::select("flexi_team", "team_name='".$_POST["localteam"]."'");
	$teamUser = dl::select("flexi_team_user", "team_id=".$teamId[0]["team_id"]." and user_id=".$fieldId);
	$fieldarr= array("team_user_id");
	$postarr= array($teamUser[0]["team_user_id"]);
	$aTeamUser = array_combine($fieldarr, $postarr);
	dl::insert("flexi_team_local", $aTeamUser);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Edit&subchoice=edituser');</SCRIPT>" ;
}

function delete_user($userId) { //sets a delete flag for a user then checks the time template to decide when to delete the users information.
	;
	dl::insert("flexi_deleted", array(user_id=>$userId, date_deleted=>date("Y-m-d")));
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Edit&subchoice=edituser');</SCRIPT>" ;
}

function check_for_deletions() {
	//dl::$debug=true;
	$deletions = dl::select("flexi_deleted");
	foreach($deletions as $del) {
		$deleteDate = $del["date_deleted"];
		$userId = $del["user_id"];
		//check the time template for the deletion time.
		$sql = "select * from flexi_user as u
		join flexi_time_template_name as tn on (u.user_time_template=time_template_name_id)
		join flexi_time_template as t on (tn.time_template_name_id=t.time_template_name_id)
		where u.user_id=".$userId;
		$delWhen = dl::getQuery($sql);
		$templateDelete = $delWhen[0]["time_template_delete"];
		switch($templateDelete) {
			case "Never":
			break;
			case "After 1 month":
				$dateCheck = add_date(strtotime($deleteDate),0,1);
			break;
			case "After 3 months":
				$dateCheck = add_date(strtotime($deleteDate),0,3);
			break;
			case "After 6 months":
				$dateCheck = add_date(strtotime($deleteDate),0,6);
			break;
			case "After 1 year":
				$dateCheck = add_date(strtotime($deleteDate),0,0,1);
			break;
		}
		if(date("Y-m-d") > $dateCheck) {
			$delArr[] = $userId;
		}
	}
	return $delArr;
}

function add_time($title, $intro) {
	if(check_permission("Add Time")) {

		//dl::$debug=true;
		// require to retrieve template settings
		if(!empty($_GET["date"]) ){
			$dateVal = $_GET["date"];
		}else{
			$dateVal = "";	
		}
		$sql = "select * from flexi_user join flexi_template as ft on (user_flexi_template=template_id) join flexi_template_days as ftd on (ftd.template_name_id=ft.template_name_id) join flexi_template_days_settings as ftds on (ftds.template_days_id=ftd.flexi_template_days_id) where user_id = ".$_SESSION["userSettings"]["userId"];
		$event_settings = dl::getQuery($sql);
		//now lets find the default event ie. Working Session
		$event = dl::select("flexi_event_type", "event_work='Yes'");
		$default_event = $event[0]["event_type_id"];
		$default_event_description = $event[0]["event_type_name"];
		// now need to retrieve the event type settings.
		//lets check for a post meaning the event type has change and forced an auto submit to enable the form to display the correct settings
		//if there is no post then use the default_event variable above
		if(!empty($_POST)) {

		}else{
			//use default settings
			$event_type_settings=dl::select("flexi_event_settings", "event_typeid=$default_event");
			$duration_type = $event_type_settings[0]["duration_type"];
			$multi_date = $event_type_settings[0]["multi_date_allowed"];
		}
		if($duration_type=="Fixed" or $duration_type=="Both") {
			$durations = dl::select("flexi_fixed_durations");
			$arrDurations[]="";
			foreach($durations as $duration) {
				//create an array to use in the drop box selection
				$arrDurations[]=$duration["name"];
			}
		}
		$event_types = dl::select("flexi_event_type", "event_work='Yes'");
		foreach($event_types as $types) {
			$eTypes[]=$types["event_type_name"];
		}
		
		echo "<div class='timesheet_workspace'>";
		$formArr[] = array("type"=>"intro", "formtitle"=>$title, "formintro"=>$intro); 
		$formArr[] = array("type"=>"form", "form"=>array("action"=>"index.php?func=saveevent&user=".$_SESSION["userSettings"]["userId"],"method"=>"post"));	
		$formArr[] = array("prompt"=>"Event Type", "type"=>"selection", "name"=>"event_type", "listarr"=>$eTypes, "selected"=>$default_event_description, "value"=>"", "clear"=>true);
		if($multi_date == "Yes") {
			$formArr[]=array("prompt"=>"Date From", "type"=>"date", "name"=>"date_name", "length"=>20, "value"=>"", zindex=>0, "clear"=>true);
			$formArr[]=array("prompt"=>"Date To", "type"=>"date", "name"=>"date_name2", "length"=>20, "value"=>"", zindex=>1, "clear"=>true);
		}else{
			$formArr[]=array("prompt"=>"Date", "type"=>"date", "name"=>"date_name", "length"=>20, "value"=>$dateVal, zindex=>1, "clear"=>true);
		}	
		if($duration_type=="Fixed" or $duration_type == "Both") {
			$formArr[]=array("prompt"=>"Start Duration", "type"=>"time", "name"=>"duration_time_start", "length"=>20, "starttime"=>$event_settings[0]["earliest_starttime"], "endtime"=>$event_settings[0]["latest_endtime"], "selected"=>"0900", "interval"=>1, "value"=>"", "clear"=>true);
			$formArr[]=array("prompt"=>"Event Duration", "type"=>"selection", "name"=>"duration", "listarr"=>$arrDurations, "selected"=>"", "value"=>"", "clear"=>true);
		}
		if($duration_type=="User definable" or $duration_type == "Both") {
			$formArr[]=array("prompt"=>"Start Time", "type"=>"time", "name"=>"time_start", "length"=>20, "starttime"=>$event_settings[0]["earliest_starttime"], "endtime"=>$event_settings[0]["latest_endtime"], "selected"=>"0900", "interval"=>1, "value"=>"", "clear"=>true);
			$formArr[]=array("prompt"=>"End Time", "type"=>"time", "name"=>"time_end", "length"=>20, "starttime"=>$event_settings[0]["earliest_endtime"], "endtime"=>$event_settings[0]["latest_endtime"], "selected"=>"1700", "interval"=>1, "value"=>"", "clear"=>true);
		}
		$formArr[]=array("prompt"=>"Extended Lunch", "type"=>"checkbox", "name"=>"extended_lunch", "value"=>"Yes", "clear"=>true);
		$formArr[]=array("prompt"=>"Lunch Start Time", "type"=>"time", "name"=>"lunch_time_start", "length"=>20, "starttime"=>$event_settings[0]["lunch_earliest_start_time"], "endtime"=>$event_settings[0]["lunch_latest_end_time"], "selected"=>"1200", "interval"=>1, "value"=>"", "clear"=>true);
		$formArr[]=array("prompt"=>"Lunch End Time", "type"=>"time", "name"=>"lunch_time_end", "length"=>20, "starttime"=>$event_settings[0]["lunch_earliest_start_time"], "endtime"=>$event_settings[0]["lunch_latest_end_time"], "selected"=>"1230", "interval"=>1, "value"=>"", "clear"=>true);
		$formArr[]=array("type"=>"submit", "buttontext"=>"Add Event", "clear"=>true); 
		$formArr[]=array("type"=>'endform');
		$form = new forms;
		$form->create_form($formArr);
		echo "</div>";
	}
}

function add_event($title, $intro, $user="") {
	if(check_permission("Events")) {
		if(empty($user)) {
			$userId = $_SESSION["userSettings"]["userId"];
		}else{
			$userId = $user;
			$_SESSION["otherUser"]=$user;
		}
		if(!empty($_GET["date"]) ){
			$dateVal = $_GET["date"];
		}else{
			$dateVal = "";	
		}
		if(!empty($_SESSION["otherUser"])) {
			$userId=$_SESSION["otherUser"];
		}
		//dl::$debug=true;
		// require to retrieve template settings
		$sql = "select * from flexi_user join flexi_template as ft on (user_flexi_template=template_id) 
		join flexi_template_days as ftd on (ftd.template_name_id=ft.template_name_id) 
		join flexi_template_days_settings as ftds on (ftds.template_days_id=ftd.flexi_template_days_id) 
		where user_id = $userId";
		$event_settings = dl::getQuery($sql);
		$userName = $event_settings[0]["user_name"];
		//now lets find the default event ie. Working Day
		$event = dl::select("flexi_event_type", "event_type_name='Working Day'");
		$default_event = $event[0]["event_type_id"];
		$default_event_description = $event[0]["event_type_name"];
		$template_days_id = $event_settings[0]["flexi_template_days_id"];
		//now lets find the flexi event id
		//this will change the entry values sent to the time entry array
		$flexi = dl::select("flexi_event_type", "event_flexi='Yes'");
		$flexi_event = $flexi[0]["event_type_id"];
		// now need to retrieve the event type settings.
		//lets check for the get array meaning the event type has change and forced a redirect to enable the form to display the correct settings
		//if there is an empty get array then use the default_event variable above
		if(!empty($_GET["type"])) {
			$event = dl::select("flexi_event_type", "event_type_name='".$_GET["type"]."'");
			$default_event = $event[0]["event_type_id"];
			$default_event_description = $event[0]["event_type_name"];
			$eventGlobal = $event[0]["event_global"];
			$event_type_settings=dl::select("flexi_event_settings", "event_typeid=$default_event");
			$duration_type = $event_type_settings[0]["duration_type"];
			$multi_date = $event_type_settings[0]["multi_date_allowed"];
			$working_session = $event[0]["event_work"];
		}else{
			//use default settings
			$eventGlobal = $event[0]["event_global"];
			$event_type_settings=dl::select("flexi_event_settings", "event_typeid=$default_event");
			$duration_type = $event_type_settings[0]["duration_type"];
			$multi_date = $event_type_settings[0]["multi_date_allowed"];
			$working_session = $event[0]["event_work"];
		}
		//now get the timesheet of the userId
		$sql = "select * from flexi_user as u join flexi_timesheet as t on (u.user_id=t.user_id)
		where u.user_id = ".$userId;
		$timesheet_id = dl::getQuery($sql);
		
		//lets check for other events already entered for this day
		$eventsToday = dl::select("flexi_event", "event_startdate_time >= '".$_GET["date"]." 00:00:00' and event_enddate_time <= '".$_GET["date"]." 23:59:59' and timesheet_id =".$timesheet_id[0]["timesheet_id"]);
		//now lets set the start Duration selected if the starttime is earlier than 10:00 and the option get value = 'another'
		$selectedTime = "09:00";
		if($_GET["option"] == "another") {
			if($eventsToday[0]["event_startdate_time"] <= $_GET["date"]." 10:00:00") {
				//lets set the selected value now
				$hrs = substr($eventsToday[0]["event_enddate_time"], 11,2);
				$mins = substr($eventsToday[0]["event_enddate_time"], 14,2);
				$selectedTime = $hrs.":".$mins;
			}
		}
		if($duration_type=="Fixed") {
			$arrDurations= array("Full day", "Half day");
			//altered to a Full day or a Half day as the new template flexi_day-times will calculate the event time for the selected Day.
		}
		if($duration_type=="Both") {
			$arrDurations= array("","Full day", "Half day");
			//altered to a Full day or a Half day as the new template flexi_day-times will calculate the event time for the selected Day.
		}
		//now lets check if the selected event needs to show if a remainder option should be added to the $arrDurations array_combine
		if($event[0]["event_remainder"]  == "Yes") {
			array_push($arrDurations, "Remainder");
		}
		$event_types = dl::select("flexi_event_type");
		
		foreach($event_types as $types) {
			if($types["event_global"] == "Yes") {
				if($_SESSION["userPermissions"]["add_global"]=="true") {
					$eTypes[]=$types["event_type_name"];
				}
			}else{
					$eTypes[]=$types["event_type_name"];
			}
		}
		echo "<div class='timesheet_workspace'>";
		$formArr[] = array("type"=>"intro", "formtitle"=>$title, "formintro"=>$intro); 
		$formArr[] = array("type"=>"form", "form"=>array("action"=>"index.php?func=saveevent&user=$userId","method"=>"post"));	
		$formArr[] = array("prompt"=>"User", "type"=>"label", "name"=>"user_name", "value"=>$userName, "clear"=>true);
		$formArr[] = array("prompt"=>"Event Type", "type"=>"selection", "name"=>"event_type", "listarr"=>$eTypes, "selected"=>$default_event_description, "value"=>"", "clear"=>true, onchange=>"this.value");
		if($multi_date == "Yes") {
			$formArr[]=array("prompt"=>"Date From", "type"=>"date", "name"=>"date_name", "length"=>20, "value"=>$dateVal, zindex=>2, "clear"=>true);
			$formArr[]=array("prompt"=>"Date To", "type"=>"date", "name"=>"date_name2", "length"=>20, "value"=>$dateVal, zindex=>1, "clear"=>true);
		}else{
			$formArr[]=array("prompt"=>"Date", "type"=>"date", "name"=>"date_name", "length"=>20, "value"=>$dateVal, zindex=>1, "clear"=>true);
		}	
		if($duration_type=="Fixed" or $duration_type == "Both") {
			$formArr[]=array("prompt"=>"Start Duration", "type"=>"time", "name"=>"duration_time_start", "length"=>20, "starttime"=>$event_settings[0]["earliest_starttime"], "endtime"=>$event_settings[0]["latest_endtime"], "selected"=>$selectedTime, "interval"=>1, "value"=>$selectedTime, "clear"=>true);
			$formArr[]=array("prompt"=>"Event Duration", "type"=>"selection", "name"=>"duration", "listarr"=>$arrDurations, "selected"=>"", "value"=>"", "clear"=>true);
		}
		if($duration_type=="User definable" or $duration_type == "Both") {
			$formArr[]=array("prompt"=>"Start Time", "type"=>"time", "name"=>"time_start", "length"=>20, "starttime"=>$event_settings[0]["earliest_starttime"], "endtime"=>$event_settings[0]["latest_endtime"], "selected"=>$selectedTime, "interval"=>1, "value"=>$selectedTime, "clear"=>true);
			$formArr[]=array("prompt"=>"End Time", "type"=>"time", "name"=>"time_end", "length"=>20, "starttime"=>$event_settings[0]["earliest_starttime"], "endtime"=>$event_settings[0]["latest_endtime"], "selected"=>"1700", "interval"=>1, "value"=>"", "clear"=>true);
		}
		if($working_session=="Yes") {
			$formArr[]=array("prompt"=>"Extended Lunch", "type"=>"checkbox", "name"=>"extended_lunch", "value"=>"Yes", "clear"=>true);
			$formArr[]=array("prompt"=>"Lunch Start Time", "type"=>"time", "name"=>"lunch_time_start", "length"=>20, "starttime"=>$event_settings[0]["lunch_earliest_start_time"], "endtime"=>$event_settings[0]["lunch_latest_end_time"], "selected"=>"1200", "interval"=>1, "value"=>"", "clear"=>true);
			$formArr[]=array("prompt"=>"Lunch End Time", "type"=>"time", "name"=>"lunch_time_end", "length"=>20, "starttime"=>$event_settings[0]["lunch_earliest_start_time"], "endtime"=>$event_settings[0]["lunch_latest_end_time"], "selected"=>"1230", "interval"=>1, "value"=>"", "clear"=>true);
		}
		if($eventGlobal=="Yes") {
			//add teams to provide multiple selection
			$teams = dl::select("flexi_team", "", "team_name");
			foreach($teams as $team) {
				$aTeams[]=$team["team_name"];
			}
			$formArr[] = array("prompt"=>"Select Teams", "type"=>"selection", "name"=>"teams[]", "listarr"=>$aTeams, multiple=>true, "selected"=>"", "value"=>"", "clear"=>true);
			$formArr[]=array("prompt"=>"Individual Edit?", "type"=>"checkbox", "name"=>"individual", "value"=>"Yes", "clear"=>true);
		}
		
		$formArr[]=array("prompt"=>"Event Note", "type"=>"textarea", "name"=>"event_note", rows=>8, cols=>50, "value"=>"", "clear"=>true);
		$formArr[]=array("prompt"=>"The Note type allows you to select whether the above note is Public or Private. A `Public` note can <u>ONLY</u> be seen by you and your manager, whereas a `Private` note can only be seen by you.", "type"=>"note", "name"=>"void", "value"=>"", "clear"=>true);
		$formArr[] = array("prompt"=>"Note Type", "type"=>"radio", "name"=>"event_note_type", "listarr"=>array("Public","Private"), "selected"=>"Public", "value"=>"", "clear"=>true);
		$formArr[]=array("type"=>"submit", "buttontext"=>"Add Event", "clear"=>true); 
		$formArr[]=array("type"=>'endform');
		$form = new forms;
		$form->create_form($formArr);
		echo "</div>";
	}
}

function save_event($userId) {
	include("inc/email_messages.inc");
	//dl::$debug=true;
	$eventType							= $_POST["event_type"];
	$leaveDuration						= $_POST["duration"]; // this 'Full day', 'Half day' 'Remainder' or Blank
	$event 								= dl::select("flexi_event_type", "event_type_name='$eventType'");
	$eventId 							= $event[0]["event_type_id"];
	//check the event type to see if an authorisation is required
	$eventName 							= $event[0]["event_type_name"];
	$eventAuthorisation 				= $event[0]["event_authorisation"];
	$eventWork 							= $event[0]["event_work"];
	$eventGlobal 						= $event[0]["event_global"];
	$eventAnnualLeave 					= $event[0]["event_al"];
	$eventFlexiLeave 					= $event[0]["event_flexi"];
	$eventDelete 						= $event[0]["event_delete"];
	$eventOverride						= $event[0]["event_override"];
	$eventRemainder						= $event[0]["event_remainder"];
	
	//*******************************************
	//check the flexi template days settings to make sure the entered times are within the template ranges specified
	$sql 								= "select * from flexi_user as u 
	join flexi_template as t on (u.user_flexi_template=t.template_id) 
	join flexi_template_days as td on (td.template_name_id=t.template_name_id) 
	join flexi_template_days_settings as tds on (tds.template_days_id=td.flexi_template_days_id)
	join flexi_al_template as al on (al.al_template_id=u.user_al_template) 
	where u.user_id =".$userId;
	$ranges 							= dl::getQuery($sql);
	$earliest_start 					= $ranges[0]["earliest_starttime"];
	$latest_start 						= $ranges[0]["latest_starttime"];
	$earliest_lunch_start				= $ranges[0]["lunch_earliest_start_time"];
	$latest_lunch_end					= $ranges[0]["lunch_latest_end_time"];
	$earliest_end 						= $ranges[0]["earliest_endtime"];
	$latest_end 						= $ranges[0]["latest_endtime"];
	$days_settings_id 					= $ranges[0]["days_settings_id"];
	$minimum_lunch 						= $ranges[0]["minimum_lunch"];
	$minimum_lunch_duration 			= $ranges[0]["minimum_lunch_duration"];
	$proRataLeave						= $ranges[0]["max_surplus"];
	$userWorkStatus						= $ranges[0]["al_type"]; // is the user Full or Part time
	//$normal_day_duration[0]["normal_day_duration"]; the normal day duration is no longer required as the new flexi_day_times template accomodates the times for individual and multiple days
	//********************************************
	$eventSettings 						= dl::select("flexi_event_settings", "event_typeid=".$eventId);
	$durationType 						= $eventSettings[0]["duration_type"];
	//check to see if the duration is fixed that a value has been entered for event duration
	if($durationType == "Fixed" and empty($_POST["duration"])) { //need to message the user ?>
		<SCRIPT language="javascript">
		alert("The event type you have tried to enter is a fixed type and therefore requires you to select an event duration. \n\nPlease re-enter the times to include the duration time.");
		redirect("index.php?choice=Add&subchoice=addevent");
		</SCRIPT>
    	<?php
		die();
	}
	$multiDate = $eventSettings[0]["multi_date_allowed"];
	//get timesheet Id
	$timesheet = dl::select("flexi_timesheet", "user_id=".$userId);
	$timeSheetId = $timesheet[0]["timesheet_id"];
	if($multiDate=="Yes") {
		if($_POST["date_name"] != $_POST["date_name2"]) {
			$multipleDates=true;
			//check if the duration is not Remainder
			if($leaveDuration == "Remainder") {
				echo "<SCRIPT language='javascript'>alert('The event duration you selected was \"Remainder\" this cannot be selected with Multiple dates. Please select \"Fullday\" to add your events.');" ;
				echo "redirect('index.php?choice=Add&subchoice=addevent&type=".$_GET["type"]."');</SCRIPT>" ;
				die();
			}
			if($_POST["date_name"] > $_POST["date_name2"]) {
				?>
				<SCRIPT language="javascript">
				alert("The dates you have entered are incorrect. \n\nPlease re-enter the dates making sure the `Date To` is the same or later than the `Date From` date.");
				redirect("index.php?choice=Add&subchoice=addevent");
				</SCRIPT>
				<?php
				die();
			}
		}
	}
	// is it fixed duration
	if($durationType=="Fixed" or $durationType=="Both"){
		//need to get the times dependant on posted choice
		//need to find out which day the posted date is for
		$dayNo = date("N", strtotime($_POST["date_name"]));
		//now need to find the flexi_template_days_setting
		
		//lets get the day duration for the first day of the event
		$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = ".$dayNo);
		if(empty($duration_link)) { //then assume this is a template that applies to all of the days
			$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = 6");
		}
		if(empty($duration_link) and $eventGlobal=="No" and $eventOverride == "No") { //this is the first day of an event addition. If it lands on a day that is not setup in the days_settings template then a message is displayed and the program execution halted. Only halted if the Template Override is not set for this event type
			echo "<SCRIPT language='javascript'>alert('You have tried to add a leave request on a day that is not specified in your settings template. Choose a day that matches a working day in your working week and if you are adding multiple requests then choose any end date in the following days. The system will then check your template and add only the matching days.');" ;
				echo "redirect('index.php?choice=Add&subchoice=addevent&type=".$_GET["type"]."');</SCRIPT>" ;
			die();
		}
		$add_lunch = 0; //no minimum lunch for this template
		//need to check if there is a minimum lunch to add to the end time
		if($minimum_lunch == "Yes") {
			if($leaveDuration == 'Full day') {
				if(date("h", strtotime($duration_link[0]["fdt_working_time"])) >= 6) { // check if the time is greater than or equal to 6 hours. This is a EUROPEAN directive....
					$add_lunch = strtotime($minimum_lunch_duration); // add min lunch as it will be taken off
				}	
			}elseif($leaveDuration == "Half Day"){
				if(date("h", strtotime($duration_link[0]["fdt_working_time"]))/2 >= 6) { // check if the time is greater than or equal to 6 hours. This is a EUROPEAN directive....
					$add_lunch = strtotime($minimum_lunch_duration); // add min lunch as it will be taken off
				}
			}
		}
	}
	if(!empty($duration_link) and !empty($leaveDuration)) { //if $leaveDuration is blank then use the user entered times
		if($eventType != "Flexi Leave") {// let's check if this is NOT flexileave
			$duration = $duration_link[0]["fdt_working_time"]; //add the working time for the particular day 
		}else{
			$duration = "0".intval($proRataLeave).":".(60*fmod($proRataLeave,1)).":00"; //add the time for the proRata leave request
		}
		$startTime = $_POST["duration_time_start"].":".$_POST["duration_time_start_mins"].":00";
		$startTimeSecs = $_POST["duration_time_start"] * 60 * 60 + $_POST["duration_time_start_mins"] * 60;
		$eventsToday = dl::select("flexi_event", "event_startdate_time >= '".$_POST["date_name"]." 00:00:00' and event_enddate_time <= '".$_POST["date_name"]." 23:59:59' and timesheet_id = ".$timeSheetId." order by event_startdate_time ASC");
		$chkEventType = dl::select("flexi_event_type", "event_type_id = ".$eventsToday[0]["event_type_id"]);
		$eventTypeTxt = $chkEventType[0]["event_type_name"];
		//TODO : REMAINDER need to look at a hospital appointment when it is not at the end of the day to change the time of the appointment to not exceed an equivalent full day for the persons working pattern
		 
		if($leaveDuration == "Remainder") {
			//this is either a (Annual Leave, Flexi Leave, offsite mtg, Sickness, Training, Hospital or Emergency leave)
			/* 
			 * if this is a flexi leave or annual leave Remainder request then it can only be made by a part time user type.
			 * we also have to change the duration from the proRATA type to the working time to
			 * differentiate the remainder types. The other types can have the event entered first or indeed second
			 * but a flexi or annual leave remainder event must be entered second and can only accompany the opposite leave type.
			 * 
			 * eg. Flexi Leave must accompany Annual Leave or Working Day event types and vice versa
			 * 
			*/
			if($eventType == "Flexi Leave") {
				//if this is a remainder event then we need to change the duration from the proRata time to the working time in order to correctly calculate the amount of flexi for the remainder of the day
				//reset back to the working time because its a remainder
				$duration = $duration_link[0]["fdt_working_time"]; //add the working time for the particular day 
				if($eventTypeTxt == "Annual Leave" or $eventTypeTxt == "Working Day") {
					if($userWorkStatus == "Fulltime") {
						?>
						<SCRIPT language="javascript">
						alert("This option is only available to Part Time staff. \n\nYou must select either a full or half day event duration to add Flexi Leave.");
						redirect("index.php?choice=Add&subchoice=addevent");
						</SCRIPT>
						<?php
						die();
					}
				}else{
					?>
					<SCRIPT language="javascript">
					alert("You have tried to enter a remainder event onto an incorrect accompanying event. \n\nThis remainder event type can only be used with an Annual Leave event or Working Day event.");
					redirect("index.php?choice=Add&subchoice=addevent");
					</SCRIPT>
					<?php
					die();
				}
			}
			if($eventType == "Annual Leave") {
				if($eventTypeTxt == "Flexi Leave" or $eventTypeTxt == "Working Day") {
					if($userWorkStatus == "Fulltime") {
						?>
						<SCRIPT language="javascript">
						alert("This option is only available to Part time staff. \n\nYou must select either a full or half day event duration to add Annual Leave.");
						redirect("index.php?choice=Add&subchoice=addevent");
						</SCRIPT>
						<?php
						die();
					}
				}else{
					?>
					<SCRIPT language="javascript">
					alert("You have tried to enter a remainder event onto an incorrect accompanying event. \n\nThis remainder event type can only be used with a Flexi Leave event or Working Day event.");
					redirect("index.php?choice=Add&subchoice=addevent");
					</SCRIPT>
					<?php
					die();
				}
			}
			if(count($eventsToday) > 0) {
				 //there is an existing event on this day so get the start time and work out the end time from the day duration
				//puts the earliest event at the top
				$startSecs = substr($eventsToday[0]["event_startdate_time"],11,2) * 60 * 60 + substr($eventsToday[0]["event_startdate_time"],14,2) * 60 + substr($eventsToday[0]["event_startdate_time"],17,2) * 60;
				$durationSecs = substr($duration,0,2) * 60 * 60 + substr($duration,3,2) * 60 + substr($duration,6, 2) * 60;
				$endTimeSecs = $startSecs + $durationSecs;
				$endTimeSecs = $endTimeSecs - $startTimeSecs;
			}else{ //this is the first event on this day so lets make an assumption that the start time is 09:00
				$startSecs = 9*60*60;
				$durationSecs = substr($duration,0,2) * 60 * 60 + substr($duration,3,2) * 60 + substr($duration,6, 2) * 60;
				$endTimeSecs = $startSecs + $durationSecs;
				$endTimeSecs = $endTimeSecs - $startTimeSecs;
			}
		}else{
			//need to check if the event is a flexileave event as it may be that the duration is less than the working time on this day.
			if($eventType == "Flexi Leave"){
				//this is a full or half day of flexi leave and should not include any minimum lunch time
				$add_lunch = 0;
			}
			$endTimeSecs = substr($duration,0,2) * 60 * 60 + substr($duration,3,2) * 60 + substr($duration,6,2) * 60;
		}
		if($leaveDuration == 'Half day') {
			$endTimeSecs = $endTimeSecs/2;
		}
		$endTime = date("H:i:s", $startTimeSecs + $endTimeSecs + $add_lunch);
	}else{
		//need to find out which day the posted date is for
		$dayNo = date("N", strtotime($_POST["date_name"]));
		//lets get the day duration for the first day of the event
		$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = ".$dayNo);
		if(empty($duration_link)) { // this means that the weekday pattern is fixed for all days so lets check that with the weekday_id == 6
			$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = 6");
		}
		if(!empty($duration_link)){ 
			if($eventType != "Flexi Leave") // let's check if this is NOT flexileave
			{
				$duration = $duration_link[0]["fdt_working_time"]; //add the working time for the particular day 
			}else{
				$duration = "0".intval($proRataLeave).":".(60*fmod($proRataLeave,1)).":00"; //add the time for the proRata leave request
			}
			$eventsToday = dl::select("flexi_event", "event_startdate_time >= '".$_POST["date_name"]." 00:00:00' and event_enddate_time <= '".$_POST["date_name"]." 23:59:59' and timesheet_id = ".$timeSheetId." order by event_startdate_time ASC");
			//lets check if there is more than one event on this day and check if it is an event which is a remainder event
			//as we will probably have to change the end time of the remainder event.
			
			if(count($eventsToday) > 0) {
				//now need to check if any of the events are remainder events.
				//first event will be the earliest event lets check if the posted times are less than the first event
				if($_POST["time_start"] < substr($eventsToday[0]["event_startdate_time"],11,2)) {
					$timeStart = $_POST["time_start"];
					$timeStartMins = $_POST["time_start_mins"];
					$timeEnd = $_POST["time_end"];
					$timeEndMins = $_POST["time_end_mins"];
				}else{
					$timeStart = substr($eventsToday[0]["event_startdate_time"],11,2);
					$timeStartMins = substr($eventsToday[0]["event_startdate_time"],14,2);
					$timeEnd = substr($eventsToday[0]["event_enddate_time"],11,2);
					$timeEndMins = substr($eventsToday[0]["event_enddate_time"],14,2);
				}
				foreach($eventsToday as $evs) {
					$checkRems = dl::select("flexi_remainder", "r_event = ".$evs["event_id"]);
					if(!empty($checkRems)) {
						//need to check if  the none remainder event is greater than or equal to 6 hours. If yes we then need to add 30 mins to the remainder event
						$startSecs = $timeStart * 60 * 60 + $timeStartMins * 60;
						$endSecs = $timeEnd * 60 * 60 + $timeEndMins * 60;
						$minLunch=0;
						// the below is commented as min lunch is excluded from a remainder display
						/*if(date("H", $endSecs-$startSecs) >= 6 ) {
							$minLunch = strtotime($minimum_lunch_duration); // add min lunch as it will be taken off
						}*/
						$durationSecs = substr($duration,0,2) * 60 * 60 + substr($duration,3,2) * 60 + substr($duration,6, 2) * 60;
						$endTimeSecs = $startSecs + $durationSecs;
						$endTimeSecs = $endTimeSecs - $startTimeSecs;
						dl::update("flexi_event", array("event_enddate_time"=>$_POST["date_name"]." ".date("H:i:s", $endTimeSecs)), "event_id = ".$evs["event_id"]);
					}
				}
			}
			
		}
		$startTime = $_POST["time_start"].":".$_POST["time_start_mins"].":00";
		$endTime = $_POST["time_end"].":".$_POST["time_end_mins"].":00";
	}
	$startDateTime = $_POST["date_name"]." ".$startTime;
	$endDateTime = $_POST["date_name"]." ".$endTime;
	//check to see if a date has been entered that lands on a weekend.
	if(date("l", strtotime($_POST["date_name"])) == "Saturday" or date("l", strtotime($_POST["date_name"])) == "Sunday" or date("l", strtotime($_POST["date_name2"])) == "Saturday" or date("l", strtotime($_POST["date_name2"])) == "Sunday") {
		//redirect to message and reenter details ?>
		<SCRIPT language="javascript">
		alert("The start/end date you entered lands on a weekend and therefore cannot be added to the flexitime system. \n\nPlease re-enter the times to fall outside of the weekend. Be aware that you are able to enter dates that span a weekend as the system will ignore the weekend dates.");
		redirect("index.php?choice=Add&subchoice=addevent&type="+<?php echo $_GET["type"]?>);
		</SCRIPT>
		<?php die();
	}
	//check for posted times are within range
	//check to see if there is any event entered other than working time in the morning as this will allow the addition of working time outside of the core time
	$sql = "select * from flexi_event as e join flexi_event_type as et on (e.event_type_id=et.event_type_id) where substr(event_startdate_time,1,10) = '".substr($startDateTime,0,10)."' and timesheet_id=".$timeSheetId." and event_work='No'";
	$earlyFlex = dl::getQuery($sql);
	if($startTime < $earliest_start or $startTime > $latest_start and $eventWork=="Yes" and empty($earlyFlex)) {
		//redirect to message and reenter details
		echo "<SCRIPT language='javascript'>alert('The start/end time you entered is not within the range specified for the Clinical Research Platforms flexitime scheme. Please re-enter the times to fall between the ranges of (Earliest Start : $earliest_start & Latest Start : $latest_start). If you have legitimately worked outside of these ranges please speak with Mandy Jarvis who will alter your times to reflect this.');" ;
		echo "redirect('index.php?choice=Add&subchoice=addevent&type=".$_GET["type"]."');</SCRIPT>" ;
		die();
	}else{
		if($_POST["extended_lunch"]=="Yes") {
			if(strtotime($_POST["lunch_time_start"].":".$_POST["lunch_time_start_mins"].":00") < strtotime($earliest_lunch_start) or strtotime($_POST["lunch_time_end"].":".$_POST["lunch_time_end_mins"].":00") > strtotime($latest_lunch_end)) {
				//redirect to message and reenter details
				echo "<SCRIPT language='javascript'>alert('The start/end time you entered for your extended lunch is not within the range specified for the Clinical Research Platforms flexitime scheme. Please re-enter the times to fall between the ranges of (Lunch Start : ".$earliest_lunch_start." & Lunch End : ".$latest_lunch_end.").');" ;
				echo "redirect('index.php?choice=Add&subchoice=addevent&type=".$_GET["type"]."');</SCRIPT>" ;
				die();
			}
		}
		if($eventWork == "Yes" and empty($earlyFlex)) {
			if($endTime < $earliest_end or $endTime > $latest_end) {
				//echo $eventWork;
				echo "<SCRIPT language='javascript'>alert('The start/end time you entered is not within the range specified for the Clinical Research Platforms flexitime scheme. Please re-enter the times to fall between the ranges of (Earliest End Time : $earliest_end & Latest End Time : $latest_end). If you have legitimately worked outside of these ranges please speak with Mandy Jarvis who will alter your times to reflect this.');" ;
				echo "redirect('index.php?choice=Add&subchoice=addevent&type=".$_GET["type"]."');</SCRIPT>" ;
				die();
			}
		}
	}
	//lets check to see if the event is for FlexiTime.
	//if it is then need to check how much flexi has been taken this period and if this request is allowed
	if($eventFlexiLeave=="Yes") {
		//get the period date values
		$sql = "select * from flexi_user as u 
		join flexi_template as t on (u.user_flexi_template=t.template_id) 
		join flexi_template_name as tn on (t.template_name_id=tn.flexi_template_name_id) 
		join flexi_template_days as td on (tn.flexi_template_name_id=td.template_name_id) 
		join flexi_template_days_settings as tds on (td.flexi_template_days_id=tds.template_days_id) where user_id = ".$userId;
		$dates = dl::getQuery($sql);
		$startPeriod = $dates[0]["start_period"]." 00:00:00";
		$endPeriod = $dates[0]["end_period"]." 23:59:59";
		$flexitime = dl::select("flexi_event", "event_type_id = ".$eventId. " and timesheet_id = ".$timeSheetId." and event_startdate_time >= '".$startPeriod."' and event_enddate_time <= '".$endPeriod."'");
		if(!empty($flexitime)) {
			foreach($flexitime as $ft) {
				$addup = strtotime($ft["event_enddate_time"]) - strtotime($ft["event_startdate_time"]);
				$flexiAdd += $addup;
			}
		}
		if($flexiAdd == 0 ) { //this is the first flexi request
			$firstFlexi = true;
		}
		//add the posted flexitime to the existing flexitime
		$flexiAdd = $flexiAdd + ( strtotime($endDateTime) - strtotime( $startDateTime ) );
		//work out the minimum lunch
		$hours = date("H", strtotime($minimum_lunch_duration));
		$mins = date("i", strtotime($minimum_lunch_duration));
		$hours = $hours * 60 * 60;
		$mins = $mins *60;
		//subtract the minimum lunch
		$flexiAdd = $flexiAdd - $hours - $mins;
		//now need to get the template information to find out how much flexitime this person is allowed to take within the period.
		$surplus = $dates[0]["max_surplus"]*60*60;
		$surplus = $surplus * $dates[0]["max_holiday"]; //max_holiday is the maximum flexi-leave the person is allowed within a 4 week period.
		if(date("H:i:s", $flexiAdd) > date("H:i:s", $surplus) ){
			echo "<SCRIPT language='javascript'>alert('The flexitime request you are trying to create exceeds the flexitime leave you are allowed within this period. Please contact Mandy Jarvis who will be able to review this request. Thank you.');" ;
			echo "redirect('index.php?choice=View&subchoice=timesheet')</SCRIPT>" ;
			die();
		}
	}
	$extendedLunch = strtotime("00:00:00");
	if($_POST["extended_lunch"] == "Yes"){
		$extendedLunch = strtotime($_POST["lunch_time_end"].":".$_POST["lunch_time_end_mins"].":00") - strtotime($_POST["lunch_time_start"].":".$_POST["lunch_time_start_mins"].":00");
	}
	//check to see if the time has already been entered and does not overlap any other time/leave etc.
	$checkEntered = dl::select("flexi_event", "(substr(event_startdate_time,1,10) = '".substr($startDateTime,0,10)."' and substr(event_enddate_time,1,10) = '".substr($startDateTime,0,10)."') and timesheet_id=".$timeSheetId);
	$checkStartTime = date("H:i:s", strtotime($startDateTime));
	$checkEndTime = date("H:i:s", strtotime($endDateTime));
	$checked=true;
	if(!empty($checkEntered)) {
		//check to see if the time entered overlaps an existing event
		foreach($checkEntered as $ce) {
			$sTime = date("H:i:s", strtotime($ce["event_startdate_time"]));
			$eTime = date("H:i:s", strtotime($ce["event_enddate_time"]));
			if($sTime < $checkStartTime and $eTime <= $checkStartTime) {
				$checked=true;
			}elseif($sTime >= $checkEndTime and $eTime > $checkEndTime) {
				$checked=true;
			}else{
				$checked=false;
			}
		}
	}
	if($checked or $eventGlobal=="Yes") { // the checks are completed further down, if this is a global event addition! it may be that the event has been added as an individual event before the global event addition. This allows the addition to happen...correctly
		// write the first record
		$fieldarr = array("timesheet_id", "event_startdate_time", "event_enddate_time", "event_type_id", "event_lunch");
		$postarr = array($timeSheetId, $startDateTime, $endDateTime, $eventId, date("H:i:s", $extendedLunch));
		$save = array_combine($fieldarr, $postarr);
		if($eventGlobal=="No"){ //will write this record lower down.
			dl::insert("flexi_event", $save);
			$lastId = dl::getId();
			$sql = "select MAX(event_id) as maxId from flexi_event where timesheet_id = ".$timeSheetId;
			$lastRec = dl::getQuery($sql);
			
			if($eventType == 'Annual Leave') {
				if($leaveDuration == "Full day") {
					dl::insert("flexi_leave_count", array("flc_fullorhalf"=>1.0, "flc_event_id"=>$lastRec[0]["maxId"]));
				}else{
					dl::insert("flexi_leave_count", array("flc_fullorhalf"=>0.5, "flc_event_id"=>$lastRec[0]["maxId"]));
				}
			}
			if($leaveDuration == "Remainder") { 
				//if this is a remainder then add a record to the remainder table. Used to change the end time of the remainder event when a working event is added with a real start time.
				//the remainder event initially assumes a start time of 09:00 in the morning, uses the day duration from the users working pattern to create a supposed end time.
				dl::insert("flexi_remainder", array("r_event"=>$lastId));
			}
		}
		//get the event Id for the event that has just been written
		$id = dl::select("flexi_event", "timesheet_id=".$timeSheetId." and event_startdate_time = '".$startDateTime."' and event_type_id=".$eventId);
		if($eventFlexiLeave == "Yes") {
			//write the request record
			dl::insert("flexi_requests", array(request_event_id=>$id[0]["event_id"]));
		}else{
			if($eventAuthorisation == "Yes") {
				//write the request record
				dl::insert("flexi_requests", array(request_event_id=>$id[0]["event_id"]));
			}
		}
		if(!empty($_POST["event_note"])) {
			dl::insert("flexi_notes", array("notes_note"=>$_POST["event_note"], "notes_type"=>$_POST["event_note_type"]));
			$sql = "select MAX(notes_id) as max_id from flexi_notes";
			$notes = dl::getQuery($sql);
			dl::insert("flexi_event_notes", array("event_id"=>$id[0]["event_id"], "note_id"=>$notes[0]["max_id"]));
		}
		//first record written now check if need to add other dates
		if($multipleDates) { // already checked that the dates do not match
			//make sure that date_name2 is greater than date_name
			$startDateTime = $_POST["date_name"]." ".$startTime;
			$endDateTime = $_POST["date_name2"]." ".$endTime;
			if($endDateTime > $startDateTime) {
				// ready to continue
				//add 1 to startDateTime as have already written the first record higher up
				$timeStart = strtotime($startDateTime);
				$timeStart = strtotime(add_date($timeStart, 1));
				$timeEnd = strtotime($endDateTime);
				$endTimePortion = substr(date('Y-m-d H:i:s', $timeEnd), -8);
				//capture the dates to display within the email and the day Count.
				$emailStartDate = $_POST["date_name"];
				$emailEndDate = $_POST["date_name2"];
				$emailDayCount = 1;			
				while($timeStart <= $timeEnd) {
					//check if it's a weekday
					// might need to check here for the flexi time template to see if weekend are on the template
					//certainly shouldn't apply for leave ???
					if(date('l',$timeStart) !="Saturday" and date('l',$timeStart) !="Sunday") {
						$startDateTime = date('Y-m-d H:i:s', $timeStart);
						//need to check here for the days_setting template to see if the day is valid and can be entered
						//need to find out which day the posted date is for
						$dayNo = date("N", $timeStart);
						//now need to find the flexi_template_days_setting
						
						//lets get the day duration for the first day of the event
						$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = ".$dayNo);
						if(empty($duration_link)) { //then assume this is a template that applies to all of the days
							$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = 6");
						}
						if(empty($duration_link)) { //this is not a day matched in the template
							$checked =false;
						}else{
							$add_lunch = 0; //no minimum lunch for this template
							//need to check if there is a minimum lunch to add to the end time
							if($minimum_lunch == "Yes") {
								if(date("h", strtotime($duration_link[0]["fdt_working_time"])) >= 6) { // check if the time is greater than or equal to 6 hours. This is a EUROPEAN directive....
									$add_lunch = strtotime($minimum_lunch_duration); // add min lunch as it will be taken off
								}			
							}
							$duration = $duration_link[0]["fdt_working_time"];
							$startTime = $_POST["duration_time_start"].":".$_POST["duration_time_start_mins"].":00";
							$startTimeSecs = $_POST["duration_time_start"] * 60 * 60 + $_POST["duration_time_start_mins"] * 60;
							$endTimeSecs = substr($duration,0,2) * 60 * 60 + substr($duration,3,2) * 60 + substr($duration,6,2) * 60;
							$endTime = date("H:i:s", $startTimeSecs + $endTimeSecs+ $add_lunch);
							if(empty($leaveDuration)) { // on a multi date entry use the user defined times therefore overriding the template end times
								$endTime = $_POST["time_end"].":".$_POST["time_end_mins"].":00";
							}
							$endDateTime = date('Y-m-d', $timeStart)." ".$endTime;
							//check to see if the time has already been entered and does not overlap any other time/leave etc.
							$checkEntered = dl::select("flexi_event", "(substr(event_startdate_time,1,10) = '".substr($startDateTime,0,10)."' and substr(event_enddate_time,1,10) = '".substr($startDateTime,0,10)."') and timesheet_id=".$timeSheetId);
							$checkStartTime = date("H:i:s", strtotime($startDateTime));
							$checkEndTime = date("H:i:s", strtotime($endDateTime));
							$checked=true;
							if(!empty($checkEntered)) {
								//check to see if the time entered overlaps an existing event
								foreach($checkEntered as $ce) {
									$sTime = date("H:i:s", strtotime($ce["event_startdate_time"]));
									$eTime = date("H:i:s", strtotime($ce["event_enddate_time"]));
									if($sTime < $checkStartTime and $eTime <= $checkStartTime) {
										$checked=true;
									}elseif($sTime >= $checkEndTime and $eTime > $checkEndTime) {
										$checked=true;
									}else{
										$checked=false;
									}
									
								}
							}
						}
						
						if($checked) {
							$emailDayCount++;
							$fieldarr = array("timesheet_id","event_startdate_time","event_enddate_time","event_type_id");
							$postarr = array($timeSheetId, $startDateTime, $endDateTime, $eventId);
							$save = array_combine($fieldarr, $postarr);
							dl::insert("flexi_event", $save);
							$sql = "select MAX(event_id) as maxId from flexi_event where timesheet_id = ".$timeSheetId;
							$lastRec = dl::getQuery($sql);
							if($eventType == 'Annual Leave') {
								if($leaveDuration == "Full day") {
									dl::insert("flexi_leave_count", array("flc_fullorhalf"=>1.0, "flc_event_id"=>$lastRec[0]["maxId"]));
								}else{
									dl::insert("flexi_leave_count", array("flc_fullorhalf"=>0.5, "flc_event_id"=>$lastRec[0]["maxId"]));
								}
							}
							$id = dl::select("flexi_event", "timesheet_id=".$timeSheetId." and event_startdate_time = '".$startDateTime."'");
							if($eventFlexiLeave == "Yes") {
								if(date("h", strtotime($endDateTime) - strtotime($startDateTime)) >=4) {
									//write the request record
									dl::insert("flexi_requests", array("request_event_id"=>$id[0]["event_id"]));
								}
							}else{
								if($eventAuthorisation == "Yes") {
									//write the request record
									dl::insert("flexi_requests", array("request_event_id"=>$id[0]["event_id"]));
								}
							}
							
							if(!empty($_POST["event_note"])) {
								$notes = dl::select("flexi_notes", "notes_note='".$_POST["event_note"]."'");
								dl::insert("flexi_event_notes", array("event_id"=>$id[0]["event_id"], "note_id"=>$notes[0]["notes_id"]));
							}	
						}
					}
					$timeStart = strtotime(add_date($timeStart, 1));
				}
			}else{
				echo "Not found";	
			}
				
		}
		//now need to check what type of event it is and see if an authorisation is required
		if($eventAuthorisation == "Yes") { 
			if(!empty($_SESSION["otherUser"])){
				$userInfo = dl::select("flexi_user", "user_id=".$_SESSION["otherUser"]);
				$userName=$userInfo[0]["user_name"];
				$userEmail=$userInfo[0]["user_email"];
			}else{
				$userName=$_SESSION["userSettings"]["name"];
				$userEmail=$_SESSION["userSettings"]["email"];
				$userId=$_SESSION["userSettings"]["userId"];
			}
			//find the team the user is a local member of
			$sql = "select * from flexi_team as ft 
			join flexi_team_user as ftu on (ftu.team_id=ft.team_id)
			join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id) 
			where ftu.user_id=".$userId;
			$teams = dl::getQuery($sql);
			$team_id = $teams[0]["team_id"];
			$highLevel=false;
			//now need to see if this user is a manager/approver within this team
			//this determines if the manager in this team receives the approval request or the none local team member approver/manager
			$sql = "select fu.user_id, fu.user_email, fpt.permission_LM_constraint from flexi_permission_template as fpt 
			join flexi_user as fu on (fu.user_permission_id=fpt.permission_template_name_id) 
			join flexi_team_user as ftu on (ftu.user_id=fu.user_id)
			left outer join flexi_team_local as tl on (ftu.team_user_id=tl.team_user_id)
			left outer join flexi_deleted as d on (fu.user_id=d.user_id) 
			join flexi_team as ft on (ft.team_id=ftu.team_id) 
			where ft.team_id = ".$team_id." and fpt.permission_team_authorise = 'true' and date_deleted IS NULL and tl.team_user_id IS NOT NULL";
			$localManager = dl::getQuery($sql);
			foreach($localManager as $lm) {
				if($lm["user_id"] == $userId) {
					//this is a local manager request therefore needs to be authorised at a higher level
					$highLevel = true;
				}
			}
			//run through the list of local managers again to check if any of them have the override Local Manager constraint which means they are in the same team but have been given the responsibility to authorise leave
			if( $highLevel ){
				foreach($localManager as $lm) {
					if($lm["permission_LM_constraint"] == 'true') {
						//this is a local manager with access to authorise other managers' leave in their local team
						$recipients[] = $lm["user_email"];
					}
				}
			}
			if($highLevel or empty($localManager)) { // this is a request from the local team manager so the request should go to the non-local manager	or there is no local manager in this group
				$sql = "select user_email from flexi_permission_template as fpt 
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
			//now create email message
			$subject = $email_6_subject;
			$bodyText = $email_6_content;
			$bodyText = str_replace("%%user%%", $userName, $bodyText);
			$bodyText = str_replace("%%event%%", $eventName, $bodyText);
			if($multipleDates) {
				$bodyText = str_replace("%%MULTIDATES%%", $email_6_multidates, $bodyText);
				$bodyText = str_replace("%%STARTDATE%%", date('d-m-Y', strtotime($emailStartDate)), $bodyText);
				$bodyText = str_replace("%%ENDDATE%%", date('d-m-Y', strtotime($emailEndDate)), $bodyText);
				$bodyText = str_replace("%%DATECOUNT%%", $emailDayCount, $bodyText);
			}else{
				$bodyText = str_replace("%%MULTIDATES%%", "", $bodyText);
			}
			//now need to email the manager with the request
			$m = new Mail();
			//send the email confirmation
			$recips=explode(", ", $recipients);
			$m->From( "fws@ncl.ac.uk" ); // the first address in the recipients list is used as the from email contact and will receive emails in response to the registration request.
			$m->autoCheck(false);
			$m->To( $recipients );
			$m->Subject( $subject );
			$m->Body( $bodyText );
			$m->Priority(3);
			$m->Send();
		}
		//need to check if the event entered is a global event
		//if it is then need to add the global event to all timesheet id's
		//but only those within the selected teams.
		if($eventGlobal == "Yes") {
			$startDateTime = $_POST["date_name"]." ".$_POST["duration_time_start"].":".$_POST["duration_time_start_mins"].":00";
			dl::$debug=true;
			if($_POST["individual"]!=="Yes") { //this means its not an individual amendment
				//create the record in flexi_global_events to store the global event date
				//this will be added to each new users timesheet
				$global_fields = array("event_date","event_type_id");
				$global_values = array(substr($startDateTime,0,10), $eventId);
				$global_write = array_combine($global_fields, $global_values);
				//check if this global event has already been entered
				$event_exists = dl::select("flexi_global_events", "event_date ='".substr($startDateTime,0,10)."' and event_type_id = ".$eventId);
				if(empty($event_exists)) {
					dl::insert("flexi_global_events", $global_write);
				}
				$globalId = dl::select("flexi_global_events", "event_date='".substr($startDateTime,0,10)."' and event_type_id = ".$eventId);
				//now need to examine the Teams to apply this too and store the information in flexi_global_teams
				foreach($_POST["teams"] as $listTeams) {
					$team = dl::select("flexi_team", "team_name='".$listTeams."'");
					$team_exists = dl::select("flexi_global_teams", "global_id = ".$globalId[0]["global_id"]." and team_id = ".$team[0]["team_id"]);
					if(empty($team_exists)) {
						dl::insert("flexi_global_teams", array(global_id=>$globalId[0]["global_id"], team_id=>$team[0]["team_id"]));
					}
				}
				//need to check for deleted timesheets
				$sql = "SELECT u.user_id, u.user_name, date_deleted from flexi_user as u 
				LEFT JOIN flexi_deleted as d 
				on (u.user_id=d.user_id)
				WHERE
				date_deleted is NULL";
				$users = dl::getQuery($sql);
				foreach($users as $user) {
					$sql = "select * from flexi_team_user as tu 
					join flexi_team as t 
					on (t.team_id=tu.team_id) 
					join flexi_team_local as tl
					on (tu.team_user_id = tl.team_user_id)
					where user_id = ".$user["user_id"];
					$teams = dl::getQuery($sql);
					if(in_array($teams[0]["team_name"],$_POST["teams"])) {
						$timeStart = strtotime($startDateTime);
						//need to check here for the days_setting template to see if the day is valid and can be entered
						//need to find out which day the posted date is for
						$dayNo = date("N", $timeStart);
						//now need to find the flexi_template_days_setting
						$sql 	= "select * from flexi_user as u 
						join flexi_template as t on (u.user_flexi_template=t.template_id) 
						join flexi_template_days as td on (td.template_name_id=t.template_name_id) 
						join flexi_template_days_settings as tds on (tds.template_days_id=td.flexi_template_days_id) 
						where u.user_id =".$user["user_id"];
						$settings = dl::getQuery($sql);
						$days_settings_id = $settings[0]["days_settings_id"];
						//lets get the day duration for the first day of the event
						$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = ".$dayNo);
						if(empty($duration_link)) { //then assume this is a template that applies to all of the days
							$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = 6");
						}
						if(empty($duration_link)) { //this is not a day matched in the template
							$checked =false;
						}else{
							$add_lunch = 0; //no minimum lunch for this template
							//need to check if there is a minimum lunch to add to the end time
							if($minimum_lunch == "Yes") {
								if(date("h", strtotime($duration_link[0]["fdt_working_time"])) >= 6) { // check if the time is greater than or equal to 6 hours. This is a EUROPEAN directive....
									$add_lunch = strtotime($minimum_lunch_duration); // add min lunch as it will be taken off
								}			
							}
							$duration = $duration_link[0]["fdt_working_time"];
							$startTime = $_POST["duration_time_start"].":".$_POST["duration_time_start_mins"].":00";
							$startTimeSecs = $_POST["duration_time_start"] * 60 * 60 + $_POST["duration_time_start_mins"] * 60;
							$endTimeSecs = substr($duration,0,2) * 60 * 60 + substr($duration,3,2) * 60 + substr($duration,6,2) * 60;
							$endTime = date("H:i:s", $startTimeSecs + $endTimeSecs+ $add_lunch);
							$startDateTime = date('Y-m-d', $timeStart)." ".$startTime;
							$endDateTime = date('Y-m-d', $timeStart)." ".$endTime;
							$sql = "select * from flexi_user as u join flexi_timesheet as t on (u.user_id=t.user_id) where u.user_id = ".$user["user_id"];
							$timesheet = dl::getQuery($sql);
							$timeSheetId = $timesheet[0]["timesheet_id"];
							$eventFields = array("timesheet_id","event_startdate_time","event_enddate_time","event_type_id");
							$eventValues = array($timeSheetId, $startDateTime, $endDateTime, $eventId);
							$save = array_combine($eventFields, $eventValues);

							//check if the event already exists and don't add it if it does.
							$sql = "select * from flexi_event as e 
							join flexi_timesheet as t 
							on (e.timesheet_id=t.timesheet_id)
							where t.user_id = ".$user["user_id"]." and event_startdate_time = '". $startDateTime. "' and event_enddate_time = '".$endDateTime."'";
							$exists = dl::getQuery($sql);
							echo "<BR>$sql<BR>";
							if(empty($exists)) {
								dl::insert("flexi_event", $save);
							}else{
								echo "<BR>Event already exists... Not Added:<BR>";
							}
						}
					}
					
				}
				die();
			}else{ // this is an individual amendment to a GLOBAL event
				$timeStart = strtotime($startDateTime);
				//need to check here for the days_setting template to see if the day is valid and can be entered
				//need to find out which day the posted date is for
				$dayNo = date("N", $timeStart);
				//now need to find the flexi_template_days_setting
				$sql 	= "select * from flexi_user as u 
				join flexi_template as t on (u.user_flexi_template=t.template_id) 
				join flexi_template_days as td on (td.template_name_id=t.template_name_id) 
				join flexi_template_days_settings as tds on (tds.template_days_id=td.flexi_template_days_id) 
				where u.user_id =".$_GET["user"];
				$settings = dl::getQuery($sql);
				$days_settings_id = $settings[0]["days_settings_id"];
				//lets get the day duration for the first day of the event
				$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = ".$dayNo);
				if(empty($duration_link)) { //then assume this is a template that applies to all of the days
					$duration_link = dl::select("flexi_day_times", "fdt_flexi_days_id = ".$days_settings_id." and fdt_weekday_id = 6");
				}
				if(empty($duration_link)) { //this is not a day matched in the template
					echo "<SCRIPT language='javascript'>alert('You have tried to add a leave request on a day that is not specified in the users settings template. Choose a day that matches a working day in your working week and if you are adding multiple requests then choose any end date in the following days. The system will then check your template and add only the matching days.')</SCRIPT>" ;
				}else{
					$add_lunch = 0; //no minimum lunch for this template
					//need to check if there is a minimum lunch to add to the end time
					if($minimum_lunch == "Yes") {
						if(date("h", strtotime($duration_link[0]["fdt_working_time"])) >= 6) { // check if the time is greater than or equal to 6 hours. This is a EUROPEAN directive....
							$add_lunch = strtotime($minimum_lunch_duration); // add min lunch as it will be taken off
						}			
					}
					$duration = $duration_link[0]["fdt_working_time"];
					$startTime = $_POST["duration_time_start"].":".$_POST["duration_time_start_mins"].":00";
					$startTimeSecs = $_POST["duration_time_start"] * 60 * 60 + $_POST["duration_time_start_mins"] * 60;
					$endTimeSecs = substr($duration,0,2) * 60 * 60 + substr($duration,3,2) * 60 + substr($duration,6,2) * 60;
					$endTime = date("H:i:s", $startTimeSecs + $endTimeSecs+ $add_lunch);
					$startDateTime = date('Y-m-d', $timeStart)." ".$startTime;
					$endDateTime = date('Y-m-d', $timeStart)." ".$endTime;
					$sql = "select * from flexi_user as u join flexi_timesheet as t on (u.user_id=t.user_id) where u.user_id = ".$_GET["user"];
					$timesheet = dl::getQuery($sql);
					$timeSheetId = $timesheet[0]["timesheet_id"];
					$eventFields = array("timesheet_id","event_startdate_time","event_enddate_time","event_type_id");
					$eventValues = array($timeSheetId, $startDateTime, $endDateTime, $eventId);
					$save = array_combine($eventFields, $eventValues);

					//check if the event already exists and don't add it if it does.
					$sql = "select * from flexi_event as e 
					join flexi_timesheet as t 
					on (e.timesheet_id=t.timesheet_id)
					where t.user_id = ".$_GET["user"]." and event_startdate_time = '". $startDateTime. "' and event_enddate_time = '".$endDateTime."'";
					$exists = dl::getQuery($sql);
					echo "<BR>$sql<BR>";
					if(empty($exists)) {
						dl::insert("flexi_event", $save);
					}else{
						echo "<BR>Event already exists... Not Added:<BR>";
					}
				}
				die();
				echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=timesheet&type=".$_GET["type"]."&userid=".$_GET["user"]."')</SCRIPT>" ;
			}
		}
		$_SESSION["otherUser"]=""; //remove the session variable 
	}else{
		echo "<SCRIPT language='javascript'>alert('This event cannot overlap another event. Please check you have entered the correct dates for this event, or delete the existing event to create the new one.')</SCRIPT>" ;
	}
	if($_SESSION["pageLocation"] == "nextperiod" or $_SESSION["pageLocation"] == "previousperiod") {
		echo "<SCRIPT language='javascript'>redirect('index.php?func=".$_SESSION["pageLocation"]."&start=".$_SESSION["startPeriod"]."&end=".$_SESSION["endPeriod"]."&userid=".$_SESSION["user"]."')</SCRIPT>" ;
	}elseif($_SESSION["pageLocation"] == "viewuserstimesheet") {
			echo "<SCRIPT language='javascript'>redirect('index.php?func=".$_SESSION["pageLocation"]."&userid=".$_SESSION["user"]."')</SCRIPT>" ;
	}else{
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=timesheet')</SCRIPT>" ;
	}
}

function add_date($givendate,$day=0,$mth=0,$yr=0) {
	$cd = $givendate;
	$newdate = date('Y-m-d H:i:s', mktime(date('h',$cd),
    date('i',$cd), date('s',$cd), date('m',$cd)+$mth,
    date('d',$cd)+$day, date('Y',$cd)+$yr));
      return $newdate;
}

function sub_date($givendate,$day=0,$mth=0,$yr=0) {
	$cd = $givendate;
	$newdate = date('Y-m-d H:i:s', mktime(date('h',$cd),
    date('i',$cd), date('s',$cd), date('m',$cd)-$mth,
    date('d',$cd)-$day, date('Y',$cd)-$yr));
      return $newdate;
}

function view_events($userId="", $page=0) {
	if(check_permission("Events")) {

		$rows = 25;
		if(empty($userId)) {
			$userId=$_SESSION["userSettings"]["userId"];
		}
		//find the timesheet id for the user
		$timesheet = dl::select("flexi_timesheet", "user_id=".$userId);
		$sql="select * from flexi_user join 
		flexi_time_template_name as tn on (time_template_name_id=user_time_template) 
		join flexi_time_template as tt on (tt.time_template_name_id=tn.time_template_name_id) 
		where user_id=".$userId;
		$date_format = dl::getQuery($sql);
		$dateFormat = $date_format[0]["time_template_date_format"];
		if($dateFormat == "dd-mm-yyyy") {
			$dFormat="d-m-Y";	
		}else{
			$dFormat="m-d-Y";
		}
		echo "<div class='timesheet_header'>Viewing ".$date_format[0]["user_name"]."'s Events</div>";
		echo "<div class='timesheet_name'>To edit an event first delete it then create it again<BR>Only your manager can edit locked events<br></div>";		
		$events = dl::select("flexi_event", "timesheet_id=".$timesheet[0]["timesheet_id"], "event_startdate_time DESC LIMIT $page,$rows");
		$sql = "select count(event_id) as num from flexi_event where timesheet_id=".$timesheet[0]["timesheet_id"]; 
		$numRows = dl::getQuery($sql);
		$rowCount = $numRows[0]["num"];
		echo "<table class='table_view'>";
		echo "<tr><th>Date</th><th>Start Time</th><th>End Time</th><th>Event Type</th><th>Delete</th></tr>";
		foreach($events as $event) {
			$type=dl::select("flexi_event_type", "event_type_id=".$event["event_type_id"]);
			$requests = dl::select("flexi_requests", "request_event_id = ".$event["event_id"]);
			if(empty($requests[0]["request_approved"]) and !empty($requests)) { //request has yet to be approved
				$css="border:1px ".$type[0]["event_colour"]." solid;";
			}else{
				$css="background-color:".$type[0]["event_colour"].";";
			}
			//are there any notes attached to this event
			$eventNotes = dl::select("flexi_event_notes", "event_id = ".$event["event_id"]);
			$notes = dl::select("flexi_notes", "notes_id=".$eventNotes[0]["note_id"]." and notes_type <> 'Private'");
			//need to find the flexi template period start and end date and check the dates are within the range.
			$user=dl::select("flexi_user", "user_id=".$userId);
			$flexi_template=dl::select("flexi_template", "template_id=".$user[0]["user_flexi_template"]);
			$startPeriod = $flexi_template[0]["start_period"];
			$endPeriod = $flexi_template[0]["end_period"];
			if(substr($event["event_startdate_time"],0,10)>=$startPeriod) {
				$lockDate = sub_date(strtotime(date("Y-m-d")), 7);
				if(substr($event["event_startdate_time"],0,10) < date("Y-m-d",strtotime($lockDate))) {
					if($type[0]["event_global"]=="Yes") {
						if($_SESSION["userPermissions"]["override_delete"]=='false') {
							echo "<tr><td>".date($dFormat, strtotime(substr($event["event_startdate_time"],0,10)))." [".date("l", strtotime(substr($event["event_startdate_time"],0,10)))."]"."</td><td>".substr($event["event_startdate_time"],11,5)."</td><td>".substr($event["event_enddate_time"],11,5)."</td>";
							if(empty($notes[0]["notes_note"])) {
								echo "<td style='".$css."'>".$type[0]["event_type_name"]."</td>";
							}else{
								echo "<td style='".$css."'><a href='#' title='".$notes[0]["notes_note"]."'>".$type[0]["event_type_name"]."</a></td>";
							}
							echo "<td style='text-align:center'><img src='inc/images/Padlock-red.png'></td></tr>";
						}else{
							echo "<tr><td>".date($dFormat, strtotime(substr($event["event_startdate_time"],0,10)))." [".date("l", strtotime(substr($event["event_startdate_time"],0,10)))."]"."</td><td>".substr($event["event_startdate_time"],11,5)."</td><td>".substr($event["event_enddate_time"],11,5)."</td>";
							if(empty($notes[0]["notes_note"])) {
								echo "<td style='".$css."'>".$type[0]["event_type_name"]."</td>";
							}else{
								echo "<td style='".$css."'><a href='#' title='".$notes[0]["notes_note"]."'>".$type[0]["event_type_name"]."</a></td>";
							}
							echo "<td><a href='index.php?func=deleteevent&id=".$event["event_id"]."'>Delete</a></td></tr>";			
						}
					}else{
						echo "<tr><td>".date($dFormat, strtotime(substr($event["event_startdate_time"],0,10)))." [".date("l", strtotime(substr($event["event_startdate_time"],0,10)))."]"."</td><td>".substr($event["event_startdate_time"],11,5)."</td><td>".substr($event["event_enddate_time"],11,5)."</td>";
						if(empty($notes[0]["notes_note"])) {
								echo "<td style='".$css."'>".$type[0]["event_type_name"]."</td>";
							}else{
								echo "<td style='".$css."'><a href='#' title='".$notes[0]["notes_note"]."'>".$type[0]["event_type_name"]."</a></td>";
							}
						echo "<td><a href='index.php?func=deleteevent&id=".$event["event_id"]."'>Delete</a></td></tr>";			
					}
				}else{
					if($type[0]["event_global"]=="Yes") {
						if($_SESSION["userPermissions"]["override_delete"]=='false') {
							echo "<tr><td>".date($dFormat, strtotime(substr($event["event_startdate_time"],0,10)))." [".date("l", strtotime(substr($event["event_startdate_time"],0,10)))."]"."</td><td>".substr($event["event_startdate_time"],11,5)."</td><td>".substr($event["event_enddate_time"],11,5)."</td>";
							if(empty($notes[0]["notes_note"])) {
								echo "<td style='".$css."'>".$type[0]["event_type_name"]."</td>";
							}else{
								echo "<td style='".$css."'><a href='#' title='".$notes[0]["notes_note"]."'>".$type[0]["event_type_name"]."</a></td>";
							}
							echo "<td style='text-align:center'><img src='inc/images/Padlock-red.png'></td></tr>";
						}else{
							echo "<tr><td>".date($dFormat, strtotime(substr($event["event_startdate_time"],0,10)))." [".date("l", strtotime(substr($event["event_startdate_time"],0,10)))."]"."</td><td>".substr($event["event_startdate_time"],11,5)."</td><td>".substr($event["event_enddate_time"],11,5)."</td>";
							if(empty($notes[0]["notes_note"])) {
								echo "<td style='".$css."'>".$type[0]["event_type_name"]."</td>";
							}else{
								echo "<td style='".$css."'><a href='#' title='".$notes[0]["notes_note"]."'>".$type[0]["event_type_name"]."</a></td>";
							}
							echo "<td><a href='index.php?func=deleteevent&id=".$event["event_id"]."'&userid=".$userId.">Delete</a></td></tr>";			
						}
					}else{
						echo "<tr><td>".date($dFormat, strtotime(substr($event["event_startdate_time"],0,10)))." [".date("l", strtotime(substr($event["event_startdate_time"],0,10)))."]"."</td><td>".substr($event["event_startdate_time"],11,5)."</td><td>".substr($event["event_enddate_time"],11,5)."</td>";
						if(empty($notes[0]["notes_note"])) {
							echo "<td style='".$css."'>".$type[0]["event_type_name"]."</td>";
						}else{
							echo "<td style='".$css."'><a href='#' title='".$notes[0]["notes_note"]."'>".$type[0]["event_type_name"]."</a></td>";
						}
						echo "<td><a href='index.php?func=deleteevent&id=".$event["event_id"]."'>Delete</a></td></tr>";			
					}
				}
			}else{
				if($_SESSION["userPermissions"]["override_delete"]=='true') {
					echo "<tr><td style='color:#888'>".date($dFormat, strtotime(substr($event["event_startdate_time"],0,10)))." [".date("l", strtotime(substr($event["event_startdate_time"],0,10)))."]"."</td><td style='color:#888'>".substr($event["event_startdate_time"],11,5)."</td><td style='color:#888'>".substr($event["event_enddate_time"],11,5)."</td>";
					if(empty($notes[0]["notes_note"])) {
						echo "<td style='".$css."'>".$type[0]["event_type_name"]."</td>";
					}else{
						echo "<td style='".$css."'><a href='#' title='".$notes[0]["notes_note"]."'>".$type[0]["event_type_name"]."</a></td>";
					}
					echo "<td><a href='index.php?func=deleteevent&id=".$event["event_id"]."'>Delete</a></td></tr>";
				}else{
					if(substr($event["event_startdate_time"],0,10)>=sub_date(strtotime($startPeriod),28) and substr($event["event_startdate_time"],0,10)<=sub_date(strtotime($endPeriod),28)) {
						echo "<tr><td style='color:#888'>".date($dFormat, strtotime(substr($event["event_startdate_time"],0,10)))." [".date("l", strtotime(substr($event["event_startdate_time"],0,10)))."]"."</td><td style='color:#888'>".substr($event["event_startdate_time"],11,5)."</td><td style='color:#888'>".substr($event["event_enddate_time"],11,5)."</td>";
						if(empty($notes[0]["notes_note"])) {
								echo "<td style='".$css."'>".$type[0]["event_type_name"]."</td>";
							}else{
								echo "<td style='".$css."'><a href='#' title='".$notes[0]["notes_note"]."'>".$type[0]["event_type_name"]."</a></td>";
							}
						echo "<td style='text-align:center'><img src='inc/images/Padlock-red.png'></td></tr>";
					}
				}
			}
		}
		echo "</table>";
		$page+=25;
		if($page > 25 ){
			$prevPage = $page - 50;
			$previous = true;
		}
		if($page < $rowCount) {
			echo "<BR>&nbsp;<a href='index.php?func=edituserevents&userid=$userId&page=$page'>Next Page</a>";
		}else{
			echo "<br>";
		}
		if($previous){
			echo "&nbsp;<a href='index.php?func=edituserevents&userid=$userId&page=$prevPage'>Previous Page</a>";
		}
		if(check_permission("Team Events")) {
			echo "<div class='edit_icon'><BR> <a href='index.php?func=adduserevent&userid=$userId' ><img src='inc/images/notebook_edit.png' align='middle' border='0' />Add user Event</a></div>";
		}

	}
}

function delete_events($id, $confirmation="", $deltype="") {
	if(check_permission("Events")) {

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
		if(empty($confirmation)) {
			if($eventGlobal=="No"){ 
				?>
				<script type="text/javascript">
				<!--
		
				var answer = confirm ("Confirm that you really want to delete this event? \n\nBe aware that an email will be sent to your manager informing them of the deletion, if this deletion is any type of leave request.")
				if (answer)
					redirect ("index.php?func=deleteevent&id=<?php echo $id?>&confirmation=true");
				else
						redirect ("index.php?func=edituserevents&userid=<?php echo $userId?>&page=0");
				// -->
				</script> 
		
				<?php
			}else{ //this is a global event deletion. Need to find out if it is a full deletion or just from an individual
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
		}else{
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
			if($eventWork == "No") {
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
			//also need to delete the request to tie up all the loose ends.
			echo "<SCRIPT language='javascript'>redirect('index.php?func=edituserevents&userid=$userId&page=0')</SCRIPT>" ;
		}
	}
}


function add_event_type() {
	if(check_permission("Event Types")) {
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Add Event Type", "formintro"=>"Create the Event types"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveeventtype","method"=>"post", "formname"=>"types")),	
			array("prompt"=>"Event Type Name", "type"=>"text", "name"=>"type_name", "length"=>20, "value"=>"Enter a name", "clear"=>true),	
			array("prompt"=>"Event Short code", "type"=>"text", "name"=>"short_code", "length"=>20, "value"=>"Two character code", "clear"=>true),
			array("prompt"=>"Event Type Description", "type"=>"textarea", "name"=>"type_description", "rows"=>6, "cols"=>50, "value"=>"", "clear"=>true),
			array("prompt"=>"Signifies Leave", "type"=>"checkbox", "name"=>"leave_tag", "value"=>"Yes", "clear"=>false),
			array("prompt"=>"Signifies Flexitime", "type"=>"checkbox", "name"=>"flexi_tag", "value"=>"Yes", "clear"=>false),
			array("prompt"=>"Working Session", "type"=>"checkbox", "name"=>"work_tag", "value"=>"Yes", "clear"=>true),
			array("prompt"=>"Signifies Sickness", "type"=>"checkbox", "name"=>"sick_tag", "value"=>"Yes", "clear"=>false),
			array("prompt"=>"Global", "type"=>"checkbox", "name"=>"global_tag", "value"=>"Yes", "clear"=>false),
			array("prompt"=>"Requires Authorisation", "type"=>"checkbox", "name"=>"author_tag", "value"=>"Yes", "clear"=>true),
			array("prompt"=>"User can delete", "type"=>"checkbox", "name"=>"del_tag", "value"=>"Yes", "clear"=>false),
			array("prompt"=>"Can override Template", "type"=>"checkbox", "name"=>"override_template", "value"=>"Yes", "clear"=>false),
			array("prompt"=>"Show Remainder", "type"=>"checkbox", "name"=>"remainder", "value"=>"Yes", "clear"=>true),
			array("prompt"=>"Select Colour", "type"=>"colour", "name"=>"sel_colour", "length"=>20, "value"=>"", "clear"=>true),
			array("prompt"=>"Duration Type", "type"=>"radio", "name"=>"duration_type", "listarr"=>array("Fixed","User definable", "Both"), "selected"=>"fixed", "value"=>"", "clear"=>true),
			array("prompt"=>"Changes Time", "type"=>"checkbox", "name"=>"changes_time", "value"=>"Yes", "clear"=>false),
			array("prompt"=>"Multi Date Allowed", "type"=>"checkbox", "name"=>"multi_date", "value"=>"Yes", "clear"=>true),
			array("prompt"=>"Working Event", "type"=>"checkbox", "name"=>"work", "value"=>"Yes", "clear"=>false),
			array("prompt"=>"Deduct Lunch", "type"=>"checkbox", "name"=>"lunch_deduction", "value"=>"Yes", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Add Event", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
	}
}

function save_event_type() {
	$fieldarr = array("event_type_name", "event_colour", "event_shortcode","event_description","event_al", "event_flexi","event_work","event_global","event_authorisation", "event_sickness","event_delete", "event_override", "remainder");
	$leave=$_POST["leave_tag"];
	$flexi=$_POST["flexi_tag"];
	$work=$_POST["work_tag"];
	$sick=$_POST["sick_tag"];
	$global=$_POST["global_tag"];
	$author=$_POST["author_tag"];
	$delete=$_POST["del_tag"];
	$override=$_POST["override_template"];
	$remainder = $_POST["remainder"];
	if($leave == "") {
		$leave = "No";
	}
	if($flexi == "") {
		$flexi = "No";
	}
	if($work == "") {
		$work = "No";
	}
	if($global == "") {
		$global = "No";
	}
	if($sick == "") {
		$sick = "No";
	}
	if($author == "") {
		$author = "No";
	}
	if($delete == "") {
		$delete = "No";
	}
	if($override== "") {
		$override = "No";
	}
	if($remainder== "") {
		$remainder = "No";
	}
	$postarr = array($_POST["type_name"],$_POST["sel_colour"],$_POST["short_code"],$_POST["type_description"],$leave, $flexi, $work, $global, $author, $sick, $delete, $override, $remainder);
	$save = array_combine($fieldarr, $postarr);
	dl::insert("flexi_event_type", $save);
	//saved the template name now need to get the template name id
	$get_id = dl::select("flexi_event_type", "event_type_name = '".$_POST['type_name']."'");
	foreach($get_id as $id) {
		$fieldId = $id["event_type_id"];
	}
	$fieldarr= array("event_typeid", "duration_type", "changes_time","multi_date_allowed","working_event","lunch_deduction");
	$postarr= array($fieldId,$_POST["duration_type"],$_POST["changes_time"],$_POST["multi_date"],$_POST["work"],$_POST["lunch_deduction"]);
	$save=array_combine($fieldarr, $postarr);
	dl::insert("flexi_event_settings", $save);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=EventTypes')</SCRIPT>" ;
}

function view_event_types() {
	echo "<div class='timesheet_header'>View Event Types</div>";
	$events = dl::select("flexi_event_type");
	echo "<table class='table_view'>";
	echo "<tr><th>Name</th><th>Colour</th><th>Short Code</th><th>Description</th><th>Annual Leave</th><th>Flexi</th><th>Work</th><th>Sick</th><th>Global</th><th>Author-isation</th><th>Can delete</th><th>Can Override</th><th>Show remainder</th></tr>";
	foreach($events as $event) {
		echo "<tr><td>".$event["event_type_name"]."</td><td style='background-color:".$event["event_colour"].";' >&nbsp;</td><td>".$event["event_shortcode"]."</td><td>".$event["event_description"]."</td><td>".$event["event_al"]."</td><td>".$event["event_flexi"]."</td><td>".$event["event_work"]."</td><td>".$event["event_sickness"]."</td><td>".$event["event_global"]."</td><td>".$event["event_authorisation"]."</td><td>".$event["event_delete"]."</td><td>".$event["event_override"]."</td><td>".$event["event_remainder"]."</td></tr>";
	}
	echo "</table>";
}
function select_event_types() {
	if(check_permission("Event Types")) {

		echo "<div class='timesheet_header'>Edit Event Types</div>";
		$events = dl::select("flexi_event_type");
		echo "<table class='table_view'>";
		echo "<tr><th>Name</th><th>Colour</th><th>Short Code</th><th>Description</th><th>Leave</th><th>Flexi</th><th>Work</th><th>Sick</th><th>Global</th><th>Author-isation</th><th>Can delete</th><th>Can Override</th><th>Show remainder</th><th>Delete</th><th>Edit</th></tr>";
		foreach($events as $event) {
			echo "<tr><td>".$event["event_type_name"]."</td><td style='background-color:".$event["event_colour"].";' >&nbsp;</td><td>".$event["event_shortcode"]."</td><td>".$event["event_description"]."</td><td>".$event["event_al"]."</td><td>".$event["event_flexi"]."</td><td>".$event["event_work"]."</td><td>".$event["event_sickness"]."</td><td>".$event["event_global"]."</td><td>".$event["event_authorisation"]."</td><td>".$event["event_delete"]."</td><td>".$event["event_override"]."</td><td>".$event["event_remainder"]."</td><td><a href='index.php?func=deleteeventtype&id=".$event["event_type_id"]."'>delete</a></td><td><a href='index.php?func=editeventtype&id=".$event["event_type_id"]."'>edit</a></td></tr>";
		}
		echo "</table>";
	}
}

function edit_event_types() {
	if(check_permission("Event Types")) {

		$sql = "select * from flexi_event_type join flexi_event_settings on (event_type_id=event_typeid) where event_type_id = ".$_GET["id"];
		$editTypes = dl::getQuery($sql);
		echo "<div class='timesheet_workspace'>";
		foreach($editTypes as $editType) {
			$formArr = array(array("type"=>"intro", "formtitle"=>"Edit Event Type", "formintro"=>"Edit the Event types"), 
				array("type"=>"form", "form"=>array("action"=>"index.php?func=saveeventtypeedit&id=".$_GET["id"],"method"=>"post", "formname"=>"types")),	
				array("prompt"=>"Event Type Name", "type"=>"text", "name"=>"type_name", "length"=>20, "value"=>$editType["event_type_name"], "clear"=>true),	
				array("prompt"=>"Event Short code", "type"=>"text", "name"=>"short_code", "length"=>20, "value"=>$editType["event_shortcode"], "clear"=>true),
				array("prompt"=>"Event Type Description", "type"=>"textarea", "name"=>"type_description", rows=>6, cols=>50, "value"=>$editType["event_description"], "clear"=>true),
				array("prompt"=>"Signifies Leave", "type"=>"checkbox", "name"=>"leave_tag", "selected"=>$editType["event_al"], "value"=>"Yes", "clear"=>false),
				array("prompt"=>"Signifies Flexi", "type"=>"checkbox", "name"=>"flexi_tag", "selected"=>$editType["event_flexi"], "value"=>"Yes", "clear"=>false),
				array("prompt"=>"Working Session", "type"=>"checkbox", "name"=>"work_tag", "selected"=>$editType["event_work"], "value"=>"Yes", "clear"=>true),
				array("prompt"=>"Signifies Sickness", "type"=>"checkbox", "name"=>"sick_tag", "selected"=>$editType["event_sickness"], "value"=>"Yes", "clear"=>false),
				array("prompt"=>"Global", "type"=>"checkbox", "name"=>"global_tag", "selected"=>$editType["event_global"], "value"=>"Yes", "clear"=>false),
				array("prompt"=>"Requires Authorisation", "type"=>"checkbox", "name"=>"author_tag", "selected"=>$editType["event_authorisation"], "value"=>"Yes", "clear"=>true),
				array("prompt"=>"User can delete", "type"=>"checkbox", "name"=>"del_tag", "selected"=>$editType["event_delete"], "value"=>"Yes", "clear"=>false),
				array("prompt"=>"Can Override Template", "type"=>"checkbox", "name"=>"override_template", "selected"=>$editType["event_override"], "value"=>"Yes", "clear"=>false),
				array("prompt"=>"Show remainder", "type"=>"checkbox", "name"=>"remainder", "selected"=>$editType["event_remainder"], "value"=>"Yes", "clear"=>true),
				array("prompt"=>"Current Colour (select a new colour to change)", "type"=>"text", "name"=>"sel_colour", "length"=>20, "value"=>$editType["event_colour"], "clear"=>true),
				array("prompt"=>"Select Colour", "type"=>"colour", "name"=>"new_colour", "length"=>20, "value"=>$editType["event_colour"], "clear"=>true),
				array("prompt"=>"Duration Type", "type"=>"radio", "name"=>"duration_type", "listarr"=>array("Fixed","User definable", "Both"), "selected"=>$editType["duration_type"], "value"=>"", "clear"=>true),
				array("prompt"=>"Changes Time", "type"=>"checkbox", "name"=>"changes_time", "selected"=>$editType["changes_time"], "value"=>"Yes", "clear"=>false),
				array("prompt"=>"Multi Date Allowed", "type"=>"checkbox", "name"=>"multi_date", "selected"=>$editType["multi_date_allowed"], "value"=>"Yes", "clear"=>true),
				array("prompt"=>"Working Event", "type"=>"checkbox", "name"=>"work", "selected"=>$editType["working_event"], "value"=>"Yes", "clear"=>false),
				array("prompt"=>"Deduct Lunch", "type"=>"checkbox", "name"=>"lunch_deduction", "selected"=>$editType["lunch_deduction"], "value"=>"Yes", "clear"=>true),
				array("type"=>"submit", "buttontext"=>"Save Event", "clear"=>true), 
				array("type"=>'endform'));
		}
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
	}
}

function save_event_type_edit() {
	$fieldarr = array("event_type_name", "event_colour", "event_shortcode","event_description", "event_al", "event_flexi","event_work","event_global","event_authorisation","event_sickness","event_delete", "event_override", "event_remainder");
	if(!empty($_POST["new_colour"])) {
		$colour=$_POST["new_colour"]; 
	}else{
		$colour=$_POST["sel_colour"];
	}	
	$leave=$_POST["leave_tag"];
	$flexi=$_POST["flexi_tag"];
	$work=$_POST["work_tag"];
	$global=$_POST["global_tag"];
	$sick=$_POST["sick_tag"];
	$author=$_POST["author_tag"];
	$delete=$_POST["del_tag"];
	$override = $_POST["override_template"];
	$remainder = $_POST["remainder"];
	if($leave == "") {
		$leave = "No";
	}
	if($flexi == "") {
		$flexi = "No";
	}
	if($work == "") {
		$work = "No";
	}
	if($global == "") {
		$global = "No";
	}
	if($sick == "") {
		$sick = "No";
	}
	if($author == "") {
		$author = "No";
	}
	if($override == "") {
		$override = "No";
	}
	if($remainder == "") {
		$remainder = "No";
	}
	if($delete == "") {
		$delete = "No";
	}
	$postarr = array($_POST["type_name"],$colour,$_POST["short_code"],$_POST["type_description"],$leave, $flexi, $work, $global, $author, $sick, $delete, $override, $remainder);
	$save = array_combine($fieldarr, $postarr);
	dl::update("flexi_event_type", $save, "event_type_id=".$_GET["id"]);
	//now update the settings
	$fieldarr= array("duration_type", "changes_time","multi_date_allowed","working_event","lunch_deduction");
	$postarr= array($_POST["duration_type"],$_POST["changes_time"],$_POST["multi_date"],$_POST["work"],$_POST["lunch_deduction"]);
	$save=array_combine($fieldarr, $postarr);
	dl::update("flexi_event_settings", $save, "event_typeid=".$_GET["id"]);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=EventTypes')</SCRIPT>" ;	
}

function delete_event_type($id) {
	if(check_permission("Event Types")) {

		dl::delete("flexi_event_type", "event_type_id=$id");
		dl::delete("flexi_event_settings", "event_typeid=$id");
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=Edit&subchoice=editeventtype')</SCRIPT>" ;
	}
}

function add_event_duration() {
	if(check_permission("Event Types")) {

		$template_names = dl::select("flexi_template_days","","template_days_name ASC");
		foreach($template_names as $names) {
			$tNames[]=$names["template_days_name"];
		}
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Add Event Duration", "formintro"=>"Create an Event Duration"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveeventduration","method"=>"post")),	
			array("prompt"=>"Name", "type"=>"text", "name"=>"name", "length"=>20, "value"=>"Enter a name", "clear"=>true),
			array("prompt"=>"Template", "type"=>"selection", "name"=>"choose_template", "listarr"=>$tNames, "value"=>"", "clear"=>true),	
			array("prompt"=>"Duration", "type"=>"time", "name"=>"duration", "starttime"=>"0100", "endtime"=>"0900", "interval"=>1, "value"=>"Enter leave period", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Add New Duration", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
	}
}

function save_event_duration() {
	$id = dl::select("flexi_template_days", "template_days_name='".$_POST["choose_template"]."'");
	$fieldarr = array("name", "template_link", "duration");
	$postarr = array($_POST["name"],$id[0]["flexi_template_days_id"],$_POST["duration"].":".$_POST["duration_mins"].":00");
	$save = array_combine($fieldarr, $postarr);
	dl::insert("flexi_fixed_durations", $save);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=EventDurations')</SCRIPT>" ;	
}

function save_event_duration_edit($id) {
	$link = dl::select("flexi_template_days", "template_days_name='".$_POST["choose_template"]."'");
	$fieldarr = array("name", "template_link", "duration");
	$postarr = array($_POST["name"],$link[0]["flexi_template_days_id"],$_POST["duration"].":".$_POST["duration_mins"].":00");
	$save = array_combine($fieldarr, $postarr);
	dl::update("flexi_fixed_durations", $save, "fixed_durations_id=".$id);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=EventDurations')</SCRIPT>" ;	
}

function view_event_durations() {
	echo "<div class='timesheet_header'>View Event Durations</div>";
	$events = dl::select("flexi_fixed_durations");
	echo "<table class='table_view'>";
	echo "<tr><th>Name</th><th>Duration</th></tr>";
	foreach($events as $event) {
		echo "<tr><td>".$event["name"]."</td><td>".$event["duration"]."</td></tr>";
	}
	echo "</table>";
}

function edit_event_durations() {
	if(check_permission("Event Types")) {

		$edits = dl::select("flexi_fixed_durations", "fixed_durations_id = ".$_GET["id"]);
		$link = $edits[0]["template_link"];
		$template_names = dl::select("flexi_template_days","","template_days_name ASC");
		foreach($template_names as $names) {
			$tNames[]=$names["template_days_name"];
		}
		$selection = dl::select("flexi_template_days", "flexi_template_days_id=$link");
		echo "<div class='timesheet_workspace'>";
		foreach($edits as $edit) {
			$formArr = array(array("type"=>"intro", "formtitle"=>"Edit Event Duration", "formintro"=>"Edit an Event Duration"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveeventdurationedit&id=".$_GET["id"],"method"=>"post")),	
			array("prompt"=>"Name", "type"=>"text", "name"=>"name", "length"=>20, "value"=>$edit["name"], "clear"=>true),
			array("prompt"=>"Template", "type"=>"selection", "name"=>"choose_template", "listarr"=>$tNames, "selected"=>$selection[0]["template_days_name"], "value"=>"", "clear"=>true),	
			array("prompt"=>"Duration", "type"=>"time", "name"=>"duration", "starttime"=>"0100", "endtime"=>"0900", "interval"=>1, "selected"=>$edit["duration"], "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Save Duration", "clear"=>true), 
			array("type"=>'endform'));
		}
		$form = new forms;
		$form->create_form($formArr);
		
		echo "</div>";
	}
}

function select_event_durations() {
	if(check_permission("Event Types")) {
		echo "<div class='timesheet_header'>Edit Event Durations</div>";
		$events = dl::select("flexi_fixed_durations");
		echo "<table class='table_view'>";
		echo "<tr><th>Name</th><th>Duration</th><th>Delete</th><th>Edit</th></tr>";
		foreach($events as $event) {
			echo "<tr><td>".$event["name"]."</td><td>".$event["duration"]."</td><td><a href='index.php?func=deleteeventdurations&id=".$event["fixed_durations_id"]."'>delete</a></td><td><a href='index.php?func=editeventdurations&id=".$event["fixed_durations_id"]."'>edit</a></td></tr>";
		}
		echo "</table>";
	}
}

function delete_event_durations($id) {
	if(check_permission("Event Types")) {
		dl::delete("flexi_fixed_durations", "fixed_durations_id=$id");
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=Edit&subchoice=selecteventdurations')</SCRIPT>" ;
	}
}

/*
Start of the templates  
add Permissions template
*/
function add_permissions() {
	if(check_permission("Templates")) {
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Create Permission Template", "formintro"=>"Fill out the fields below to create the permission template"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=savepermissions","method"=>"post")),	
			array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>20, "value"=>"Enter template name", "clear"=>true),	
			array("prompt"=>"Template Description", "type"=>"textarea", "name"=>"temp_description", "rows"=>8, "cols"=>50, "value"=>"", "clear"=>true),
			array("prompt"=>"Edit Users", "type"=>"selection", "name"=>"edit_users", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"Edit Templates", "type"=>"selection", "name"=>"edit_templates", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"Manage Teams", "type"=>"selection", "name"=>"edit_teams", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"Edit Team Events", "type"=>"selection", "name"=>"edit_teamevents", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"Team Authority", "type"=>"selection", "name"=>"team_authority", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"Create Events", "type"=>"selection", "name"=>"events", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"Create Event Types", "type"=>"selection", "name"=>"event_types", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"Add Time", "type"=>"selection", "name"=>"add_time", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"Edit Time", "type"=>"selection", "name"=>"edit_time", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"Edit Locked Time", "type"=>"selection", "name"=>"edit_locked", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"Add Global Events", "type"=>"selection", "name"=>"add_global", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"Override Deletes", "type"=>"selection", "name"=>"override", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"Edit Flexipot", "type"=>"selection", "name"=>"flexipot", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"View User Leave", "type"=>"selection", "name"=>"userleave", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"User Messaging", "type"=>"selection", "name"=>"usermessaging", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),	
			array("prompt"=>"Allow view timesheet", "type"=>"selection", "name"=>"usertimesheet", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"View reports", "type"=>"selection", "name"=>"viewreports", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"Year End reports", "type"=>"selection", "name"=>"yearend", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"Override App Lock", "type"=>"selection", "name"=>"overrideLock", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>false),
			array("prompt"=>"Override view timesheet", "type"=>"selection", "name"=>"overridetimesheet", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("prompt"=>"Override Local Manager constraint", "type"=>"selection", "name"=>"overrideLM", "listarr"=>array( "false", "true" ), "selected"=>"false", "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Create Template", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
			$template=dl::select("flexi_permission_template_name");
			echo "<div style='clear:both'><table class='table_view'>";
			echo "<tr><th>Template Name</th><th>Delete</th><th>Edit</th></tr>";
			foreach($template as $temp) {
				echo "<tr><td>".$temp["permission_template_name"]."</td><td><a href='index.php?func=deletepermissiontemplate&id=".$temp["permission_id"]."'>delete</a></td><td><a href='index.php?func=editpermissiontemplate&id=".$temp["permission_id"]."'>edit</a></td></tr>";
			}
			echo "</table></div>";
		echo "</div>";
	}
}

function save_permissions() {
	$fieldarr=array("permission_template_name");
	$save = array_combine($fieldarr, array($_POST['template_name']));
	dl::insert("flexi_permission_template_name", $save);
	//saved the template name now need to get the template name id
	$get_id = dl::select("flexi_permission_template_name", "permission_template_name = '".$_POST['template_name']."'");
	foreach($get_id as $id) {
		$fieldId = $id["permission_id"];
	}
	$fieldarr= array("permission_template_name_id", "permission_description", "permission_user","permission_templates","permission_teams","permission_team_events","permission_team_authorise","permission_events","permission_event_types","permission_add_time","permission_edit_time","permission_edit_locked_time","permission_add_global","permission_override_delete","permission_edit_flexipot","permission_view_leave","permission_messaging", "permission_view_timesheet", "permission_view_override", "permission_LM_constraint","permission_view_reports", "permission_year_end", "permission_lock");
	$postarr= array($fieldId,$_POST["temp_description"],$_POST["edit_users"],$_POST["edit_templates"],$_POST["edit_teams"],$_POST["edit_teamevents"],$_POST["team_authority"],$_POST["events"], $_POST["event_types"],$_POST["add_time"],$_POST["edit_time"],$_POST["edit_locked"], $_POST["add_global"], $_POST["override"], $_POST["flexipot"], $_POST["userleave"], $_POST["usertimesheet"], $_POST["usermessaging"], $_POST["overridetimesheet"], $_POST["overrideLM"], $_POST["viewreports"], $_POST["yearend"], $_POST["overrideLock"]);
	$save=array_combine($fieldarr, $postarr);
	dl::insert("flexi_permission_template", $save);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=permissiontemplate')</SCRIPT>" ;
}

function edit_permissions() {
	if(check_permission("Templates")) {
		//get the permissions to display
		$permission_name = dl::select("flexi_permission_template_name", "permission_id=".$_GET["id"]);
		$permissions = dl::select("flexi_permission_template","permission_template_name_id=".$_GET["id"]);
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Create Permission Template", "formintro"=>"Fill out the fields below to create the permission template"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=savepermissiontemplateedit&id=".$_GET["id"],"method"=>"post")),	
			array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>20, "value"=>$permission_name[0]["permission_template_name"], "clear"=>true),	
			array("prompt"=>"Template Description", "type"=>"textarea", "name"=>"temp_description", "rows"=>8, "cols"=>50, "value"=>$permissions[0]["permission_description"], "clear"=>true),
			array("prompt"=>"Edit Users", "type"=>"selection", "name"=>"edit_users", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_user"], "value"=>"", "clear"=>false),
			array("prompt"=>"Edit Templates", "type"=>"selection", "name"=>"edit_templates", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_templates"], "value"=>"", "clear"=>true),
			array("prompt"=>"Manage Teams", "type"=>"selection", "name"=>"edit_teams", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_teams"], "value"=>"", "clear"=>false),
			array("prompt"=>"Edit Team Events", "type"=>"selection", "name"=>"edit_teamevents", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_team_events"], "value"=>"", "clear"=>true),
			array("prompt"=>"Team Authority", "type"=>"selection", "name"=>"team_authority", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_team_authorise"], "value"=>"", "clear"=>false),
			array("prompt"=>"Create Events", "type"=>"selection", "name"=>"events", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_events"], "value"=>"", "clear"=>true),
			array("prompt"=>"Create Event Types", "type"=>"selection", "name"=>"event_types", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_event_types"], "value"=>"", "clear"=>false),
			array("prompt"=>"Add Time", "type"=>"selection", "name"=>"add_time", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_add_time"], "value"=>"", "clear"=>true),
			array("prompt"=>"Edit Time", "type"=>"selection", "name"=>"edit_time", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_edit_time"], "value"=>"", "clear"=>false),
			array("prompt"=>"Edit Locked Time", "type"=>"selection", "name"=>"edit_locked", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_edit_locked_time"], "value"=>"", "clear"=>true),
			array("prompt"=>"Add Global Events", "type"=>"selection", "name"=>"add_global", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_add_global"], "value"=>"", "clear"=>false),
			array("prompt"=>"Override Deletes", "type"=>"selection", "name"=>"override", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_override_delete"], "value"=>"", "clear"=>true),
			array("prompt"=>"Edit Flexipot", "type"=>"selection", "name"=>"flexipot", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_edit_flexipot"], "value"=>"", "clear"=>false),
			array("prompt"=>"View User Leave", "type"=>"selection", "name"=>"userleave", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_view_leave"], "value"=>"", "clear"=>true),
			array("prompt"=>"User Messaging", "type"=>"selection", "name"=>"usermessaging", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_messaging"], "value"=>"", "clear"=>false),
			array("prompt"=>"Allow view timesheet", "type"=>"selection", "name"=>"usertimesheet", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_view_timesheet"], "value"=>"", "clear"=>true),
			array("prompt"=>"View Reports", "type"=>"selection", "name"=>"viewreports", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_view_reports"], "value"=>"", "clear"=>false),
			array("prompt"=>"Year End reports", "type"=>"selection", "name"=>"yearend", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_year_end"], "value"=>"", "clear"=>true),
			array("prompt"=>"Override App Lock", "type"=>"selection", "name"=>"overrideLock", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_lock"], "value"=>"", "clear"=>false),
			array("prompt"=>"Override view timesheet", "type"=>"selection", "name"=>"overridetimesheet", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_view_override"], "value"=>"", "clear"=>true),
			array("prompt"=>"Override Local Manager constraint", "type"=>"selection", "name"=>"overrideLM", "listarr"=>array( "false", "true" ), "selected"=>$permissions[0]["permission_LM_constraint"], "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Save Template", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
	}
}
function save_permissions_edit() {
	$fieldarr=array("permission_template_name");
	$save = array_combine($fieldarr, array($_POST['template_name']));
	dl::update("flexi_permission_template_name", $save, "permission_id=".$_GET["id"]);
	$fieldarr= array("permission_description", "permission_user","permission_templates","permission_teams","permission_team_events","permission_team_authorise","permission_events","permission_event_types","permission_add_time","permission_edit_time","permission_edit_locked_time", "permission_add_global","permission_override_delete","permission_edit_flexipot","permission_view_leave","permission_view_timesheet","permission_messaging", "permission_view_override", "permission_LM_constraint","permission_view_reports", "permission_year_end", "permission_lock");
	$postarr= array($_POST["temp_description"],$_POST["edit_users"],$_POST["edit_templates"],$_POST["edit_teams"],$_POST["edit_teamevents"],$_POST["team_authority"],$_POST["events"], $_POST["event_types"],$_POST["add_time"],$_POST["edit_time"],$_POST["edit_locked"],$_POST["add_global"],$_POST["override"],$_POST["flexipot"],$_POST["userleave"], $_POST["usertimesheet"], $_POST["usermessaging"], $_POST["overridetimesheet"], $_POST["overrideLM"], $_POST["viewreports"], $_POST["yearend"], $_POST["overrideLock"]);
	$save=array_combine($fieldarr, $postarr);
	dl::update("flexi_permission_template", $save, "permission_template_name_id=".$_GET["id"]);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=permissiontemplate')</SCRIPT>" ;
}

function delete_permissions($id) {
	if(check_permission("Templates")) {
		dl::delete("flexi_permission_template_name", "permission_id=$id");
		dl::delete("flexi_permission_template", "permission_template_name_id=$id");
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=permissiontemplate')</SCRIPT>" ;
	}
}

/*

add time template
*/
function add_time_template() {
	if(check_permission("Templates")) {
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Create Time Template", "formintro"=>"Fill out the fields below to create the time template"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=savetimetemplate","method"=>"post")),	
			array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>40, "value"=>"Enter template name", "clear"=>true),	
			array("prompt"=>"Date Format", "type"=>"selection", "name"=>"date_format", "listarr"=>array( "dd-mm-yyyy", "mm-dd-yyyy" ), "selected"=>"dd-mm-yyyy", "value"=>"", "clear"=>true),
			array("prompt"=>"Timezone", "type"=>"selection", "name"=>"timezone", "listarr"=>array("(GMT-12:00) International Date Line West"
			,"(GMT-11:00) Midway Island, Samoa"
			,"(GMT-10:00) Hawaii"
			,"(GMT-09:00) Alaska"
			,"(GMT-08:00) Pacific Time (US &amp; Canada); Tijuana"
			,"(GMT-07:00) Arizona"
			,"(GMT-07:00) Mountain Time (US &amp; Canada)"
			,"(GMT-07:00) Chihuahua, La Paz, Mazatlan"
			,"(GMT-06:00) Central America"
			,"(GMT-06:00) Central Time (US &amp; Canada)"
			,"(GMT-06:00) Guadalajara, Mexico City, Monterrey"
			,"(GMT-06:00) Saskatchewan"
			,"(GMT-05:00) Indiana (East)"
			,"(GMT-05:00) Eastern Time (US &amp; Canada)"
			,"(GMT-05:00) Bogota, Lima, Quito"
			,"(GMT-04:00) Santiago"
			,"(GMT-04:00) Atlantic Time (Canada)"
			,"(GMT-04:00) Caracas, La Paz"
			,"(GMT-03:30) Newfoundland"
			,"(GMT-03:00) Buenos Aires, Georgetown"
			,"(GMT-03:00) Brasilia"
			,"(GMT-03:00) Greenland"
			,"(GMT-02:00) Mid-Atlantic"
			,"(GMT-01:00) Cape Verde Is."
			,"(GMT-01:00) Azores"
			,"(GMT) Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London"
			,"(GMT) Casablanca, Monrovia"
			,"(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb"
			,"(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna"
			,"(GMT+01:00) Brussels, Copenhagen, Madrid, Paris"
			,"(GMT+01:00) West Central Africa"
			,"(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague"
			,"(GMT+02:00) Jerusalem"
			,"(GMT+02:00) Athens, Beirut, Istanbul, Minsk"
			,"(GMT+02:00) Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius"
			,"(GMT+02:00) Cairo"
			,"(GMT+02:00) Bucharest"
			,"(GMT+02:00) Harare, Pretoria"
			,"(GMT+03:00) Baghdad"
			,"(GMT+03:00) Kuwait, Riyadh"
			,"(GMT+03:00) Moscow, St. Petersburg, Volgograd"
			,"(GMT+03:00) Nairobi"
			,"(GMT+03:30) Tehran"
			,"(GMT+04:00) Baku, Tbilisi, Yerevan"
			,"(GMT+04:00) Abu Dhabi, Muscat"
			,"(GMT+04:30) Kabul"
			,"(GMT+05:00) Ekaterinburg"
			,"(GMT+05:00) Islamabad, Karachi, Tashkent"
			,"(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi"
			,"(GMT+05:45) Kathmandu"
			,"(GMT+06:00) Sri Jayawardenepura"
			,"(GMT+06:00) Astana, Dhaka"
			,"(GMT+06:00) Almaty, Novosibirsk"
			,"(GMT+06:30) Rangoon"
			,"(GMT+07:00) Bangkok, Hanoi, Jakarta"
			,"(GMT+07:00) Krasnoyarsk"
			,"(GMT+08:00) Perth"
			,"(GMT+08:00) Kuala Lumpur, Singapore"
			,"(GMT+08:00) Taipei"
			,"(GMT+08:00) Irkutsk, Ulaan Bataar"
			,"(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi"
			,"(GMT+09:00) Osaka, Sapporo, Tokyo"
			,"(GMT+09:00) Seoul"
			,"(GMT+09:00) Yakutsk"
			,"(GMT+09:30) Adelaide"
			,"(GMT+09:30) Darwin"
			,"(GMT+10:00) Brisbane"
			,"(GMT+10:00) Guam, Port Moresby"
			,"(GMT+10:00) Canberra, Melbourne, Sydney"
			,"(GMT+10:00) Hobart"
			,"(GMT+10:00) Vladivostok"
			,"(GMT+11:00) Magadan, Solomon Is., New Caledonia"
			,"(GMT+12:00) Fiji, Kamchatka, Marshall Is."
			,"(GMT+12:00) Auckland, Wellington"
			,"(GMT+13:00) Nuku'alofa"
			), "selected"=>"(GMT) Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London", "value"=>"", "clear"=>true),
			array("prompt"=>"Start Adjustment", "type"=>"selection", "name"=>"start_adjustment", "listarr"=>array( "-15","-10","-5","0" ), "selected"=>"0", "value"=>"", "clear"=>true),
			array("prompt"=>"End Adjustment", "type"=>"selection", "name"=>"end_adjustment", "listarr"=>array( "0", "+5","+10","+15" ), "selected"=>"0", "value"=>"", "clear"=>true),
			array("prompt"=>"Enable rounding", "type"=>"checkbox", "name"=>"rounding", "value"=>"Yes", "clear"=>true),
			array("prompt"=>"Round to", "type"=>"selection", "name"=>"roundto", "listarr"=>array( "5 minutes", "10 minutes", "15 minutes" ), "selected"=>"5 minutes", "value"=>"", "clear"=>true),
			array("prompt"=>"Start Times", "type"=>"selection", "name"=>"round_start_time", "listarr"=>array( "Round to nearest", "Round up", "Round down" ), "selected"=>"Round to nearest", "value"=>"", "clear"=>true),
			array("prompt"=>"End Times", "type"=>"selection", "name"=>"round_end_time", "listarr"=>array( "Round to nearest", "Round up", "Round down" ), "selected"=>"Round to nearest", "value"=>"", "clear"=>true),
			array("prompt"=>"Delete Users", "type"=>"selection", "name"=>"delete_users", "listarr"=>array( "After 1 month", "After 3 months", "After 6 months", "After 1 year", "Never" ), "selected"=>"Never", "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Create Template", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
			$template=dl::select("flexi_time_template_name");
			echo "<div style='clear:both'><table class='table_view'>";
			echo "<tr><th>Template Name</th><th>Delete</th><th>Edit</th></tr>";
			foreach($template as $temp) {
				echo "<tr><td>".$temp["time_template_name"]."</td><td><a href='index.php?func=deletetimetemplate&id=".$temp["time_template_name_id"]."'>delete</a></td><td><a href='index.php?func=edittimetemplate&id=".$temp["time_template_name_id"]."'>edit</a></td></tr>";
			}
			echo "</table></div>";
		echo "</div>";
	}
}

function save_time_template() {
	$fieldarr=array("time_template_name");
	$save = array_combine($fieldarr, array($_POST['template_name']));
	dl::insert("flexi_time_template_name", $save);
	//saved the template name now need to get the template name id
	$get_id = dl::select("flexi_time_template_name", "time_template_name = '".$_POST['template_name']."'");
	foreach($get_id as $id) {
		$fieldId = $id["time_template_name_id"];
	}
	$fieldarr= array("time_template_name_id", "time_template_date_format","time_template_timezone","time_template_start_adjustment","time_template_end_adjustment","time_template_rounding_enabled","time_template_round_to","time_template_start","time_template_end", "time_template_delete");
	$postarr= array($fieldId,$_POST["date_format"],$_POST["timezone"],$_POST["start_adjustment"],$_POST["end_adjustment"],$_POST["rounding"],$_POST["roundto"], $_POST["round_start_time"],$_POST["round_end_time"], $_POST["delete_users"]);
	$save=array_combine($fieldarr, $postarr);
	dl::insert("flexi_time_template", $save);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=timetemplate')</SCRIPT>" ;
}

function edit_time_template() {
	if(check_permission("Templates")) {
		//get the record selected to edit
		$name = dl::select("flexi_time_template_name", "time_template_name_id=".$_GET["id"]);
		$template_name = $name[0]["time_template_name"];
		$edits = dl::select("flexi_time_template", "time_template_name_id=".$_GET["id"]);
		echo "<div class='timesheet_workspace'>";
		foreach($edits as $edit) {
			$formArr = array(array("type"=>"intro", "formtitle"=>"Edit Time Template", "formintro"=>"Change the fields below to edit the time template"), 
				array("type"=>"form", "form"=>array("action"=>"index.php?func=savetimetemplateedit&id=".$_GET["id"],"method"=>"post")),	
				array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>40, "value"=>$template_name, "clear"=>true),
				array("prompt"=>"Date Format", "type"=>"selection", "name"=>"date_format", "listarr"=>array( "dd-mm-yyyy", "mm-dd-yyyy" ), "selected"=>$edit["time_template_date_format"], "value"=>$edit["time_template_date_format"], "clear"=>true),
				array("prompt"=>"Timezone", "type"=>"selection", "name"=>"timezone", "listarr"=>array("(GMT-12:00) International Date Line West"
				,"(GMT-11:00) Midway Island, Samoa"
				,"(GMT-10:00) Hawaii"
				,"(GMT-09:00) Alaska"
				,"(GMT-08:00) Pacific Time (US &amp; Canada); Tijuana"
				,"(GMT-07:00) Arizona"
				,"(GMT-07:00) Mountain Time (US &amp; Canada)"
				,"(GMT-07:00) Chihuahua, La Paz, Mazatlan"
				,"(GMT-06:00) Central America"
				,"(GMT-06:00) Central Time (US &amp; Canada)"
				,"(GMT-06:00) Guadalajara, Mexico City, Monterrey"
				,"(GMT-06:00) Saskatchewan"
				,"(GMT-05:00) Indiana (East)"
				,"(GMT-05:00) Eastern Time (US &amp; Canada)"
				,"(GMT-05:00) Bogota, Lima, Quito"
				,"(GMT-04:00) Santiago"
				,"(GMT-04:00) Atlantic Time (Canada)"
				,"(GMT-04:00) Caracas, La Paz"
				,"(GMT-03:30) Newfoundland"
				,"(GMT-03:00) Buenos Aires, Georgetown"
				,"(GMT-03:00) Brasilia"
				,"(GMT-03:00) Greenland"
				,"(GMT-02:00) Mid-Atlantic"
				,"(GMT-01:00) Cape Verde Is."
				,"(GMT-01:00) Azores"
				,"(GMT) Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London"
				,"(GMT) Casablanca, Monrovia"
				,"(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb"
				,"(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna"
				,"(GMT+01:00) Brussels, Copenhagen, Madrid, Paris"
				,"(GMT+01:00) West Central Africa"
				,"(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague"
				,"(GMT+02:00) Jerusalem"
				,"(GMT+02:00) Athens, Beirut, Istanbul, Minsk"
				,"(GMT+02:00) Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius"
				,"(GMT+02:00) Cairo"
				,"(GMT+02:00) Bucharest"
				,"(GMT+02:00) Harare, Pretoria"
				,"(GMT+03:00) Baghdad"
				,"(GMT+03:00) Kuwait, Riyadh"
				,"(GMT+03:00) Moscow, St. Petersburg, Volgograd"
				,"(GMT+03:00) Nairobi"
				,"(GMT+03:30) Tehran"
				,"(GMT+04:00) Baku, Tbilisi, Yerevan"
				,"(GMT+04:00) Abu Dhabi, Muscat"
				,"(GMT+04:30) Kabul"
				,"(GMT+05:00) Ekaterinburg"
				,"(GMT+05:00) Islamabad, Karachi, Tashkent"
				,"(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi"
				,"(GMT+05:45) Kathmandu"
				,"(GMT+06:00) Sri Jayawardenepura"
				,"(GMT+06:00) Astana, Dhaka"
				,"(GMT+06:00) Almaty, Novosibirsk"
				,"(GMT+06:30) Rangoon"
				,"(GMT+07:00) Bangkok, Hanoi, Jakarta"
				,"(GMT+07:00) Krasnoyarsk"
				,"(GMT+08:00) Perth"
				,"(GMT+08:00) Kuala Lumpur, Singapore"
				,"(GMT+08:00) Taipei"
				,"(GMT+08:00) Irkutsk, Ulaan Bataar"
				,"(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi"
				,"(GMT+09:00) Osaka, Sapporo, Tokyo"
				,"(GMT+09:00) Seoul"
				,"(GMT+09:00) Yakutsk"
				,"(GMT+09:30) Adelaide"
				,"(GMT+09:30) Darwin"
				,"(GMT+10:00) Brisbane"
				,"(GMT+10:00) Guam, Port Moresby"
				,"(GMT+10:00) Canberra, Melbourne, Sydney"
				,"(GMT+10:00) Hobart"
				,"(GMT+10:00) Vladivostok"
				,"(GMT+11:00) Magadan, Solomon Is., New Caledonia"
				,"(GMT+12:00) Fiji, Kamchatka, Marshall Is."
				,"(GMT+12:00) Auckland, Wellington"
				,"(GMT+13:00) Nuku'alofa"
				), "selected"=>$edit["time_template_timezone"], "value"=>$edit["time_template_timezone"], "clear"=>true),
				array("prompt"=>"Start Adjustment", "type"=>"selection", "name"=>"start_adjustment", "listarr"=>array( "-15","-10","-5","0" ), "selected"=>$edit["time_template_start_adjustment"], "value"=>$edit["time_template_start_adjustment"], "clear"=>true),
				array("prompt"=>"End Adjustment", "type"=>"selection", "name"=>"end_adjustment", "listarr"=>array( "0", "+5","+10","+15" ), "selected"=>$edit["time_template_end_adjustment"], "value"=>$edit["time_template_end_adjustment"], "clear"=>true),
				array("prompt"=>"Enable rounding", "type"=>"checkbox", "name"=>"rounding", "selected"=>$edit["time_template_rounding_enabled"], "value"=>"Yes", "clear"=>true),
				array("prompt"=>"Round to", "type"=>"selection", "name"=>"roundto", "listarr"=>array( "5 minutes", "10 minutes", "15 minutes" ), "selected"=>$edit["time_template_round_to"], "value"=>$edit["time_template_round_to"], "clear"=>true),
				array("prompt"=>"Start Times", "type"=>"selection", "name"=>"round_start_time", "listarr"=>array( "Round to nearest", "Round up", "Round down" ), "selected"=>$edit["time_template_start"], "value"=>$edit["time_template_start"], "clear"=>true),
				array("prompt"=>"End Times", "type"=>"selection", "name"=>"round_end_time", "listarr"=>array( "Round to nearest", "Round up", "Round down" ), "selected"=>$edit["time_template_end"], "value"=>$edit["time_template_end"], "clear"=>true),
				array("prompt"=>"Delete Users", "type"=>"selection", "name"=>"delete_users", "listarr"=>array( "After 1 month", "After 3 months", "After 6 months", "After 1 year", "Never" ), "selected"=>$edit["time_template_delete"], "value"=>$edit["time_template_delete"], "clear"=>true),
				array("type"=>"submit", "buttontext"=>"Save Template", "clear"=>true), 
				array("type"=>'endform'));
			}
			$form = new forms;
			$form->create_form($formArr);
			$template=dl::select("flexi_time_template_name");
		echo "</div>";
	}
}

function save_time_template_edit() {
	$fieldarr=array("time_template_name");
	$save = array_combine($fieldarr, array($_POST['template_name']));
	dl::update("flexi_time_template_name", $save, "time_template_name_id=".$_GET["id"]);
	$fieldarr= array("time_template_date_format","time_template_timezone","time_template_start_adjustment","time_template_end_adjustment","time_template_rounding_enabled","time_template_round_to","time_template_start","time_template_end","time_template_delete");
	$postarr= array($_POST["date_format"],$_POST["timezone"],$_POST["start_adjustment"],$_POST["end_adjustment"],$_POST["rounding"],$_POST["roundto"], $_POST["round_start_time"],$_POST["round_end_time"], $_POST["delete_users"]);
	$save=array_combine($fieldarr, $postarr);
	dl::update("flexi_time_template", $save, "time_template_name_id=".$_GET["id"]);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=timetemplate')</SCRIPT>" ;
}

function delete_time_template($id) {
	if(check_permission("Templates")) {
		dl::delete("flexi_time_template_name", "time_template_name_id=$id");
		dl::delete("flexi_time_template", "time_template_name_id=$id");
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=timetemplate')</SCRIPT>" ;
	}
}

// flexi template
function add_flexi_template() {
	if(check_permission("Templates")) {
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Flexi Template", "formintro"=>"Fill out the fields below to create the flexi template"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveflexitemplate","method"=>"post")),	
			array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>20, "value"=>"Enter template name", "clear"=>true),	
			array("prompt"=>"Account Period", "type"=>"selection", "name"=>"account_period", "listarr"=>array( "Calendar Month", "4 Weekly" ), "selected"=>"4 Weekly", "value"=>"", "clear"=>true),
			array("prompt"=>"Maximum surplus", "type"=>"text", "name"=>"max_surplus", "length"=>20, "value"=>"Enter surplus (hrs:mins)", "clear"=>true),
			array("prompt"=>"Maximum deficit", "type"=>"text", "name"=>"max_deficit", "length"=>20, "value"=>"Enter deficit (hrs:mins)", "clear"=>true),
			array("prompt"=>"Maximum flexi/period", "type"=>"text", "name"=>"leave_days", "length"=>20, "value"=>"Enter leave period", "clear"=>true),
			array("prompt"=>"Leave Expires after", "type"=>"selection", "name"=>"leave_period", "listarr"=>array("Never", "1 Month", "4 Weeks", "2 Months", "8 Weeks", "3 Months", "12 Weeks"), "selected"=>"4 Weeks", "length"=>20, "value"=>"", "clear"=>true),		
			array("prompt"=>"Period Start Date", "type"=>"date", "name"=>"period_start", "length"=>20, "value"=>"", "clear"=>true),
			array("prompt"=>"Period End date", "type"=>"date", "name"=>"period_end", "length"=>20, "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Create Template", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
			$template=dl::select("flexi_template_name");
			echo "<div style='clear:both'><table class='table_view'>";
			echo "<tr><th>Template Name</th><th>Delete</th><th>Edit</th></tr>";
			foreach($template as $temp) {
				echo "<tr><td>".$temp["description"]."</td><td><a href='index.php?func=deleteflexitemplate&id=".$temp["flexi_template_name_id"]."'>delete</a></td><td><a href='index.php?func=editflexitemplate&id=".$temp["flexi_template_name_id"]."'>edit</a></td></tr>";
			}
			echo "</table></div>";
		echo "</div>";
	}
}

function save_flexi_template() {
	$fieldarr=array("description");
	$save = array_combine($fieldarr, array($_POST['template_name']));
	dl::insert("flexi_template_name", $save);
	//saved the template name now need to get the template name id
	$get_id = dl::select("flexi_template_name", "description = '".$_POST['template_name']."'");
	foreach($get_id as $id) {
		$fieldId = $id["flexi_template_name_id"];
	}
	$fieldarr= array("template_name_id", "account_period","max_surplus","max_deficit","max_holiday","leave_period","start_period","end_period");
	$postarr= array($fieldId,$_POST["account_period"],$_POST["max_surplus"],$_POST["max_deficit"],$_POST["leave_days"],$_POST["leave_period"],$_POST["period_start"], $_POST["period_end"]);
	$save=array_combine($fieldarr, $postarr);
	dl::insert("flexi_template", $save);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=flexitemplate')</SCRIPT>" ;
}

function edit_flexi_template() {
	if(check_permission("Templates")) {
		//get the record selected to edit
		$name = dl::select("flexi_template_name", "flexi_template_name_id=".$_GET["id"]);
		$template_name = $name[0]["description"];
		$edits = dl::select("flexi_template", "template_name_id=".$_GET["id"]);
		echo "<div class='timesheet_workspace'>";
		foreach($edits as $edit) {
			$formArr = array(array("type"=>"intro", "formtitle"=>"Flexi Template", "formintro"=>"Fill out the fields below to create the flexi template"), 
				array("type"=>"form", "form"=>array("action"=>"index.php?func=saveflexitemplateedit&id=".$_GET["id"],"method"=>"post")),	
				array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>20, "value"=>$template_name, "clear"=>true),	
				array("prompt"=>"Account Period", "type"=>"selection", "name"=>"account_period", "listarr"=>array( "Calendar Month", "4 Weekly" ), "selected"=>"4 Weekly", "value"=>$edit["account_period"], "clear"=>true),
				array("prompt"=>"Maximum surplus", "type"=>"text", "name"=>"max_surplus", "length"=>20, "value"=>$edit["max_surplus"], "clear"=>true),
				array("prompt"=>"Maximum deficit", "type"=>"text", "name"=>"max_deficit", "length"=>20, "value"=>$edit["max_deficit"], "clear"=>true),
				array("prompt"=>"Maximum flexi/period", "type"=>"text", "name"=>"leave_days", "length"=>20, "value"=>$edit["max_holiday"], "clear"=>true),
				array("prompt"=>"Leave Expires after", "type"=>"selection", "name"=>"leave_period", "listarr"=>array("Never", "1 Month", "4 Weeks", "2 Months", "8 Weeks", "3 Months", "12 Weeks"), "selected"=>$edit["leave_period"], "length"=>20, "value"=>"", "clear"=>true),		
				array("prompt"=>"Period Start Date", "type"=>"date", "name"=>"period_start", "length"=>20, "value"=>$edit["start_period"], "clear"=>true),
				array("prompt"=>"Period End date", "type"=>"date", "name"=>"period_end", "length"=>20, "value"=>$edit["end_period"], "clear"=>true),
				array("type"=>"submit", "buttontext"=>"Save Template", "clear"=>true), 
				array("type"=>'endform'));
			}
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
	}
}

function save_flexi_template_edit() {
	$fieldarr=array("description");
	$save = array_combine($fieldarr, array($_POST['template_name']));
	dl::update("flexi_template_name", $save, "flexi_template_name_id=".$_GET["id"]);
	//saved the template name now need to get the template name id
	$fieldarr= array("account_period","max_surplus","max_deficit","max_holiday","leave_period","start_period","end_period");
	$postarr= array($_POST["account_period"],$_POST["max_surplus"],$_POST["max_deficit"],$_POST["leave_days"],$_POST["leave_period"],$_POST["period_start"], $_POST["period_end"]);
	$save=array_combine($fieldarr, $postarr);
	dl::update("flexi_template", $save, "template_name_id=".$_GET["id"]);
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=flexitemplate')</SCRIPT>" ;
}

function delete_flexi_template($id) {
	if(check_permission("Templates")) {
		dl::delete("flexi_template_name", "flexi_template_name_id=$id");
		dl::delete("flexi_template", "template_name_id=$id");
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=flexitemplate')</SCRIPT>" ;
	}
}

// flexi days template

function add_flexi_days_template() {
	if(check_permission("Templates")) {
		echo "<div id='timesheet_workspace' class='timesheet_workspace'>";
		// get list of flexi templates that are not linked to other templates or are linked to nothing ( eg. they are new )
		$sql = "select * from `flexi_template_name` left outer join flexi_template_days on (flexi_template_name_id=template_name_id) where `template_days_name` is NULL";
		$flexitemps = dl::getQuery($sql);
		foreach ($flexitemps as $ft) {
			$tempArr[] = $ft["description"];
		}
		echo "<div style='float: left;'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Flexi Days Template", "formintro"=>"Fill out the fields below to create the flexi days template"), 	
			array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>40, "value"=>"Enter template name", "clear"=>true),	
			array("prompt"=>"Link To", "type"=>"selection", "name"=>"link_to", "listarr"=>$tempArr, "selected"=>"", "value"=>"", "clear"=>true),
			array("prompt"=>"Template type", "type"=>"selection", "name"=>"template_type", "listarr"=>array("Weekly", "Daily"), "selected"=>"Weekly","value"=>"", "clear"=>true),
			array("prompt"=>"Earliest Start Time", "type"=>"time", "name"=>"earliest_start", "starttime"=>"0500", "endtime"=>"0900", "interval"=>15, "value"=>"Enter leave period", "clear"=>true),
			array("prompt"=>"Latest Start Time", "type"=>"time", "name"=>"latest_start", "starttime"=>"0900", "endtime"=>"1800", "interval"=>15, "value"=>"", "clear"=>true),		
			array("prompt"=>"Minimum Lunch", "type"=>"checkbox", "name"=>"minimum_lunch", "value"=>"Yes", "clear"=>true),
			array("prompt"=>"Min Lunch Duration", "type"=>"time", "name"=>"lunch_duration", "starttime"=>"0015", "endtime"=>"0100", "interval"=>15, "value"=>"00:30", "clear"=>true),
			array("prompt"=>"Lunch Earliest Start", "type"=>"time", "name"=>"lunch_earliest_start", "starttime"=>"1030", "endtime"=>"1200", "interval"=>15, "value"=>"Enter leave period", "clear"=>true),
			array("prompt"=>"Lunch Latest Finish", "type"=>"time", "name"=>"lunch_latest_end", "starttime"=>"1230", "endtime"=>"1430", "interval"=>15, "value"=>"", "clear"=>true),		
			array("prompt"=>"Earliest End Time", "type"=>"time", "name"=>"earliest_end", "starttime"=>"0745", "endtime"=>"1700", "interval"=>15, "value"=>"Enter leave period", "clear"=>true),
			array("prompt"=>"Latest End Time", "type"=>"time", "name"=>"latest_end", "starttime"=>"1700", "endtime"=>"2345", "interval"=>15, "value"=>"", "clear"=>true),
			array("prompt"=>"Days per week", "type"=>"text", "name"=>"days_week", "length"=>10, "value"=>"", "clear"=>true));	
			
			
			$formArr[] = array("type"=>"submit", "name"=>"add_template", "buttontext"=>"Create Template", "clear"=>true); 
			$form = new forms;
			$form->create_form($formArr);
			echo "</div>";
			$template=dl::select("flexi_template_days");
			echo "<div id='add_daysandtime' style='float: left;'></div>";
			echo "<div style='clear:both'><table class='table_view'>";
			echo "<tr><th>Template Name</th><th>Delete</th><th>Edit</th></tr>";
			foreach($template as $temp) {
				echo "<tr><td>".$temp["template_days_name"]."</td><td><a href='index.php?func=deleteflexidaystemplate&id=".$temp["flexi_template_days_id"]."'>delete</a></td><td><a href='index.php?func=editdaystemplate&id=".$temp["flexi_template_days_id"]."'>edit</a></td></tr>";
			}
			echo "</table></div>";
		echo "</div>";
	}
?>
<script>
$(document).ready(function(){
	$("#add_template").attr("disabled", "disabled");
	$("#days_week").on("change", function(event, ui) { 
		var func = "days_changed";
		$.post(
			"ajax.php",
			{ func: func,
				templates: <?php echo $tempArr?>,
				days_per_week: $("#days_week").val()
			},
			function (data)
			{
				$('#add_daysandtime').html(data);
		});
	});
	$("#add_template").click(function() {
		alert("saving...");
		var func = "save_template";
		var weekday_values = {};
		if(document.getElementById('week_day')) { //check if the option selected is for All the days
			weekday_values = {week_day: $("#week_day").val(), weekday_time: $("#weekday_time").val(),weekday_time_mins: $("#weekday_time_mins").val()};
		}
		
		if(document.getElementById('week_day1')) { //check if the option is to have differing times for each day
			var strArr = "{";
			for( var i=1; i<=$("#days_week").val(); i++ ) {
				key = "week_day"+i;
				value = $("#week_day"+i).val();
				strArr += "\""+key+"\":\""+value+"\","; 
				key = "weekday_time"+i;
				value = $("#weekday_time"+i).val();
				strArr += "\""+key+"\":\""+value+"\","; 
				key = "weekday_time"+i+"_mins";
				value = $("#weekday_time"+i+"_mins").val();
				strArr += "\""+key+"\":\""+value+"\","; 
			}
			strArr = strArr.substring(0, (strArr.length)-2);
			strArr += "\"}";
			weekday_values = strArr;
		}
		$.post(
			"ajax.php",
			{ func: func,
				template_name: $("#template_name").val(),
				link_to: $("#link_to").val(),
				template_type: $("#template_type").val(),
				earliest_start: $("#earliest_start").val(),
				earliest_start_mins: $("#earliest_start_mins").val(),
				latest_start: $("#latest_start").val(),
				latest_start_mins: $("#latest_start_mins").val(),
				minimum_lunch: $("#minimum_lunch").is(":checked"),
				lunch_duration: $("#lunch_duration").val(),
				lunch_duration_mins: $("#lunch_duration_mins").val(),
				lunch_earliest_start: $("#lunch_earliest_start").val(),
				lunch_earliest_start_mins: $("#lunch_earliest_start_mins").val(),
				lunch_latest_end: $("#lunch_latest_end").val(),
				lunch_latest_end_mins: $("#lunch_latest_end_mins").val(),
				earliest_end: $("#earliest_end").val(),
				earliest_end_mins: $("#earliest_end_mins").val(),
				latest_end: $("#latest_end").val(),
				latest_end_mins: $("#latest_end_mins").val(),
				templates: <?php echo $tempArr?>,
				days_per_week: $("#days_week").val(),
				week_day_array: weekday_values
			},
			function (data)
			{
				$('#add_daysandtime').html(data);
		});
	}); 
});
</script>
<?php
}

function save_flexi_days_template() {
	//check here if the name has been used for the template name
	$template_check = dl::select("flexi_template_days", "template_days_name = '".$_POST["template_name"]."'");
	if(empty($template_check)) {
		//check here if the POST week_day array is an array or if not then it is a json string which needs decoding to an array.
		if(is_array($_POST["week_day_array"])) {
			$week_day_array = $_POST["week_day_array"];
		}else{
			$week_day_array = (array) json_decode($_POST["week_day_array"]);
		}
		// need to obtain the id of the template to link to
		$linkto = dl::select("flexi_template_name", "description='".$_POST["link_to"]."'");
		foreach($linkto as $lnk) {
			$linkId = $lnk["flexi_template_name_id"];
		}
		//now need to save the template days template name and link
		$fieldarr=array("template_days_name","template_name_id");
		$save = array_combine($fieldarr, array($_POST['template_name'], $linkId));
		dl::insert("flexi_template_days", $save);
		//saved the template name now need to get the template days id
		$get_id = dl::select("flexi_template_days", "template_days_name = '".$_POST['template_name']."'");
		$fieldId = $get_id[0]["flexi_template_days_id"];
		if($_POST["minimum_lunch"]== "true") {
			$min_lunch = "Yes";
		}else{
			$min_lunch = "";
		}
		$fieldarr= array("template_days_id", "template_type", "days_week","earliest_starttime","latest_starttime","minimum_lunch","minimum_lunch_duration","lunch_earliest_start_time", "lunch_latest_end_time","earliest_endtime","latest_endtime");
		
		$postarr= array($fieldId,$_POST["template_type"],$_POST["days_per_week"], $_POST["earliest_start"].":".$_POST["earliest_start_mins"],$_POST["latest_start"].":".$_POST["latest_start_mins"],$min_lunch,$_POST["lunch_duration"].":".$_POST["lunch_duration_mins"], $_POST["lunch_earliest_start"].":".$_POST["lunch_earliest_start_mins"], $_POST["lunch_latest_end"].":".$_POST["lunch_latest_end_mins"], $_POST["earliest_end"].":".$_POST["earliest_end_mins"], $_POST["latest_end"].":".$_POST["latest_end_mins"]);
		
		$save=array_combine($fieldarr, $postarr);
		dl::insert("flexi_template_days_settings", $save);
		$sql = "select MAX(days_settings_id) as maxId from flexi_template_days_settings";
		$max = dl::getQuery($sql);
		$fields = array("fdt_weekday_id", "fdt_flexi_days_id", "fdt_working_time");
		if(in_array("All", $week_day_array)) { //this array only has one element and applies to all of the days_per_week
			//save the array details ready for writing (id of "All" = 6)
			$timeVal = $week_day_array["weekday_time"].":".$week_day_array["weekday_time_mins"].":00";
			$values = array(6, $max[0]["maxId"], $timeVal);
			$writeLine = array_combine($fields, $values);
			dl::insert("flexi_day_times", $writeLine);
		}else{ //the array has multiple day times to save
			for( $count=1; $count <= $_POST["days_per_week"]; $count++) {
				$timeVal = $week_day_array["weekday_time".$count].":".$week_day_array["weekday_time".$count."_mins"].":00";
				$wkDay = $week_day_array["week_day".$count];
				//check flexi_weekdays to get the id of the weekday (Mon, Tues, Wed, Thurs, Fri)
				$dayId = dl::select("flexi_weekdays", "fw_weekday = '".$wkDay."'");
				$values = array($dayId[0]["fw_id"], $max[0]["maxId"], $timeVal);
				$writeLine = array_combine($fields, $values);
				dl::insert("flexi_day_times", $writeLine);
			}
			$count++;
		}
	}else{
		?>
		<script>
		alert("This name already exists.");
		</script>
		<?php 
	}
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=flexidaystemplate')</SCRIPT>" ;
}

function edit_flexi_days_template() {
	if(check_permission("Templates")) {
		$days = dl::select("flexi_template_days", "flexi_template_days_id=".$_GET["id"]);
		$names = dl::select("flexi_template_name", "flexi_template_name_id=".$days[0]["template_name_id"]);
		$name = $names[0]["description"];
		// get list of flexi templates that are not linked to other templates or are linked to nothing ( eg. they are new )
		$sql = "select * from `flexi_template_name` left outer join flexi_template_days on (flexi_template_name_id=template_name_id) where `template_days_name` is NULL or description = '".$name."'";
		$flexitemps = dl::getQuery($sql);
		foreach ($flexitemps as $ft) {
			$tempArr[] = $ft["description"];
		}
		$setting = dl::select("flexi_template_days_settings", "template_days_id=".$_GET["id"]);
		$settingsId = $setting[0]["days_settings_id"];
		echo "<div id='timesheet_workspace' class='timesheet_workspace'>";
		echo "<div style='float: left;'>";
		foreach($setting as $settings) {
			$formArr = array(array("type"=>"intro", "formtitle"=>"Flexi Days Template", "formintro"=>"Fill out the fields below to create the flexi days template"), 	
				array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>40, "value"=>$days[0]["template_days_name"], "clear"=>true),	
				array("prompt"=>"Link To", "type"=>"selection", "name"=>"link_to", "listarr"=>$tempArr, "selected"=>$name, "value"=>"", "clear"=>true),
				array("prompt"=>"Template type", "type"=>"selection", "name"=>"template_type", "listarr"=>array("Weekly", "Daily"), "selected"=>$settings["template_type"],"value"=>"", "clear"=>true),
				array("prompt"=>"Earliest Start Time", "type"=>"time", "name"=>"earliest_start", "starttime"=>"0500", "endtime"=>"0845", "interval"=>15, "selected"=>$settings["earliest_starttime"], "value"=>$settings["earliest_starttime"], "clear"=>true),
				array("prompt"=>"Latest Start Time", "type"=>"time", "name"=>"latest_start", "starttime"=>"0900", "endtime"=>"1800", "interval"=>15, "selected"=>$settings["latest_starttime"], "value"=>$settings["latest_starttime"], "clear"=>true),		
				array("prompt"=>"Minimum Lunch", "type"=>"checkbox", "name"=>"minimum_lunch", "selected"=>$settings["minimum_lunch"], "value"=>"Yes", "clear"=>true),
				array("prompt"=>"Min Lunch Duration", "type"=>"time", "name"=>"lunch_duration", "starttime"=>"0015", "endtime"=>"0100", "interval"=>15, "selected"=>$settings["minimum_lunch_duration"], "value"=>$settings["minimum_lunch_duration"], "clear"=>true),
				array("prompt"=>"Lunch Earliest Start", "type"=>"time", "name"=>"lunch_earliest_start", "starttime"=>"1030", "endtime"=>"1200", "interval"=>15, "selected"=>$settings["lunch_earliest_start_time"], "value"=>$settings["lunch_earliest_start_time"], "clear"=>true),
				array("prompt"=>"Lunch Latest Finish", "type"=>"time", "name"=>"lunch_latest_end", "starttime"=>"1030", "endtime"=>"1600", "interval"=>15, "selected"=>$settings["lunch_latest_end_time"], "value"=>$settings["lunch_latest_end_time"], "clear"=>true),		
				array("prompt"=>"Earliest End Time", "type"=>"time", "name"=>"earliest_end", "starttime"=>"0700", "endtime"=>"1645", "interval"=>15, "selected"=>$settings["earliest_endtime"], "value"=>$settings["earliest_endtime"], "clear"=>true),
				array("prompt"=>"Latest End Time", "type"=>"time", "name"=>"latest_end", "starttime"=>"1700", "endtime"=>"2345", "interval"=>15, "selected"=>$settings["latest_endtime"], "value"=>$settings["latest_endtime"], "clear"=>true),		
				array("prompt"=>"Days per week", "type"=>"text", "name"=>"days_week", "length"=>10, "value"=>$settings["days_week"], "clear"=>true),
				array("type"=>"submit", "name"=>"edit_template", "buttontext"=>"Save Template", "clear"=>true));
				$form = new forms;
				$form->create_form($formArr);
		}
		echo "</div>";
		echo "<div id='add_daysandtime' style='float: left;'>";
		$times = dl::select("flexi_day_times","fdt_flexi_days_id = ".$settingsId, "fdt_weekday_id");
		$formArr = array(array("type"=>"intro", "formtitle"=>"Additional Time Details", "formintro"=>"Fill out the fields below to add additional information"));
		$i=1;
		$week_dayArr = dl::select("flexi_weekdays");
		foreach($week_dayArr as $wda) {
			$week_days[]= $wda["fw_weekday"];
		}
		if(count($times) == 1 ){
			$chosen_day = dl::select("flexi_weekdays", "fw_id = ".$times[0]["fdt_weekday_id"]);
			$formArr[] = array("prompt"=>"Weekday", "type"=>"selection", "name"=>"week_day", "listarr"=>$week_days, "selected"=>$chosen_day[0]["fw_weekday"], "value"=>"", "clear"=>true);
			$formArr[] = array("prompt"=>"Time", "type"=>"time", "name"=>"weekday_time", "starttime"=>"0000", "endtime"=>"0900", "interval"=>1, "selected"=>$times[0]["fdt_working_time"], "value"=>"Enter leave period", "clear"=>true); 
		}else{
			foreach($times as $time) {
				$chosen_day = dl::select("flexi_weekdays", "fw_id = ".$time["fdt_weekday_id"]);
				$formArr[] = array("prompt"=>"Weekday", "type"=>"selection", "name"=>"week_day".$i, "listarr"=>$week_days, "selected"=>$chosen_day[0]["fw_weekday"], "value"=>"", "clear"=>true);
				$formArr[] = array("prompt"=>"Time", "type"=>"time", "name"=>"weekday_time".$i, "starttime"=>"0000", "endtime"=>"0900", "interval"=>1, "selected"=>$time["fdt_working_time"], "value"=>"Enter leave period", "clear"=>true); 
				?>
				<script>
				$(document).ready(function(){
					$("#weekday_time<?php echo $i ?>").change(function() { 
						$("#add_template").removeAttr("disabled");
						var func = "calc_time";
						$.post(
							"ajax.php",
							{ func: func,
								days_per_week: $("#days_week").val()
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
								days_per_week: $("#days_week").val()
							},
							function (data)
							{
								$('#show_time_total').html(data);
						});
					});
				});
				</script>
				<?php
				$i++;
			}
		}
		$form = new forms;
		$form->create_form($formArr);
		echo "<div id='show_time_total'></div>";
		echo "</div></div>";
		echo "<div id='dialog' title='Change Days per Week' class='ui-helper-hidden'><p>Are you sure you want to change the no. of Days per Week?<BR><BR>This will delete all of the time details and ask you to recreate them!!!</p></div>";
?>
<script>
$(document).ready(function(){
	if($('#week_day').val() == "All") { //check if the option selected is for All the days
		var func = "calc_time";
		$.post(
			"ajax.php",
			{ func: func,
				option: "multiply",
				days_per_week: $("#days_week").val()
			},
			function (data)
			{
				$('#show_time_total').html(data);
		});
		$("#week_day").change(function() { 
			var func = "calc_hours";
			$.post(
				"ajax.php",
				{ func: func,
					days_per_week: $("#days_week").val(),
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
					days_per_week: $("#days_week").val()
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
					days_per_week: $("#days_week").val()
				},
				function (data)
				{
					$('#show_time_total').html(data);
			});
			$("#add_template").removeAttr("disabled");
		});
	}else{
		var func = "calc_time";
		$.post(
			"ajax.php",
			{ func: func,
				days_per_week: $("#days_week").val()
			},
			function (data)
			{
				$('#show_time_total').html(data);
		});
	}
	var dpw = $("#days_week").val();
	$("#days_week").on("change", function(event, ui) { 
		$("#dialog").dialog({
			resizable: false,
			height:200,
			modal: true,
			buttons: {
				"Confirm": function() {
					var func = "days_changed";
					$.post(
						"ajax.php",
						{ func: func,
							templates: <?php echo $tempArr?>,
							days_per_week: $("#days_week").val()
						},
						function (data)
						{
							$('#add_daysandtime').html(data);
					});
					$( this ).dialog( "close" );
				},
				"Cancel": function() {
					$("#days_week").val(dpw);
					$( this ).dialog( "close" );
				}
			}
		});
	});
	$("#edit_template").click(function() {
		var func = "edit_template";
		var weekday_values = {};
		if(document.getElementById('week_day')) { //check if the option selected is for All the days
			weekday_values = {week_day: $("#week_day").val(), weekday_time: $("#weekday_time").val(),weekday_time_mins: $("#weekday_time_mins").val()};
		}
		
		if(document.getElementById('week_day1')) { //check if the option is to have differing times for each day
			var strArr = "{";
			for( var i=1; i<=$("#days_week").val(); i++ ) {
				key = "week_day"+i;
				value = $("#week_day"+i).val();
				strArr += "\""+key+"\":\""+value+"\","; 
				key = "weekday_time"+i;
				value = $("#weekday_time"+i).val();
				strArr += "\""+key+"\":\""+value+"\","; 
				key = "weekday_time"+i+"_mins";
				value = $("#weekday_time"+i+"_mins").val();
				strArr += "\""+key+"\":\""+value+"\","; 
			}
			strArr = strArr.substring(0, (strArr.length)-2);
			strArr += "\"}";
			weekday_values = strArr;
		}
		$.post(
			"ajax.php",
			{ func: func,
				template_name: $("#template_name").val(),
				link_to: $("#link_to").val(),
				template_type: $("#template_type").val(),
				earliest_start: $("#earliest_start").val(),
				earliest_start_mins: $("#earliest_start_mins").val(),
				latest_start: $("#latest_start").val(),
				latest_start_mins: $("#latest_start_mins").val(),
				minimum_lunch: $("#minimum_lunch").is(":checked"),
				lunch_duration: $("#lunch_duration").val(),
				lunch_duration_mins: $("#lunch_duration_mins").val(),
				lunch_earliest_start: $("#lunch_earliest_start").val(),
				lunch_earliest_start_mins: $("#lunch_earliest_start_mins").val(),
				lunch_latest_end: $("#lunch_latest_end").val(),
				lunch_latest_end_mins: $("#lunch_latest_end_mins").val(),
				earliest_end: $("#earliest_end").val(),
				earliest_end_mins: $("#earliest_end_mins").val(),
				latest_end: $("#latest_end").val(),
				latest_end_mins: $("#latest_end_mins").val(),
				templates: <?php echo $tempArr?>,
				days_per_week: $("#days_week").val(),
				template_days_id: <?php echo $_GET["id"]?>,
				week_day_array: weekday_values
			},
			function (data)
			{
				$('#add_daysandtime').html(data);
		});
	});
});
</script>
<?php
	}
}

function save_flexi_days_template_edit() {
	//check here if the POST week_day array is an array or if not then it is a json string which needs decoding to an array.
	if(is_array($_POST["week_day_array"])) {
		$week_day_array = $_POST["week_day_array"];
	}else{
		$week_day_array = (array) json_decode($_POST["week_day_array"]);
	}
	if($_POST["minimum_lunch"]== "true") {
		$min_lunch = "Yes";
	}else{
		$min_lunch = "";
	}
	// need to obtain the id of the template to link to
	$linkto = dl::select("flexi_template_name", "description='".$_POST["link_to"]."'");
	$linkId = $linkto[0]["flexi_template_name_id"];
	$fieldarr=array("template_days_name","template_name_id");
	$save = array_combine($fieldarr, array($_POST['template_name'], $linkId));
	dl::update("flexi_template_days", $save, "flexi_template_days_id=".$_POST["template_days_id"]);
	$fieldarr= array("template_type", "days_week","earliest_starttime","latest_starttime","minimum_lunch","minimum_lunch_duration","lunch_earliest_start_time", "lunch_latest_end_time","earliest_endtime","latest_endtime");
	$postarr= array($_POST["template_type"],$_POST["days_per_week"],$_POST["earliest_start"].":".$_POST["earliest_start_mins"],$_POST["latest_start"].":".$_POST["latest_start_mins"],$min_lunch,$_POST["lunch_duration"].":".$_POST["lunch_duration_mins"], $_POST["lunch_earliest_start"].":".$_POST["lunch_earliest_start_mins"], $_POST["lunch_latest_end"].":".$_POST["lunch_latest_end_mins"], $_POST["earliest_end"].":".$_POST["earliest_end_mins"], $_POST["latest_end"].":".$_POST["latest_end_mins"]);
	$save=array_combine($fieldarr, $postarr);
	dl::update("flexi_template_days_settings", $save, "template_days_id=".$_POST["template_days_id"]);
	//get the settings id
	$settings = dl::select("flexi_template_days_settings", "template_days_id = ".$_POST["template_days_id"]);
	//need to check if this template is allocated to anyone as WILL NOT allow any time changes if it is
	/*$userLink = dl::select("flexi_template", "template_name_id = ". $linkId);
	$user = dl::select("flexi_user", "user_flexi_template = ".$userLink[0]["template_id"] );
	$count=0;
	foreach($user as $u) {
		$count++;
		echo $u["user_name"]."<BR>";
	}
	if(empty($user)) {*/
	
		//now delete the existing day times and recreate them
		dl::delete("flexi_day_times", "fdt_flexi_days_id =".$settings[0]["days_settings_id"]);
		$fields = array("fdt_weekday_id", "fdt_flexi_days_id", "fdt_working_time");
		//check to see if the time is for all days or different times for different days
		if(in_array("All", $week_day_array)){
			//weekday_id is 6
			$values = array(6, $settings[0]["days_settings_id"], $week_day_array["weekday_time"].":".$week_day_array["weekday_time_mins"].":00");
			$writeLine = array_combine($fields, $values);
			dl::insert("flexi_day_times", $writeLine);
		}else{
			for($i=1; $i<=$_POST["days_per_week"]; $i++) {
				//get the weekday_id
				$wd = dl::select("flexi_weekdays", "fw_weekday = '".$week_day_array["week_day".$i]."'");
				$values = array($wd[0]["fw_id"], $settings[0]["days_settings_id"], $week_day_array["weekday_time".$i].":".$week_day_array["weekday_time".$i."_mins"].":00");
				$writeLine = array_combine($fields, $values);
				dl::insert("flexi_day_times", $writeLine);
			}	
		}
	/*}else{
		echo "<div id='time_dialog' style='display: none;' title=Template is Allocated'>";
		echo "The template details have been updated But...<BR><BR>";
		echo "unfortunately the time details cannot be changed as the Template is allocated to ".$count." user(s)<BR /><BR />";
		echo "To change this template the user(s) must be changed to a different template, then the change can be made.<BR /><BR />";
		echo "<center><input type='button' id='close' value='Close'/></center>";
		echo "</div>";
		?>
		<script>
		$(function() {
			$("#time_dialog").dialog();
			$("#close").click(function() {
				window.location.href = "index.php?choice=Templates&subchoice=flexidaystemplate";
			});
		});
		</script>
		<?php
		die();
	}*/
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=flexidaystemplate')</SCRIPT>" ;
}

function delete_flexi_days_template($id) {
	if(check_permission("Templates")) {
		//need to find the template settings before any deletion
		$settings = dl::select("flexi_template_days", "flexi_template_days_id=$id");
		$setting_id = $settings[0]["template_name_id"];
		dl::delete("flexi_template_days", "flexi_template_days_id=$id");
		dl::delete("flexi_template_days_settings", "template_days_id=$setting_id");
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=flexidaystemplate')</SCRIPT>" ;
	}
}

function add_leave_template() {
	if(check_permission("Templates")) {
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Create Leave Template", "formintro"=>"Fill out the fields below to create the Leave template"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveleavetemplate","method"=>"post")),	
			array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>20, "value"=>"Enter template name", "clear"=>true),	
			array("prompt"=>"Leave Entitlement", "type"=>"text", "name"=>"leave_entitlement", "length"=>20, "value"=>"", "clear"=>true),
			array("prompt"=>"Leave Hours", "type"=>"text", "name"=>"leave_hours", "length"=>10, "value"=>"", "clear"=>true),
			array("prompt"=>"Start Month", "type"=>"selection", "name"=>"start_month", "listarr"=>array( "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ), "selected"=>"October", "value"=>"", "clear"=>true),
			array("prompt"=>"Template Type", "type"=>"radio", "name"=>"template_type", "listarr"=>array("Fulltime", "Parttime"), "selected"=>"", "value"=>"", "clear"=>true),
			array("prompt"=>"Leave Note", "type"=>"textarea", "name"=>"leave_note", "rows"=>8, "cols"=>70, "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Create Template", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		$leave=dl::select("flexi_al_template");
		echo "<div style='clear:both'><table class='table_view'>";
		echo "<tr><th>Template Name</th><th>Delete</th><th>Edit</th></tr>";
		foreach($leave as $l) {
			echo "<tr><td>".$l["al_description"]."</td><td><a href='index.php?func=deleteleavetemplate&id=".$l["al_template_id"]."'>delete</a></td><td><a href='index.php?func=editleavetemplate&id=".$l["al_template_id"]."'>edit</a></td></tr>";
		}
		echo "</table></div>";
		echo "</div>";
	}
}

function save_leave_template() {
    echo date("Y-m-d");
	$fieldarr= array("al_entitlement", "al_description","al_start_month", "al_type");
	$postarr= array($_POST["leave_entitlement"],$_POST["template_name"],$_POST["start_month"], $_POST["template_type"]);
	$save=array_combine($fieldarr, $postarr);
	dl::insert("flexi_al_template", $save);
	$lastId = dl::getId();
	dl::insert("flexi_al_hours", array("template_id"=>$lastId, "h_hours"=>$_POST["leave_hours"]));
    dl::insert("flexi_al_notes", array("n_template_id"=>$lastId, "n_note"=>$_POST["leave_note"], "n_date"=>date("Y-m-d")));
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=leavetemplate')</SCRIPT>" ;
}

function edit_leave_template() {
	if(check_permission("Templates")) {
		$leave=dl::select("flexi_al_template", "al_template_id=".$_GET["id"]);
		$hours=dl::select("flexi_al_hours", "template_id = ".$_GET["id"]);
        $notes=dl::select("flexi_al_notes", "n_template_id = ".$_GET["id"]);
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Edit Leave Template", "formintro"=>"Fill out the fields below to edit the Leave template"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveleavetemplateedit&id=".$_GET["id"],"method"=>"post")),	
			array("prompt"=>"Template Name", "type"=>"text", "name"=>"template_name", "length"=>20, "value"=>$leave[0]["al_description"], "clear"=>true),	
			array("prompt"=>"Leave Entitlement", "type"=>"text", "name"=>"leave_entitlement", "length"=>20, "value"=>$leave[0]["al_entitlement"], "clear"=>true),
			array("prompt"=>"Leave Hours", "type"=>"text", "name"=>"leave_hours", "length"=>10, "value"=>$hours[0]["h_hours"], "clear"=>true),
			array("prompt"=>"Start Month", "type"=>"selection", "name"=>"start_month", "listarr"=>array( "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ), "selected"=>$leave[0]["al_start_month"], "value"=>"", "clear"=>true),
			array("prompt"=>"Template Type", "type"=>"radio", "name"=>"template_type", "listarr"=>array("Fulltime", "Parttime"), "selected"=>$leave[0]["al_type"], "value"=>"", "clear"=>true),
			array("prompt"=>"Leave Note", "type"=>"textarea", "name"=>"leave_note", "rows"=>8, "cols"=>70, "value"=>"", "clear"=>true),
            array("prompt"=>"Template Change Date", "type"=>"date", "name"=>"date_change", "length"=>20, "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Save Template", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
		if(!empty($notes)){ 
    		echo "<div style='clear:both'><table class='table_view'>";
            echo "<tr><th>Date</th><th>Note</th><th>Delete</th></tr>";
            foreach($notes as $n) {
                echo "<tr><td>".$n["n_date"]."</td><td><a id='leave".$n["n_id"]."' href='#' title='".substr($n["n_note"],0,145)."...'>View Note</a></td><td>
                <a href='index.php?func=deleteleavenote&id=".$n["n_id"]."'>Delete Note</a></td></tr>";
                
            }
            echo "</table></div>";
            echo "</div>";
            foreach($notes as $n) {
                echo "<div id='dialog".$n["n_id"]."' style='display: none;' title='Viewing the leave note'>";
                echo nl2br($n["n_note"])."<BR /><BR />";
                echo "</div>";
                ?>
                <script>
                $(function() {
                    
                    $("#<?php echo "leave".$n["n_id"]?>").click(function(){
                      $("#<?php echo "dialog".$n["n_id"]?>").dialog({
                        resizable: false,
                        height:200,
                        modal: true,
                        buttons: {
                            "Cancel": function() {
                                $( "#<?php echo "dialog".$n["n_id"]?>" ).dialog( "close" );
                            }
                        }
                      });
                    });
                });
                </script>
                <?php
            }
        }
	}
}

function save_leave_template_edit() {
	$fieldarr= array("al_entitlement", "al_description","al_start_month", "al_type");
	$postarr= array($_POST["leave_entitlement"],$_POST["template_name"],$_POST["start_month"], $_POST["template_type"]);
	$save=array_combine($fieldarr, $postarr);
	dl::update("flexi_al_template", $save, "al_template_id=".$_GET["id"]);
    if(!empty($_POST["leave_note"])) {
        dl::insert("flexi_al_notes", array("n_template_id"=>$_GET["id"], "n_note"=>$_POST["leave_note"], "n_date"=>$_POST["date_change"]));
    }
	$hours = dl::select("flexi_al_hours", "template_id = ".$_GET["id"]);
	if(empty($hours)) {
		dl::insert("flexi_al_hours", array("template_id"=>$_GET["id"], "h_hours"=>$_POST["leave_hours"]));
	}else{
		dl::update("flexi_al_hours", array("h_hours"=>$_POST["leave_hours"]), "template_id=".$_GET["id"]);
	}
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=leavetemplate')</SCRIPT>" ;
}

function delete_leave_template($id) {
	if(check_permission("Templates")) {
		//need to find the template settings before any deletion
		dl::delete("flexi_al_template", "al_template_id=$id");
		dl::delete("flexi_al_hours", "template_id=$id");
		dl::delete("flexi_al_notes", "n_template_id=$id");
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=leavetemplate')</SCRIPT>" ;
	}
}

function delete_leave_note($id) {
	dl::delete("flexi_al_notes", "n_id=$id");
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=Templates&subchoice=leavetemplate')</SCRIPT>" ;
}

function add_team() {
	if(check_permission("Teams")) {
		$global_events = dl::select("flexi_global_events");
		foreach($global_events as $global_event) {
			$eventType = dl::select("flexi_event_type", "event_type_id = ".$global_event["event_type_id"]);
			$aEventTypes[]=$global_event["event_date"]." ".$eventType[0]["event_type_name"];
		}
		echo "<div class='timesheet_workspace'>";
		$formArr = array(array("type"=>"intro", "formtitle"=>"Create a new Team", "formintro"=>"Create a new team within your company"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveteam","method"=>"post")),	
			array("prompt"=>"Team Name", "type"=>"text", "name"=>"team_name", "length"=>40, "value"=>"Enter team name", "clear"=>true),	
			array("prompt"=>"Select Global Events", "type"=>"selection", "name"=>"globals[]", "listarr"=>$aEventTypes, multiple=>true, "selected"=>"", "value"=>"", "clear"=>true),
			array("type"=>"submit", "buttontext"=>"Create New Team", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		echo "</div>";
	}
}

function save_team($submitted) {
	//write to the team table
	$fieldarr=array("team_name");
	$save = array_combine($fieldarr, array($submitted["team_name"]));
	dl::insert("flexi_team", $save);
	//get the Team Id
	$team_id = dl::select("flexi_team", "team_name = '".$submitted["team_name"]."'");
	$teamId = $team_id[0]["team_id"];
	$global_events = $submitted["globals"];
	foreach($global_events as $global_event) {
		//split the passed array into date and description
		$length = strlen($global_event);
		$event_date = substr($global_event,0,10);
		$event_type = substr($global_event,11,$length-11);
		$event_typeId = dl::select("flexi_event_type", "event_type_name = '".$event_type."'");
		$global_id = dl::select("flexi_global_events", "event_date = '".$event_date."' and event_type_id = ".$event_typeId[0]["event_type_id"]);
		$globalId = $global_id[0]["global_id"];
		$fieldarr= array("global_id", "team_id");
		$values = array($globalId, $teamId);
		$save = array_combine($fieldarr,$values);
		dl::insert("flexi_global_teams", $save);
	}
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=Teams')</SCRIPT>" ;
}

function view_teams() {
	echo "<div class='timesheet_header'>View Teams</div>";
	$events = dl::select("flexi_team","","team_name");
	echo "<table class='table_view'>";
	echo "<tr><th>Name</th></tr>";
	foreach($events as $event) {
		echo "<tr><td>".$event["team_name"]."</td></tr>";
	}
	echo "</table>";
}

function select_teams() {
	if(check_permission("Teams")) {
		echo "<div class='timesheet_header'>Edit Teams</div>";
		$events = dl::select("flexi_team","","team_name");
		echo "<table class='table_view'>";
		echo "<tr><th>Name</th><th>Delete</th><th>Edit</th></tr>";
		foreach($events as $event) {
			echo "<tr><td>".$event["team_name"]."</td><td><a href='index.php?func=deleteteam&id=".$event["team_id"]."'>delete</a></td><td><a href='index.php?func=editteam&id=".$event["team_id"]."'>edit</a></td></tr>";
		}
		echo "</table>";
	}
}

function edit_teams() {
	if(check_permission("Teams")) {
		$global_events = dl::select("flexi_global_events");
		foreach($global_events as $global_event) {
			$eventType = dl::select("flexi_event_type", "event_type_id = ".$global_event["event_type_id"]);
			$aEventTypes[]=$global_event["event_date"]." ".$eventType[0]["event_type_name"];
		}
		$global_teams = dl::select("flexi_global_teams", "team_id = ".$_GET["id"]);
		foreach( $global_teams as $global_team ) {
			$globalEventsId[]=$global_team["global_id"];
		}
		foreach( $globalEventsId as $Ids ){
			$eventDesc = dl::select("flexi_global_events", "global_id=".$Ids);
			$eventType = dl::select("flexi_event_type", "event_type_id = ".$eventDesc[0]["event_type_id"]);
			$eventList[] = $eventDesc[0]["event_date"]." ".$eventType[0]["event_type_name"];
		}
		$teams = dl::select("flexi_team","team_id=".$_GET["id"]);
		echo "<div class='timesheet_workspace'>";
		foreach($teams as $team){
			$formArr = array(array("type"=>"intro", "formtitle"=>"Edit Team", "formintro"=>"Edit a team within your company"), 
			array("type"=>"form", "form"=>array("action"=>"index.php?func=saveteamedit&id=".$_GET["id"],"method"=>"post")),	
			array("prompt"=>"Team Name", "type"=>"text", "name"=>"team_name", "length"=>40, "value"=>$team["team_name"], "clear"=>true),
			array("prompt"=>"Select Global Events", "type"=>"selection", "name"=>"globals[]", "listarr"=>$aEventTypes, multiple=>true, "selected"=>$eventList, "value"=>"", "clear"=>true),	
			array("type"=>"submit", "buttontext"=>"Save Team", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr);
		}
		echo "</div>";
	}
}

function save_team_edit() {
	$fieldarr=array("team_name");
	$save = array_combine($fieldarr, array($_POST["team_name"]));
	dl::update("flexi_team", $save, "team_id=".$_GET["id"]);
	//get the Team Id
	$teamId = $_GET["id"];
	//delete the existing global team entries and add all new ones - saves checking which ones remain and which are new
	dl::delete("flexi_global_teams", "team_id = ".$teamId);
	$global_events = $_POST["globals"];
	foreach($global_events as $global_event) {
		//split the passed array into date and description
		$length = strlen($global_event);
		$event_date = substr($global_event,0,10);
		$event_type = substr($global_event,11,$length-11);
		$event_typeId = dl::select("flexi_event_type", "event_type_name = '".$event_type."'");
		$global_id = dl::select("flexi_global_events", "event_date = '".$event_date."' and event_type_id = ".$event_typeId[0]["event_type_id"]);
		$globalId = $global_id[0]["global_id"];
		$fieldarr= array("global_id", "team_id");
		$values = array($globalId, $teamId);
		$save = array_combine($fieldarr,$values);
		dl::insert("flexi_global_teams", $save);
	}
	echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=Teams')</SCRIPT>" ;
}

function delete_teams() {
	if(check_permission("Teams")) {
		dl::delete("flexi_team","team_id=".$_GET["id"]);
		echo "<SCRIPT language='javascript'>redirect('index.php?choice=View&subchoice=Teams')</SCRIPT>" ;
	}
}

function leave_dates($user_id, $year="") {
	//find users leave teamplate
	$user 							= dl::select("flexi_user", "user_id=".$user_id);
	$userName 						= $user[0]["user_name"];
	$leaveCheck 					= new check_leave($user_id);
	$accType 						= $leaveCheck->getLeaveAccountType();
	$proRataTime					= $leaveCheck->getProRataTime();
	$entitlement					= $leaveCheck->getLeaveEntitledTo();
	//get annual leave entitlement
	$al 							= dl::select("flexi_al_template", "al_template_id=".$user[0]["user_al_template"]);
	$entitledTo 					= $al[0]["al_entitlement"];
	$leavestart 					= $al[0]["al_start_month"];
	//get used leave
	if(date("n") 					>= date("n", strtotime($leavestart))){
		//year is this year
		if(empty($year)) {
			$year 					= date("Y");
		}
		$datetoCompare 				= date("Y-m-d", mktime(0,0,0,date("n",strtotime($leavestart)),1,$year))." 00:00:00";
		$lastdate 					= date("Y-m-d", mktime(0,0,0,date("n",strtotime($leavestart)),1,$year+1))." 00:00:00";
	}else{
		//year is last year
		if(empty($year)) {
			$year 					= date("Y")-1;
		}
		$datetoCompare				= date("Y-m-d", mktime(0,0,0,date("n",strtotime($leavestart)),1,$year))." 00:00:00";
		$lastdate 					= date("Y-m-d", mktime(0,0,0,date("n",strtotime($leavestart)),1,$year+1))." 00:00:00";
	}
	// need to find out which event signifies an annual leave event type
	$leaveEvent 					= dl::select("flexi_event_type", "event_al='Yes'");
	$leaveId 						= $leaveEvent[0]["event_type_id"];
	echo "<div class='timesheet_header'>Leave for $userName</div>";
	$sql 							= "Select fe.event_id, fe.event_startdate_time, fe.event_enddate_time from flexi_event as fe 
	join flexi_event_type as fet on (fet.event_type_id=fe.event_type_id) 
	join flexi_timesheet as ft on (fe.timesheet_id=ft.timesheet_id) 
	where fe.event_type_id = $leaveId and event_al = 'Yes' and event_startdate_time >= '$datetoCompare' and event_startdate_time < '$lastdate' and user_id =".$user_id." order by event_startdate_time";
	$l 								= dl::getQuery($sql);
	echo "<table class='table_view'>";
	echo "<tr><th>Date</th><th>Start Time</th><th>End Time</th><th>Accumulated<br>Leave taken<br>(Hrs)</th>";
	if($accType 					== "Fulltime") {
		echo "<th>Accumulated<br>Leave taken<br> (Days)</th>";
	}
	echo "</tr>";
	foreach($l as $leave) {
		$date 						= substr($leave["event_startdate_time"],0,10);
		$time1 						= substr($leave["event_startdate_time"],11,8);
		$time2 						= substr($leave["event_enddate_time"],11,8);
		$time1Secs 					= (substr($time1,0,2)*60*60) + (substr($time1,3,2)*60);
		$time2Secs 					= (substr($time2,0,2)*60*60) + (substr($time2,3,2)*60);
		$timeSecs					= $time2Secs - $time1Secs;
		if(date("H", $timeSecs) 	>= 6){
			$timeSecs 				= $timeSecs - 30*60; //remove 30 minutes for a day in excess of 6 hours as per minimum lunch
		}
		$accHours 					+= date("H", $timeSecs);
		$accMins					+= date("i", $timeSecs);
		if($accMins 				> 60) {
			$accMins 				-= 60;
			$accHours 				+=1;
		}
		$decimal 					= $accMins/60;
		$timeTaken 					= $accHours + $decimal;
		if($accType 				== "Fulltime") {
			$daysFromHrs 			= $timeTaken/$proRataTime;
		}
		echo "<tr><td>".$date."</td><td align='center'>$time1</td><td align='center'>$time2</td><td align='center'>".$timeTaken."</td>";
		if($accType 				== "Fulltime") {
			echo "<td align='center'> ".round($daysFromHrs,2)." day(s)</td>";
		}
		echo "</tr>";
	} 
	echo "</table>";
	$timesheet 						= dl::select("flexi_timesheet", "user_id=".$user_id);
	$checkadditional 				= dl::select("flexi_additional_leave", "timesheet_id=".$timesheet[0]["timesheet_id"]." and leave_year = ". $_GET["year"]);
	if(!empty($checkadditional)) {
		$nextYr = $_GET["year"]+1; 
		echo "<DIV class='timesheet_header'>Year ".$_GET["year"]."-".$nextYr." Summary</DIV>";
		echo "<table class='table_view'>";
		if($_GET["year"] < 2012) { // 2013 was the year the backend changed to hours so all leave etc. was managed using hours. Therefore the report displays differently from this date.
			echo "<tr><th>Additional Days</th><th>Month</th><th>Year</th><th>Taken</th><th>Left</th></tr>";
			foreach( $checkadditional as $ca ) {
				echo "<tr><td>".$ca["additional_days"]."</td><td>".$ca["leave_month"]."</td><td>".$ca["leave_year"]."</td><td>".$ca["leave_taken"]."</td><td>".$ca["leave_left"]."</td></tr>";
			}
		}else{
			echo "<tr><th>Leave<BR>Entitlement<BR>(hrs)</th><th>Additional<BR>Hours</th><th>Year End<br>Month</th><th>Year</th><th>Leave<BR>Taken<BR>(Hrs)</th><th>Leave<BR>Carried<BR>Over</th><th>Leave<BR>Notes</th></tr>";
			foreach( $checkadditional as $ca ) {
				echo "<tr><td align='center'>".$ca["entitlement"]."</td><td align='center'>".$ca["additional_days"]."</td><td align='center'>".$ca["leave_month"]."</td><td align='center'>".$ca["leave_year"]."</td><td align='center'>".$ca["leave_taken"]."</td><td align='center'>".$ca["leave_left"]."</td>";
				echo "<td align='center'><img src='inc/images/notes-icon.png' id='view_notes'></td>";
				echo "</tr>";
			}
		}
		echo "</table>";
		$userTemplate 			= dl::select("flexi_user", "user_id = ".$user_id);
		$alTemplate 			= dl::select("flexi_al_notes", "n_template_id = ". $userTemplate[0]["user_al_template"]." and n_date >= '".$datetoCompare."' and n_date <= '".$lastdate."'");
		$timesheet				= dl::select("flexi_timesheet", "user_id = ".$user_id);
		$carriedOverNotes		= dl::select("flexi_carried_forward_notes", "timesheet_id = ".$timesheet[0]["timesheet_id"]." and note_datetime >= '".$datetoCompare."' and note_datetime <= '".$lastdate."'");
		//setup of the dialog div
		echo "<div id='notes_div' title='Summary Notes for ".$_GET["year"]."-".$nextYr."' style='display: none; overflow-y:scroll'>";
		echo "<div id='note_title'>Leave Notes</div>";
		if(!empty($alTemplate)) {
			foreach($alTemplate as $alt) {
				echo "<div id='note_date'>".$alt["n_date"]."</div>";
				echo "<div id='note_line'>".nl2br($alt["n_note"])."</div>";
			}
		}else{
			echo "<div id='note_line'>There are no Leave Notes for this template within this period.</div>";
		}
		echo "<div id='note_title'>Carriedover Notes</div>";
		if(!empty($carriedOverNotes)) {
			foreach($carriedOverNotes as $co) {
				echo "<div id='note_date'>".$co["note_datetime"]."</div>";
				echo "<div id='note_line'>".nl2br($co["note"])."</div>";
			}
		}else{
			echo "<div id='note_line'>There are no Carried Over Notes for this user within this period.</div>";
		}
		echo "</div>";
		?>
        <script>
        $(function() {
            
            $("#view_notes").click(function(){
              $("#notes_div").dialog({
                resizable: false,
                height:500,
                width: 500,
                modal: true,
                buttons: {
                    "Close": function() {
                        $( "#notes_div" ).dialog( "close" );
                    }
                }
              });
            });
        });
        </script>
        <?php
	}
	$yr								=date("Y")-5;
	echo "<BR />View leave from previous years<BR />"; //5 years in the past
	for($i=$yr; $i<=$yr+5; $i++) {
		$nxtYear = $i+1;
		echo " <a href='index.php?func=showuserleave&year=$i&userid=$user_id'>".$i."-".$nxtYear."</a> |";
	}
	echo "<br><br>";
}

function calendar_picker($date_name, $date_view, $date_select="", $date_ZIndex) {
	$myCalendar = new tc_calendar($date_name, true);
	$myCalendar->setZIndex($date_ZIndex);
	$myCalendar->setIcon("inc/css/images/iconCalendar.gif");
	// $date_view parameter is decided by date format field.
	if(empty($date_select)){
		$myCalendar->setDate(date('d'), date('m'), date('Y'));
	}else{
		$myCalendar->setDate(date('d',mktime(0,0,0,0,substr($date_select,8,2),0)), date('m', mktime(0,0,0,substr($date_select,5,2)+1,0,0)), date('Y', mktime(0,0,0,0,0,substr($date_select,0,4)+1)));
	}
	$myCalendar->setPath("inc/classes/");
	$myCalendar->setYearInterval(2010, 2020);
	$myCalendar->dateAllow('2008-05-13', '2015-03-01', false);
	$myCalendar->startMonday(true);
	$myCalendar->writeScript();
}
/* REPORTS */

function show_sickness_report() {
	//search for the event type that signifies sickness
	//dl::$debug=true;
	if(!empty($_POST)) {
		$startdate=$_POST["date_name"]." 00:00:00";
		$enddate = $_POST["date_name2"]." 23:59:59";
	}else{
		$startdate = date("Y-m-d")." 00:00:00";
		$enddate = date("Y-m-d")." 23:59:59";
	}
	$event_types = dl::select("flexi_event_type", "event_sickness='Yes'");
	$eventTypeId = $event_types[0]["event_type_id"];
	$sql = "select * from flexi_team_user as tu join flexi_team as t on (t.team_id=tu.team_id) 
			where user_id = ".$_SESSION["userSettings"]["userId"]." order by team_name ASC";
	$teams = dl::getQuery( $sql );
	echo "<div class='report_body'><div class='report_heading_space'><div class='report_heading'>SICKNESS ANALYSIS</div></div><div class='report_space'>";
	echo "<div width='100%'>";
	$formArr = array(array("type"=>"intro", "formtitle"=>"Enter Dates", "formintro"=>"Add date range to display sickness (Default = Current Year)"), 
			array("type"=>"form", "form"=>array("action"=>"reports.php?func=Sickness","method"=>"post")),	
			array("prompt"=>"From", "type"=>"date", "name"=>"date_name", "length"=>20, "value"=>$_POST["date_name"], "clear"=>false),
			array("prompt"=>"To", "type"=>"date", "name"=>"date_name2","length"=>20, "value"=>$_POST["date_name2"], "clear"=>true),	
			array("type"=>"submit", "buttontext"=>"New Date Range", "clear"=>true), 
			array("type"=>'endform'));
			$form = new forms;
			$form->create_form($formArr, "102px;");
	echo "</div>";
	foreach($teams as $team) {
		//find members in the team whose home team is the current team
		$sql="Select * from flexi_team_user as tu 
		join flexi_team_local as tl on (tu.team_user_id=tl.team_user_id) 
		where team_id = ".$team["team_id"];
		$userIds = dl::getQuery($sql);
		foreach($userIds as $userId) {
			//get timesheet_id
			$timesheet = dl::select("flexi_timesheet", "user_id = ".$userId["user_id"]);
			//get user Name
			$userName = dl::select("flexi_user", "user_id = ".$userId["user_id"]);
			$sickness = dl::select("flexi_event", "timesheet_id=".$timesheet[0]["timesheet_id"]." and event_type_id=".$event_types[0]["event_type_id"]." and event_startdate_time >= '".$startdate."' and event_enddate_time <= '".$enddate."'", "event_startdate_time DESC");
			if(!empty($sickness)) {
				if($teamCheck != $team["team_name"]) {
					echo "<div class='report_team_heading'>".$team["team_name"]."</div>";
				}
				$teamCheck = $team["team_name"];
			}
			$count=0;
			foreach($sickness as $sn) {
				echo "<span class='report_name'> ".$userName[0]["user_name"]." </span><span class='report_date'> ".date('l jS F, Y', strtotime($sn["event_startdate_time"]))." <span class='report_time'> [ ".substr($sn["event_startdate_time"],11,8)." ".substr($sn["event_enddate_time"],11,8)." ] </span></span><BR>";
				$count++;
			}
			if(!empty($sickness)) {
				echo "<br><div class='report_name'>Sickness Events = ".$count."</div>";
				echo "<div class='report_spacer'></div>";
			}
		}
	}
	echo "</div></div>";
}

function show_leave_report() {		
	//dl::$debug=true;
	$user_id = $_SESSION["userSettings"]["userId"]; //this is the logged on user id.
	echo "<div class='report_body'><div class='report_heading_space'><div class='report_heading'>LEAVE MANAGEMENT REPORT</div></div><div class='report_space'>";
	$leave = dl::select("flexi_carried_forward_notes", "", "note_datetime DESC");
	foreach($leave as $l) {
		$userId = dl::select("flexi_timesheet", "timesheet_id = ".$l["timesheet_id"]);
		$user = dl::select("flexi_user", "user_id = ".$userId[0]["user_id"]);
		$sql = "select * from flexi_team_user as tu join flexi_team_local as tl on (tu.team_user_id=tl.team_user_id) where user_id = ".$user[0]["user_id"];
		$teamLink = dl::getQuery($sql);
		$inTeam = dl::select("flexi_team_user", "user_id = ".$user_id." and team_id = ".$teamLink[0]["team_id"]);
		if(!empty($inTeam)) {
			echo "<span class='report_name'> ".$user[0]["user_name"]." </span><span class='report_date'> ".date('l jS F, Y', strtotime($l["note_datetime"]))." <div class='report_text'>".$l["note"]."</div></span><BR>";
		}
	}
	echo "</div></div>";	
}

function reset_leave_report() {
	//dl::$debug=true;
	echo "<div class='report_body'>
		<div class='report_heading_space'>
			<div class='report_heading'>Leave Year End REPORT</div>
		</div>
	<div class='report_space'>";
	$sql = "select u.user_id, user_name, user_email, user_al_template, date_deleted from flexi_user as u left outer join flexi_deleted as d on (u.user_id = d.user_id) 
				where date_deleted is NULL order by u.user_id ASC";
	$users = dl::getQuery($sql);
	$reset = new report_on_leave( );
	$header = array("Select","Leave month","User Name","Leave days", "Additional days", "Leave taken", "Leave Left");
	$spacing = array(10,10,10,80,10,10,10);
	echo $reset->show_header( $header, $spacing );
	$spacing = array(90,160,90,90,70,60);
	echo $reset->open_form( "form1", "reports.php?func=saveLeave" );
	foreach($users as $user) {
		$leave = new check_leave( $user["user_id"] );
		$annualLeave = $leave->getLeaveEntitledTo();
		$monthName = $reset->get_month($user["user_al_template"]);
		$additionalLeave = round($reset->get_additional_leave( $user["user_id"] ), 1);
		
		$usedleave =  round(usedLeaveLastYear(date("F"), $user["user_id"]), 1);
		$checkUpdated = dl::select("flexi_additional_leave", "timesheet_id = ".$reset->timesheet[0]["timesheet_id"]." and leave_month = '".$monthName."' and leave_year = ".date("Y", strtotime($reset->startDate)));
		if(!empty($checkUpdated)) {
			echo $reset->add_select( "select", $user["user_id"], "disabled" );	
		}else{
			echo $reset->add_select( "select", $user["user_id"] );
		}
		$leaveLeft = round($additionalLeave + $annualLeave - $usedleave, 1);
		$line = array( $monthName, $user["user_name"], $annualLeave, $additionalLeave, $usedleave, $leaveLeft );
		echo $reset->show_line( $line, $spacing );
		echo "<BR />";
	}
	echo $reset->show_button( "Save Data" );
	echo $reset->close_form( );
	echo "</div></div>";
}

function save_additional_leave() {
	foreach( $_POST["select"] as $posted ) {
		$save 						= new check_leave( $posted );
		$users 						= dl::select("flexi_user", "user_id = ".$posted);
		$leave_entitlement 			= $save->getLeaveEntitledTo();
		$annualLeave 				= $save->getHoursTaken();
		$monthName 					= $save->getStartMonth();
		$timesheet 					= dl::select("flexi_timesheet", "user_id = ".$posted);
		$additional 				= dl::select( "flexi_carried_forward_live", "timesheet_id = ". $timesheet[0]["timesheet_id"] );
		$additionalLeave 			= $additional[0]["additional_leave"];
		$usedleave 					= usedLeaveLastYear($monthName, $posted);
		$startDate 					= date( "Y-m-d H:i:s", mktime(0,0,0,date("m"),1,date("Y")-1) );
		$fields 					= array( "timesheet_id", "entitlement", "additional_days", "leave_month", "leave_year", "leave_taken", "leave_left" );
		$leaveRemaining				= round($leave_entitlement + $additionalLeave - $usedleave, 1);
		$values 					= array( $timesheet[0]["timesheet_id"], $leave_entitlement, $additionalLeave, $monthName, date("Y", strtotime($startDate)),$usedleave, $leaveRemaining);
		$arrWrite 					= array_combine($fields, $values);
		//insert the year end leave record into the additional leave table
		dl::insert("flexi_additional_leave", $arrWrite);
		
		/*need to get the users weekly hours and check if they have that to carry over if more then it should equal the weekly hours.
		 * If it's less that the weekly hours then just carry over the calculated hours.
		 */
		$sql 						= "select * from flexi_user as u join flexi_template as t on (u.user_flexi_template=t.template_id)
									join flexi_template_days as td on (td.template_name_id=t.template_id) 
									join flexi_template_days_settings as tds on (td.flexi_template_days_id=tds.template_days_id)
									where user_id = ".$posted;
		$settings					= dl::getQuery($sql);
		$times_id					= $settings[0]["days_settings_id"];
		$times						= dl::select("flexi_day_times", "fdt_flexi_days_id = ".$times_id, "fdt_weekday_id");
		$hr							= 0;
		$min						= 0;
		if($times[0]["fdt_weekday_id"] == 6 ) { //this is same time for 5 days
			$hr 					= date("g", strtotime($times[0]["fdt_working_time"]));
			$min 					= date("i", strtotime($times[0]["fdt_working_time"]));
			$hr 					= $hr * 5;
			$min 					= $min * 5;
			while( $min >= 60 ) {
				$min 				= $min - 60;
				$hr++;
			}
			$min = $min/60; //convert $min to a decimal
			$decimal = round($hr+$min, 2);
		}else{
			foreach ($times as $t) {
				$hr 				+= date("g", strtotime($t["fdt_working_time"]));
				$min 				+= date("i", strtotime($t["fdt_working_time"]));

			}
			while( $min >= 60 ) {
				$min 				= $min - 60;
				$hr++;
			}
			$min 					= $min/60; //convert $min to a decimal
			$decimal 				= round($hr+$min, 1);
		}
		//$decimal is the maximum amount of hours that the user can carry over. This equates to the full hours they work in a week.
		// Equivalent to a week of leave...
		if($leaveRemaining < $decimal) {
			$leaveToCarryOver 		= $leaveRemaining;
		}else{
			$leaveToCarryOver 		= $decimal;
		}
		dl::update( "flexi_carried_forward_live", array("additional_leave"=>$leaveToCarryOver), "timesheet_id=".$timesheet[0]["timesheet_id"]	);
	}
	echo "<SCRIPT language='javascript'> window.location='reports.php?func=leaveReset'</SCRIPT>" ;
}

function usedLeaveLastYear($monthName, $userId) {
	$year 						= date("Y")-1;
	$datetoCompare 				= date("Y-m-d", mktime(0,0,0,date("n",strtotime($monthName)),1,$year));
	//must also check for leave in the following year eg october to december
	$dateNextYr 				= date("Y-m-d", mktime(0,0,0,date("n",strtotime($monthName)),1,date("Y")));
	$sql 						= "Select fe.event_id, fe.event_startdate_time, fe.event_enddate_time from flexi_event as fe 
	join flexi_event_type as fet on (fet.event_type_id=fe.event_type_id) 
	join flexi_timesheet as ft on (fe.timesheet_id=ft.timesheet_id) 
	where fe.event_type_id = 3 and event_al = 'Yes' and event_startdate_time >= '$datetoCompare' and event_startdate_time <= '$dateNextYr' and user_id =".$userId;
	$l 							= dl::getQuery($sql);
	$accHours 					= 0;
	$accMins					= 0;
	foreach($l as $leave) {
		$date 					= substr($leave["event_startdate_time"],0,10);
		$time1 					= substr($leave["event_startdate_time"],11,8);
		$time2 					= substr($leave["event_enddate_time"],11,8);
		$time1Secs 				= (substr($time1,0,2)*60*60) + (substr($time1,3,2)*60);
		$time2Secs 				= (substr($time2,0,2)*60*60) + (substr($time2,3,2)*60);
		$timeSecs				= $time2Secs - $time1Secs;
		if(date("H", $timeSecs) >= 6){
			$timeSecs 			= $timeSecs - 30*60; //remove 30 minutes for a day in excess of 6 hours as per minimum lunch
		}
		$accHours 				+= date("H", $timeSecs);
		$accMins				+= date("i", $timeSecs);
		if($accMins 			> 60) {
			$accMins 			-= 60;
			$accHours 			+=1;
		}
		$decimal 				= $accMins/60;
		$timeTaken 				= $accHours + $decimal;
	}
	return round($timeTaken, 1);
}

?>