<?php


/**
* CLASS LAYOUT_MAP
*/
class layout_map {



	/**
	* GET_LAYOUT_MAP
	* Calculate display items to generate portal html
	* Cases:
	*	1. Mode 'list' : Uses children to build layout map
	* 	2. Mode 'edit' : Uses related terms to build layout map (default)
	* @return array $layout_map
	*/
		// public static function get_layout_map($request_options, $request_config) { // $section_tipo, $tipo, $mode, $user_id, $view='full'

		// 	// debug
		// 		// $bt = debug_backtrace();
		// 		// 	dump($bt[0], ' bt[0] ++ '.to_string($request_options->tipo));
		// 		// 	dump($bt[1], ' bt[1] ++ '.to_string($request_options->tipo));

		// 	$options = new stdClass();
		// 		$options->section_tipo			= null;
		// 		$options->tipo					= null;
		// 		$options->mode					= null;
		// 		$options->user_id				= navigator::get_user_id();
		// 		$options->view					= 'full';
		// 		$options->request_config_type	= 'show';
		// 		$options->lang					= null;
		// 		$options->add_section			= false;
		// 		$options->external				= false;
		// 		foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}

		// 	// cache
		// 		static $resolved_layout_map = [];
		// 		$resolved_key = $options->section_tipo .'_'. $options->tipo .'_'. $options->mode .'_'. $options->user_id .'_'. $options->view .'_'. $options->request_config_type.'_'. $options->lang.'_'. (int)$options->add_section.'_'. (int)$options->external;
		// 		// $resolved_key = $options->section_tipo .'_'. $options->tipo .'_'. $options->request_config_type .'_'. $options->user_id ;
		// 		if (isset($resolved_layout_map[$resolved_key])) {
		// 		// if (isset($_SESSION['dedalo']['resolved_layout_map'][$resolved_key])) {

		// 			debug_log(__METHOD__." Returned resolved layout_map with key: ".to_string($resolved_key), logger::DEBUG);
		// 			// dump($resolved_layout_map[$resolved_key], ' var ++ '.to_string($resolved_key)); //die();
		// 			// $bt = debug_backtrace();
		// 			// dump($bt, ' bt ++ '.to_string()); die();
		// 			$layout_map = $resolved_layout_map[$resolved_key];
		// 			// $layout_map = $_SESSION['dedalo']['resolved_layout_map'][$resolved_key];

		// 			return $layout_map;
		// 		}

		// 	// madatory
		// 		$ar_mandatory = ['section_tipo','tipo','mode'];
		// 		foreach ($ar_mandatory as $current_property) {
		// 			if (empty($options->{$current_property})) {
		// 				debug_log(__METHOD__." Error. property $current_property is mandatory for $options->tipo ". RecordObj_dd::get_termino_by_tipo($options->tipo,null,true) ." !".to_string(), logger::ERROR);
		// 				// dump($options, ' get_layout_map options ++ '.to_string());
		// 				return [];
		// 			}
		// 		}

		// 	// short vars
		// 		$section_tipo			= $options->section_tipo;
		// 		$tipo					= $options->tipo;
		// 		$model					= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
		// 		$mode					= $options->mode;
		// 		$user_id				= $options->user_id;
		// 		$view					= $options->view;
		// 		$lang					= $options->lang ?? DEDALO_DATA_LANG;
		// 		$request_config_type	= $options->request_config_type;
		// 		$parent					= $tipo;
		// 		$ddo_key 				= $section_tipo.'_'.$tipo.'_'.$mode;

		// 		// if($_SESSION['dedalo']['config']['ddo'][$section_tipo][$ddo_key]){
		// 		// 	return  $_SESSION['dedalo']['config']['ddo'][$section_tipo][$ddo_key]
		// 		// }


		// 	// properties
		// 		// $RecordObj_dd	= new RecordObj_dd($tipo);
		// 		// $properties		= $RecordObj_dd->get_properties();

		// 	#dump(dd_core_api::$ar_dd_objects, '+++++++++++++++++++ dd_core_api::$ar_dd_objects ++ '."[$section_tipo-$tipo]".to_string());

		// 	// OLD
		// 		// 1. dd_core_api::$ar_dd_objects
		// 			// if (isset(dd_core_api::$ar_dd_objects)) {
		// 			if($_SESSION['dedalo']['config']['ddo'][$section_tipo]){
		// 				# dump(dd_core_api::$ar_dd_objects, '+++++++++++++++++++ dd_core_api::$ar_dd_objects ++ '.to_string($tipo));

