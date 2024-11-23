<?php
// JSON data component controller



// component configuration vars
	$permissions	= $this->get_component_permissions();
	$mode			= $this->get_mode();
	$properties		= $this->get_properties();



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
					$current_context->features->key_dir					= 'image_'.$this->tipo.'_'.$this->section_tipo;
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
					// value. list_value is a reduced files list info
					$value = $this->get_list_value();

					// data item
					$item = $this->get_data_item($value);

					// external source (link to image outside Dédalo media)
					$item->external_source = $this->get_external_source();
					break;

				case 'edit':
				default:
					// value. full files list info
					$value = $this->get_dato();

					// data item
					$item = $this->get_data_item($value);

					// external source (link to image outside Dédalo media)
					$item->external_source = $this->get_external_source();

					// base_svg_url
					$item->base_svg_url = $this->get_base_svg_url(true);
					break;
			}


		$data[] = $item;
	}//end if($options->get_data===true && $permissions>0)



// JSON string
	return common::build_element_json_output($context, $data);
