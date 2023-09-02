<?php
// JSON data component controller



// component configuration vars
	$permissions	= $this->get_component_permissions();
	$mode			= $this->get_mode();
	$properties		= $this->get_properties();



// context
	$context = [];

	if($options->get_context===true) { //  && $permissions>0

		$add_rqo = isset($properties->unique) ? true : false;
		switch ($options->context_type) {

			case 'simple':
				// Component structure context_simple (tipo, relations, properties, etc.)
				$context[] = $this->get_structure_context_simple(
					$permissions,
					$add_rqo
				);
				break;

			default:

				// Component structure context (tipo, relations, properties, etc.)
					$context[] = $this->get_structure_context(
						$permissions,
						$add_rqo
					);

				// add buttons
					$context = array_merge($context, $this->get_structure_buttons($permissions));
				break;
		}
	}//end if($options->get_context===true)



// data
	$data = [];

	if($options->get_data===true && $permissions>0) {

		$user_id = get_user_id();

		// value
			switch ($mode) {
				case 'list':
				case 'tm':
					$value		= $this->get_list_value();
					break;

				case 'edit':
				default:
					$value		= $this->get_dato();
					$datalist	= $this->get_datalist($user_id);
					break;
			}
		// data item
			$item = $this->get_data_item($value);
				$item->parent_tipo			= $this->get_tipo();
				$item->parent_section_id	= $this->get_section_id();

			// datalist
			if (isset($datalist)) {
				$item->datalist = $datalist;
			}

		$data[] = $item;
	}//end if($options->get_data===true && $permissions>0)



// JSON string
	return common::build_element_json_output($context, $data);
