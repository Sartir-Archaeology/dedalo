<?php
/**
* COMMON (ABSTRACT CLASS)
* Métodos compartidos por todos los componentes y secciones
* declarar los métodos public
*/
abstract class common {

	// permissions. int value from 0 to 3
	public $permissions;

	// ar_loaded_modelos_name. List of all components/sections model name used in current page (without duplicates). Used to determine
	// the css and css files to load
	static $ar_loaded_modelos_name = array();

	// identificador_unico. UID used to set dom elements id unique based on section_tipo, section_id, lang, modo, etc.
	public $identificador_unico;
	// variant. Modifier of identificador_unico
	public $variant;

	// bl_loaded_structure_data. Set to true when element structure data is loaded. Avoid reload structure data again
	protected $bl_loaded_structure_data;
	//bl_loaded_matrix_data. Set to true when element matrix data is loaded. Avoid reconnect to db data again
	protected $bl_loaded_matrix_data;

	// TABLE  matrix_table
	// public $matrix_table;

	// context. Object with information about context of current element
	public $context;

	// public properties
	public $properties;

	// from_parent. Used to link context ddo elements
	public $from_parent;

	// parent_grouper
	public $parent_grouper;

	// build options sended by the client into show ddo to modify the standard get data.
	// in area_thesaurus it send if the thesaurus need get models or terms.
	// in component_portal it send if the source external need to be updated.
	public $build_options = null;

	// request config with show, select and search of the item
	public $request_config;

	// request_ddo_value
	public $request_ddo_value;


	// REQUIRED METHODS
	#abstract protected function define_id($id);
	#abstract protected function define_tipo();
	#abstract protected function define_lang();
	#abstract public function get_html();


	// temporal excluded/mapped models
		public static $ar_temp_map_models = [
			// map to => old model
			'component_portal' 	=> 'component_autocomplete_hi',
			'component_portal' 	=> 'component_autocomplete',
			'section_group' 	=> 'section_group_div'
		];
		public static $ar_temp_exclude_models = [
			// v5
			'component_security_areas',
			'component_autocomplete_ts', // ?
			// v6
			// 'component_autocomplete'
			// 'component_av'
			// 'component_calculation',
			// 'component_check_box'
			// 'component_date'
			// 'component_email'
			// 'component_external',
			// 'component_filter'
			// 'component_filter_master'
			// 'component_filter_records'
			// 'component_geolocation'
			'component_html_file',
			// 'component_html_text',
			// 'component_image'
			// 'component_info',
			// 'component_input_text'
			'component_input_text_large',
			//'component_inverse',
			'component_ip',
			// 'component_iri'
			// 'component_json'
			'component_layout',
			// 'component_number'
			// 'component_password',
			// 'component_pdf'
			// 'component_portal'
			// 'component_publication'
			// 'component_radio_button'
			// 'component_relation_children',
			// 'component_relation_index',
			// 'component_relation_model',
			// 'component_relation_parent',
			// 'component_relation_related',
			'component_relation_struct',
			'component_score',
			// 'component_section_id'
			// 'component_security_access'
			'component_security_tools',
			// 'component_select'
			// 'component_select_lang'
			'component_state',
			// 'component_svg'
			// 'component_text_area'
		];
		public static $groupers = [
			'section_group',
			'section_tab',
			'tab',
			'section_group_relation',
			'section_group_portal',
			'section_group_div'
		];



	# ACCESSORS
	final public function __call($strFunction, $arArguments) {

		$strMethodType 		= substr($strFunction, 0, 4); # like set or get_
		$strMethodMember 	= substr($strFunction, 4);
		switch($strMethodType) {
			case 'set_' :
				if(!isset($arArguments[0])) return(false);	#throw new Exception("Error Processing Request: called $strFunction without arguments", 1);
				return($this->SetAccessor($strMethodMember, $arArguments[0]));
				break;
			case 'get_' :
				return($this->GetAccessor($strMethodMember));
				break;
		}
		return(false);
	}
	# SET
	final protected function SetAccessor($strMember, $strNewValue) {

		if(property_exists($this, $strMember)) {
			$this->$strMember = $strNewValue;
		}else{
			return(false);
		}
	}
	# GET
	final protected function GetAccessor($strMember) {

		if(property_exists($this, $strMember)) {
			$strRetVal = $this->$strMember;
			# stripslashes text values
			#if(is_string($strRetVal)) $strRetVal = stripslashes($strRetVal);
			return($strRetVal);
		}else{
			return(false);
		}
	}



	/**
	* GET_PERMISSIONS
	* @param string $tipo
	* @return int $permissions
	*/
	public static function get_permissions( $parent_tipo=null, $tipo=null ) {

		if(login::is_logged()!==true)
			return 0;

		if( empty($parent_tipo) ) {
			if(SHOW_DEBUG===true) {
				dump($parent_tipo,'parent_tipo');
				throw new Exception("Error Processing Request. get_permissions: parent_tipo is empty", 1);
			}
			#die("Error Processing Request. get_permissions: tipo is empty");
			debug_log(__METHOD__." Error Processing Request. get_permissions: tipo is empty ".to_string(), logger::ERROR);
			return 0;
		}
		if( empty($tipo) ) {
			if(SHOW_DEBUG===true) {
				dump($tipo,'get_permissions error for tipo');
				throw new Exception("Error Processing Request. get_permissions: tipo is empty", 1);
			}
			#die("Error Processing Request. get_permissions: tipo is empty");
			debug_log(__METHOD__." Error Processing Request. get_permissions: tipo is empty ".to_string(), logger::ERROR);
			return 0;
		}

		// dd1324 Tools Register section
			if ($parent_tipo==='dd1324') {
				return 1;
			}

		$permissions = security::get_security_permissions($parent_tipo, $tipo);


		return (int)$permissions;
	}//end get_permissions



	/**
	* SET_PERMISSIONS
	*/
	public function set_permissions( $number ) {
		$this->permissions = (int)$number;
	}//end set_permissions



	/**
	* LOAD STRUCTURE DATA
	* Get data once from structure (tipo, modelo, norden, estraducible, etc.)
	*/
	protected function load_structure_data() {

		if( empty($this->tipo) ) {
			dump($this, " DUMP ELEMENT WITHOUT TIPO - THIS ");
			throw new Exception("Error (3): tipo is mandatory!", 1);
		}


		if( !$this->bl_loaded_structure_data) {

			$this->RecordObj_dd	= new RecordObj_dd($this->tipo);

			# Fix vars
			$this->modelo	= $this->RecordObj_dd->get_modelo();
			$this->norden	= $this->RecordObj_dd->get_norden();
			$this->required	= $this->RecordObj_dd->get_usableIndex();


			$this->label = RecordObj_dd::get_termino_by_tipo($this->tipo,DEDALO_APPLICATION_LANG,true);		#echo 'DEDALO_APPLICATION_LANG: '.DEDALO_APPLICATION_LANG ;#var_dump($this->label);	#die();


			# TRADUCIBLE
			$this->traducible = $this->RecordObj_dd->get_traducible();
			# Si el elemento no es traducible, fijamos su 'lang' en 'lg-nolan' (DEDALO_DATA_NOLAN)
			if ($this->traducible==='no') {
				$this->fix_language_nolan();
			}

			# properties : Always JSON decoded
			#dump($this->RecordObj_dd->get_properties()," ");
			$properties = $this->RecordObj_dd->get_properties();
			$this->properties = !empty($properties) ? $properties : false;

			# MATRIX_TABLE
			#if(!isset($this->matrix_table))
			#$this->matrix_table = self::get_matrix_table_from_tipo($this->tipo);

			# NOTIFY : Notificamos la carga del elemento a common
			$modelo_name = get_called_class();
			common::notify_load_lib_element_tipo($modelo_name, $this->modo);

			# BL_LOADED_STRUCTURE_DATA
			$this->bl_loaded_structure_data = true;
		}
	}//end load_structure_data



	/**
	* GET MATRIX_TABLE FROM TIPO
	* @param string $tipo
	* @return string $matrix_table
	*/
	public static function get_matrix_table_from_tipo($tipo) {

		if (empty($tipo)) {
			trigger_error("Error Processing Request. tipo is empty");
			return false;
		}elseif ($tipo==='matrix') {
			trigger_error("Error Processing Request. tipo is invalid (tipo:$tipo)");
			return false;
		}

		static $matrix_table_from_tipo;

		if(isset($matrix_table_from_tipo[$tipo])) {
			return($matrix_table_from_tipo[$tipo]);
		}

		#if(SHOW_DEBUG===true) $start_time = start_time();

		# Default value:
		$matrix_table = 'matrix';

		$modelo_name  = RecordObj_dd::get_modelo_name_by_tipo($tipo, true);
			if (empty($modelo_name)) {
				debug_log(__METHOD__." Current tipo ($tipo) modelo name is empty. Default table 'matrix' was used.".to_string(), logger::DEBUG);
			}

		if ($modelo_name==='section') {

			# SECTION CASE
			switch (true) {
				case ($tipo===DEDALO_SECTION_PROJECTS_TIPO):
					$matrix_table = 'matrix_projects';
					#error_log("Error. Table for section projects tipo is not defined. Using default table: '$matrix_table'");
					break;
				case ($tipo===DEDALO_SECTION_USERS_TIPO):
					$matrix_table = 'matrix_users';
					#error_log("Error. Table for section users tipo is not defined. Using default table: '$matrix_table'");
					break;
				default:

					$table_is_resolved = false;

					# SECTION : If section have TR of model name 'matrix_table' takes its matrix_table value
					$ar_related = common::get_ar_related_by_model('matrix_table', $tipo);
					if ( isset($ar_related[0]) ) {
						// REAL OR VIRTUAL SECTION
						# Set custom matrix table
						$matrix_table = RecordObj_dd::get_termino_by_tipo($ar_related[0],null,true);
							#if (SHOW_DEBUG===true) dump($matrix_table,"INFO: Switched table to: $matrix_table for tipo:$tipo ");
						$table_is_resolved = true;
					}
					// CASE VIRTUAL SECTION
					if ($table_is_resolved===false) {
						$tipo 		= section::get_section_real_tipo_static($tipo);
						$ar_related = common::get_ar_related_by_model('matrix_table', $tipo);
						if ( isset($ar_related[0]) ) {
							// REAL SECTION
							# Set custom matrix table
							$matrix_table = RecordObj_dd::get_termino_by_tipo($ar_related[0],null,true);
								#if (SHOW_DEBUG===true) dump($matrix_table,"INFO: Switched table to: $matrix_table for tipo:$tipo ");
							$table_is_resolved = true;
						}
					}
			}//end switch

		}else{
			if(SHOW_DEBUG===true) {
				dump(debug_backtrace(), 'debug_backtrace() ++ '.to_string());;
			}
			throw new Exception("Error Processing Request. Don't use non section tipo ($tipo - $modelo_name) to calculate matrix_table. Use always section_tipo", 1);

			/*
			# COMPONENT CASE
			# Heredamos la tabla de la sección parent (si la hay)
			$ar_parent_section = RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($tipo, $modelo_name='section', $relation_type='parent');
			if (isset($ar_parent_section[0])) {
				$parent_section_tipo = $ar_parent_section[0];
				$ar_related = common::get_ar_related_by_model('matrix_table', $parent_section_tipo);
				if ( isset($ar_related[0]) ) {
					# Set custom matrix table
					$matrix_table = RecordObj_dd::get_termino_by_tipo($ar_related[0],null,true);
				}
			}
			*/
		}
		#dump($matrix_table,'$matrix_table for tipo: '.$tipo);

		# Cache
		$matrix_table_from_tipo[$tipo] = $matrix_table;

		#if(SHOW_DEBUG===true) $GLOBALS['log_messages'][] = exec_time($start_time, __METHOD__, 'logger_backend_activity '.$tipo);

		return (string)$matrix_table;
	}//end get_matrix_table_from_tipo



	/**
	* GET_MATRIX_TABLES_WITH_RELATIONS
	* Note: Currently tables are static. make a connection to db to do dynamic ASAP
	* @return array $ar_tables
	*/
	public static function get_matrix_tables_with_relations() {

		static $ar_tables;

		if (isset($ar_tables)) {
			return $ar_tables;
		}

		$ar_tables = [];

		# Tables
		# define('DEDALO_TABLES_LIST_TIPO', 'dd627'); // Matrix tables box elements
		$ar_children_tables = RecordObj_dd::get_ar_childrens('dd627', 'norden');
		foreach ($ar_children_tables as $table_tipo) {
			$RecordObj_dd	= new RecordObj_dd( $table_tipo );
			$modelo_name	= RecordObj_dd::get_modelo_name_by_tipo($table_tipo,true);
			if ($modelo_name!=='matrix_table') {
				continue;
			}
			$properties = $RecordObj_dd->get_properties();
			if (property_exists($properties,'inverse_relations') && $properties->inverse_relations===true) {
				$ar_tables[] = RecordObj_dd::get_termino_by_tipo($table_tipo, DEDALO_STRUCTURE_LANG, true, false);
			}
		}

		if (empty($ar_tables)) {
			trigger_error("Error on read structure tables list. Old structure version < 26-01-2018 !");
			$ar_tables = [
				"matrix",
				"matrix_list",
				"matrix_activities",
				"matrix_hierarchy"
			];
		}
		#debug_log(__METHOD__." ar_tables ".json_encode($ar_tables), logger::DEBUG);


		return $ar_tables;
	}//end get_matrix_tables_with_relations



	/**
	* SET_DATO
	*/
	public function set_dato($dato){

		# UNSET previous calculated valor
		unset($this->valor);
		# UNSET previous calculated ar_list_of_values
		unset($this->ar_list_of_values);

		$this->dato = $dato;
	}//end set_dato



	/**
	* SET_LANG
	* When isset lang, valor and dato are cleaned
	* and $this->bl_loaded_matrix_data is reset to force load from database again
	*/
	public function set_lang($lang) {

		#if($lang!==DEDALO_DATA_LANG) {

			# FORCE reload dato from database when dato is requested again
			$this->set_to_force_reload_dato();
		#}

		$this->lang = $lang;
	}//end set_lang



	/**
	* SET_TO_FORCE_RELOAD_DATO
	*/
	public function set_to_force_reload_dato() {

		# UNSET previous calculated valor
		unset($this->valor);

		#$this->dato_resolved = false;
		#unset($this->dato);

		# FORCE reload dato from database when dato is requested again
		$this->bl_loaded_matrix_data = false;
	}//end set_to_force_reload_dato



