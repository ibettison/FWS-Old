<?php
//TODO : Year end report for hours (October)
class report_on_leave {
	public $leave_entitlement;
	public $leave_month;
	public $timesheet;
	public $startDate;
	public $endDate;
	public $day_duration;
	

	public function __construct(  ) {
		$this->get_dates( date("Y"), date("m") );
	}
	
	public function show_header( $header, $spacing ) {
		$base = "";
		$sp=0;
		foreach($header as $head) {
			$base .= "<div style='padding-left:".$spacing[$sp++]."px; float:left;'>$head</div>";
		}
		$base .="<br />";
		return $base;
	}
	
	public function show_line( $line, $spacing ) {
		$base = "";
		$sp=0;
		foreach($line as $ln) {
			$base .= "<div style='width:".$spacing[$sp++]."px; float:left;'>$ln</div>";
		}
		$base .="<br />";
		return $base;
	}
	
	public function add_select( $name, $value, $disable="") {
		$base = "<div style='padding-left:12px; padding-right:20px; float:left;'><input type='checkbox' name='$name"."[]"."' value='$value' $disable /></div>";
		return $base;
	}
	
	public function get_month( $leaveId ) {
		$leave = dl::select("flexi_al_template", "al_template_id = ".$leaveId);
		$this->leave_month = $leave[0]["al_start_month"];
		return $this->leave_month;
	}
	
	public function get_leave( ){
		return $this->leave_entitlement;
	}
	
	public function timesheetId( $userId ) {
		$this->timesheet = dl::select( "flexi_timesheet", "user_id = ".$userId );
	}
	
	public function get_additional_leave( $userId ) {
		$this->timesheetId( $userId );
		$additional = dl::select( "flexi_carried_forward_live", "timesheet_id = ". $this->timesheet[0]["timesheet_id"] );
		return $additional[0]["additional_leave"];			
	}
	
	public function used_leave( $userId ) {
		$this->day_duration = $this->time_template_settings( $userId );
		$this->timesheetId( $userId );
		$sql = "select * from flexi_event
		where event_startdate_time >= '".$this->startDate."' and event_startdate_time < '".$this->endDate."'
		and event_type_id = 3 and timesheet_id = ".$this->timesheet[0]["timesheet_id"];
		$count = dl::getQuery($sql);
		$counter = 0;
		foreach( $count as $c ) {
			if(date("H:i:s", strtotime($c["event_enddate_time"]) - strtotime($c["event_startdate_time"]) ) >= $this->day_duration/2 ) {
				$counter += 1;
			}else{
				$counter += 0.5;
			}
		}
		return $counter;
	}
	
	public function time_template_settings( $userId ) {
		$this->timesheetId( $userId );
		$sql = "select * from flexi_user as u 
		join flexi_template as t on (t.template_id=u.user_flexi_template) 
		join flexi_template_days as td on (td.template_name_id=t.template_name_id) 
		join flexi_template_days_settings as tds on (td.flexi_template_days_id=tds.template_days_id) 
		where user_id = $userId";
		$settings = dl::getQuery($sql);
		return $settings[0]["day_duration"];
	}
	
	public function get_duration( ) {
		return $this->day_duration;
	}
	
	public function get_dates( $year, $month ) {
		$this->startDate = date( "Y-m-d H:i:s", mktime(0,0,0,$month,1,$year-1) );
		$this->endDate = date( "Y-m-d H:i:s", mktime(0,0,0,$month,1,$year) );
	}
	
	public function open_form( $name, $action ) {
		return "<form name='$name' action='$action' method='POST' >";	
	}
	
	public function close_form(  ) {
		return "</form>";	
	}
	
	public function show_button( $value ){
		return "<input type='submit' value='$value' />";	
	}
	
	public function update_leave( $fields, $values ) {
		$checkFile = dl::select("flexi_additional_leave", "timesheet_id = ".$values[0]." and leave_month = '".$values[2]."' and leave_year = ".$values[3]);
		if(empty($checkFile)) {
			$writeln = array_combine($fields, $values);
			dl::insert("flexi_additional_leave", $writeln);	
		}else{
			$writeln = array_combine($fields, $values);
			dl::update("flexi_additional_leave", $writeln, "timesheet_id = ".$values[0]." and leave_month = '".$values[2]."' and leave_year = ".$values[3]);
		}
	}
}
?>