<?php
/**
* CLASS DD_OBJECT (ddo)
* Defines object with normalized properties and checks
*
*/
class dd_object extends stdClass {



	// properties
		/*
		// typo					: "ddo"  (ddo | sqo)
		public $typo;
		// type					: "component"  (section | component | grouper | button | tool ..)
		public $type;
		// tipo					: 'oh14',
		public $tipo;
		// section_tipo			: 'oh1',
		public $section_tipo;
		// parent				: 'oh2', // caller section / portal  tipo
		public $parent;
		// parent_grouper		: 'oh7', // structure parent
		public $parent_grouper;
		// lang					: 'lg-eng',
		public $lang;
		// mode					: "list",
		public $mode;
		// model				: 'component_input_text',
		public $model;
		// properties			: {}
		public $properties;
		// permissions			: 1
		public $permissions;
		// label				: 'Title'
		public $label;
		// labels				: ['Title']
		public $labels;
		// translatable			: true
		public $translatable;
		// tools				: [] // array of tools dd_objects (context)
		public $tools;
		// buttons				: [] // array of buttons dd_objects (context)
		public $buttons;
		// css					: {}
		public $css;
		// target_sections		: [{'tipo':'dd125','label':'Projects']
		public $target_sections;
		// request_config		: [],
		public $request_config;
		// columns_map			: array
		public $columns_map;
		// view					: string|null like 'table'
		public $view;
		// children_view		: string like "text"
		public $children_view;
		// section_id			: int like 1 // Used by tools
		public $section_id;
		// name					: string like 'tool_lang' // Used by tools
		public $name;
		// description			: string like 'Description of tool x' // Used by tools
		public $description;
		// icon					: string like '/tools/tool_lang/img/icon.svg' // Used by tools
		public $icon;
		// show_in_inspector	: bool // Used by tools
		public $show_in_inspector;
		// show_in_component	: bool // Used by tools
		public $show_in_component;
		// config				: object // Used by tools
		public $config;
		// sortable				: bool // Used by components (columns)
		public $sortable;
		// fields_separator		: string like ", " // used by portal to join different fields
		public $fields_separator;
		// records_separator	: string like " | " // used by portal to join different records (rows)
		public $records_separator;
		// legacy_model			: string like "component_autocomplet_hi"
		public $legacy_model;
		// relation_list		: string
		public $relation_list;
		// path					: array
		public $path;
		// debug				: object
		public $debug;
		// add_label : bool
		public $add_label;
		// string|null time_machine_list . Get the time machine list tipo for the section
		public $time_machine_list;
		// object features. Use this container to add custom properties like 'notes_publication_tipo' in text area
		public $features;
		// array toolbar_buttons
		public $toolbar_buttons;
		// bool value_with_parents
		public $value_with_parents;
		// object tool_config
		public $tool_config;
		// array search_operators_info
		public $search_operators_info;
		// string search_options_title
		public $search_options_title;
		// string target_section_tipo
		public $target_section_tipo;
		*/


		// ar_type_allowed
		static $ar_type_allowed = [
			'section',
			'component',
			'grouper',
			'button',
			'area',
			'tm',
			'widget',
			'install',
			'login',
			'menu',
			'tool',
			'detail', // used by time_machine_list, relation_list, component_history_list
			'dd_grid'
		];



	/**
	* __CONSTRUCT
	* @param object $data =null
	*/
	public function __construct( object $data=null ) {

		if (is_null($data)) return;

		# Nothing to do on construct (for now)
		if (!is_object($data)) {
			trigger_error("wrong data format. Object expected. Given: ".gettype($data));
			return;
		}

		// set typo always
			$this->typo = 'ddo';


		// set model in first time
			if(isset($data->model)) {
				$this->set_model($data->model);
			}

		// set all properties
			foreach ($data as $key => $value) {
				$method = 'set_'.$key;
				$this->{$method}($value);
			}

		// resolve type
			$model = $this->model;
			if (strpos($model, 'component_')===0) {
				$type = 'component';
			}elseif ($model==='section') {
				$type = 'section';
			}elseif (in_array($model, section::get_ar_grouper_models())) {
				$type = 'grouper';
			}elseif (strpos($model, 'button')===0) {
				$type = 'button';
			}elseif (strpos($model, 'area')===0) {
				$type = 'area';
			}elseif ($model==='login') {
				$type = 'login';
			}elseif ($model==='menu') {
				$type = 'menu';
			}elseif ($model==='install') {
				$type = 'install';
			}elseif ($model==='dd_grid') {
				$type = 'dd_grid';
			}elseif (strpos($model, 'tool_')===0) {
				$type = 'tool';
			}else{
				$msg = __METHOD__." UNDEFINED model: $model - ".$this->tipo;
				debug_log($msg, logger::ERROR);
				trigger_error($msg);
				return;
			}
			$this->set_type($type);
	}//end __construct



