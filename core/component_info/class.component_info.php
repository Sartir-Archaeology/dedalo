<?php
include_once dirname(dirname(__FILE__)). '/widgets/widget_common/class.widget_common.php';
/*
* CLASS COMPONENT_INFO
*
*
*/
class component_info extends component_common {



	public $widget_lang;
	public $widget_mode;


	/**
	* GET_DATO
	* @return array|null $dato
	*/
	public function get_dato() {

		// the component info dato will be the all widgets data
		$dato = [];

		$properties = $this->get_properties();
		// get the widgets defined in the ontology
		$widgets = $properties->widgets ?? null;
		if (empty($widgets) || !is_array($widgets)) {
			debug_log(__METHOD__." Empty defined widgets for ".get_called_class()." : $this->label [$this->tipo] ".to_string($widgets), logger::ERROR);
			return null;
		}

		// every widget will be created and calculate your own data
		foreach ($widgets as $widget_obj) {

			$widget_options = new stdClass();
				$widget_options->section_tipo		= $this->get_section_tipo();
				$widget_options->section_id			= $this->get_section_id();
				$widget_options->lang				= DEDALO_DATA_LANG;
				// $widget_options->component_info	= $this;
				$widget_options->widget_name		= $widget_obj->widget_name;
				$widget_options->path				= $widget_obj->path;
				$widget_options->ipo				= $widget_obj->ipo;

			// instance the current widget
			$widget = widget_common::get_instance($widget_options);

			// Widget data
			$widget_value = $widget->get_dato();
			if (!empty($widget_value)) {
				$dato = array_merge($dato, $widget_value);
			}
		}//end foreach ($widgets as $widget)

		// set the component info dato with the result
		$this->dato = $dato;

		return $dato;
	}//end get_dato



	/**
	* GET_VALOR
	* @return string $valor
	*/
	public function get_valor( $widget_lang=DEDALO_DATA_LANG ) : string {

		$this->widget_lang = $widget_lang;

		$valor = $this->get_html();
		$valor = !empty($valor)
			? strip_tags($valor)
			: $valor;

		return $valor;
	}//end get_valor



	/**
	* GET_VALOR_EXPORT
	* Return component value sent to export data
	* @return string $valor
	*/
	public function get_valor_export($valor=null, $lang=DEDALO_DATA_LANG, $quotes=null, $add_id=null) {

		#if (empty($valor)) {

			#$this->set_mode('export');

			$this->widget_lang = $lang;
			$this->widget_mode = 'export';

			$valor = $this->get_html();
			#$valor = strip_tags($valor);
		#}

		return to_string($valor);
	}//end get_valor_export



	/**
	* GET_DATA_LIST
	* Get and fix the ontology defined widgets data_list
	* @return array $data_list
	*/
	public function get_data_list() : ?array {

		// the component info dato will be the all widgets data
		$data_list = [];

		$properties = $this->get_properties();
		// get the widgets defined in the ontology
		$widgets = $properties->widgets ?? null;
		if (empty($widgets) || !is_array($widgets)) {
			debug_log(__METHOD__." Empty or invalid defined widgets for ".get_called_class()." : $this->label [$this->tipo] ".to_string($widgets), logger::ERROR);
			return null;
		}
		// every widget will be created and calculate your own data
		foreach ($widgets as $widget_obj) {

			$widget_options = new stdClass();
				$widget_options->section_tipo		= $this->get_section_tipo();
				$widget_options->section_id			= $this->get_section_id();
				$widget_options->lang				= DEDALO_DATA_LANG;
				// $widget_options->component_info	= $this;
				$widget_options->widget_name		= $widget_obj->widget_name;
				$widget_options->path				= $widget_obj->path;
				$widget_options->ipo				= $widget_obj->ipo;

			// instance the current widget
			$widget = widget_common::get_instance($widget_options);

			// Widget data
			$widget_data_list = method_exists($widget, 'get_data_list') ? $widget->get_data_list() : null;

			if($widget_data_list !== null){
				$data_list = array_merge($data_list, $widget_data_list);
			}
		}//end foreach ($widgets as $widget_obj)

		// set the component info dato with the result
		$this->data_list = $data_list;

		return $data_list;
	}//end get_data_list



	/**
	* GET_TOOLS
	* Overrides common method to prevent loading of default tools
	* This component don't have tools
	* @return array
	*/
	public function get_tools() : array {

		return [];
	}//end get_tools



	/**
	* GET_SORTABLE
	* @return bool
	* 	Default is true. Override when component is sortable
	*/
	public function get_sortable() : bool {

		return false;
	}//end get_sortable



	/**
	* GET_LIST_VALUE
	* Unified value list output
	* By default, list value is equivalent to dato. Override in other cases.
	* Note that empty array or string are returned as null
	* A param '$options' is added only to allow future granular control of the output
	* @param object $options = null
	* 	Optional way to modify result. Avoid using it if it is not essential
	* @return array|null $list_value
	*/
	public function get_list_value(object $options=null) : ?array {

		$dato = $this->get_dato();
		if (empty($dato)) {
			return null;
		}

		$list_value = $dato;

		return $list_value;
	}//end get_list_value



}//end class component_info