		// 				$self_ar_dd_objects = [];
		// 				foreach ($_SESSION['dedalo']['config']['ddo'][$section_tipo] as $key => $curernt_ddo) {
		// 					# code...
		// 				}


		// 				// check found dd_objects of current portal
		// 				$self_ar_dd_objects = array_filter(dd_core_api::$ar_dd_objects, function($item) use($tipo, $section_tipo, $model){
		// 					if($item->tipo===$tipo) return false;

		// 					if ($model==='section') {
		// 						if($item->section_tipo===$section_tipo) return $item;
		// 					}else{
		// 						// dump($item->ar_sections_tipo, '  ++ $item->ar_sections_tipo - '.to_string($section_tipo));
		// 						// dump($item, ' $item ++ '.to_string($tipo));
		// 						if($item->parent===$tipo) {
		// 							return $item;
		// 						}
		// 						// else if(isset($item->ar_sections_tipo) && in_array($item->parent, $item->ar_sections_tipo)) {
		// 						// 	// $item->section_tipo = $item->parent;
		// 						// 	return $item;
		// 						// }
		// 					}
		// 				});
		// 				#if($tipo==='test175') dump($self_ar_dd_objects, ' self_ar_dd_objects ++ '.to_string($tipo));
		// 				if (!empty($self_ar_dd_objects)) {

		// 					// groupers with childrens already defined case
		// 						if (in_array($model, common::$groupers)) {
		// 							return []; // stop here (!)
		// 						}

		// 					// layout_map
		// 						$layout_map = array_values($self_ar_dd_objects);
		// 						#$a = debug_backtrace(); error_log( print_r($a,true) );
		// 						debug_log(__METHOD__." layout map selected from 'dd_core_api::ar_dd_objects' [$section_tipo-$tipo] key:".to_string($resolved_key), logger::DEBUG);
		// 						#dump($layout_map, ' layout_map 1 ++ '.to_string($tipo));
		// 						if(SHOW_DEBUG===true) {
		// 							foreach ($layout_map as $current_item) {
		// 								$current_item->debug_from = 'calculated from dd_core_api::$ar_dd_objects ['.$tipo.'] (1)';
		// 							}
		// 						}
		// 				}
		// 			}
		// 		//
		// 		// // 2. search in user presets
		// 		// 	if (!isset($layout_map)) {
		// 		// 		$user_preset = layout_map::search_user_preset_layout_map($tipo, $section_tipo, $user_id, $mode, $view);
		// 		// 		if (!empty($user_preset)) {
		// 		// 			// layout_map
		// 		// 				// $layout_map = $user_preset;
		// 		// 				$layout_map = [];
		// 		// 				foreach ($user_preset as $preset_item) {
		// 		// 					if ($preset_item->typo==='ddo') {
		// 		// 						$layout_map[] = $preset_item;
		// 		// 					}
		// 		// 				}
		// 		// 				debug_log(__METHOD__." layout map calculated from user preset [$section_tipo-$tipo] key:".to_string($resolved_key), logger::DEBUG);
		// 		// 				//dump($layout_map, ' layout_map 2 ++ '.to_string($tipo));
		// 		// 				if(SHOW_DEBUG===true) {
		// 		// 					foreach ($layout_map as $current_item) {
		// 		// 						$current_item->debug_from = 'calculated from user_preset ['.$tipo.'] (2)';
		// 		// 					}
		// 		// 				}
		// 		// 		}
		// 		// 	}

		// 	// 3. calculate from section list or related terms
		// 		if (!isset($layout_map)) {

		// 			// $request_config  = v5 definition (related terms) and v6 definition (properties)
		// 			// layout_map
		// 			$layout_map = [];
		// 			foreach ($request_config as $item_request_config) {

		// 				if (!is_object($item_request_config)) {
		// 					dump($item_request_config, ' item_request_config ++ '.to_string($tipo));
		// 					dump($request_config, ' request_config ++ '.to_string($tipo));
		// 					continue;
		// 				}

		// 				if($item_request_config->typo !== 'rqo') continue;

		// 				foreach ($item_request_config->section_tipo as $current_section_tipo) {
		// 					if ($options->add_section===true) {
		// 						$layout_map[] = layout_map::get_section_ddo($current_section_tipo, $mode, $lang);
		// 					}

