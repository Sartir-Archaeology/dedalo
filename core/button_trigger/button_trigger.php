<?php
	
	# CONTROLLER
	
	$tipo 					= $this->get_tipo();
	$target_tipo			= $this->get_target();
	$id						= NULL;
	$mode					= $this->get_mode();
	$label 					= $this->get_label();
	$section_tipo 			= $this->get_section_tipo();
	$debugger				= $this->get_debugger();
	$permissions			= common::get_permissions($section_tipo, $tipo);
	$html_title				= "Info about $tipo";
	$file_name 				= $mode;
	
	switch($mode) {
		
		case 'list':
		case 'edit':		
				$file_name 		= 'edit';
				$properties 	= $this->get_properties();
				
				$properties->component_parent 	= $this->parent;	# add current parent section_id to vars
				$properties->lang_filter 		= DEDALO_DATA_LANG;	# add current lang to vars
				$properties_json 				= json_handler::encode($properties);
	
				# Custom js_exec_function (instead default 'trigger')
					$js_exec_function = isset($properties->js_exec_function) ? $properties->js_exec_function : false;
				break;
		
		default:
				throw new Exception("Error Processing Request. Mode '$mode' not supported by $label", 1);
	}
	

	$page_html	= DEDALO_CORE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if( !include($page_html) ) {
		echo "<div class=\"error\">Invalid mode $this->mode</div>";
	}