<?php
class calendars {
	var $todays_date;
	var $todays_day;
	var $todays_month;
	var $todays_year;
	var $dateFormat;
	function calendars() {
	}
	function set_date_format($dFormat){
		//date format is either dd-mm-yyyy or mm-dd-yyyy
		if($dFormat == "dd-mm-yyyy") {
			$this->dateFormat = "dd-mm-yyyy";
		}else{
			$this->dateFormat = "mm-dd-yyyy";
		}
		return true;
	}
	function get_todays_date(){
		
	}
	function get_todays_day(){
		return(date("j"));//numeric representation of the date day eg. returns 1 if today is the first of the month
	}
	function get_todays_month_num() {
		return date("n");
	}
	function get_todays_month($month_offset=0){
		return date("F",mktime(0,0,0,$this->get_todays_month_num()+$month_offset,1,$this->get_todays_year())); //returns the month name eg. January to December
	}
	function get_todays_year(){
		return(date("Y"));
	}
	function get_first_day($month_offset=0) {
		// find the first day of the month. Need this to display calendar correctly.
		return date("l", mktime(0,0,0,$this->get_todays_month_num()+$month_offset,1,$this->get_todays_year()));
	}
	function get_first_day_num($month_offset=0){
		return date("j", mktime(0,0,0,$this->get_todays_month_num()+$month_offset,1,$this->get_todays_year()));
	}
}
?>