		// 					// ddo_map
		// 						$current_ddo_map = $item_request_config->{$request_config_type}->ddo_map ?? false;
		// 						if ($current_ddo_map!==false) {
		// 							foreach ((array)$current_ddo_map as $item) {
		// 								// $db = debug_backtrace();	dump($db, ' $db ++ '.to_string());
		// 								$current_mode = isset($item->mode) ? $item->mode : $mode;

		// 								// if (isset($item->tipo) && is_string($item->tipo)) {
		// 								// 	$current_model = RecordObj_dd::get_modelo_name_by_tipo($item,true);
		// 								// 	if (in_array($current_model, common::$ar_temp_exclude_models)) {
		// 								// 		// Skip
		// 								// 		continue;
		// 								// 	}
		// 								// }

		// 								if(isset($item->tipo)){
		// 									$item = $item->tipo;
		// 								}
		// 								// dump($item, ' $item +///////-------///////////+ '.to_string());

		// 								$ar_ddo = is_string($item)
		// 									? [layout_map::get_component_ddo($request_config_type, $current_section_tipo, $item, $current_mode, $lang, $parent)]
		// 									: layout_map::get_f_path_ddo($item, $request_config_type, $current_section_tipo, $current_mode, $lang, $parent);

		// 								$layout_map = array_merge($layout_map, $ar_ddo);
		// 							}
		// 						}else{
		// 							debug_log(__METHOD__." Ignored not existing ddo_map for config_type: '$request_config_type' ".to_string(), logger::WARNING);
		// 							if ($request_config_type!=='select') {
		// 								dump($item_request_config, ' ERROR !!!!!!!!!!!!!!!!!! item_request_config ++ '.to_string($request_config_type));
		// 							}
		// 						}
		// 				}// end iterate sections
		// 			}//end foreach ($request_config as $item_request_config)

		// 			// dump($layout_map, ' $layout_map ++ '.to_string()); die();

		// 			if(SHOW_DEBUG===true) {
		// 				// dump($layout_map, ' layout_map ++ '.to_string());
		// 				foreach ($layout_map as $current_item) {
		// 					if (!isset($current_item->tipo)) {
		// 						// dump($current_item, ' current_item ++ '.to_string());
		// 						continue;
		// 					}
		// 					$current_item->debug_label = RecordObj_dd::get_termino_by_tipo($current_item->tipo, $lang, true, true);
		// 					$current_item->debug_from = 'calculated from section list or related terms ['.$tipo.'] (3)';
		// 				}
		// 			}
		// 		}//end if (!isset($layout_map))


		// 	// Remove_exclude_terms : config excludes. If instalation config value DEDALO_AR_EXCLUDE_COMPONENTS is defined, remove elements from layout_map
		// 		if (defined('DEDALO_AR_EXCLUDE_COMPONENTS') && !empty($layout_map)) {
		// 			$DEDALO_AR_EXCLUDE_COMPONENTS = DEDALO_AR_EXCLUDE_COMPONENTS;
		// 			foreach ($layout_map as $key => $item) {
		// 				// if (empty($item)) {
		// 				// 	debug_log(__METHOD__." Skipped empty item ".to_string(), logger::DEBUG);
		// 				// 	continue;
		// 				// }
		// 				$current_tipo = $item->tipo;
		// 				if (in_array($current_tipo, $DEDALO_AR_EXCLUDE_COMPONENTS)) {
		// 					unset( $layout_map[$key]);
		// 					debug_log(__METHOD__." DEDALO_AR_EXCLUDE_COMPONENTS: Removed portal layout_map term $current_tipo ".to_string(), logger::DEBUG);
		// 				}
		// 			}
		// 			$layout_map = array_values($layout_map);
		// 		}
		// 		// dump($layout_map, ' layout_map ++++++++++++ $resolved_key: '.to_string($resolved_key));

		// 	// cache
		// 		$resolved_layout_map[$resolved_key] = $layout_map;
		// 		// $_SESSION['dedalo']['resolved_layout_map'][$resolved_key] = $layout_map;

		// 	// dump(null, 'Time to get_layout_map '.$model.' '.$tipo.' - time: '.exec_time_unit($start_time,'ms')." ms".to_string());

		// 	return (array)$layout_map;
		// }//end get_layout_map