	/**
	* GET_MAIN_LANG
	* @return string $main_lang
	*/
	public static function get_main_lang( $section_tipo, $section_id=null ) {
		#dump($section_tipo, ' section_tipo ++ '.to_string());
		# Always fixed lang of languages as English
		if ($section_tipo==='lg1') {
			return 'lg-eng';
		}

		static $current_main_lang;
		$uid = $section_tipo.'_'.$section_id;
		if (isset($current_main_lang[$uid])) {
			return $current_main_lang[$uid];
		}

		# De momento, el main_lang default para todas las jerarquias será lg-spa porque es nuestra base de trabajo
		# Dado que cada section id puede tener un main_lang diferente, estudiar este caso..
		# DEDALO_HIERARCHY_SECTION_TIPO = hierarchy1
		if ($section_tipo===DEDALO_HIERARCHY_SECTION_TIPO) {

			$main_lang = 'lg-spa'; # Default for hierarchy

			if (!is_null($section_id)) {
				$section		= section::get_instance($section_id, $section_tipo);
				$modelo_name	= RecordObj_dd::get_modelo_name_by_tipo(DEDALO_HIERARCHY_LANG_TIPO,true);
				$component		= component_common::get_instance($modelo_name,
																 DEDALO_HIERARCHY_LANG_TIPO,
																 $section_id,
																 'list',
																 DEDALO_DATA_NOLAN,
																 $section_tipo);
				 $dato = $component->get_dato();
				 if (isset($dato[0])) {
					$lang_code = lang::get_code_from_locator($dato[0], $add_prefix=true);
					# dump($lang_code, ' lang_code ++ '.to_string());
					$main_lang = $lang_code;
				 }
			}

		}else{

			#$matrix_table = common::get_matrix_table_from_tipo($section_tipo);
			#if ($matrix_table==='matrix_hierarchy') {
			#	$main_lang = hierarchy::get_main_lang( $section_tipo );
			#		dump($main_lang, ' main_lang ++ '.to_string());
			#}

			# If current section is virtual of DEDALO_THESAURUS_SECTION_TIPO, search main lang in self hierarchy
			$ar_related_section_tipo = common::get_ar_related_by_model('section', $section_tipo);

			switch (true) {

				# Thesaurus virtual
				case (isset($ar_related_section_tipo[0]) && $ar_related_section_tipo[0]===DEDALO_THESAURUS_SECTION_TIPO):
					$main_lang = hierarchy::get_main_lang($section_tipo);
					if (empty($main_lang)) {
						debug_log(__METHOD__." Empty main_lang for section_tipo: $section_tipo using 'hierarchy::get_main_lang'. Default value fallback is used (DEDALO_DATA_LANG_DEFAULT): ".DEDALO_DATA_LANG_DEFAULT, logger::WARNING);
						#trigger_error("Empty main_lang for section_tipo: $section_tipo using 'hierarchy::get_main_lang'. Default value fallback is used (DEDALO_DATA_LANG_DEFAULT): ".DEDALO_DATA_LANG_DEFAULT);
						$main_lang = DEDALO_DATA_LANG_DEFAULT;
					}
					break;

				default:
					$main_lang = DEDALO_DATA_LANG_DEFAULT;
					break;
			}
		}
		#debug_log(__METHOD__." main_lang ".to_string($main_lang), logger::DEBUG);

		$current_main_lang[$uid] = $main_lang;


		return (string)$main_lang;
	}//end get_main_lang



	/**
	* NOTIFY_LOAD_LIB_ELEMENT_TIPO
	*/
	public static function notify_load_lib_element_tipo($modelo_name, $modo) {

		#if ($modo!=='edit') {
		#	return false;
		#}

		if (empty($modelo_name) || in_array($modelo_name, common::$ar_loaded_modelos_name)) {
			return false;
		}
		common::$ar_loaded_modelos_name[] = $modelo_name;

		return true;
	}//end notify_load_lib_element_tipo



	/**
	* SETVAR
	*/
	public static function setVar($name, $default=false) {

		if($name==='name') throw new Exception("Error Processing Request [setVar]: Name 'name' is invalid", 1);

		$$name = $default;
		if(isset($_REQUEST[$name])) $$name = $_REQUEST[$name];

		if(isset($$name)) {

			$$name = safe_xss($$name);

			return $$name;
		}

		return false;
	}//end setVar



	/**
	* SETVARDATA
	* @param string $name
	* @param onject $data_obj
	*/
	public static function setVarData($name, $data_obj, $default=false) {

		if($name==='name') throw new Exception("Error Processing Request [setVarData]: Name 'name' is invalid", 1);

		$$name = $default;
		if(isset($data_obj->{$name})) $$name = $data_obj->{$name};

		if(isset($$name)) {
			# Not sanitize here (can loose some transcriptions tags) !
			#$$name = safe_xss($$name);

			return $$name;
		}

		return false;
	}//end setVar



	/**
	* GET_PAGE_QUERY_STRING . REMOVED ORDER CODE BY DEFAULT
	*/
	public static function get_page_query_string($remove_optional_vars=true) {

		$queryString = $_SERVER['QUERY_STRING']; # like max=10
		$queryString = safe_xss($queryString);

		if($remove_optional_vars === false) return $queryString;

		$qs 				= false ;
		$ar_optional_vars	= array('order_by','order_dir','lang','accion','pageNum');

		$search  		= array('&&',	'&=',	'=&',	'??',	'==');
		$replace 		= array('&',	'&',	'&',	'?',	'=' );
		$queryString 	= str_replace($search, $replace, $queryString);

		$posAND 	= strpos($queryString, '&');
		$posEQUAL 	= strpos($queryString, '=');

		# go through and rebuild the query without the optional variables
		if($posAND !== false){ # query tipo ?captacionID=1&informantID=6&list=0

			$ar_pares = explode('&', $queryString);
			if(is_array($ar_pares)) foreach ($ar_pares as $key => $par){

				#echo " <br> $key - $par ";
				if(strpos($par,'=')!==false) {

					$troz		= explode('=',$par) ;

					$varName 	= false;	if(isset($troz[0])) $varName  = $troz[0];
					$varValue 	= false;	if(isset($troz[1])) $varValue = $troz[1];

					if(!in_array($varName, $ar_optional_vars)) {
						$qs .= $varName . '=' . $varValue .'&';
					}
				}
			}

		}else if($posAND === false && $posEQUAL !== false) { # query tipo ?captacionID=1

			$qs = $queryString ;
		}

		$qs = str_replace($search, $replace, $qs);

		# if last char is & delete it
		if(substr($qs, -1)==='&') $qs = substr($qs, 0, -1);

		return $qs;
	}//end get_page_query_string



	/**
	* GET HTML CODE . RETURN INCLUDE FILE __CLASS__.PHP
	* @return string $html
	*	Get standard path file "DEDALO_CORE_PATH .'/'. $class_name .'/'. $class_name .'.php'" (ob_start)
	*	and return rendered html code
	*/
	public function get_html() {

		if(SHOW_DEBUG===true) $start_time = start_time();

			# Class name is called class (ex. component_input_text), not this class (common)
			ob_start();
			include ( DEDALO_CORE_PATH .'/'. get_called_class() .'/'. get_called_class() .'.php' );
			$html = ob_get_clean();

		if(SHOW_DEBUG===true) {
			#$GLOBALS['log_messages'][] = exec_time($start_time, __METHOD__. ' ', "html");
			global$TIMER;$TIMER[__METHOD__.'_'.get_called_class().'_'.$this->tipo.'_'.$this->modo.'_'.microtime(1)]=microtime(1);
		}

		return (string)$html;
	}//end get_html


	/**
	* GET_AR_ALL_LANGS : Return array of all langs of all projects in Dédalo
	* @return array $ar_all_langs
	*	like (lg-eng=>locator,lg-spa=>locator) or resolved (lg-eng => English, lg-spa => Spanish)
	*/
	public static function get_ar_all_langs() {

		$ar_all_langs = unserialize(DEDALO_PROJECTS_DEFAULT_LANGS);

		return (array)$ar_all_langs;
	}//end get_ar_all_langs



	/**
	* GET_AR
	* @param string $lang
	*	Default DEDALO_DATA_LANG
	* @return array $ar_all_langs_resolved
	*/
	public static function get_ar_all_langs_resolved( $lang=DEDALO_DATA_LANG ) {

		$ar_all_langs = common::get_ar_all_langs();

		$ar_all_langs_resolved=array();
		foreach ((array)$ar_all_langs as $current_lang) {

			$lang_name = lang::get_name_from_code( $current_lang, $lang );
			$ar_all_langs_resolved[$current_lang] = $lang_name;
		}

		return $ar_all_langs_resolved;
	}//end get_ar_all_langs_resolved



	/**
	* GET_properties : Alias of $this->RecordObj_dd->get_properties() but json decoded
	*/
	public function get_properties() {

		if(isset($this->properties)) return $this->properties;

		# Read string from database str
		$properties = $this->RecordObj_dd->get_properties();

		return $properties;
	}//end get_properties



	/**
	* SET_properties
	* @return bool
	*/
	public function set_properties($value) {
		if (is_string($value)) {
			$properties = json_decode($value);
		}else{
			$properties = $value;
		}

		# Fix properties obj
		$this->properties = (object)$properties;

		return true;
	}//end set_properties



	/**
	* GET_AR_RELATED_COMPONENT_TIPO
	* @return array $ar_related_component_tipo
	*/
	public function get_ar_related_component_tipo() {
		$ar_related_component_tipo=array();
		#dump($this, ' this ++ '.to_string());
		$relaciones = $this->RecordObj_dd->get_relaciones();
		if(is_array($relaciones )) {
			foreach ($relaciones as $key => $value) {
				$tipo = reset($value);
				$ar_related_component_tipo[] = $tipo;
			}
		}

		return (array)$ar_related_component_tipo;
	}//end get_ar_related_component_tipo



	/**
	* GET_AR_RELATED_BY_MODEL
	* @return array $ar_related_by_model
	*/
	public static function get_ar_related_by_model($modelo_name, $tipo, $strict=true) {

		static $ar_related_by_model_data;
		$uid = $modelo_name.'_'.$tipo;
		if (isset($ar_related_by_model_data[$uid])) {
			return $ar_related_by_model_data[$uid];
		}

		$RecordObj_dd = new RecordObj_dd($tipo);
		$relaciones   = $RecordObj_dd->get_relaciones();

		$ar_related_by_model=array();
		foreach ((array)$relaciones as $relation) foreach ((array)$relation as $modelo_tipo => $current_tipo) {

			# Calcularlo desde el modelo_tipo no es seguro, ya que el modelo de un componente pude cambiar y esto no actualiza el modelo_tipo de la relación
			#$related_terms[$tipo] = RecordObj_dd::get_termino_by_tipo($modelo_tipo, DEDALO_STRUCTURE_LANG, true, false);	//$terminoID, $lang=NULL, $from_cache=false, $fallback=true
			# Calcular siempre el modelo por seguridad
			$current_modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo, true);
			if ($strict===true) {
				// Default compare equal
				if ($current_modelo_name===$modelo_name) {
					$ar_related_by_model[] = $current_tipo;
				}
			}else{
				if (strpos($current_modelo_name, $modelo_name)!==false) {
					$ar_related_by_model[] = $current_tipo;
				}
			}

		}
		#debug_log(__METHOD__." ar_related_by_model - modelo_name:$modelo_name - tipo:$tipo - ar_related_by_model:".json_encode($ar_related_by_model), logger::DEBUG);

		$ar_related_by_model_data[$uid] = $ar_related_by_model;