	/**
	* SET  METHODS
	* Verify values and set property to current object
	*/


	/**
	* SET_TYPE
	* Only allow 'section','component','grouper','button'
	* @param string $value
	* @return void
	*/
	public function set_type(string $value) : void  {
		$ar_type_allowed = self::$ar_type_allowed;
		if( !in_array($value, $ar_type_allowed) ) {
			// throw new Exception("Error Processing Request. Invalid locator type: $value. Only are allowed: ".to_string($ar_type_allowed), 1);
			debug_log(__METHOD__
				." Invalid locator: " .PHP_EOL
				. to_string($value) .PHP_EOL
				. ' Only are allowed' .PHP_EOL
				. to_string($ar_type_allowed)
				, logger::ERROR
			);
		}
		$this->type = $value;
	}//end set_type



	/**
	* SET_TIPO
	* @param string $value
	* @return void
	*/
	public function set_tipo(string $value) : void  {
		if(!RecordObj_dd::get_prefix_from_tipo($value)) {
			// throw new Exception("Error Processing Request. Invalid tipo: $value", 1);
			debug_log(__METHOD__
				." Invalid tipo: " .PHP_EOL
				. to_string($value)
				, logger::ERROR
			);
		}
		$this->tipo = $value;
	}//end set_tipo



	/**
	* SET_SECTION_TIPO
	* @param string|array $value
	* 	Could be array or string
	* @return void
	*/
	public function set_section_tipo($value) : void  { // string|array
		if (!isset($this->model) && isset($this->tipo)) {
			$this->model = RecordObj_dd::get_modelo_name_by_tipo($this->tipo,true);
		}
		// if(strpos($this->model, 'area')!==0 && !RecordObj_dd::get_prefix_from_tipo($value)) {
		// 	throw new Exception("Error Processing Request. Invalid section_tipo: $value", 1);
		// }
		$this->section_tipo = $value;
	}//end set_section_tipo



	/**
	* SET_PARENT
	* @param string $value
	* @return void
	*/
	public function set_parent(?string $value) : void {
		if(empty($value) || !RecordObj_dd::get_prefix_from_tipo($value)) {
			// throw new Exception("Error Processing Request. Invalid parent: $value", 1);
			debug_log(__METHOD__
				." Error Processing Request. Invalid parent: "
				.to_string($value)
				, logger::ERROR
			);
		}
		$this->parent = $value;
	}//end set_parent



	/**
	* SET_PARENT_GROUPER
	* @param string $value
	* @return void
	*/
	public function set_parent_grouper(?string $value) : void {

		if(empty($value) || !RecordObj_dd::get_prefix_from_tipo($value)) {
			debug_log(__METHOD__
				." Error Processing Request. Invalid parent_grouper: "
				.to_string($value)
				, logger::ERROR
			);
		}
		$this->parent_grouper = $value;
	}//end set_parent_grouper



	/**
	* SET_LANG
	* @param string $value
	* @return void
	*/
	public function set_lang(string $value) : void {
		if(strpos($value, 'lg-')!==0) {
			// throw new Exception("Error Processing Request. Invalid lang: $value", 1);
			debug_log(__METHOD__
				." Error Processing Request. Invalid lang: "
				.to_string($value)
				, logger::ERROR
			);
		}
		$this->lang = $value;
	}//end set_lang



	/**
	* SET_MODE
	* @param string $value
	* @return void
	*/
	public function set_mode(string $value) : void {

		$this->mode = $value;
	}//end set_mode



	/**
	* SET_MODEL
	* @param string $value
	* @return void
	*/
	public function set_model(string $value) : void {

		$this->model = $value;
	}//end set_model



	/**
	* SET_LEGACY_MODEL
	* @param string|null $value
	* @return void
	*/
	public function set_legacy_model(?string $value) : void {

		$this->legacy_model = $value;
	}//end set_legacy_model



	/**
	* SET_PROPERTIES
	* Note hint parameter 'object' is not supported bellow php 7.2
	* @see https://php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration
	* @param object|array|null $value
	* @return void
	*/
	public function set_properties($value) : void {

		$this->properties = $value;
	}//end set_properties



	/**
	* SET_PERMISSIONS
	* @param int $value
	* @return void
	*/
	public function set_permissions(int $value) : void {

		$this->permissions = $value;
	}//end set_permissions



	/**
	* SET_LABEL
	* @param string $value
	* @return void
	*/
	public function set_label(string $value) : void {

		$this->label = $value;
	}//end set_label



	/**
	* SET_LABELS
	* Used by tools
	* @param array|null $value
	* @return void
	*/
	public function set_labels(?array $value) : void {

		$this->labels = $value;
	}//end set_labels