	/**
	* GET_SECTION_DDO
	* @return object $dd_object
	*/
	public static function get_section_ddo(string $section_tipo, string $mode, string $lang) : object {

		// section add
		$dd_object = new dd_object((object)[
			'label'			=> RecordObj_dd::get_termino_by_tipo($section_tipo, $lang, true, true),
			'tipo'			=> $section_tipo,
			'section_tipo'	=> $section_tipo,
			'model'			=> 'section',
			'mode'			=> $mode,
			'lang'			=> DEDALO_DATA_NOLAN,
			'parent'		=> 'root',
			'config_type'	=> 'show'
		]);

		return $dd_object;
	}//end get_section_ddo



	/**
	* GET_COMPONENT_DDO
	* @return object $dd_object
	*/
	public static function get_component_ddo($request_config_type, $current_section_tipo, $current_tipo, $mode, $lang, $parent) {

		// parent
			$current_parent = ($request_config_type==='select')
				? $current_section_tipo
				: $parent;
				// : (function($current_tipo){
				// 	$RecordObj_dd 	= new RecordObj_dd($current_tipo);
				// 	return $RecordObj_dd->get_parent();
				// })($current_tipo);

		// model
			$current_model = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);


		// common temporal excluded/mapped models *******-
			// $match_key = array_search($current_model, common::$ar_temp_map_models);
			$mapped_model = isset(common::$ar_temp_map_models[$current_model])
				? common::$ar_temp_map_models[$current_model]
				: false;
			if (false!==$mapped_model) {
				debug_log(__METHOD__." +++ Mapped model $current_model to $mapped_model from layout map ".to_string(), logger::WARNING);
				$current_model = $mapped_model;
			}else if (in_array($current_model, common::$ar_temp_exclude_models)) {
				debug_log(__METHOD__." +++ Excluded model $current_model from layout map ".to_string(), logger::WARNING);
				return false;
			}

		$component_lang = common::get_element_lang($current_tipo, $lang);

		// component add
			$dd_object = new dd_object((object)[
				'label'			=> RecordObj_dd::get_termino_by_tipo($current_tipo, $lang, true, true),
				'tipo'			=> $current_tipo,
				'section_tipo'	=> $current_section_tipo,
				'model'			=> $current_model,
				'mode'			=> $mode,
				'lang'			=> $component_lang,
				'parent'		=> $current_parent,
				'config_type'	=> $request_config_type
			]);

			if($current_model === 'component_external'){
				$RecordObj_dd = new RecordObj_dd($current_tipo);
				$dd_object->properties = $RecordObj_dd->get_properties();
			}

