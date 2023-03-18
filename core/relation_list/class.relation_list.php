<?php
/*
* CLASS RELATION_LIST
* Manage the relations of the sections
* build the list of the relations between sections
*/
class relation_list extends common {



	/**
	* CLASS VARS
	*/
		protected $section_id;
		protected $section_tipo;
		protected $sqo;
		protected $count;



	/**
	* CONSTRUCT
	*
	*/
	public function __construct(string $tipo, $section_id, string $section_tipo, string $mode='list') {

		$this->tipo			= $tipo;
		$this->section_id	= $section_id;
		$this->section_tipo	= $section_tipo;
		$this->mode			= $mode;
	}//end __construct



	/**
	* GET_INVERSE_REFERENCES
	* Get calculated inverse locators for all matrix tables
	* @see search::calculate_inverse_locator
	* @return array $inverse_locators
	*/
	public function get_inverse_references(object $sqo) : array {
		
		// sections
			$sections			= sections::get_instance(null, $sqo, $this->section_tipo, $this->mode);
			$inverse_sections	= $sections->get_dato();

		return $inverse_sections;
	}//end get_inverse_references



	/**
	* GET_RELATION_LIST_OBJ
	* @return object $json
	*/
	public function get_relation_list_obj(array $ar_inverse_references) : object {

		$json		= new stdClass;
		$ar_context	= [];
		$ar_data	= [];

		$sections_related		= [];
		$ar_relation_components	= [];
		# loop the locators that call to the section
		foreach ((array)$ar_inverse_references as $current_record) {

			$current_section_tipo = $current_record->section_tipo;

			# 1 get the @context
			if (!in_array($current_section_tipo, $sections_related )){

				$sections_related[] =$current_section_tipo;

				//get the id
				$current_id = new stdClass;
					$current_id->section_tipo 		= $current_section_tipo;
					$current_id->section_label 		= RecordObj_dd::get_termino_by_tipo($current_section_tipo,DEDALO_APPLICATION_LANG, true);
					$current_id->component_tipo		= 'id';
					$current_id->component_label	= 'id';

					$ar_context[] = $current_id;

				//get the columns of the @context
				$ar_model_name_required = array('relation_list');
				$resolve_virtual 		 = false;

				// Locate relation_list element in current section (virtual ot not)
				$ar_children = section::get_ar_children_tipo_by_model_name_in_section($current_section_tipo, $ar_model_name_required, $from_cache=true, $resolve_virtual, $recursive=false, $search_exact=true);

				// If not found children, try resolving real section
				if (empty($ar_children)) {
					$resolve_virtual = true;
					$ar_children = section::get_ar_children_tipo_by_model_name_in_section($current_section_tipo, $ar_model_name_required, $from_cache=true, $resolve_virtual, $recursive=false, $search_exact=true);
				}// end if (empty($ar_children))


				if( isset($ar_children[0]) ) {
					$current_children 		= reset($ar_children);
					$recordObjdd 			= new RecordObj_dd($current_children);
					$ar_relation_components[$current_section_tipo] = $recordObjdd->get_relaciones();
					if(isset($ar_relation_components[$current_section_tipo])){
						foreach ($ar_relation_components[$current_section_tipo] as $current_relation_component) {
							foreach ($current_relation_component as $tipo) {

								$current_relation_list = new stdClass;
									$current_relation_list->section_tipo 	= $current_section_tipo;
									$current_relation_list->section_label 	= RecordObj_dd::get_termino_by_tipo($current_section_tipo,DEDALO_APPLICATION_LANG, true);
									$current_relation_list->component_tipo	= $tipo;
									$current_relation_list->component_label	= RecordObj_dd::get_termino_by_tipo($tipo, DEDALO_APPLICATION_LANG, true);

									$ar_context[] = $current_relation_list;
							}
						}
					}
				}

			}// end if (!in_array($current_section_tipo, $sections_related )

			# 2 get ar_data
			$ar_components = $ar_relation_components[$current_section_tipo] ?? [];
			if (empty($ar_components)) {
				debug_log(__METHOD__." Section without relation_list. Please, define relation_list for section: $current_section_tipo ".to_string(), logger::WARNING);
			}
			$ar_data_result = $this->get_ar_data($current_record, $ar_components);
			$ar_data 		= array_merge($ar_data, $ar_data_result);
		}// end foreach

		// $context = 'context';
		$json->context	= $ar_context;
		$json->data		= $ar_data;

		return $json;
	}//get_relation_list_obj



	/**
	* GET_AR_DATA
	* @param object $current_record
	* @param array $ar_components
	* @return array $data
	*/
	public function get_ar_data(object $current_record, array $ar_components) : array {

		$data = [];

		$section_tipo 	= $current_record->section_tipo;
		$section_id 	= $current_record->section_id;

		// section instance
			$section = section::get_instance($section_id, $section_tipo, $this->mode, $cache=true);
		// inject dato to section when the dato come from db and set as loaded
			$datos = $current_record->datos ?? null;
			if (!is_null($datos)) {
				$section->set_dato($datos);
			}

		$current_id = new stdClass;
			$current_id->section_tipo	= $section_tipo;
			$current_id->section_id		= $section_id;
			$current_id->component_tipo	= 'id';

		$data[] = $current_id;

		if(!empty($ar_components)){
			foreach ($ar_components as $current_relation_component) {
				foreach ($current_relation_component as $modelo => $tipo) {
					$model_name			= RecordObj_dd::get_modelo_name_by_tipo($modelo, true);
					$current_component	= component_common::get_instance(
						$model_name,
						$tipo,
						$section_id,
						'list',
						DEDALO_DATA_LANG,
						$section_tipo
					);
					$value = $current_component->get_valor();

					$component_object = new stdClass;
						$component_object->section_tipo		= $section_tipo;
						$component_object->section_id 		= $section_id;
						$component_object->component_tipo	= $tipo;
						$component_object->value 			= $value;

					$data[] = $component_object;
				}
			}
		}

		return $data;
	}//end get_data



	/**
	* GET_JSON
	* @param object $request_options
	* 	Optional. Default is false
	* @return object $json
	*	Object with data and context (configurable) like:
	* {
	* 	context : [...],
	* 	data : [...]
	* }
	*/
	public function get_json(object $request_options=null) : object {

		$path = DEDALO_CORE_PATH .'/'. get_called_class() .'/'. get_called_class() .'_json.php';

		// controller include
			$json = include( $path );

		return $json;
	}//end get_json



}//end class relation_list