	/**
	* SET_TRANSLATABLE
	* @param bool $value
	* @return void
	*/
	public function set_translatable(bool $value) : void {

		$this->translatable = $value;
	}//end set_translatable



	/**
	* SET_TOOLS
	* @param array|null $value
	* @return void
	*/
	public function set_tools(?array $value) : void {

		if(!is_null($value) && !is_array($value)){
			// throw new Exception("Error Processing Request, Tools only had allowed array or null values. ".gettype($value). " is received" , 1);
			debug_log(__METHOD__
				." Tools only had allowed array or null values. ".gettype($value). " is received "
				.to_string($value)
				, logger::ERROR
			);
		}

		$this->tools = $value;
	}//end set_tools



	/**
	* SET_BUTTONS
	* @param array|null $value
	* @return void
	*/
	public function set_buttons(?array $value) : void {

		if(!is_null($value) && !is_array($value)){
			// throw new Exception("Error Processing Request, Buttons only had allowed array or null values. ".gettype($value). " is received" , 1);
			debug_log(__METHOD__
				." Buttons only had allowed array or null values. ".gettype($value). " is received "
				.to_string($value)
				, logger::ERROR
			);
		}

		$this->buttons = $value;
	}//end set_buttons



	/**
	* SET_CSS
	* @param object|null $value
	* @return void
	*/
	public function set_css(?object $value) : void {

		$this->css = $value;
	}//end set_css



	/**
	* SET_TARGET_SECTIONS
	* @param array $value
	* @return void
	*/
	public function set_target_sections(array $value) : void {

		$this->target_sections = $value;
	}//end set_target_sections



	/**
	* SET_REQUEST_CONFIG
	* @param array|null $value
	* @return void
	*/
	public function set_request_config(?array $value) : void {

		$this->request_config = $value;
	}//end set_request_config



	/**
	* SET_COLUMNS_MAP
	* @param array|null $value
	* @return void
	*/
	public function set_columns_map(?array $value) : void {

		$this->columns_map = $value;
	}//end set_columns_map



	/**
	* SET_VIEW
	* @param string|null $value
	* @return void
	*/
	public function set_view(?string $value) : void {

		$this->view = $value;
	}//end set_view



	/**
	* SET_CHILDREN_VIEW
	* @param string|null $value
	* @return void
	*/
	public function set_children_view(?string $value) : void {

		$this->children_view = $value;
	}//end set_view



	/**
	* SET_SECTION_ID
	* Used by tools
	* @param int|null $value
	* @return void
	*/
	public function set_section_id(?int $value) : void {

		$this->section_id = $value;
	}//end set_section_id



	/**
	* SET_NAME
	* Used by tools
	* @param string $value
	* @return void
	*/
	public function set_name(string $value) : void {

		$this->name = $value;
	}//end set_name



	/**
	* SET_DESCRIPTION
	* Used by tools
	* @param string $value
	* @return void
	*/
	public function set_description(string $value) : void {

		$this->description = $value;
	}//end set_description



	/**
	* SET_ICON
	* Used by tools
	* @param string $value
	* @return void
	*/
	public function set_icon(string $value) : void {

		$this->icon = $value;
	}//end set_icon


	/**
	* SET_SHOW_IN_INSPECTOR
	* Used by tools
	* @param bool $value
	* @return void
	*/
	public function set_show_in_inspector(bool $value) : void {

		$this->show_in_inspector = $value;
	}//end set_show_in_inspector



	/**
	* SET_SHOW_IN_COMPONENT
	* Used by tools
	* @param bool $value
	* @return void
	*/
	public function set_show_in_component(bool $value) : void {

		$this->show_in_component = $value;
	}//end set_show_in_component



	/**
	* SET_CONFIG
	* Used by tools
	* @param object|null $value
	* @return void
	*/
	public function set_config(?object $value) : void {

		$this->config = $value;
	}//end set_config



	/**
	* SET_SORTABLE
	* Used by components (columns)
	* @param bool $value
	* @return void
	*/
	public function set_sortable(bool $value) : void {

		$this->sortable = $value;
	}//end set_sortable



	/**
	* SET_FIELDS_SEPARATOR
	* Used by tools
	* @param string $value
	* @return void
	*/
	public function set_fields_separator(string $value) : void {

		$this->fields_separator = $value;
	}//end set_fields_separator



	/**
	* SET_RECORDS_SEPARATOR
	* Used by tools
	* @param string $value
	* @return void
	*/
	public function set_records_separator(string $value) : void {

		$this->records_separator = $value;
	}//end set_records_separator



	/**
	* SET_AUTOLOAD
	* Used by tools
	* @param bool $value
	* @return void
	*/
	public function set_autoload(bool $value) : void {

		$this->autoload = $value;
	}//end set_autoload



