<?php
// JSON data component controller (this controls the context and the data, coming from the PHP class, that are sent to client -> JS object)



// component configuration vars
	$permissions	= $this->get_component_permissions();
	$mode			= $this->get_mode();



// context
	$context = [];


	if($options->get_context===true) { //  && $permissions>0
		switch ($options->context_type) {
			case 'simple':
				// Component structure context_simple (tipo, relations, properties, etc.)
				$context[] = $this->get_structure_context_simple($permissions);
				break;

			default:

				$current_context = $this->get_structure_context($permissions);

				// append additional info
				$current_context->features = new stdClass();
					$current_context->features->allowed_extensions		= $this->get_allowed_extensions();
					$current_context->features->default_target_quality	= $this->get_original_quality();
					$current_context->features->ar_quality				= $this->get_ar_quality(); // defined in config
					$current_context->features->default_quality			= $this->get_default_quality();
					$current_context->features->quality					= $this->get_quality(); // current instance quality
					$current_context->features->key_dir					= '3d';
					$current_context->features->alternative_extensions	= $this->get_alternative_extensions();
					$current_context->features->extension				= $this->get_extension();

				$context[] = $current_context;

				break;
		}
	}//end if($options->get_context===true)



// data
	$data = [];

	if($options->get_data===true && $permissions>0) {

		// value
			switch ($mode) {
				case 'list':
				case 'tm':
					$value = $this->get_list_value();
					break;

				case 'edit':
				default:
					$value = $this->get_dato();
					break;
			}

		// item
			$item = $this->get_data_item($value);

			// posterframe_url
				$item->posterframe_url = empty($value)
					? null
					: $this->get_posterframe_url();

		// player mode case. Send the media header when the component are working as player
			if($mode==='edit') {

				// media info
					$item->media_info = $this->get_media_streams();
			}

		$data[] = $item;  // append to the end of the array
	}//end if($options->get_data===true && $permissions>0)



// JSON string
	return common::build_element_json_output($context, $data);
