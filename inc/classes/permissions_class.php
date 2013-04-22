<?php
class forms {
	var $field_prompt;
	var $field_type;
	var $field_length;
	var $field_value;
	var $field_name;
	var $fieldlist;
	function calendars() {
	}
	function create_form($arrFields){
		//process the array
		foreach( $arrFields as $aField ) {
			$this->field_prompt = $aField['prompt'];
			$this->field_type = $aField['type'];
			$this->field_length = $aField['length'];
			$this->field_value = $aField['value'];
			$this->field_name = $aField['name'];
			if(is_array($aField['values'])) {
				//process the values to list in the dropdown/radio/checkbox	option
			}
		}
	}

}
?>