	/**
	* SET_ROLE
	* Used by tools
	* @param string $value
	* @return void
	*/
	public function set_role(string $value) : void {

		$this->role = $value;
	}//end set_role




	/**
	* COMPARE_DDO
	* @param object $ddo1
	* @param object $ddo2
	* @param array $ar_properties = ['model','tipo','section_tipo','mode','lang', 'parent','typo','type']
	* @param array $ar_exclude_properties = []
	* @return bool $equal
	*/
	public static function compare_ddo(object $ddo1, object $ddo2, array $ar_properties=['model','tipo','section_tipo','mode','lang', 'parent','typo','type'], array $ar_exclude_properties=[]) : bool {

		// if (!is_object($ddo1) || !is_object($ddo2)) {
		// 	return false;
		// }

		if (empty($ar_properties)){
			foreach ($ddo1 as $property => $value) {
				if (!in_array($property, $ar_exclude_properties)) {
					$ar_properties[] = $property;
				}
			}

			foreach ($ddo2 as $property => $value) {
				if (!in_array($property, $ar_exclude_properties)) {
					$ar_properties[] = $property;
				}
			}

			$ar_properties = array_unique($ar_properties);
		}


		$equal = true;

		foreach ((array)$ar_properties as $current_property) { // 'section_tipo','section_id','type','from_component_tipo','component_tipo','tag_id'

			#if (!is_object($ddo1) || !is_object($ddo2)) {
			#	$equal = false;
			#	break;
			#}

			$property_exists_in_l1 = property_exists($ddo1, $current_property);
			$property_exists_in_l2 = property_exists($ddo2, $current_property);


			# Test property exists in all items
			#if (!property_exists($ddo1, $current_property) && !property_exists($ddo2, $current_property)) {
			if ($property_exists_in_l1===false && $property_exists_in_l2===false) {
				# Skip not existing properties
				#debug_log(__METHOD__." Skipped comparison property $current_property. Property not exits in any locator ", logger::DEBUG);
				continue;
			}

			# Test property exists only in one locator
			#if (property_exists($ddo1, $current_property) && !property_exists($ddo2, $current_property)) {
			if ($property_exists_in_l1===true && $property_exists_in_l2===false) {
				#debug_log(__METHOD__." Property $current_property exists in ddo1 but not exits in ddo2 (false is returned): ".to_string($ddo1).to_string($ddo2), logger::DEBUG);
				$equal = false;
				break;
			}
			#if (property_exists($ddo2, $current_property) && !property_exists($ddo1, $current_property)) {
			if ($property_exists_in_l2===true && $property_exists_in_l1===false) {
				#debug_log(__METHOD__." Property $current_property exists in ddo2 but not exits in ddo1 (false is returned): ".to_string($ddo1).to_string($ddo2), logger::DEBUG);
				$equal = false;
				break;
			}

			# Compare verified existing properties
			if ($current_property==='section_id') {
				if( $ddo1->$current_property != $ddo2->$current_property ) {
					$equal = false;
					break;
				}
			}else{
				if( $ddo1->$current_property !== $ddo2->$current_property ) {
					$equal = false;
					break;
				}
			}
		}

		return (bool)$equal;
	}//end compare_ddo



	/**
	* IN_ARRAY_DDO
	* @param object $ddo
	* @param array $ar_ddo
	* @param array $ar_properties = ['model','tipo','section_tipo','mode','lang', 'parent','typo','type']
	* @return bool $found
	*/
	public static function in_array_ddo(object $ddo, array $ar_ddo, array $ar_properties=['model','tipo','section_tipo','mode','lang', 'parent','typo','type']) : bool {

		$found = false;

		foreach ((array)$ar_ddo as $current_ddo) {
			$found = self::compare_ddo( $ddo, $current_ddo, $ar_properties );
			if($found===true) break;
		}

		#$ar = array_filter(
		#		$ar_ddo,
		#		function($current_ddo) use($ddo, $ar_properties){
		#			return self::compare_ddos( $ddo, $current_ddo, $ar_properties );
		#		}
		#); return $ar;


		return $found;
	}//end in_array_ddo



	/**
	* GET METHODS
	* By accessors. When property exits, return property value, else return null
	* @param string $name
	*/
	final public function __get(string $name) {

		if (isset($this->$name)) {
			return $this->$name;
		}

		// $trace = debug_backtrace();
		// debug_log(
		// 	__METHOD__
		// 	.' Undefined property via __get(): '.$name .
		// 	' in ' . $trace[0]['file'] .
		// 	' on line ' . $trace[0]['line'],
		// 	logger::DEBUG);
		return null;
	}
	// final public function __set($name, $value) {
	// 	$this->$name = $value;
	// }



}//end dd_object
