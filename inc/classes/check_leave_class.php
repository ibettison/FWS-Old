<?php
class check_leave {
	private $userId;
	private $entitledTo;
	private $nextYrLeave;
	private $hoursLeave;
	private $hoursTaken;
	private $leaveAccountType;
	private $proRataTime;
	private $leavestart;
	
	
	public function __construct( $user_id ) {
		$this->userId 				=	$user_id;
		$this->checkLeaveEntitlement();
	}
	
	private function checkLeaveEntitlement() {
		$userSettings 				= dl::select("flexi_user", "user_id=".$this->userId);
		$al 						= dl::select("flexi_al_template", "al_template_id=".$userSettings[0]["user_al_template"]);
		$hours 						= dl::select("flexi_al_hours", "template_id=".$al[0]["al_template_id"]);
		$this->leaveAccountType		= $al[0]["al_type"];
		$this->entitledTo			= $hours[0]["h_hours"];	
		$this->leavestart 			= $al[0]["al_start_month"];
		$this->hoursLeave 			= $hours[0]["h_hours"];
		
		//get used leave
		if(date("n") 				>= date("n", strtotime($this->leavestart))){
			//year is this year
			$year 					= date("Y");
			$datetoCompare			= date("Y-m-d", mktime(0,0,0,date("n",strtotime($this->leavestart)),1,$year));
			$dateNextYr  			= date("Y-m-d", mktime(0,0,0,date("n",strtotime($this->leavestart)),1,date("Y")+1));
			
		}else{
			//the year is last year
			$year 					= date("Y")-1;
			$datetoCompare 			= date("Y-m-d", mktime(0,0,0,date("n",strtotime($this->leavestart)),1,$year));
			//must also check for leave in the following year eg october to december
			$dateNextYr 			= date("Y-m-d", mktime(0,0,0,date("n",strtotime($this->leavestart)),1,date("Y")));
		}
		//check if you have any leave booked from next years entitlement
		$sql 						= "select * from flexi_event as e
		join flexi_event_type as fet on (fet.event_type_id=e.event_type_id) 
		join flexi_timesheet as ft on (e.timesheet_id=ft.timesheet_id)
		where event_startdate_time >= '$dateNextYr' and e.event_type_id = 3 and event_al = 'Yes' and user_id = ".$this->userId;
		$nextDaysTaken 				= 0;
		$nextYrL 					= dl::getQuery($sql);
		foreach($nextYrL as $nYr) {
			$checkLeave 			= dl::select("flexi_leave_count", "flc_event_id = ".$nYr["event_id"]);
			if(!empty($checkLeave)) {
				$nextDaysTaken   	+= $checkLeave[0]["flc_fullorhalf"];
			}
		}
		$this->nextYrLeave 			= $nextDaysTaken;
		$sql 						= "Select fe.event_id, fe.event_startdate_time, fe.event_enddate_time from flexi_event as fe 
		join flexi_event_type as fet on (fet.event_type_id=fe.event_type_id) 
		join flexi_timesheet as ft on (fe.timesheet_id=ft.timesheet_id) 
		where fe.event_type_id = 3 and event_al = 'Yes' and event_startdate_time >= '$datetoCompare' and event_startdate_time <= '$dateNextYr' and user_id =".$this->userId;
		$l 							= dl::getQuery($sql);
		$sql 						= "select * from flexi_user as u join flexi_template as t on (u.user_flexi_template=t.template_id)
					join flexi_template_days as td on (td.template_name_id=t.template_id) 
					join flexi_template_days_settings as tds on (td.flexi_template_days_id=tds.template_days_id)
					where user_id = ".$this->userId;
		$template_days_id 			= dl::getQuery($sql);
		$this->proRataTime			= $template_days_id[0]["max_surplus"];
		$daysTaken					= 0;
		$hour						= 0;
		$min						= 0;
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
		$this->hoursTaken 			= $timeTaken;
		return true;
	}
	
	public function getNextYrLeaveTaken(){
		return $this->nextYrLeave;
	}
	
	public function getHoursTaken() {
		return $this->hoursTaken;
	} 
	
	public function getHoursLeave() {
		return $this->hoursLeave;
	}
	
	public function getStartMonth() {
		return $this->leavestart;
		
	}
	
	public function getLeaveAccountType() {
		return $this->leaveAccountType;
	}
	
	public function getLeaveEntitledTo() {
		return $this->entitledTo;
	}
	
	public function getProRataTime() {
		return $this->proRataTime;
	}
}
?>