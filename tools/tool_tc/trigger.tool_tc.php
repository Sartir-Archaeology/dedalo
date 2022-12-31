<?php
$start_time=hrtime(true);
include( dirname(dirname(dirname(__FILE__))) .'/config/config.php');
# TRIGGER_MANAGER. Add trigger_manager to receive and parse requested data
common::trigger_manager();



/**
* CHANGE_ALL_TIMECODES
*
*/
function change_all_timecodes(object $json_data) : object {
	global $start_time;

	$response = new stdClass();
		$response->result 	= false;
		$response->msg 		= 'Error. Request failed ['.__FUNCTION__.']';


	# set vars
		$vars = array('component_tipo','section_tipo','section_id','lang','offset_seconds');

		foreach($vars as $name) {
			$$name = common::setVarData($name, $json_data);
			# DATA VERIFY
			if (empty($$name)) {
				$response->msg = 'Trigger Error: ('.__FUNCTION__.') Empty '.$name.' (is mandatory)';
				return $response;
			}
		}


	$model_name 	 = RecordObj_dd::get_modelo_name_by_tipo($component_tipo, true);
	$component_obj 	 = component_common::get_instance($model_name,
													  $component_tipo,
													  $section_id,
													  'edit',
													  $lang,
													  $section_tipo);

	$tool_tc  = new tool_tc($component_obj);

	$response = (object)$tool_tc ->change_all_timecodes($offset_seconds);

	# Debug
	if(SHOW_DEBUG===true) {
		$debug = new stdClass();
			$debug->exec_time	= exec_time_unit($start_time,'ms')." ms";
			foreach($vars as $name) {
				$debug->{$name} = $$name;
			}

		$response->debug = $debug;
	}

	return (object)$response;
}//end change_all_timecodes