		return $ar_related_by_model;
	}//end get_ar_related_by_model



	/**
	* GET_ALLOWED_RELATION_TYPES
	* Search in structure and return an array of tipos
	* @return array $allowed_relations
	*/
	public static function get_allowed_relation_types() {

		# For speed, we use constants now
		$ar_allowed = array(DEDALO_RELATION_TYPE_CHILDREN_TIPO,
							DEDALO_RELATION_TYPE_PARENT_TIPO,
							DEDALO_RELATION_TYPE_RELATED_TIPO,
							#DEDALO_RELATION_TYPE_EQUIVALENT_TIPO,
							DEDALO_RELATION_TYPE_INDEX_TIPO,
							DEDALO_RELATION_TYPE_STRUCT_TIPO,
							DEDALO_RELATION_TYPE_MODEL_TIPO,
							DEDALO_DATAFRAME_TYPE_UNCERTAINTY,
							DEDALO_DATAFRAME_TYPE_TIME,
							DEDALO_DATAFRAME_TYPE_SPACE,
							DEDALO_RELATION_TYPE_LINK,
							DEDALO_RELATION_TYPE_FILTER
							); // DEDALO_RELATION_TYPE_RECORD_TIPO
		/*
		$tipo 		  = 'dd427';
		$modelo_name  = 'relation_type';
		$relation_type= 'children';
		$ar_allowed   = (array)RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($tipo, $modelo_name, $relation_type, $search_exact=true);
		*/

		return (array)$ar_allowed;
	}//end get_allowed_relation_types



	/**
	* TRIGGER_MANAGER
	* @param php://input
	* @return object $response
	*/
	public static function trigger_manager($request_options=false) {

		// options parse
			$options = new stdClass();
				$options->test_login 		= true;
				$options->source 	 		= 'php://input';
				$options->set_json_header 	= true;
				if($request_options!==false) {
					foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}
				}

		# Set JSON headers for all responses (default)
			if ($options->set_json_header===true) {
				#header('Content-Type: application/json');
				header('Content-Type: application/json; charset=utf-8');
			}


		# JSON_DATA
		# javascript common.get_json_data sends a stringify json object
		# this object is getted here and decoded with all ajax request vars
			if ($options->source==='GET') {
				#$str_json = json_encode($_GET);
				// Verify all get vars before json encode
				$get_obj = new stdClass();
				foreach ($_GET as $key => $value) {
					$get_obj->{$key} = safe_xss($value);
				}
				$str_json = json_encode($get_obj);
			}elseif ($options->source==='POST') {
				#$str_json = json_encode($_GET);
				// Verify all get vars before json encode
				$get_obj = new stdClass();
				foreach ($_POST as $key => $value) {
					$get_obj->{$key} = safe_xss($value);
				}
				$str_json = json_encode($get_obj);
			}else{
				$str_json = file_get_contents('php://input');
			}
			if (!$json_data = json_decode($str_json)) {
				$response = new stdClass();
					$response->result 	= false;
					$response->msg 		= "Error on read php://input data";

				return false;
			}

		# DEDALO_MAINTENANCE_MODE
			$mode = $json_data->mode;
			if ($mode!=="Save" && $mode!=="Login") {
				if (DEDALO_MAINTENANCE_MODE===true && (isset($_SESSION['dedalo']['auth']['user_id']) && $_SESSION['dedalo']['auth']['user_id']!=DEDALO_SUPERUSER)) {
					debug_log(__METHOD__." Kick user ".to_string(), logger::DEBUG);

					# Unset user session login
					# Delete current Dédalo session
					unset($_SESSION['dedalo']['auth']);

					# maintenance check
					$response = new stdClass();
						$response->result 	= true;
						$response->msg 		= "Sorry, this site is under maintenace now";
					echo json_encode($response);
					#exit();
					return false;
				}
			}


		# LOGGED USER CHECK. Can be disabled in options (login case)
			if($options->test_login===true && login::is_logged()!==true) {
				$response = new stdClass();
					$response->result 	= false;
					$response->msg 		= "Error. Auth error: please login [1]";
				echo json_encode($response);
				#exit();
				return false;
			}


		# MODE Verify
			if(empty($json_data->mode)) {
				$response = new stdClass();
					$response->result 	= false;
					$response->msg 		= "Error. mode is mandatory";
				echo json_encode($response);
				#exit();
				return false;
			}


		# CALL FUNCTION

			if ( function_exists($json_data->mode) ) {

				$response = (object)call_user_func($json_data->mode, $json_data);

			}else{

				$response = new stdClass();
					$response->result 	= false;
					$response->msg 		= 'Error. Request failed. json_data->mode not exists: '.to_string($json_data->mode);
			}

			// echo final string
				$json_params = (SHOW_DEBUG===true) ? JSON_PRETTY_PRINT : null;
				echo json_encode($response, $json_params);


		return true;
	}//end trigger_manager



	/**
	* GET_REQUEST_VAR
	* Alias of core function get_request_var
	* @return mixed string | bool $var_value
	*/
	public static function get_request_var($var_name) {

		return get_request_var($var_name);
	}//end get_request_var



	/**
	* GET_COOKIE_PROPERTIES
	* @return object $cookie_properties
	* Calculate safe cookie properties to use on set/delete http cookies
	*/
	public static function get_cookie_properties() {

		# Cookie properties
		$domain 	= $_SERVER['SERVER_NAME'];
		$secure 	= stripos(DEDALO_PROTOCOL,'https')!==false ? 'true' : 'false';
		$httponly 	= 'true'; # Not accessible for javascript, only for http/s requests

		$cookie_properties = new stdClass();
			$cookie_properties->domain 	 = $domain;
			$cookie_properties->secure 	 = $secure;
			$cookie_properties->httponly = $httponly;


		return $cookie_properties;
	}//end get_cookie_properties



	/**
	* GET_CLIENT_IP
	* @return string $ipaddress
	*/
	public static function get_client_ip() {

		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';

		return $ipaddress;
	}//end get_client_ip



	/**
	* TRUNCATE_TEXT
	* Multibyte truncate or trim text
	*/
	public static function truncate_text($string, $limit, $break=" ", $pad='...') {

		// returns with no change if string is shorter than $limit
			$str_len = mb_strlen($string, '8bit');
			if($str_len <= $limit) {
				return $string;
			}
		// substring multibyte
			$string_fragment = mb_substr($string, 0, $limit);

		// cut fragment by break char (if is possible)
			if(false !== ($breakpoint = mb_strrpos($string_fragment, $break))) {
				$final_string = mb_substr($string_fragment, 0, $breakpoint);
			}else{
				$final_string = $string_fragment;
			}

		return $final_string . $pad;
	}//end truncate_text



	/**
	* TRUNCATE_HTML
	* Thanks to Søren Løvborg (printTruncated)
	*/
	public static function truncate_html($maxLength, $html, $isUtf8=true) {
	    $printedLength = 0;
	    $position = 0;
	    $tags = array();

	    $full_text = '';

	    // For UTF-8, we need to count multibyte sequences as one character.
	    $re = $isUtf8
	        ? '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;|[\x80-\xFF][\x80-\xBF]*}'
	        : '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}';

	    while ($printedLength < $maxLength && preg_match($re, $html, $match, PREG_OFFSET_CAPTURE, $position))
	    {
	        list($tag, $tagPosition) = $match[0];

	        // Print text leading up to the tag.
	        $str = substr($html, $position, $tagPosition - $position);
	        if ($printedLength + strlen($str) > $maxLength)
	        {
	            #print(substr($str, 0, $maxLength - $printedLength));
	            $full_text .= substr($str, 0, $maxLength - $printedLength);
	            $printedLength = $maxLength;
	            break;
	        }

	        #print($str);
	        $full_text .= $str;
	        $printedLength += strlen($str);
	        if ($printedLength >= $maxLength) break;

	        if ($tag[0] === '&' || ord($tag) >= 0x80)
	        {
	            // Pass the entity or UTF-8 multibyte sequence through unchanged.
	            #print($tag);
	            $full_text .= $tag;
	            $printedLength++;
	        }
	        else
	        {
	            // Handle the tag.
	            $tagName = $match[1][0];
	            if ($tag[1] === '/')
	            {
	                // This is a closing tag.

	                $openingTag = array_pop($tags);
					//assert($openingTag === $tagName); // check that tags are properly nested.

	                #print($tag);
	                $full_text .= $tag;
	            }
	            else if ($tag[strlen($tag) - 2] === '/')
	            {
	                // Self-closing tag.
	                #print($tag);
	                $full_text .= $tag;
	            }
	            else
	            {
	                // Opening tag.
	                #print($tag);
	                $full_text .= $tag;
	                $tags[] = $tagName;
	            }
	        }

	        // Continue after the tag.
	        $position = $tagPosition + strlen($tag);
	    }

	    // Print any remaining text.
	    if ($printedLength < $maxLength && $position < strlen($html))
	        #print(substr($html, $position, $maxLength - $printedLength));
	    	$full_text .= substr($html, $position, $maxLength - $printedLength);

	    // Close any open tags.
	    while (!empty($tags)) {
	        #printf('</%s>', array_pop($tags));
	        $full_text .= sprintf('</%s>', array_pop($tags));
	    }

	    return $full_text;
	}//end truncate_html



	/**
	* BUILD_ELEMENT_JSON_OUTPUT
	* Simply group context and data into a ¡n object and encode as JSON string
	* @param object $context
	* @param object $data
	* @return string $result
	*/
	public static function build_element_json_output($context, $data=[]) {

		$element = new stdClass();
			$element->context = $context;
			$element->data 	  = $data;

		#if(SHOW_DEBUG===true) {
		#	$result = json_encode($element, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		#}else{
		#	$result = json_encode($element, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		#}
		$result = $element;

		return $result;
	}//end build_element_json_output



	/**
	* GET_JSON
	* @param object $request_options
	* 	Optional. Default is false
	* @return array $json
	*	Array of objects with data and context (configurable)
	*/
	public function get_json($request_options=false) {

		// Debug
			if(SHOW_DEBUG===true) $start_time = start_time();

		// options parse
			$options = new stdClass();
				$options->get_context			= true;
				$options->context_type			= 'default';
				$options->get_data				= true;
				$options->get_request_config	= false;
				if($request_options!==false) foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}

			$called_model = get_class($this); // get_called_class(); // static::class
			$called_tipo  = $this->get_tipo();

		// cache context
			// static $resolved_get_json = [];
			// $resolved_get_json_key = $called_model .'_'. $called_tipo .'_'. ($this->section_tipo ?? '') .'_'. $this->modo .'_'. (int)$options->context_type . '_'. (int)$options->get_request_config;
			// if ($options->get_data===false && isset($resolved_get_json[$resolved_get_json_key])) {
			// 	debug_log(__METHOD__." Returned resolved json with key: ".to_string($resolved_get_json_key), logger::DEBUG);
			// 	$json = $resolved_get_json[$resolved_get_json_key];
			// 	return $json;
			// }
			// dump($options, ' options ++ '.to_string($called_model) .' - '.$resolved_get_json_key);

		// old way
			// path. Class name is called class (ex. component_input_text), not this class (common)
				$path = DEDALO_CORE_PATH .'/'. $called_model .'/'. $called_model .'_json.php';
					// dump($resolved_get_json_key, ' show path ++ HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH '.$called_model .' - '. $called_tipo.' - '. ($options->get_context===true ? 'context' : '' ) . ' ' .($options->get_data===true ? 'data' : '' ) );

			// controller include
				$json = include( $path );

		// new way
			// $json = new stdClass();
			// 	if (true===$options->get_context) {
			// 		$json->context = $this->get_context($options);
			// 	}
			// 	if (true===$options->get_data) {
			// 		$json->data = $this->get_data($options);
			// 	}

		// Debug
			if(SHOW_DEBUG===true) {
				$exec_time = exec_time_unit($start_time,'ms')." ms";
				#$element = json_decode($json);
				#	$element->debug = new stdClass();
				#	$element->debug->exec_time = $exec_time;
				#$json = json_encode($element, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
				$json->debug = new stdClass();
					$json->debug->exec_time = $exec_time;

					if (strpos($called_model, 'component_')!==false && $options->get_data===true && !empty($json->data)) { //

						$current = reset($json->data);
							// $current->debug_time_json	= $exec_time;
							$current->debug_model		= $called_model;
							$current->debug_label		= $this->get_label();
							$current->debug_mode		= $this->get_modo();
						#$bt = debug_backtrace()[0];
						#	dump($json->data, ' json->data ++ '.to_string($bt));
					}
			}

		// cache context
			// if ($options->get_data===false) $resolved_get_json[$resolved_get_json_key] = $json;


		return $json;
	}//end get_json



	/**
	* GET_CONTEXT
	* @return array $context
	*/
	public function get_context__DES($options) {

		$called_model = get_class($this); // get_called_class(); // static::class

		// context and subcontext from API dd_request if already exists sections
			if ($called_model!=='sections' && isset(dd_core_api::$dd_request)) {

				// get request_ddo object
					$dd_request		= dd_core_api::$dd_request;
					$request_ddo	= array_find($dd_request, function($item){
						return $item->typo==='request_ddo';
					});

				// when no empty request_ddo->value
					if ($request_ddo && !empty($request_ddo->value)) {
						// $context = array_values(array_filter($request_ddo->value, function($ddo){
						// 	return $ddo->tipo===$this->tipo || $ddo->parent===$this->tipo || $ddo->section_tipo===$this->tipo;
						// }));
						$context = $request_ddo->value;
						dd_core_api::$context_dd_objects = $context;
						return $context; // stop here (!)

						// if (!empty($context)) {
						// 		dump($context, ' context ///////////////////////////////////////////////////////////////////////////////////////////////// '.to_string(get_class($this).' - '.$this->tipo));
						// 	// edit and add to static
						// 		foreach ($context as $element) {
						// 			// $element->parent				= $this->tipo;
						// 			// $element->get_request_config	= $options->get_request_config;

						// 			dd_core_api::$context_dd_objects[] = $element;
						// 		}

						// 	return $context; // stop here (!)
						// }
					}
			}


		// path. Class name is called class (ex. component_input_text), not this class (common)
			$path = DEDALO_CORE_PATH .'/'. $called_model .'/'. $called_model .'_json.php';
				// dump($resolved_get_json_key, ' show path ++ HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH '.$called_model .' - '. $called_tipo.' - '. ($options->get_context===true ? 'context' : '' ) . ' ' .($options->get_data===true ? 'data' : '' ) );

		// controller include
			$json = include( $path );

		return $json->context;
	}//end get_context



	/**
	* GET_DATA
	* @return array $data
	*/
	public function get_data__DES($options) {

		$called_model = get_class($this); // get_called_class(); // static::class

		// path. Class name is called class (ex. component_input_text), not this class (common)
			$path = DEDALO_CORE_PATH .'/'. $called_model .'/'. $called_model .'_json.php';
				// dump($resolved_get_json_key, ' show path ++ HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH '.$called_model .' - '. $called_tipo.' - '. ($options->get_context===true ? 'context' : '' ) . ' ' .($options->get_data===true ? 'data' : '' ) );

		// controller include
			$json = include( $path );


		return $json->data;
	}//end get_data



	/**
	* GET_STRUCTURE_CONTEXT
	* @return object $dd_object
	*/
	public function get_structure_context($permissions=0, $add_request_config=false) {

		// short vars
			$model			= get_class($this);
			$tipo			= $this->get_tipo();
			$section_tipo	= $this->get_section_tipo();
			$translatable	= $this->RecordObj_dd->get_traducible()==='si';
			$mode			= $this->get_modo();
			$label			= $this->get_label();
			$lang			= $this->get_lang();
			$ddo_key		= $section_tipo.'_'.$tipo.'_'.$mode;

		// cache. get from session
			// if(isset($_SESSION['dedalo']['config']['ddo'][$section_tipo][$ddo_key])){
			// 	return $_SESSION['dedalo']['config']['ddo'][$section_tipo][$ddo_key];
			// }

		// properties
			$properties = $this->get_properties() ?? new stdClass();
			// if (empty($properties)) {
			// 	$properties = new stdClass();
			// }

		// css
			$css = new stdClass();
			if (isset($properties->css)) {
				$css = $properties->css;
				// remove from properties object
				unset($properties->css);
			}
			// (!) new. Section overwrite css (virtual sections case)
			if (strpos($model, 'component_')===0) {
				$RecordObj_dd		= new RecordObj_dd($section_tipo);
				$section_properties	= $RecordObj_dd->get_properties();
				if (isset($section_properties->css) && isset($section_properties->css->{$tipo})) {
					$css = $section_properties->css->{$tipo};
				}
			}

		// parent
			// 1 . From requested context
				// if (isset(dd_core_api::$dd_request)) {

				// 	$dd_request		= dd_core_api::$dd_request;
				// 	$request_ddo	= array_find($dd_request, function($item){
				// 		return $item->typo==='request_ddo';
				// 	});

				// 	// ar_dd_objects . Array of all dd objects in requested context
				// 		// $ar_dd_objects = array_values( array_filter($dd_request, function($item) {
				// 		// 	 if($item->typo==='ddo') return $item;
				// 		// }) );
				// 		$ar_dd_objects = $request_ddo
				// 			? $request_ddo->value
				// 			: [];

				// 	if (isset($this->from_parent)) {
				// 		$current_from_parent = $this->from_parent;
				// 		$request_dd_object = array_reduce($ar_dd_objects, function($carry, $item) use($tipo, $section_tipo, $current_from_parent){
				// 			if ($item->tipo===$tipo && $item->section_tipo===$section_tipo && $item->parent===$current_from_parent) {
				// 				return $item;
				// 			}
				// 			return $carry;
				// 		});
				// 	}else{
				// 	 	$request_dd_object = array_reduce($ar_dd_objects, function($carry, $item) use($tipo, $section_tipo){
				// 			if ($item->tipo===$tipo && $item->section_tipo===$section_tipo) {
				// 				return $item;
				// 			}
				// 			return $carry;
				// 		});
				// 	}
				// 	if (!empty($request_dd_object->parent)) {
				// 		// set
				// 		$parent = $request_dd_object->parent;
				// 	}
				// }

			// 1 . From session
				if (isset($_SESSION['dedalo']['config']['ddo'][$section_tipo])) {

					$section_ddo = $_SESSION['dedalo']['config']['ddo'][$section_tipo];

					if (isset($this->from_parent)) {
						$current_from_parent = $this->from_parent;
						$dd_object = array_reduce($section_ddo, function($carry, $item) use($tipo, $section_tipo, $current_from_parent){
							if ($item->tipo===$tipo && $item->section_tipo===$section_tipo && $item->parent===$current_from_parent) {
								return $item;
							}
							return $carry;
						});
					}else{
					 	$dd_object = array_reduce($section_ddo, function($carry, $item) use($tipo, $section_tipo){
							if ($item->tipo===$tipo && $item->section_tipo===$section_tipo) {
								return $item;
							}
							return $carry;
						});
					}
					if (!empty($dd_object->parent)) {
						// set
						$parent = $dd_object->parent;
					}
				}

			// 2 . From injected 'from_parent'
				if (!isset($parent) && isset($this->from_parent)) {

					// injected by the element
					$parent = $this->from_parent;
				}

			// 3 . From structure (fallback)
				if (!isset($parent)) {

					// use section tipo as parent
					$parent = $this->get_section_tipo();
				}

			// 4 . From structure (area case)
				if (empty($parent)) {

					// use structure term tipo as parent
					$parent = $this->RecordObj_dd->get_parent();
				}


		// parent_grouper (structure parent)
			$parent_grouper = !empty($this->parent_grouper)
				? $this->parent_grouper
				: $this->RecordObj_dd->get_parent();


		// tools
			$tools = array_map(function($item){

				// $label = array_reduce($item->label, function($carry, $el){
				// 	return ($el->lang===DEDALO_DATA_LANG) ? $el->value : $carry;
				// }, null);
				$label = array_find($item->label, function($el){
					return $el->lang===DEDALO_DATA_LANG;
				})->value ?? reset($item->label)->value;

				//dump($label, ' label ++ '.to_string());

				$tool = new stdClass();
					$tool->section_id			= $item->section_id;
					$tool->section_tipo			= $item->section_tipo;
					$tool->name					= $item->name;
					$tool->label				= $label;
					$tool->icon					= DEDALO_TOOLS_URL . '/' . $item->name . '/img/icon.svg';
					$tool->show_in_inspector	= $item->show_in_inspector;
					$tool->show_in_component	= $item->show_in_component;

				return $tool;
			}, $this->get_tools());


		// request_config
			$request_config = $add_request_config===true
				? ($this->build_request_config() ?? [])
				:  null;


		// dd_object
			$dd_object = new dd_object((object)[
				'label'				=> $label, // *
				'tipo'				=> $tipo,
				'section_tipo'		=> $section_tipo, // *
				'model'				=> $model, // *
				'parent'			=> $parent, // *
				'parent_grouper'	=> $parent_grouper,
				'lang'				=> $lang,
				'mode'				=> $mode,
				'translatable'		=> $translatable,
				'properties'		=> $properties,
				'css'				=> $css,
				'permissions'		=> $permissions,
				'tools'				=> $tools,
				'request_config'	=> $request_config
			]);


		// optional properties		
			// Filter_by_list
				if (isset($properties->source->filter_by_list)) {
					// Calculate ar elements to show in filter. Resolve self section items
						$filter_list = array_map(function($item){
							$item->section_tipo = ($item->section_tipo==='self')
								? $this->section_tipo
								: $item->section_tipo;
							return $item;
						}, $properties->source->filter_by_list);

					$filter_by_list = component_relation_common::get_filter_list_data($filter_list);
					$dd_object->filter_by_list = $filter_by_list;
				}
			// search operators info (tool tips)
				if ($mode==='search' && strpos($model, 'component_')===0) {
					$dd_object->search_operators_info	= $this->search_operators_info();
					$dd_object->search_options_title	= search::search_options_title($dd_object->search_operators_info);
				}


		// cache. fix context dd_object
			// $_SESSION['dedalo']['config']['ddo'][$section_tipo][$ddo_key] = $dd_object;
			// write_session_value(['config','ddo',$section_tipo,$ddo_key], $dd_object);


		return $dd_object;
	}//end get_structure_context



	/**
	* GET_STRUCTURE_CONTEXT_simple
	* @return object $dd_object
	*/
	public function get_structure_context_simple($permissions=0, $add_request_config=false) {

		$full_ddo = $this->get_structure_context($permissions, $add_request_config);

		// dd_object
			// $dd_object = new dd_object((object)[
			// 	'label'			=> $full_ddo->label,
			// 	'tipo'			=> $full_ddo->tipo,
			// 	'section_tipo'	=> $full_ddo->section_tipo,
			// 	'model'			=> $full_ddo->model,
			// 	'parent'		=> $full_ddo->parent,
			// 	'lang'			=> $full_ddo->lang,
			// 	'mode'			=> $full_ddo->mode,
			// 	'translatable'	=> $full_ddo->translatable,
			// 	'permissions'	=> $full_ddo->permissions,
				
			// ]);


		return $full_ddo;
	}//end get_structure_context_simple



	/**
	* GET_AR_SUBCONTEXT
	* @return array $ar_subcontext
	*/
	public function get_ar_subcontext_DES($from_parent=null, $from_parent_grouper=null) {

		$ar_subcontext = [];

		// already_calculated
			static $ar_subcontext_calculated = [];

		// (!) En proceso: eliminar var $this->request_ddo_value y buscar en un único cajón '$this->request_ddo_value'. Por acabar ..
		$request_ddo_value = array_filter($this->request_ddo_value, function($item){
		// $request_ddo_value = array_filter(dd_core_api::$request_ddo_value, function($item){
			if (empty($item)) {
				// dump($this->request_ddo_value, ' EMPTY ITEM FOUND IN this->request_ddo_value ++ '.to_string());
				// debug_log(__METHOD__." EMPTY ITEM FOUND IN this->request_ddo_value  ".to_string($this->request_ddo_value), logger::DEBUG);
				return false;
			}
			$section_tipo = $this->get_section_tipo() ?? $this->tipo;
			return ($item->config_type==='show' && $item->tipo!==$this->tipo && $item->typo==='ddo' && $item->tipo!==$section_tipo);
		});
		// dump($this->request_ddo_value, ' this->request_ddo_value ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ '.$this->get_section_tipo().' - '.$this->tipo.' - '. get_called_class() );
		// dump(dd_core_api::$request_ddo_value, 'dd_core_api::$request_ddo_value ++**************************************************************************'. count(dd_core_api::$request_ddo_value).' '.to_string($this->tipo));
		// dump($this->request_ddo_value, '$this->request_ddo_value ++****************************************************************************************'. count($this->request_ddo_value).' '.to_string($this->tipo));
		// dump($request_ddo_value, ' $request_ddo_value +------///////---------+ '.$this->get_section_tipo().' - '.$this->tipo.' - '. get_called_class() );


			// subcontext from layout_map items
				// $layout_map_options = new stdClass();
				// 	$layout_map_options->tipo					= $this->get_tipo();
				// 	$layout_map_options->section_tipo			= $this->get_section_tipo();
				// 	$layout_map_options->modo					= $records_mode;
				// 	$layout_map_options->add_section			= false;
				// 	$layout_map_options->request_config_type	= 'show';

				// $layout_map = layout_map::get_layout_map($layout_map_options, $request_config);
				// 	// dump($layout_map, ' layout_map ++ '.$this->tipo." ".to_string() );
				// 	// dump(debug_backtrace()[3], 'debug_backtrace()[3] ++ '.to_string());
			if(!empty($request_ddo_value)) foreach($request_ddo_value as $dd_object) {

				if($dd_object->model==='section'){
					if (!dd_object::in_array_ddo($dd_object, dd_core_api::$context_dd_objects, ['model','tipo','section_tipo','mode'])) {
						$ar_subcontext[] = $dd_object;
						dd_core_api::$context_dd_objects[] = $dd_object;
					}
					continue;
				}

				// if ($dd_object->tipo===$this->tipo || $dd_object->model==='section')  { // || $dd_object->model==='section'
					// continue; // self exclude
				// }
				// dump($dd_object, ' $dd_object +/////--------///////////+ '.to_string($this->tipo));
				// dump(dd_core_api::$context_dd_objects, ' dd_core_api::$context_dd_objects +---------********----------+ '.to_string());


				// short vars
					$dd_object				= (object)$dd_object;
					$current_tipo			= $dd_object->tipo;
					$current_section_tipo	= $dd_object->section_tipo;
					$mode					= $dd_object->mode ?? $this->get_modo();
					$model					= $dd_object->model; //RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);

				// ar_subcontext_calculated
					$cid = $current_tipo . '_' . $current_section_tipo;
					if (in_array($cid, $ar_subcontext_calculated)) {
						debug_log(__METHOD__." Error Processing Request. Already calculated! ".$cid .to_string(), logger::ERROR);
						// throw new Exception("Error Processing Request. Already calculated! ".$cid, 1);
					}

				// common temporal excluded/mapped models *******
					// $match_key = array_search($model, common::$ar_temp_map_models);
					// if (false!==$match_key) {
					// 	debug_log(__METHOD__." +++ Mapped model $model to $match_key from layout map ".to_string(), logger::WARNING);
					// 	$model = $match_key;
					// }else if (in_array($model, common::$ar_temp_exclude_models)) {
					// 	debug_log(__METHOD__." +++ Excluded model $model from layout map ".to_string(), logger::WARNING);
					// 	continue;
					// }

				switch (true) {
					// component case
					case (strpos($model, 'component_')===0):

						$current_lang		= $dd_object->lang ?? common::get_element_lang($current_tipo, DEDALO_DATA_LANG);
						$related_element	= component_common::get_instance($model,
																			 $current_tipo,
																			 null,
																			 $mode,
																			 $current_lang,
																			 $current_section_tipo);
						break;

					// grouper case
					case (in_array($model, common::$groupers)):
						// $grouper_model		= ($model==='section_group_div') ? 'section_group' : $model;
						$related_element	= new $model($current_tipo, $current_section_tipo, $mode);
						break;

					// others case
					default:
						debug_log(__METHOD__ ." Ignored model '$model' - current_tipo: '$current_tipo' ".to_string(), logger::WARNING);
						break;
				}

				// add
					if (isset($related_element)) {

						// Inject var from_parent as from_parent
							if (isset($from_parent)) {
								$related_element->from_parent = $from_parent;
							}

						// parent_grouper
							if (isset($parent_grouper)) {
								$related_element->parent_grouper = $parent_grouper;
							}

						// get the JSON context of the related component
							$item_options = new stdClass();
								$item_options->get_context	= true;
								$item_options->get_data		= false;
							$element_json = $related_element->get_json($item_options);

						// temp ar_subcontext
							$ar_subcontext = array_merge($ar_subcontext, $element_json->context);
				}

				// add calculated subcontext
					$ar_subcontext_calculated[] = $cid;

			}//end foreach ($layout_map as $section_tipo => $ar_list_tipos) foreach ($ar_list_tipos as $current_tipo)


		return $ar_subcontext;
	}//end get_ar_subcontext



	/**
	* GET_AR_SUBCONTEXT
	* @return array $ar_subcontext
	*/
	public function get_ar_subcontext($from_parent=null, $from_parent_grouper=null) {
		// dump(null, ' get_ar_subcontext call this **************************** '.to_string($this->tipo).' - $from_parent: '.$from_parent);

		$ar_subcontext = [];

		// already_calculated
			static $ar_subcontext_calculated = [];
		
		// request_config
			$request_config = $this->request_config ?? null;
			if(empty($request_config)) {
				return null;
			}

		// select api_engine dedalo only configs
			$request_config_dedalo = array_filter($request_config, function($el){
				return $el->api_engine==='dedalo';
			});
		
		// children_resursive function
			if (!function_exists('get_children_resursive')) {
				function get_children_resursive($ar_ddo, $dd_object) {
					$ar_children = [];

					foreach ($ar_ddo as $ddo) {
						if($ddo->parent===$dd_object->tipo) {
							$ar_children[] = $ddo;
							$result = get_children_resursive($ar_ddo, $ddo);
							if (!empty($result)) {
								$ar_children = array_merge($ar_children, $result);
							}
						}
					}
					
					return $ar_children;
				}
			}

		foreach ($request_config_dedalo as $request_config_item) {

			// skip empty ddo_map
				if(empty($request_config_item->show->ddo_map)) {
					debug_log(__METHOD__." Ignored empty show ddo_map in request_config_item:".to_string($request_config_item), logger::ERROR);
					continue;
				}

			foreach($request_config_item->show->ddo_map as $dd_object) {
				
				// prevent resolve non children from path ddo
					if (isset($dd_object->parent) && $dd_object->parent!==$this->tipo) {
						// dump($dd_object, ' dd_object SKIP dd_object ++ '.to_string($this->tipo));
						continue;
					}

				// skip security_areas
					if($dd_object->tipo===DEDALO_COMPONENT_SECURITY_AREAS_PROFILES_TIPO) {
						continue; //'component_security_areas' removed in v6 but the component will stay in ontology, PROVISIONAL, only in the alpha state of V6 for compatibility of the ontology of V5.
					}
			
				// short vars
					$current_tipo				= $dd_object->tipo;
					$ar_current_section_tipo	= $dd_object->section_tipo ?? $dd_object->tipo;
					$mode						= $dd_object->mode ?? $this->get_modo();
					$model						= RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
					$label						= $dd_object->label;

				// current_section_tipo
					$current_section_tipo = is_array($ar_current_section_tipo)
						? reset($ar_current_section_tipo)
						: $ar_current_section_tipo;

				// ar_subcontext_calculated
					$cid = $current_tipo . '_' . $current_section_tipo;
					if (in_array($cid, $ar_subcontext_calculated)) {
						debug_log(__METHOD__." Error Processing Request. Already calculated! ".$cid .to_string(), logger::ERROR);
						// throw new Exception("Error Processing Request. Already calculated! ".$cid, 1);
					}

				// common temporal excluded/mapped models *******					
					$match_key = array_search($model, common::$ar_temp_map_models);
					if (false!==$match_key) {
						debug_log(__METHOD__." +++ Mapped model $model to $match_key from layout map ".to_string(), logger::WARNING);
						$model = $match_key;
					}else if (in_array($model, common::$ar_temp_exclude_models)) {
						debug_log(__METHOD__." +++ Excluded model $model from layout map ".to_string(), logger::WARNING);
						continue;
					}

				// related_element switch
					switch (true) {
						// component case
						case (strpos($model, 'component_')===0):

							$current_lang		= $dd_object->lang ?? common::get_element_lang($current_tipo, DEDALO_DATA_LANG);
							$related_element	= component_common::get_instance($model,
																				 $current_tipo,
																				 null,
																				 $mode,
																				 $current_lang,
																				 $current_section_tipo);
							// virtual request_config
								$children = get_children_resursive($request_config_item->show->ddo_map, $dd_object);
								// dump($children, ' children +++++++++++++++++++++++++++++++++++++++++++++++++ '.to_string($dd_object->tipo));	
									// dump($request_config_item->show->ddo_map, ' $request_config_item->show->ddo_map ++ '.to_string($current_tipo));								
								if (!empty($children)) {
									$new_rqo_config = unserialize(serialize($request_config_item));
									$new_rqo_config->show->ddo_map = $children;
								
									$related_element->request_config = [$new_rqo_config];
								}
							break;				

						// grouper case
						case (in_array($model, common::$groupers)):
							$related_element = new $model($current_tipo, $current_section_tipo, $mode);
							break;

						// time machine id case
						// case ($tipo='dd784'): #id_matrix
						// 	$related_element	= component_common::get_instance('component_section_id',
						// 														 $current_tipo,
						// 														 null,
						// 														 $mode,
						// 														 DEDALO_DATA_NOLAN,
						// 														 $current_section_tipo);
						// 	break;

						// others case
						default:
							debug_log(__METHOD__ ." Ignored model '$model' - current_tipo: '$current_tipo' ".to_string(), logger::WARNING);
							break;
					}//end switch (true)

				// add
					if (isset($related_element)) {

						// Inject var from_parent as from_parent
							if (isset($from_parent)) {
								$related_element->from_parent = $from_parent;
							}

						// parent_grouper
							if (isset($parent_grouper)) {
								$related_element->parent_grouper = $parent_grouper;
							}

						// get the JSON context of the related component
							$item_options = new stdClass();
								$item_options->get_context	= true;
								$item_options->get_data		= false;
								// $item_options->context_type = 'simple';
							$element_json = $related_element->get_json($item_options);

						// temp ar_subcontext
							if (is_null($ar_subcontext)) {
								$bt = debug_backtrace();
								dump($bt, ' ar_subcontext bt ++ '.to_string($current_tipo));
							}
							$ar_subcontext = array_merge($ar_subcontext, $element_json->context);
					}

				// add calculated subcontext
					$ar_subcontext_calculated[] = $cid;

			}//end foreach ($layout_map as $section_tipo => $ar_list_tipos) foreach ($ar_list_tipos as $current_tipo)
		}//end foreach ($request_config_dedalo as $request_config_item)
		// dump($ar_subcontext, ' ar_subcontext ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ '.to_string($this->tipo)); //die();
			
		return $ar_subcontext;
	}//end get_ar_subcontext



	/**
	* GET_AR_SUBDATA
	* @param array $ar_locators
	* @return array $ar_subcontext
	*/
	public function get_ar_subdata($ar_locators) {
		
		$ar_subdata = [];

		$source_model	= get_called_class();
		$all_ar_ddo		= isset(dd_core_api::$ddo_map)
			? dd_core_api::$ddo_map
			: null;

		// filter 
			$ar_ddo = array_filter($all_ar_ddo, function($el){
				return $el->parent===$this->tipo;
			});
				// dump($ar_ddo, ' ar_ddo ++ '.to_string($this->tipo));
				// dump($ar_locators, '$ar_locators ++ '.to_string());

		// des
			// dump($context_dd_objects, ' context_dd_objects ++ '.to_string());			
			// $ar_ddo = [];
			// foreach ($context_dd_objects as $current_ar_ddo) {
			// 	foreach ($current_ar_ddo as $ddo) {
			// 		$ar_ddo[] = $ddo;
			// 	}
			// }
			// dump($ar_ddo, ' ar_ddo ++ '.to_string());

			// // source. calculate context if not already calculated
			// 	$source = array_find($context_dd_objects, function($item) {
			// 		return $item->tipo===$this->tipo;
			// 	});
			// 	if (empty($source)) {
			// 		// context (force calculate)
			// 			$json_options = new stdClass();
			// 				$json_options->get_context	= true;
			// 				$json_options->get_data		= false;
			// 			$context = $this->get_json($json_options)->context;
			// 		// source
			// 			$source = array_find($context, function($item) {
			// 				return $item->tipo===$this->tipo;
			// 			});
			// 	}

			// // ar_ddo . Filter from precalculated contex
			// 	$ar_ddo = array_values(array_filter($context_dd_objects, function($item){
			// 		return $item->parent===$this->tipo && $item->type==='component';
			// 	}));
			// dump($ar_ddo, ' SUBDATA ar_ddo '.$this->tipo.'++ ********************************************************************************************** '.to_string($this->tipo));

		if(!empty($ar_ddo)) foreach($ar_locators as $current_locator) {

			// check locator format
				if (!is_object($current_locator)) {
					if(SHOW_DEBUG===true) {
						dump($current_locator, ' current_locator ++ '.to_string());
						dump($ar_locators, ' ar_locators ++ '.to_string());
						throw new Exception("Error Processing Request. current_locator is not an object", 1);
					}
					continue;
				}

			$section_id		= $current_locator->section_id;
			$section_tipo	= $current_locator->section_tipo;
			
			foreach ((array)$ar_ddo as $dd_object) {
					
				$ar_section_tipo = is_array($dd_object->section_tipo) ? $dd_object->section_tipo : [$dd_object->section_tipo];
				if (!in_array($section_tipo, $ar_section_tipo)) {
					continue; // prevents multisection duplicate items
				}

				$current_tipo	= $dd_object->tipo;
				$mode			= $dd_object->mode ?? 'edit'; // $records_mode;
				$model			= $dd_object->model ?? RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
				$current_lang	= $dd_object->lang ?? common::get_element_lang($current_tipo, DEDALO_DATA_LANG);

				// common temporal excluded/mapped models *******
					// if($current_tipo === DEDALO_COMPONENT_SECURITY_AREAS_PROFILES_TIPO) continue; //'component_security_areas' removed in v6 but the component will stay in ontology, PROVISIONAL, only in the alpha state of V6 for compatibility of the ontology of V5.

					// $match_key = array_search($model, common::$ar_temp_map_models);
					// if (false!==$match_key) {
					// 	debug_log(__METHOD__." +++ Mapped model $model to $match_key from layout map ".to_string(), logger::WARNING);
					// 	$model = $match_key;
					// }else if (in_array($model, common::$ar_temp_exclude_models)) {
					// 	debug_log(__METHOD__." +++ Excluded model $model from layout map ".to_string(), logger::WARNING);
					// 	continue;
					// }				
					
				switch (true) {

					// section case
					case ($model==='section'):

						$datos = isset($current_locator->datos) ? json_decode($current_locator->datos) : null;

						// section
							$section = section::get_instance($section_id, $section_tipo, $mode, $cache=true);
							if (!is_null($datos)) {
								$section->set_dato($datos);
								$section->set_bl_loaded_matrix_data(true);
							}

						// get component json
							$get_json_options = new stdClass();
								$get_json_options->get_context 	= false;
								$get_json_options->get_data 	= true;
							$element_json = $section->get_json($get_json_options);
						break;

					// components case
					case (strpos($model, 'component_')===0):

						// // components
						// 	$current_component  = component_common::get_instance($model,
						// 														 $current_tipo,
						// 														 $section_id,
						// 														 $mode,
						// 														 $current_lang,
						// 														 $section_tipo);
						// // properties
						// 	// if (isset($dd_object->properties)){
						// 	// 	$current_component->set_properties($dd_object->properties);
						// 	// }
						// // Inject this tipo as related component from_component_tipo
						// 	if (strpos($source_model, 'component_')===0){
						// 		$current_component->from_component_tipo = $this->tipo;
						// 		$current_component->from_section_tipo 	= $this->section_tipo;
						// 	}

						// // get component json
						// 	$get_json_options = new stdClass();
						// 		$get_json_options->get_context 	= false;
						// 		$get_json_options->get_data 	= true;
						// 	$element_json = $current_component->get_json($get_json_options);

						// component_subdata is calculated in a separated function to allow override it in time machine mode
						$element_json = $this->build_component_subdata($model, $current_tipo, $section_id, $section_tipo, $mode, $current_lang, $source_model);
						break;

					// grouper case
					case (in_array($model, common::$groupers)):

						// $grouper_model		= ($model==='section_group_div') ? 'section_group' : $model;
						$related_element	= new $model($current_tipo, $section_tipo, $mode);

						// inject section_id
							$related_element->section_id = $section_id;

						// get component json
							$get_json_options = new stdClass();
								$get_json_options->get_context 	= false;
								$get_json_options->get_data 	= true;
							$element_json = $related_element->get_json($get_json_options);
						break;

					// oters
					default:
						# not defined model from context / data
						debug_log(__METHOD__." Ignored model '$model' - current_tipo: '$current_tipo' ".to_string(), logger::WARNING);
						break;
				}

				if (isset($element_json)) {

					// row_section_id
					// add parent_section_id with the main locator section_id that define the row, to perserve row coherence between all columns
					// (some columns can has other portals or subdata and it's necesary preserve the root locator section_id)
					// add parent_tipo with the caller tipo, it define the global context (portal or section) that are creating the rows.
						$ar_final_subdata = [];
						foreach ($element_json->data as $key => $value_obj) {
							$value_obj->row_section_id	= $section_id;
							$value_obj->parent_tipo		= $this->tipo;
							$ar_final_subdata[] = $value_obj;
						}

					//dd_info, additional information to the component, like parents
						$value_with_parents = $dd_object->value_with_parents ?? false;
						if ($value_with_parents===true) {
							$dd_info = common::get_ddinfo_parents($current_locator, $this->tipo);
							$ar_final_subdata[] = $dd_info;
						}

					// data add
						$ar_subdata = array_merge($ar_subdata, $ar_final_subdata);
					// data add
						#$ar_subdata[] = $element_json->data;
				}

			}//end iterate display_items

			// dd_info, additional information about row
				// // rqo. (Request Query Object)
				// 	$request_config = $source->request_config; 	dump($request_config, ' request_config ++ '.to_string($this->tipo));
				// 	$rqo = array_find($request_config, function($item) {
				// 		return $item->typo==='rqo';
				// 	});
				// // value_with_parents. Check optional API request (for example, from service_autocomplete)
				// 	$value_with_parents_object = array_find(dd_core_api::$dd_request, function($element){
				// 		return $element->typo==='value_with_parents';
				// 	});
				// 	$value_with_parents = ($value_with_parents_object)
				// 		? ($value_with_parents_object->value ?? false) // from request objrct
				// 		: (($rqo && isset($rqo->show))
				// 			? ($rqo->show->value_with_parents ?? false)// from properties source->request_config->show
				// 			: false);
				// 	if ($value_with_parents===true) {
				// 		$dd_info = common::get_ddinfo_parents($current_locator, $this->tipo);
				// 		$ar_subdata[] = $dd_info;
				// 	}

		}//end foreach ($ar_locators as $current_locator)


		return $ar_subdata;
	}//end get_ar_subdata



	/**
	* BUILD_COMPONENT_SUBDATA
	* @return object $element_json
	*/
	public function build_component_subdata($model, $tipo, $section_id, $section_tipo, $mode, $lang, $source_model, $custom_dato='no_value') {

		// components
			$current_component  = component_common::get_instance($model,
																 $tipo,
																 $section_id,
																 $mode,
																 $lang,
																 $section_tipo);
		// properties
			// if (isset($dd_object->properties)){
			// 	$current_component->set_properties($dd_object->properties);
			// }
		// Inject this tipo as related component from_component_tipo
			if (strpos($source_model, 'component_')===0){
				$current_component->from_component_tipo = $this->tipo;
				$current_component->from_section_tipo 	= $this->section_tipo;
			}

		// inject dato if is received
			if ($custom_dato!=='no_value') {
				$current_component->set_dato($custom_dato);
			}

		// get component json
			$get_json_options = new stdClass();
				$get_json_options->get_context 	= false;
				$get_json_options->get_data 	= true;
			$element_json = $current_component->get_json($get_json_options);

		// dd_info, additional information to the component, like parents
			// $value_with_parents = $dd_object->value_with_parents ?? false;
			// if ($value_with_parents===true) {
			// 	$dd_info = common::get_ddinfo_parents($locator, $this->tipo);
			// 	$ar_subdata[] = $dd_info;
			// }
		

		// dump($element_json, ' element_json ++ '.to_string("$model, $tipo, $section_id, $section_tipo, $mode, $lang, $source_model - dato: ") . to_string($dato));

		return $element_json;
	}//end build_component_subdata



	/**
	* BUILD_RERQUEST_CONFIG
	* Calculate the sqo for the components or section that need search by own (section, autocomplete, portal, ...)
	* The search_query_object_context (request_config) have at least:
	* one sqo, that define the search with filter, offest, limit, etc, the select option is not used (it will use the ddo)
	* one ddo for the searched section (source ddo)
	* one ddo for the component searched.
	* 	is possible create more than one ddo for different components.
	* @return array | json
	*/
	public function build_request_config() {

		if (isset($this->request_config)) {
			return $this->request_config;
		}

		if(SHOW_DEBUG===true) {
			$idd = $this->tipo . ' ' . RecordObj_dd::get_modelo_name_by_tipo($this->tipo,true);
			// dump($idd, ' idd ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ '.to_string($this->modo));
		}
		
		$requested_source = dd_core_api::$rqo->source ?? false;		
		if($requested_source) { // && $requested_source->tipo===$this->tipo
			
			// set the request_config with the API rqo sent by client
				
			// requested_show. get the rqo sent to the API
			$requested_show = isset(dd_core_api::$rqo) && isset(dd_core_api::$rqo->show)
				? unserialize(serialize(dd_core_api::$rqo->show))
				: false;

			if (!empty($requested_show)) {
					
				// consolidate ddo items properties
					foreach ($requested_show->ddo_map as $key => $current_ddo) {
						//get the direct ddo linked by the source
						if ($current_ddo->parent===$requested_source->tipo || $current_ddo->parent==='self') {
							// check if the section_tipo of the current_ddo, is compatible with the section_tipo of the current instance
							if(in_array($this->tipo, (array)$current_ddo->section_tipo) || $current_ddo->section_tipo==='self')
								$current_ddo->parent		= $this->tipo;
								$current_ddo->section_tipo	= $this->tipo;
						}
						// added label & mode if not are already defined
						if(!isset($current_ddo->label)) {
							$current_ddo->label = RecordObj_dd::get_termino_by_tipo($current_ddo->tipo, DEDALO_APPLICATION_LANG, true, true);
						}
						if(!isset($current_ddo->mode)) {
							$current_ddo->mode = $this->mode;
						}
					}//end oreach ($requested_show->ddo_map as $key => $current_ddo)

				// create the new request_config with the caller
					$request_config = new stdClass();
						$request_config->api_engine	= 'dedalo';
						$request_config->show		= $requested_show;

					// sqo add
						if (isset(dd_core_api::$rqo->sqo)) {
							$sqo = unserialize(serialize(dd_core_api::$rqo->sqo));
							$sqo->section_tipo = array_map(function($el){
								return (object)[
									'tipo' => $el
								];
							}, $sqo->section_tipo);
							$request_config->sqo = $sqo;
						}

					$this->request_config = [$request_config];

				// merge ddo elements
					dd_core_api::$ddo_map = array_merge(dd_core_api::$ddo_map, $request_config->show->ddo_map);
					// dump($this->request_config, ' this->request_config +--------------------------------+ '.to_string($this->tipo));
					// dump(dd_core_api::$ddo_map, 'dd_core_api::$ddo_map ++ '.to_string());
				
				return $this->request_config; // we have finished ! Note we stop here (!)
			}//end if (!empty($requested_show))
		}//end if(!empty($requested_show))


		// $records_mode	= $this->get_records_mode();
		$mode				= $this->get_modo();
		$tipo				= $this->get_tipo();
		$section_tipo		= $this->get_section_tipo();
		$section_id			= $this->get_section_id();

		// 1. From user preset
			$user_preset = layout_map::search_user_preset_layout_map($tipo, $section_tipo, navigator::get_user_id(), $mode, null);
				// dump($user_preset, ' user_preset ++ '." tipo:$tipo, section_tipo:$section_tipo, user_id:navigator::get_user_id(), mode:$mode ".to_string());
			if (!empty($user_preset)) {
				
				$request_config = $user_preset;

				// $request_config = array_filter($user_preset, function($item){
				// 	return $item->typo==='rqo';
				// });
				// dump($request_config, ' request_config ++ [1] '.to_string());
				debug_log(__METHOD__." request_query_objects calculated from user preset [$section_tipo-$tipo] ", logger::DEBUG);
			}

		// 2. From structure
			if (empty($request_config)) {

				$options = new stdClass();
					$options->tipo			= $tipo;
					$options->external		= false;
					$options->section_tipo	= $section_tipo;
					$options->mode			= $mode;
					$options->section_id	= $section_id;
				
				$request_config = common::get_ar_request_config($options);
					// dump($request_config, ' request_config [2] ++ '.to_string($this->tipo));
			}
		

		// request_config value
			// $request_config = array_merge([$source], $request_config);
			// fix request_config value
				$this->request_config = $request_config;

			// ddo_map (dd_core_api static var)
				$dedalo_request_config = array_find($request_config, function($el){
					return $el->api_engine==='dedalo';
				});
				if (!empty($dedalo_request_config)) {

					// sqo. Preserves filter across calls using session sqo if exists
						$model	= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
						$sqo_id	= implode('_', [$model,$section_tipo]);
						if ($model==='section' && isset($_SESSION['dedalo']['config']['sqo'][$sqo_id])) {
							// replace default sqo whith the already stored in session (except section_tipo to void loose labels and limit to avoid overwrite list in edit and viceversa)
							foreach ($_SESSION['dedalo']['config']['sqo'][$sqo_id] as $key => $value) {
								if($key==='section_tipo' || $key==='limit') continue;
								$dedalo_request_config->sqo->{$key} = $value;
							}
							// dump($dedalo_request_config->sqo->filter, ' dedalo_request_config->sqo->filter ++++++++++ CHANGED !!!!!!!!!!!!!!!! '.to_string($sqo_id));
						}

					// add ddo_map
						dd_core_api::$ddo_map = array_merge(dd_core_api::$ddo_map, $dedalo_request_config->show->ddo_map);
				}				
		// des
			// // request_ddo. Insert into the global dd_objects storage the current dd_objects that will needed
			// 	// received request_ddo
			// 		$request_ddo = array_find($dd_request, function($item) {
			// 			return $item->typo==='request_ddo';
			// 		});
			// 	// not received request_ddo
			// 		if(empty($request_ddo)) {
			// 			// preset request_ddo
			// 				if (!isset($user_preset)) {
			// 					$user_preset = layout_map::search_user_preset($tipo, $section_tipo, navigator::get_user_id(), $mode, null);
			// 				}
			// 				if (!empty($user_preset)) {
			// 					$request_ddo = array_find($user_preset, function($item){
			// 						return $item->typo==='request_ddo';
			// 					});
			// 				}

			// 			// calculated request_ddo
			// 				if (empty($request_ddo)) {
			// 					$request_ddo = $this->get_request_ddo();
			// 				}
			// 		}

			// 	// fix request_ddo_value for current element
			// 		$this->request_ddo_value = $request_ddo->value;

			// 	// add non existent ddo's to static var dd_core_api::$request_ddo_value
			// 		foreach ($request_ddo->value as $ddo) {
			// 			if (!dd_object::in_array_ddo($ddo, dd_core_api::$request_ddo_value, ['model','tipo','section_tipo','mode','lang', 'parent','typo','type'])) {
			// 				dd_core_api::$request_ddo_value[] = $ddo;
			// 			}
			// 		}


		return $request_config;
	}//end build_rqo



	/**
	* GET_REQUEST_PROPERTIES_PARSED
	* Resolves the component config context with backward compatibility
	* The proper config in v6 is on term properties config, NOT as related terms
	* Note that section tipo 'self' will be replaced by argument '$section_tipo'
	* @param string $tipo
	*	component tipo
	* @param bool $external
	*	optional default false
	* @param string $section_tipo
	*	optional default null
	* @return object $request_config
	*/
	public static function get_ar_request_config($request_options) {

		$options = new stdClass();
			$options->tipo			= null;
			$options->external		= false;
			$options->section_tipo	= null;
			$options->mode			= 'list';
			$options->section_id	= null;
			foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}

		// options
			$tipo			= $options->tipo;
			$external		= $options->external;
			$section_tipo	= $options->section_tipo;
			$mode			= $options->mode;
			$section_id		= $options->section_id;

		// debug
			// if (to_string($section_tipo)==='self') {
			// 	throw new Exception("Error Processing get_request_config (6) unresolved section_tipo:".to_string($section_tipo), 1);
			// }

		// cache
			static $resolved_request_properties_parsed = [];
			$resolved_key = $tipo .'_'. $section_tipo .'_'. (int)$external .'_'. $mode .'_'. $section_id;
			if (isset($resolved_request_properties_parsed[$resolved_key])) {
				return $resolved_request_properties_parsed[$resolved_key];
			}

		$RecordObj_dd	= new RecordObj_dd($tipo);
		$properties		= $RecordObj_dd->get_properties();
		$model			= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);

		// pagination defaults. Note that limit defaults are set on element construction based on properties
			// $limit = $mode==='edit' ? ($model==='section' ? 1 : 5) : 10;			
			$limit = (function() use($model, $tipo, $section_tipo, $mode){
				switch (true) {
					case $model==='section':
						$section = section::get_instance(null, $tipo, $mode, true);
						return $section->pagination->limit;
						break;					
					case strpos($model, 'component_')===0:
						$recordObjdd = new RecordObj_dd($tipo);
						$translatable = $recordObjdd->get_traducible()=== 'si';

						$current_lang = $translatable ? DEDALO_DATA_LANG : DEDALO_DATA_NOLAN;
						$component = component_common::get_instance($model, $tipo, null, $mode, $current_lang, $section_tipo);
						
						return $component->pagination->limit;
						break;
					default:
						break;
				}
				return 10;
			})();
			$offset	= 0;

		$ar_request_query_objects = [];
		if(isset($properties->source->request_config) || $model==='component_autocomplete_hi'){
			// V6, properties request_config is defined

			// fallback component_autocomplete_hi
				// if (!isset($properties->source->request_config) && $model==='component_autocomplete_hi') {
				// 	$properties->source->request_config = json_decode('[
				//            {
				//                "show": {
				//                    "ddo_map": [
				//                        "hierarchy25"
				//                    ],
				//                    "sqo_config": {
				//                        "operator": "$or"
				//                    }
				//                },
				//                "search": {
				//                    "ddo_map": [
				//                        "hierarchy25"
				//                    ],
				//                    "sqo_config": {
				//                        "operator": "$or"
				//                    }
				//                },
				//                "records_mode": "list",
				//                "section_tipo": [
				//                    {
				//                        "value": [
				//                            2
				//                        ],
				//                        "source": "hierarchy_types"
				//                    }
				//                ],
				//                "search_engine": "search_dedalo"
				//            }
				//        ]');
				//        debug_log(__METHOD__." Using default config for non defined source of component '$model' tipo: ".to_string($tipo), logger::ERROR);
				// }//end if (!isset($properties->source->request_config) && $model==='component_autocomplete_hi')

			foreach ($properties->source->request_config as $item_request_config) {

				// if($external===false && $item_request_config->sqo->type==='external') continue; // ignore external

				$parsed_item = new stdClass();
					// $parsed_item->typo = 'rqo';
					// $parsed_item->sqo->tipo = $tipo;

				// api_engine
					$parsed_item->api_engine = isset($item_request_config->api_engine)
						? $item_request_config->api_engine
						: 'dedalo';

				// SQO
					$parsed_item->sqo = $item_request_config->sqo ?? new stdClass();

				// section_tipo. get the ar_sections as ddo
					if (isset($parsed_item->sqo->section_tipo)){
						$ar_section_tipo = component_relation_common::get_request_config_section_tipo($parsed_item->sqo->section_tipo, $section_tipo, $section_id);
					}else{
						$ar_section_tipo = [$section_tipo];
					}

					$parsed_item->sqo->section_tipo = array_map(function($section_tipo){
						$ddo = new dd_object();
							$ddo->set_tipo($section_tipo);
							$ddo->set_label(RecordObj_dd::get_termino_by_tipo($section_tipo, DEDALO_APPLICATION_LANG, true, true));
						return $ddo;
					}, $ar_section_tipo);

				// filter_by_list. get the filter_by_list (to set the prefilter selector)
					if (isset($item_request_config->sqo->filter_by_list)) {
						$parsed_item->sqo->filter_by_list = component_relation_common::get_filter_list_data($item_request_config->sqo->filter_by_list);
					}

				// fixed_filter
					if (isset($item_request_config->sqo->fixed_filter)) {
						$parsed_item->sqo->fixed_filter = component_relation_common::get_fixed_filter($item_request_config->sqo->fixed_filter, $section_tipo, $section_id);
					}

				// show (mandatory) it change when the mode is list, since it is possible to define a different show named show_list
					// dump($mode, ' $mode ++ '.to_string($tipo));
					$parsed_item->show = ($mode==='list' && isset($item_request_config->show_list))
						? $item_request_config->show_list
						: $item_request_config->show;

					// get the all ddo and set the label to every ddo (used for showing into the autocomplete like es1: Spain, fr1: France)
					$ar_ddo_map = $parsed_item->show->ddo_map;
					
					// ddo_map
						$final_ddo_map = [];
						foreach ($ar_ddo_map as $current_ddo_map) {
							if (!isset($current_ddo_map->tipo)) {
								dump($current_ddo_map, ' current_ddo_map don\'t have tipo: ++ '.to_string($tipo));
								continue;
							}
							// add label to ddo_map items
							// $final_ddo_map[] = array_map(function($ddo){
							// 	$ddo->set_label(RecordObj_dd::get_termino_by_tipo($tipo, DEDALO_APPLICATION_LANG, true, true));								
							// 	return $ddo;
							// }, $current_ddo_map);
							$current_ddo_map->label = RecordObj_dd::get_termino_by_tipo($current_ddo_map->tipo, DEDALO_APPLICATION_LANG, true, true);
							
							//set the default "self" value to the current section_tipo (the section_tipo of the parent)
							$current_ddo_map->section_tipo = $current_ddo_map->section_tipo==='self'
								? $ar_section_tipo
								: $current_ddo_map->section_tipo;
							
							//set the default "self" value to the current tipo (the parent)
							$current_ddo_map->parent = $current_ddo_map->parent==='self'
								? $tipo
								: $current_ddo_map->parent;

							$current_ddo_map->mode = isset($current_ddo_map->mode)
								? $current_ddo_map->mode
								: ($model !== 'section'
									? 'list'
									: $mode);

							$final_ddo_map[] = $current_ddo_map;
						}

					$parsed_item->show->ddo_map = $final_ddo_map;

					if (isset($parsed_item->show->sqo_config)) {
						// fallback non defined operator
						if (!isset($parsed_item->show->sqo_config->operator)) {
							$parsed_item->show->sqo_config->operator = '$or';
						}
					}else{
						// fallback non defined sqo_config
						$sqo_config = new stdClass();
							$sqo_config->full_count		= false;
							// $sqo_config->add_select	= false;
							// $sqo_config->direct		= true;
							$sqo_config->limit			= $limit;
							$sqo_config->offset			= $offset;
							$sqo_config->mode			= $mode;
							$sqo_config->operator		= '$or';
						$parsed_item->show->sqo_config = $sqo_config;
					}

				// search
					if (isset($item_request_config->search)) {
						// set item
						$parsed_item->search = $item_request_config->search;
						if (isset($parsed_item->search->sqo_config)) {
							// fallback non defined operator
							if (!isset($parsed_item->search->sqo_config->operator)) {
								$parsed_item->search->sqo_config->operator = '$or';
							}
						}else{
							// fallback non defined sqo_config
							$sqo_config = new stdClass();
								$sqo_config->full_count		= false;
								// $sqo_config->add_select	= false;
								// $sqo_config->direct		= true;
								$sqo_config->limit			= $limit;
								$sqo_config->offset			= $offset;
								$sqo_config->mode			= $mode;
								$sqo_config->operator		= '$or';
							$parsed_item->search->sqo_config = $sqo_config;
						}
					}

				// choose
					if (isset($item_request_config->choose)) {
						$choose_ddo_map = $item_request_config->choose->ddo_map;

						foreach ($choose_ddo_map as $current_ddo_map) {

							$current_ddo_map->section_tipo = $current_ddo_map->section_tipo==='self'
								? $ar_section_tipo
								: $current_ddo_map->section_tipo;

							//set the default "self" value to the current tipo (the parent)
							$current_ddo_map->parent = $current_ddo_map->parent==='self'
								? $tipo
								: $current_ddo_map->parent;

							$final_ddo_map[] = $current_ddo_map;
						}

						// $parsed_item->show->ddo_map = $choose_ddo_map;
						// set item
						$parsed_item->choose = $item_request_config->choose;
					}


				// add parsed item
					$ar_request_query_objects[] = $parsed_item;
						// dump($ar_request_query_objects, ' ar_request_query_objects ++ '.to_string($tipo)); die();
			}

		}else{
			// V5 model

			// if (in_array($model, component_relation_common::get_components_with_relations()) ) {

			switch ($mode) {
				case 'edit':
					if ($model==='section') {
						// section
						$ar_modelo_name_required = ['component_','section_group','section_tab','tab','section_group_relation','section_group_portal','section_group_div'];
						$ar_related = section::get_ar_children_tipo_by_modelo_name_in_section($tipo, $ar_modelo_name_required, $from_cache=true, $resolve_virtual=true, $recursive=true, $search_exact=false, $ar_tipo_exclude_elements=false);
					}elseif (in_array($model, common::$groupers)) {
						// groupers
						$ar_related = (array)RecordObj_dd::get_ar_childrens($tipo);
					}else{
						// components
						$ar_related = (array)RecordObj_dd::get_ar_terminos_relacionados($tipo, $cache=true, $simple=true);
					}
					break;
				case 'list':
				case 'search':
				case 'portal_list':
				default:
					if ($model==='section') {
						# case section list is defined
						$ar_terms = (array)RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($tipo, 'section_list', 'children', true);
						if(isset($ar_terms[0])) {
							# Use found related terms as new list
							$current_term = $ar_terms[0];
							$ar_related   = (array)RecordObj_dd::get_ar_terminos_relacionados($current_term, $cache=true, $simple=true);
						}
					}elseif (in_array($model, common::$groupers)) {
						// groupers
						$ar_related = (array)RecordObj_dd::get_ar_childrens($tipo);
					}else{
						# portal cases
						# case section list is defined
						$ar_terms = (array)RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($tipo, 'section_list', 'children', true);
						if(isset($ar_terms[0])) {
							# Use found related terms as new list
							$current_term = $ar_terms[0];
							$ar_related   = (array)RecordObj_dd::get_ar_terminos_relacionados($current_term, $cache=true, $simple=true);
						}else{
							# Fallback related when section list is not defined; portal case.
							$ar_related = (array)RecordObj_dd::get_ar_terminos_relacionados($tipo, $cache=true, $simple=true);
						}
					}
					break;
			}//end switch ($mode)



			// related_clean
				$ar_related_clean 	 = [];
				$target_section_tipo = $section_tipo;

				foreach ((array)$ar_related as $key => $current_tipo) {
					$current_model = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
					if ($current_model==='section') {
						$target_section_tipo = $current_tipo; // Overwrite
						continue;
					}else if ($current_model==='section' || $current_model==='exclude_elements') {
						continue;
					}else if($current_tipo === DEDALO_COMPONENT_SECURITY_AREAS_PROFILES_TIPO){ 
						continue; //'component_security_areas' removed in v6 but the component will stay in ontology, PROVISIONAL, only in the alpha state of V6 for compatibility of the ontology of V5.
					}

					$ar_related_clean[] = $current_tipo;
				}
				if (empty($ar_related_clean)) {
					// $ar_related_clean = [$tipo]; Loop de la muerte (!)
					debug_log(__METHOD__." Empty related items. Review your structure config to fix this error. $model - tipo: ".to_string($tipo), logger::ERROR);
				}

			// target_section_tipo
				if (!isset($target_section_tipo)) {
					$target_section_tipo = $section_tipo;
				}


			// sqo_config
				$sqo_config = new stdClass();
					$sqo_config->full_count		= false;
					// $sqo_config->add_select	= false;
					// $sqo_config->direct		= true;
					$sqo_config->limit			= $limit;
					$sqo_config->offset			= $offset;
					$sqo_config->mode			= $mode;
					$sqo_config->operator		= '$or';

			// ddo_map
					// dump($ar_related_clean, ' ar_related_clean ++ '.to_string($tipo));
					// $bt = debug_backtrace();
					// dump($bt, ' bt ++ '.to_string());

				$current_mode = $model !== 'section'
					? 'list'
					: $mode;


				$ddo_map = array_map(function($current_tipo) use($tipo, $target_section_tipo, $current_mode){
					$ddo = new dd_object();
						$ddo->set_tipo($current_tipo);
						$ddo->set_section_tipo($target_section_tipo);
						$ddo->set_parent($tipo);
						$ddo->set_mode($current_mode);
						$ddo->set_label(RecordObj_dd::get_termino_by_tipo($current_tipo, DEDALO_APPLICATION_LANG, true, true));

					return $ddo;
				}, $ar_related_clean);
					// dump($ddo_map, ' ddo_map ++ '.to_string()); die();

			// show
				$show = new stdClass();
					$show->ddo_map		= $ddo_map;
					$show->sqo_config	= $sqo_config;

			// // search
				// 	$search = new stdClass();
				// 		$search->ddo_map	= $ar_related_clean;
				// 		$search->sqo_config	= $sqo_config;

			// // select
				// 	$select = new stdClass();
				// 		$select->ddo_map	= $ar_related_clean;

			// sqo section_tipo as ddo
				$ar_section_tipo = is_array($target_section_tipo) ? $target_section_tipo : [$target_section_tipo];
				$ddo_section_tipo = array_map(function($section_tipo){
					$ddo = new dd_object();
						$ddo->set_tipo($section_tipo);
						$ddo->set_label(RecordObj_dd::get_termino_by_tipo($section_tipo, DEDALO_APPLICATION_LANG, true, true));
					return $ddo;
				}, $ar_section_tipo);

			// sqo
				$sqo = new stdClass();
					$sqo->section_tipo = $ddo_section_tipo;

			// request_config_item. build
				$request_config_item = new stdClass();
					$request_config_item->api_engine	= 'dedalo';
					$request_config_item->show			= $show;
					$request_config_item->sqo			= $sqo;
					// $request_config_item->sqo->tipo	= $tipo;
					// $request_config_item->search		= $search;
					// $request_config_item->select		= $select;

					// dump($request_config_item, ' ------------------------------------------ request_config_item ++ '.to_string()); die();

			// set item
				$ar_request_query_objects[] = $request_config_item;

			// set var (TEMPORAL TO GIVE ACCESS FROM GET_SUB_DATA)
				dd_core_api::$context_dd_objects = $ddo_map; 

		}//end if(isset($properties->source->request_config) || $model==='component_autocomplete_hi')

		// cache
			$resolved_request_properties_parsed[$resolved_key] = $ar_request_query_objects;


		return $ar_request_query_objects;
	}//end get_ar_request_config



	/**
	* GET_RECORDS_MODE
	* @return string $records_mode
	*/
	public function get_records_mode() {

		$model			= get_called_class();
		$properties		= $this->get_properties();
		$records_mode	= isset($properties->source->records_mode)
							? $properties->source->records_mode
							: (in_array($model, component_relation_common::get_components_with_relations())
								? 'list'
								: $this->get_modo()
							);

		return $records_mode;
	}//end get_records_mode



	/**
	* GET_SOURCE
	* @return object | json
	*/
	public function get_source() {

		$source = new request_query_object();
			// $source->set_typo('source');
			$source->set_tipo($this->get_tipo());
			$source->set_section_tipo($this->get_section_tipo());
			$source->set_lang($this->get_lang());
			$source->set_mode($this->get_modo());
			$source->set_section_id($this->get_section_id());
			$source->set_model(get_class($this));

		return $source;
	}//end get_source



	/**
	* GET_DDINFO_PARENTS
	* @return object $dd_info
	*/
	public static function get_ddinfo_parents($locator, $source_component_tipo) {

		$section_id 	= $locator->section_id;
		$section_tipo 	= $locator->section_tipo;

		// ($locator, $lang=DEDALO_DATA_LANG, $show_parents=false, $ar_components_related=false, $divisor=', ', $include_self=true, $glue=true)
		$dd_info_value = component_relation_common::get_locator_value($locator, DEDALO_DATA_LANG, true, false, null, false, false);

		$dd_info = new stdClass();
			$dd_info->tipo			= 'ddinfo';
			$dd_info->section_id	= $section_id;
			$dd_info->section_tipo	= $section_tipo;
			$dd_info->value			= $dd_info_value;
			$dd_info->parent		= $source_component_tipo;


		return $dd_info;
	}//end get_ddinfo_parents



	/**
	* BUILD_SEARCH_QUERY_OBJECT
	* Generic builder for search_query_object (override when need)
	* @return object $query_object
	*/
	public static function build_search_query_object( $request_options ) {

		$start_time=microtime(1);

		$options = new stdClass();
			$options->q						= null;
			$options->q_operator			= null;
			$options->q_split				= null;
			$options->limit					= 10;
			$options->offset				= 0;
			$options->lang					= 'all';
			$options->logical_operator		= '$or';
			$options->id					= 'temp';
			$options->tipo					= null;
			$options->section_tipo			= null; // use always array as value
			$options->add_filter			= true;
			$options->add_select			= true;
			$options->order_custom			= null;
			$options->full_count			= false;
			$options->filter_by_locator		= false;
			$options->filter_by_locators	= false; // different of 'filter_by_locator' (!)
			$options->direct				= false; // true for section (!)
			$options->mode					= 'list'; // It is necessary to calculate the ddo's to search / show (layout_map)
			foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}

		$id					= $options->id;
		$logical_operator	= $options->logical_operator;
		$tipo				= $options->tipo;

		# Default from options (always array)
		$section_tipo = is_array($options->section_tipo) ? $options->section_tipo : [$options->section_tipo];

		# Defaults
		$filter_group = null;
		$select_group = array();
		$total_locators = false;

		// filter_by_locator_builder
			$filter_by_locator_builder = function($filter_by_locator, $section_tipo) {

				if (is_array($section_tipo)) {
					$section_tipo = reset($section_tipo);
				}

				// Is an array of objects
					$ar_section_id = [];
					foreach ((array)$filter_by_locator as $key => $value_obj) {
						$current_section_id = (int)$value_obj->section_id;
						if (!in_array($current_section_id, $ar_section_id)) {
							$ar_section_id[] = $current_section_id;
						}
					}

				$filter_element = new stdClass();
					$filter_element->q 		= json_encode($ar_section_id);
					$filter_element->path 	= json_decode('[
						{
							"section_tipo": "'.$section_tipo.'",
							"component_tipo": "dummy",
							"modelo": "component_section_id",
							"name": "Searching"
						}
	                ]');

				$op = '$and';
				$filter_group = new stdClass();
					$filter_group->$op = [$filter_element];

				$total_locators = count($ar_section_id);

				return [
					'filter_group' 	 => $filter_group,
					'total_locators' => $total_locators
				];
			};

		if ($options->direct===true) {

			# FILTER
				if ($options->add_filter===true) {

					if ($options->filter_by_locators!==false) {

						// filter_by_locators case
						$filter_by_locators	= $options->filter_by_locators;
						$filter_group		= false;
						$total_locators		= count($filter_by_locators);

					}elseif ($options->filter_by_locator!==false){

						// filter_by_locator case
						$filter_by_locator_data = $filter_by_locator_builder($options->filter_by_locator, $section_tipo);

						$filter_group	= $filter_by_locator_data['filter_group'];
						$total_locators	= $filter_by_locator_data['total_locators'];
					}

				}//end if ($options->add_filter===true)

		}else{

			$RecordObj_dd_component_tipo = new RecordObj_dd($tipo);
			$component_tipo_properties 	 = $RecordObj_dd_component_tipo->get_properties(true);

			// source search. If not defined, use fallback to legacy related terms and build one
				$request_config = common::get_request_config($tipo, $external=false, $section_tipo, $mode);

			// request_config iteration
				foreach ($request_config as $source_search_item) {

					// current section tipo
						$current_section_tipo = $source_search_item->section_tipo;

					foreach ($source_search_item->search as $current_tipo) {

						// check is real component
							$model = RecordObj_dd::get_modelo_name_by_tipo($current_tipo, true);
							if (strpos($model,'component')!==0) {
								debug_log(__METHOD__." IGNORED. Expected model is component, but '$model' is received for current_tipo: $current_tipo ".to_string(), logger::ERROR);
								continue;
							}

						$path = search::get_query_path($current_tipo, $current_section_tipo);

						# FILTER . filter_element (operator_group) - default is true
							if ($options->add_filter===true) {

								if ($options->filter_by_locator!==false) {

									// filter_by_locators case
									$filter_by_locators	= $options->filter_by_locators;
									$filter_group		= false;
									$total_locators		= count((array)$filter_by_locators);

								}elseif ($options->filter_by_locators!==false) {

									// filter_by_locator case
									$filter_by_locator_data = $filter_by_locator_builder($options->filter_by_locator, $current_section_tipo);

									$filter_group 	= $filter_by_locator_data['filter_group'];
									$total_locators = $filter_by_locator_data['total_locators'];

								}else{//end if ($options->filter_by_locator!==false)

									// if (!empty($options->q)) {
										$filter_element = new stdClass();
											$filter_element->q 		= $options->q ?? '';
											$filter_element->lang 	= $options->lang;
											$filter_element->path 	= $path;

										$filter_group = new stdClass();
											$filter_group->$logical_operator[] = $filter_element;
									// }
								}
							}//end if ($options->add_filter===true)


						# SELECT . Select_element (select_group)
							if($options->add_select===true){

								# Add options lang
								$end_path = end($path);
								$end_path->lang = $options->lang;

								$select_element = new stdClass();
									$select_element->path = $path;

								$select_group[] = $select_element;
							}

					}//end foreach ($source_search_item->components as $current_tipo)

				}//end foreach ($source_search as $source_search_item) {

		}//end if ($options->direct===true)

		$full_count		= $total_locators ?? $options->full_count;
		$mode			= $options->mode ?? null;
		$order_custom	= $options->order_custom ?? null;

		// sqo
			// $query_object = new stdClass();
			// 	$query_object->typo			= 'sqo';
			// 	$query_object->id			= $id;
			// 	$query_object->section_tipo	= $section_tipo;
			// 	$query_object->filter		= $filter_group;
			// 	$query_object->select		= $select_group;
			// 	$query_object->limit		= $options->limit;
			// 	$query_object->offset		= $options->offset;
			// 	$query_object->full_count	= $full_count;

			// 	if (!empty($options->mode)) {
			// 		$query_object->mode = $options->mode;
			// 	}
			// 	if (!empty($filter_by_locators)) {
			// 		$query_object->filter_by_locators = $filter_by_locators;
			// 	}
			// 	if (!empty($options->order_custom)) {
			// 		$query_object->order_custom = $options->order_custom;
			// 	}

		// sqo
			$sqo = new build_search_query_object();
				$sqo->set_id($id);
				$sqo->set_section_tipo($section_tipo);
				$sqo->set_filter($filter);
				$sqo->set_select($select);
				$sqo->set_limit($limit);
				$sqo->set_offset($offset);
				$sqo->set_full_count($full_count);

				if (!empty($mode)) {
					$sqo->set_mode($mode);
				}
				if (!empty($filter_by_locators)) {
					$sqo->set_filter_by_locators($filter_by_locators);
				}
				if (!empty($order_custom)) {
					$sqo->set_order_custom($order_custom);
				}
		

		return (object)$query_object;
	}//end build_search_query_object



	/**
	* GET_REQUEST_QUERY_OBJECT
	*
	* @return object | json
	*/
	public function get_request_query_object() {

		// rqo. from request_config
			$records_mode	= $this->get_modo();
			$mode			= $records_mode;
			$tipo			= $this->get_tipo();
			$section_tipo	= $this->get_section_tipo();
			$section_id		= $this->get_section_id();


			$options = new stdClass();
				$options->tipo			= $tipo;
				$options->external		= false;
				$options->section_tipo	= $section_tipo;
				$options->mode			= $mode;
				$options->section_id	= $section_id;				
			$ar_request_query_objects = common::get_ar_request_config($options);


		$request_config = reset($ar_request_query_objects);


		return $request_config;
	}//end get_request_query_object	



	/**
	* get_REQUEST_DDO
	* Calculates and set in dd_core_api static var 'request_ddo', ddo items from all modes (show, select, search)
	* @return array $added_ddo
	*/
		// public function get_request_ddo() {

		// 	$records_mode	= $this->get_records_mode();
		// 	$request_config	= $this->request_config;

		// 	// layout_map subcontext from layout_map items
		// 		$layout_map_options = new stdClass();
		// 			$layout_map_options->tipo					= $this->get_tipo();
		// 			$layout_map_options->section_tipo			= $this->get_section_tipo();
		// 			$layout_map_options->mode					= $records_mode;
		// 			$layout_map_options->add_section			= true;
		// 			$layout_map_options->request_config_type	= ''; // overwrite in each case

		// 	// show
		// 		$layout_map_options->request_config_type	= 'show';
		// 		$layout_map_result							= layout_map::get_layout_map($layout_map_options, $request_config);
		// 		$ddo										= $layout_map_result;


		// 	// only for non list mode (excludding section)
		// 		if (get_called_class()!=='section' && $this->modo!=='list') {

		// 			// search
		// 				$layout_map_options->request_config_type	= 'search';
		// 				$layout_map_options->add_section			= false;
		// 				$layout_map_result							= layout_map::get_layout_map($layout_map_options, $request_config);
		// 				$ddo										= array_merge($ddo, $layout_map_result);

		// 			// choose
		// 				$layout_map_options->request_config_type	= 'choose';
		// 				$layout_map_options->add_section			= false;
		// 				$layout_map_result							= layout_map::get_layout_map($layout_map_options, $request_config);
		// 				$ddo										= array_merge($ddo, $layout_map_result);
		// 		}

		// 	$request_ddo = new stdClass();
		// 		$request_ddo->typo	= 'request_ddo';
		// 		$request_ddo->value	= $ddo;


		// 	return $request_ddo;
		// }//end get_request_ddo



	/**
	* GET_DATA_ITEM
	* Only to maintain vars and format unified
	* @param mixed $value
	* @return object $item
	*/
	public function get_data_item($value) {

		$item = new stdClass();
			$item->section_id 			= $this->get_section_id();
			$item->section_tipo 		= $this->get_section_tipo();
			$item->tipo 				= $this->get_tipo();
			$item->pagination			= $this->get_pagination();
			$item->from_component_tipo 	= isset($this->from_component_tipo) ? $this->from_component_tipo : $item->tipo;
			$item->value 				= $value;

		return $item;
	}//end get_data_item



	/**
	* GET_ELEMENT_LANG
	* Used to resolve component lang before construct it
	* @return lang code like 'lg-spa'
	*/
	public static function get_element_lang($tipo, $data_lang=DEDALO_DATA_LANG) {

		$translatable 	= RecordObj_dd::get_translatable($tipo);
		$lang 			= ($translatable===true) ? $data_lang : DEDALO_DATA_NOLAN;

		return $lang;
	}//end get_element_lang



	/**
	* GET_SECTION_ELEMENTS_CONTEXT
	* Get list of all components available for current section using get_context_simple
	* Used to build search presets in filter
	* @param array $request_options
	* @return array $context
	*/
	public static function get_section_elements_context($request_options) {
		$start_time=microtime(1);

		$options = new stdClass();
			$options->context_type 				= 'simple';
			$options->ar_section_tipo 			= null;
			$options->path 						= [];
			$options->ar_tipo_exclude_elements 	= [];
			$options->ar_components_exclude 	= [
				'component_password',
				'component_filter_records',
				'component_image',
				'component_av',
				'component_pdf',
				//'component_relation_children',
				//'component_relation_related',
				//'component_relation_model',
				//'component_relation_parent',
				//'component_relation_index',
				//'component_relation_struct',
				'component_geolocation',
				// 'component_info',
				'component_state',
				'section_tab',
				'component_json'
			];
			$options->ar_include_elements 		= [
				'component',
				'section_group',
				'section_group_div',
				'section_tab'
			];
			foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}


		$ar_section_tipo 			= $options->ar_section_tipo;
		$path 						= $options->path;
		$ar_tipo_exclude_elements 	= $options->ar_tipo_exclude_elements;
		$ar_components_exclude 		= $options->ar_components_exclude;
		$ar_include_elements 		= $options->ar_include_elements;
		$context_type 				= $options->context_type;

		# Manage multiple sections
		# section_tipo can be an array of section_tipo. To prevent duplicates, check and group similar sections (like es1, co1, ..)
		#$ar_section_tipo = (array)$section_tipo;
		$resolved_section = [];
		$context = [];
		foreach ((array)$ar_section_tipo as $section_tipo) {
			$section_real_tipo = section::get_section_real_tipo_static($section_tipo);
			if (in_array($section_real_tipo, $resolved_section)) {
				continue;
			}
			$resolved_section[] = $section_real_tipo;

			$section_permisions = security::get_security_permissions($section_tipo, $section_tipo);
			$user_id_logged 	= navigator::get_user_id();

			if ( $section_tipo!==DEDALO_THESAURUS_SECTION_TIPO
				&& $user_id_logged!=DEDALO_SUPERUSER
				&& ((int)$section_permisions<1)) {
				// user don't have access to current section. skip section
				continue;
			}

			$section_tipo = $section_real_tipo;
			//create the section instance and get the context_simple
				$dd_section = section::get_instance(null, $section_tipo, $modo='list', $cache=true);

			// element json
				$get_json_options = new stdClass();
					$get_json_options->get_context 		= true;
					$get_json_options->context_type 	= $context_type;
					$get_json_options->get_data 		= false;
				$element_json = $dd_section->get_json($get_json_options);

			// item context simple
				$item_context = $element_json->context;

			$context = array_merge($context, $item_context);

			$ar_elements = section::get_ar_children_tipo_by_modelo_name_in_section($section_tipo, $ar_include_elements, $from_cache=true, $resolve_virtual=true, $recursive=true, $search_exact=false, $ar_tipo_exclude_elements);


			foreach ($ar_elements as $element_tipo) {

				if($element_tipo === DEDALO_COMPONENT_SECURITY_AREAS_PROFILES_TIPO) continue; //'component_security_areas' removed in v6 but the component will stay in ontology, PROVISIONAL, only in the alpha state of V6 for compatibility of the ontology of V5.

				$model = RecordObj_dd::get_modelo_name_by_tipo($element_tipo,true);

				// common temporal excluded/mapped models *******
					$match_key = array_search($model, common::$ar_temp_map_models);
					if (false!==$match_key) {
						debug_log(__METHOD__." +++ Mapped model $model to $match_key from layout map ".to_string(), logger::WARNING);
						$model = $match_key;
					}else if (in_array($model, common::$ar_temp_exclude_models)) {
						debug_log(__METHOD__." +++ Excluded model $model from layout map ".to_string(), logger::WARNING);
						continue;
					}

				switch (true) {
					// component case
					case (strpos($model, 'component_')===0):
						$recordObjdd = new RecordObj_dd($element_tipo);
						$translatable = $recordObjdd->get_traducible()=== 'si';

						$current_lang = $translatable ? DEDALO_DATA_LANG : DEDALO_DATA_NOLAN;
						$element  = component_common::get_instance(	$model,
																	$element_tipo,
																	null,
																	'list',
																	$current_lang,
																	$section_tipo);
						break;

					// grouper case
					case (in_array($model, common::$groupers)):

						$grouper_model	= ($model==='section_group_div') ? 'section_group' : $model;
						$element		= new $model($element_tipo, $section_tipo, 'list');
						break;

					// others case
					default:

						debug_log(__METHOD__ ." Ignored model '$model' - current_tipo: '$element_tipo' ".to_string(), logger::WARNING);
						break;
				}//end switch (true)

				// element json
					$get_json_options = new stdClass();
						$get_json_options->get_context 		= true;
						$get_json_options->context_type 	= $context_type;
						$get_json_options->get_data 		= false;
					$element_json = $element->get_json($get_json_options);

				// item context simple
					$item_context = $element_json->context;

				// target section tipo add
					if ($model==='component_portal') {
						$ddo = reset($item_context);
						$target_section_tipo = $element->get_ar_target_section_tipo();
						// Check target section access here ?
						$n_sections = count($target_section_tipo);
						if ($n_sections===1) {
							$ddo->target_section_tipo = $target_section_tipo;
						}else{
							#$ddo->target_section_tipo = reset($target_section_tipo);
							debug_log(__METHOD__." Ignored $element_tipo - $model with section tipo: ".to_string($target_section_tipo).' only allowed 1 section_tipo' , logger::ERROR);
						}
					}

				// context add
					$context = array_merge($context, $item_context);

			}//end foreach ($ar_elements as $element_tipo)

		}//end foreach ((array)$ar_section_tipo as $section_tipo)


		return $context;
	}//end get_section_elements_context



	/**
	* GET_TOOLS
	* @return array $tools
	*/
	public function get_tools() {

		$registered_tools	= $this->get_client_registered_tools();
		$model				= get_class($this);
		$tipo				= $this->tipo;
		$is_component		= strpos($model, 'component_')===0;
		$translatable		= $this->traducible;
		$properties			= $this->get_properties();
		$with_lang_versions	= isset($properties->with_lang_versions) ? $properties->with_lang_versions : false;

		$tools = [];
		foreach ($registered_tools as $tool) {

			$affected_tipos  = isset($tool->affected_tipos)  ? (array)$tool->affected_tipos : [];
			$affected_models = isset($tool->affected_models) ? (array)$tool->affected_models : [];
			$requirement_translatable = isset($tool->requirement_translatable) ? (bool)$tool->requirement_translatable : false;

			if( 	in_array($model, $affected_models)
				||  in_array($tipo,  $affected_tipos)
				||  ($is_component===true && in_array('all_components', $affected_models))
			  ) {

				if ($requirement_translatable===true) {

					$is_translatable = ($is_component===true)
						? (($translatable==='no' && $with_lang_versions!==true) ? false : true)
						: false;

					if ($requirement_translatable===$is_translatable) {
						$tools[] = $tool;
					}

				}else{
					$tools[] = $tool;
				}
			}
		}


		return $tools;
	}//end get_tools



	/**
	* GET_REGISTERED_TOOLS
	* @return array $registered_tools
	*/
	public function get_client_registered_tools() {

		$registered_tools = [];

		// if(isset($_SESSION['dedalo']['registered_tools'])) {
		// 	return $_SESSION['dedalo']['registered_tools'];
		// }

		// get all tools config sections
			$ar_config = tools_register::get_all_config_tool_client();

		// get all active and registered tools
		$sqo_tool_active = json_decode('{
				"section_tipo": "dd1324",
				"limit": 0,
				"filter": {
				    "$and": [
				        {
				            "q": {"section_id":"1","section_tipo":"dd64","type":"dd151","from_component_tipo":"dd1354"},
				            "q_operator": null,
				            "path": [
				                {
									"section_tipo": "dd1324",
									"component_tipo": "dd1354",
									"modelo": "component_radio_button",
									"name": "Active"
				                }
				            ]
				        }
				    ]
				},
				"full_count": false
			}');

		$search = search::get_instance($sqo_tool_active);
		$result = $search->search();
		// get the simple_tool_object		
		foreach ($result->ar_records as $record) {

			$section 		= section::get_instance($record->section_id, $record->section_tipo);
			$section_dato 	= $record->datos;
			$section->set_dato($section_dato);
			$section->set_bl_loaded_matrix_data(true);

			$component_tipo	= 'dd1353';
			$model			= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);
			$component		= component_common::get_instance($model,
															 $component_tipo,
															 $record->section_id,
															 'list',
															 DEDALO_DATA_NOLAN,
															 $record->section_tipo);
			$dato = $component->get_dato();
			$current_value = reset($dato);

			// append config
			$current_config = array_filter($ar_config, function($item) use($current_value){
				if($item->name === $current_value->name) {
					return $item;
				}
			});
			$current_value->config = !empty($current_config[0])
				? $current_config[0]->config
				: null;

			$registered_tools[] = $current_value;
		}

		// $_SESSION['dedalo']['registered_tools'] = $registered_tools;
		// write_session_value('registered_tools', $registered_tools);


		return $registered_tools;
	}//end get_client_registered_tools



}//end class