			return $dd_object;
	}//end get_component_ddo



	/**
	* GET_F_PATH_DDO
	* @return array $ar_dd_object
	*/
	public static function get_f_path_ddo(object $f_path_object, $request_config_type, object $current_section_tipo, object $mode, object $lang, $parent) : array {

		$f_path = $f_path_object->f_path;

		$ar_dd_object = [];
		foreach ($f_path as $key => $value) {
			if($key % 2 === 0){
				if($value === 'self') continue;
				$ar_dd_object[] = layout_map::get_section_ddo($value, $mode, $lang);
			}else{
				$section_tipo = ($f_path[$key-1] === 'self')
					? $section_tipo = $current_section_tipo
					: $f_path[$key-1];
				$component_ddo = layout_map::get_component_ddo($request_config_type, $section_tipo, $value, $mode, $lang, $parent);
				if (!empty($component_ddo)) {
					$ar_dd_object[] = $component_ddo;
				}
			}
		}

		return $ar_dd_object;
	}//end get_fpath_ddo



	/**
	* SEARCH_USER_PRESET_LAYOUT_MAP
	* Get user layout map preset
	* @return array $result
	*/
	public static function search_user_preset_layout_map(string $tipo, string $section_tipo, int $user_id, string $mode, string $view=null) : array {

		// cache
			$key_cache = implode('_', [$tipo, $section_tipo, $user_id, $mode, $view]);
			if (isset($_SESSION['dedalo']['config']['user_preset_layout_map'][$key_cache])) {
				return $_SESSION['dedalo']['config']['user_preset_layout_map'][$key_cache];
			}

		// preset const
			$user_locator = new locator();
				$user_locator->set_section_tipo('dd128');
				$user_locator->set_section_id($user_id);
				$user_locator->set_from_component_tipo('dd654');

		// preset section vars
			$preset_section_tipo = 'dd1244';
			$component_json_tipo = 'dd625';

		// filter
			$filter = 	[
				(object)[
					'q'		=> '\''.$tipo.'\'',
					'path'	=> [(object)[
						'section_tipo'		=> $preset_section_tipo,
						'component_tipo'	=> 'dd1242',
						'model'			=> 'component_input_text',
						'name'				=> 'Tipo'
					]]
				],
				(object)[
					'q'		=> '\''.$section_tipo.'\'',
					'path'	=> [(object)[
						'section_tipo'		=> $preset_section_tipo,
						'component_tipo'	=> 'dd642',
						'model'			=> 'component_input_text',
						'name'				=> 'Section tipo'
					]]
				],
				// (object)[
				// 	'q'		=> $user_locator,
				// 	'path'	=> [(object)[
				// 		'section_tipo'		=> $preset_section_tipo,
				// 		'component_tipo'	=> 'dd654',
				// 		'model'				=> 'component_select',
				// 		'name'				=> 'User'
				// 	]]
				// ],
				(object)[
					'q'		=> '\''.$mode.'\'',
					'path'	=> [(object)[
						'section_tipo'		=> $preset_section_tipo,
						'component_tipo'	=> 'dd1246',
						'model'			=> 'component_input_text',
						'name'				=> 'Mode'
					]]
				]
			];

			// add filter view if exists
			if (!empty($view)) {
				$filter[] = (object)[
					'q'		=> '\''.$view.'\'',
					'path'	=> [
						(object)[
							'section_tipo'		=> $preset_section_tipo,
							'component_tipo'	=> 'dd1247',
							'model'			=> 'component_input_text',
							'name'				=> 'view'
						]
					]
				];
			}

		// search query object
			$search_query_object = (object)[
				'id'			=> 'search_user_preset_layout_map',
				'mode'			=> 'list',
				'section_tipo'	=> 'dd1244',
				'limit'			=> 1,
				'full_count'	=> false,
				'filter'		=> (object)[
					'$and' => $filter
				]//,
				// 'select' 		=> [
				// 	(object)[
				// 		'path' 	=> [
				// 			(object)[
				// 				'section_tipo' 	=> $preset_section_tipo,
				// 				'component_tipo'=> $component_json_tipo,
				// 				'model' 		=> 'component_json',
				// 				'name'			=> 'JSON Data'
				// 			]
				// 		],
				// 		'component_path' => [
				// 	        'components',
				// 	        $component_json_tipo,
				// 	        'dato',
				// 	        'lg-nolan'
				// 	    ]
				// 	]
				// ]

			];
			#dump($search_query_object, ' search_query_object ++ '.to_string());
			#error_log('Preset layout_map search: '.PHP_EOL.json_encode($search_query_object));

		$search		= search::get_instance($search_query_object);
		$rows_data	= $search->search();

		$ar_records = $rows_data->ar_records;
		if (empty($ar_records)) {

			$result = [];

		}else{
			$dato = reset($ar_records);
			if (isset($dato->datos->components->{$component_json_tipo}->dato->{DEDALO_DATA_NOLAN})) {

				$json_data		= reset($dato->datos->components->{$component_json_tipo}->dato->{DEDALO_DATA_NOLAN});
				$preset_value	= is_array($json_data) ? $json_data : [$json_data];

				// check proper config of items
					// $valid_items = [];
					// foreach ($preset_value as $key => $item) {
					//
					// 	// typo
					// 		if (!isset($item->typo) || $item->typo!=='ddo') {
					// 			debug_log(__METHOD__." Ignored invalid user preset typo ! ".to_string($item), logger::DEBUG);
					// 			continue;
					// 		}
					//
					// 	// tipo
					// 		if (!isset($item->tipo)) {
					// 			debug_log(__METHOD__." Invalid user preset item ! ".to_string($item), logger::ERROR);
					// 			continue;
					// 		}
					//
					// 	// label
					// 		if (!property_exists($item, 'label')) {
					// 			$item->label = RecordObj_dd::get_termino_by_tipo($item->tipo, DEDALO_DATA_LANG, true, true);
					// 		}
					//
					// 	$valid_items[] = $item;
					// }
					//
					// $result = $valid_items;

				$result = $preset_value;

			}else{

				$result = [];
			}
		}

		// cache
			$_SESSION['dedalo']['config']['user_preset_layout_map'][$key_cache] = $result;


		return $result;
	}//end search_user_preset_layout_map



}//end class layout_map
