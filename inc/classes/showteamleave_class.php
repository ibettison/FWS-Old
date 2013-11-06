<?php
/*this class is to display leave for a whole team
 * but it will also display the leave for the list of teams a manager is responsible for
 * providing a full overview of the people in one report
 * 
 * 		$user_id is the user id of the manager
 * 		$start is the start date of the period to view
 * 		$end is the end date of the period to view
 */
class showteamleave {
	public static $user;
	public static $start;
	public static $end;
	
	function __construct() {
	}
	
	static function display_leave($user_id, $start, $end) {
		self::$user 											= $user_id;
		self::$start 											= $start;
		self::$end												= $end;	
			
	}
	
	
	static function display_title($teamName) {
		echo "<div class='timesheet_team_leave'><div class='timesheet_team_name'>Team Name : ".$teamName."</div>";
		echo "<div class='timesheet_members'>MEMBERS</div><div class='timesheet_members'>LEAVE IN PERIOD</div>";
	}
	
	static function display_members($team_members) {
		foreach($team_members as $members) {
			//get timesheet id of member
			$timesheet_id 										= dl::select("flexi_timesheet", "user_id = ".$members["user_id"]);
			$userName 											= dl::select("flexi_user", "user_id = ".$members["user_id"]);
			//need to check if the user has not got the permission_view_override permmission set to true
			$sql 												= "select * from flexi_user as u join flexi_permission_template_name as n on (u.user_permission_id=n.permission_id) 
																join flexi_permission_template as t on (n.permission_id=t.permission_template_name_id)
																where u.user_id = ".$members["user_id"];
			$checkPermission 									= dl::getQuery($sql);
			if($checkPermission[0]["permission_view_override"] 	== 'false') {
			//check for a deleted user too
				$deleted 										= dl::select("flexi_deleted", "user_id = ".$members["user_id"]);
				if(empty($deleted)) {
					$events 									= dl::select("flexi_event", "event_startdate_time >= '".self::$start."' and event_enddate_time <= '".self::$end."' and timesheet_id = ".$timesheet_id[0]["timesheet_id"]. " and event_type_id != 1", "event_startdate_time");
					echo "<div class='timesheet_leave_name'><a href='index.php?func=nextperiod&start=".substr(self::$start,0,10)."&end=".substr(self::$end,0,10)."&userid=".$members["user_id"]."'>".$userName[0]["user_name"]."</a></div>";
					foreach($events as $event) {
						$event_type 							= dl::select("flexi_event_type", "event_type_id = ".$event["event_type_id"]);
						echo "<div class='timesheet_leave_day' style='background-color: ".$event_type[0]["event_colour"]."'>".date("d/m", strtotime($event["event_startdate_time"]))." (".$event_type[0]["event_shortcode"].")</div>";
					}
				}
			}
			echo "<BR>";
		}
		echo "</div>"; //timesheet_team_leave
	}
}

class showlocalteamleave extends showteamleave {
	static function display_leave($user_id, $start, $end) {
		parent::display_leave($user_id, $start, $end);
		$team = dl::select("flexi_team_user", "user_id = ".self::$user);
		foreach($team as $t) {
			$localTeam 											= dl::select("flexi_team_local", "team_user_id = ".$t["team_user_id"]);
			if(!empty($localTeam)) {
				$userLocalTeam_id 								= $t["team_id"];
				$localTeamName 									= dl::select("flexi_team", "team_id = ".$userLocalTeam_id);
				$sql 											= "select * from flexi_team_user as u join flexi_team_local as l on (u.team_user_id=l.team_user_id)
																join flexi_user as fu on (fu.user_id=u.user_id)
																where u.team_id = ".$userLocalTeam_id." and l.team_user_id IS NOT NULL order by SUBSTRING_INDEX(user_name, ' ', -1)";
				$localTeamMembers 								= dl::getQuery($sql);
			}
		}
		self::display_title($localTeamName[0]["team_name"]);
		self::display_members($localTeamMembers);
	}
}

class showallteamleave extends showteamleave {
	static function display_leave($user_id, $start, $end) {
		parent::display_leave($user_id, $start, $end);
		$team = dl::select("flexi_team_user", "user_id = ".self::$user);
		foreach($team as $t) {
			$team_name 											= dl::select("flexi_team", "team_id = ".$t["team_id"]);
			if(!empty($team)) {
				self::display_title($team_name[0]["team_name"]);
				$userTeam_id 									= $t["team_id"];
				$sql 											= "select * from flexi_team_user as u join flexi_team_local as l on (u.team_user_id=l.team_user_id)
																join flexi_user as fu on (fu.user_id=u.user_id)
																where u.team_id = ".$userTeam_id." and l.team_user_id IS NOT NULL order by SUBSTRING_INDEX(user_name, ' ',-1)";
				$teamMembers 									= dl::getQuery($sql);
				self::display_members($teamMembers);
			}
		}
	}
}
?>