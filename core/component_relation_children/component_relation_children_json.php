<?php
// JSON data component controller



// component configuration vars
	$permissions	= $this->get_component_permissions();
	$modo			= $this->get_modo();
	$section_tipo	= $this->section_tipo;
	$lang			= $this->lang;
	$tipo			= $this->get_tipo();
	$properties		= $this->get_properties() ?? new stdClass();



// context

	// if($options->get_context===true && $permissions>0){
	// 	switch ($options->context_type) {
	// 		case 'simple':
	// 			// Component structure context_simple (tipo, relations, properties, etc.)
	// 			$context[] = $this->get_structure_context_simple($permissions, $add_rqo=true);
	// 			break;

	// 		default:
	// 			// Component structure context (tipo, relations, properties, etc.)
	// 				$current_context = $this->get_structure_context($permissions, $add_rqo=true);

	// 				$context[] = $current_context;

	// 			// subcontext from element layout_map items (from_parent, parent_grouper)
	// 				$ar_subcontext = $this->get_ar_subcontext($tipo, $tipo);
	// 				foreach ($ar_subcontext as $current_context) {
	// 					$context[] = $current_context;
	// 				}
	// 			break;
	// 	}
	// }//end if($options->get_context===true)



// data
	$context	= [];
	$data		= [];

	// Component structure context (tipo, relations, properties, etc.)
		$this->context = $this->get_structure_context(
			$permissions,
			true // bool add_rqo
		);
		$context[] = $this->context;

	if($permissions>0) {

		$dato = $this->get_dato();

		if (!empty($dato)) {

			$value		= $this->get_dato_paginated();
			$section_id	= $this->get_parent();
			$limit		= $this->pagination->limit;
			$offset		= $this->pagination->offset;

			// data item
				$item = $this->get_data_item($value);
					$item->parent_tipo 			= $tipo;
					$item->parent_section_id 	= $section_id;
					// fix pagination vars
						$pagination = new stdClass();
							$pagination->total	= count($dato);
							$pagination->limit 	= $limit;
							$pagination->offset = $offset;
					$item->pagination = $pagination;

				$data[] = $item;

			// subcontext data from layout_map items
				// $ar_subdata = $this->get_ar_subdata($value);

			// subdatum
				$subdatum = $this->get_subdatum($tipo, $value);

				// add subcontext
				$ar_subcontext	= $subdatum->context;
				foreach ($ar_subcontext as $current_context) {
					$context[] = $current_context;
				}

				// add subdata
				$ar_subdata = $subdatum->data;
				if ($modo==='list') {
					foreach ($ar_subdata as $current_data) {
						$current_data->parent_tipo			= $tipo;
						$current_data->parent_section_id	= $section_id;

						$data[] = $current_data;
					}
				}else{
					foreach ($ar_subdata as $current_data) {
						$data[] = $current_data;
					}
				}
		}//end if (!empty($dato))
	}//end if $options->get_data===true && $permissions>0



// JSON string
	return common::build_element_json_output($context, $data);
