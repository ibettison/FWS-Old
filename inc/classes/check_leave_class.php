<?php
class check_leave {
	private $userId;
	private  $entitledTo;
	private $nextYrLeave;
	private $hoursLeave;
	private $hoursTaken;
	private $leaveAccountType;
	private $proRataTime;
	
	
	public function __construct( $user_id ) {
		$this->userId 						=	$user_id;
		$this->checkLeaveEntitlement();
	}
	
	private function checkLeaveEntitlement() {
		$userSettings 						= dl::select("flexi_user", "user_id=".$this->userId);
		$al 										= dl::select("flexi_al_template", "al_template_id=".$userSettings[0]["user_al_template"]);
		$hours 									= dl::select("flexi_al_hours", "template_id=".$al[0]["al_template_id"]);
		$this->leaveAccountType		= $al[0]["al_type"];
		$this->entitledTo					= $hours[0]["h_hours"];	
		$leavestart 							= $al[0]["al_start_month"];
		$this->hoursLeave 					= $hours[0]["h_hours"];
		
		//get used leave
		if(date("n") 							>= date("n", strtotime($leavestart))){
			//year is this year
			$year 								= date("Y");
			$datetoCompare				= date("Y-m-d", mktime(0,0,0,date("n",strtotime($leavestart)),1,$year));
			$dateNextYr  					= date("Y-m-d", mktime(0,0,0,date("n",strtotime($leavestart)),1,date("Y")+1));
			
		}else{
			//the year is last year
			$year 								= date("Y")-1;
			$datetoCompare 				= date("Y-m-d", mktime(0,0,0,date("n",strtotime($leavestart)),1,$year));
			//must also check for leave in the following year eg october to december
			$dateNextYr 						= date("Y-m-d", mktime(0,0,0,date("n",strtotime($leavestart)),1,date("Y")));
		}
		//check if you have any leave booked from next years entitlement
		$sql 										= "select * from flexi_event as e
		join flexi_event_type as fet on (fet.event_type_id=e.event_type_id) 
		join flexi_timesheet as ft on (e.timesheet_id=ft.timesheet_id)
		where event_startdate_time >= '$dateNextYr' and e.event_type_id = 3 and event_al = 'Yes' and user_id = ".$this->userId;
		$nextDaysTaken 					= 0;
		$nextYrL 								= dl::getQuery($sql);
		foreach($nextYrL as $nYr) {
			$checkLeave 					= dl::select("flexi_leave_count", "flc_event_id = ".$nYr["event_id"]);
			if(!empty($checkLeave)) {
				$nextDaysTaken   			+= $checkLeave[0]["flc_fullorhalf"];
			}
		}
		$this->nextYrLeave 				= $nextDaysTaken;
		$sql 										= "Select fe.event_id, fe.event_startdate_time, fe.event_enddate_time from flexi_event as fe 
		join flexi_event_type as fet on (fet.event_type_id=fe.event_type_id) 
		join flexi_timesheet as ft on (fe.timesheet_id=ft.timesheet_id) 
		where fe.event_type_id = 3 and event_al = 'Yes' and event_startdate_time >= '$datetoCompare' and event_startdate_time <= '$dateNextYr' and user_id =".$this->userId;
		$l 										= dl::getQuery($sql);
		$sql 										= "select * from flexi_user as u join flexi_template as t on (u.user_flexi_template=t.template_id)
					join flexi_template_days as td on (td.template_name_id=t.template_id) 
					join flexi_template_days_settings as tds on (td.flexi_template_days_id=tds.template_days_id)
					where user_id = ".$this->userId;
		$template_days_id 				= dl::getQuery($sql);
		$this->proRataTime				= $template_days_id[0]["max_surplus"];
		$daysTaken							= 0;
		$hour									= 0;
		$min										= 0;
		foreach($l as $leave) {
			$dayVal 							= date("N", strtotime($leave["event_startdate_time"]));
			$time								= dl::select("flexi_day_times", "fdt_weekday_id=".$dayVal." and fdt_flexi_days_id = ".$template_days_id[0]["days_settings_id"]);
			
			if(empty($time)) { // assuming this applies to all days but lets check
				$time 							= dl::select("flexi_day_times", "fdt_weekday_id = 6 and fdt_flexi_days_id = ".$template_days_id[0]["days_settings_id"]);
			}
			$checkLeave 					= dl::select("flexi_leave_count", "flc_event_id = ".$leave["event_id"]);
			if(!empty($checkLeave)) {
				$daysTaken 					+= $checkLeave[0]["flc_fullorhalf"];
				$hour 							+= substr($time[0]["fdt_working_time"],0,2) * $checkLeave[0]["flc_fullorhalf"]; 			//multiply by 1 or 0.5 depending on if its a full or half days leave
				$min 							+= (substr($time[0]["fdt_working_time"],3,2)/60) * $checkLeave[0]["flc_fullorhalf"]; 	//convert to a decimal divide by 60
			}
		}
		$this->hoursTaken 				= $hour + $min;
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