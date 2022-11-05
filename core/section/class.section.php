<?php
/*
* CLASS SECTION
*/
// include_once(DEDALO_CORE_PATH . '/search/records_search/class.records_search.php');
// include_once(DEDALO_CORE_PATH . '/search/records_navigator/class.records_navigator.php');



class section extends common {

	/**
	* CLASS VARS
	*/
		# Overwrite __construct var lang passed in this component
		protected $lang;

		# FIELDS
		protected $section_id;
		protected $tipo;
		protected $dato;

		# STATE
		protected $modo;

		# STRUCTURE DATA
		protected $modelo;
		protected $label;

		# Buttons objects
		public $ar_buttons;

		public $ar_all_project_langs;

		public $show_inspector = true;	# default show: true

		public $section_virtual = false;
		public $section_real_tipo;

		static $active_section_id;

		public $is_temp = false;	# Used to force save data to session instead database. Default is false

		public $options;

		# SAVE_HANDLER
		# Default is 'database'. Other options like 'session' are accepted
		# Note that section change automatically this value (to 'session' for example) when received section_id is like 'temp1' for manage this cases as temporal section
		public $save_handler = 'database';

		# static cache for section instances
		static $ar_section_instances;

		public $save_modified = true; # Default is true

		public $layout_map;

		// injected 'dd_request'
		// public $dd_request;

		// injected whole database record, with all columns
		public $record;

		public $pagination;



	# DIFFUSION INFO
	# Store section diffusion info. If empty, current section is not publish.
	# Format is array or null
	# protected $diffusion_info;



	# INVERSE RELATIONS
	# Parents sections that call to this sections with portals or autocompletes
	# array of locators with, section_id, section_tipo and component_tipo (the component that call),
	# public $inverse_locators;



	/**
	* GET_INSTANCE
	* Cache section instances (singleton pattern)
	* @param string|int|null $section_id = null
	* @param string $tipo = null
	* @param string|null $modo = 'list'
	* @param bool $cache = true
	*
	* @return instance section
	*/
	public static function get_instance($section_id=null, string $tipo=null, string $modo='list', bool $cache=true) : section {

		// check valid tipo
			if (empty($tipo)) {
				throw new Exception("Error: on construct section : tipo is mandatory. section_id:$section_id, tipo:$tipo, modo:$modo", 1);
			}

		// Not cache new sections (without section_id)
			if (empty($section_id)) {
				return new section(null, $tipo, $modo);
			}

		return new section($section_id, $tipo, $modo);

		// removed cache features temporally (!) Verify real speed benefits
			// // Direct construct without cache instance
			// // Use this config in imports
			// 	if ($cache===false) {
			// 		return new section($section_id, $tipo, $modo);
			// 	}

			// # key for cache
			// $key = $section_id .'_'. $tipo.'_'.$modo;

			// $max_cache_instances = 300*3; // Default 300
			// $cache_slice_on 	 = 100*3; // Default 100

			// # OVERLOAD : If ar_section_instances > 99 , not add current section to cache to avoid overload
			// # array_slice ( array $array , int $offset [, int $length = NULL [, bool $preserve_keys = false ]] )
			// if (isset(self::$ar_section_instances) && sizeof(self::$ar_section_instances)>$max_cache_instances) {
			// 	self::$ar_section_instances = array_slice(self::$ar_section_instances, $cache_slice_on, null, true);
			// 	if(SHOW_DEBUG===true) {
			// 		debug_log(__METHOD__.' '.DEDALO_HOST." Overload sections prevent (max $max_cache_instances). Unset first $cache_slice_on cache items [$key]", logger::DEBUG);
			// 	}

			// 	// let GC do the memory job
			// 	//time_nanosleep(0, 10000000); // 10 ms
			// 	time_nanosleep(0, 2000000); // 02 ms
			// }

			// # FIND CURRENT INSTANCE IN CACHE
			// if ( !array_key_exists($key, (array)self::$ar_section_instances) ) {
			// 	self::$ar_section_instances[$key] = new section($section_id, $tipo, $modo);
			// }

			// return self::$ar_section_instances[$key];
	}//end get_instance



	/**
	* CONSTRUCT
	* Extends parent abstract class common
	* La sección, a diferencia de los componentes, se comporta de un modo particular:
	* Si se le pasa sólo el tipo, se espera un listado (modo list)
	* Si se le pasa sólo el section_id, se espera una ficha (modo edit)
	*/
	private function __construct($section_id=null, ?string $tipo=null, ?string $modo='edit') {

		if (empty($tipo)) {
			throw new Exception("Error: on construct section : tipo is mandatory. section_id:$section_id, tipo:$tipo, modo:$modo", 1);
		}

		if(SHOW_DEBUG===true) {
			#$section_name = RecordObj_dd::get_termino_by_tipo($tipo,null,true);
			#global$TIMER;$TIMER[__METHOD__.'_' .$section_name.'_IN_'.$tipo.'_'.$modo.'_'.$section_id.'_'.start_time()]=start_time();
		}

		// Set general vars
			$this->lang			= DEDALO_DATA_NOLAN;
			$this->section_id	= $section_id;
			$this->tipo			= $tipo;
			$this->modo			= $modo ?? 'edit';
			$this->parent		= 0;

		// load_structure_data. When tipo is set, calculate structure data
			parent::load_structure_data();


		// active_section_section_id : Set global var
			if(	   $modo==='edit'
				&& (isset($this->section_id) && ($this->section_id>0 || strpos($this->section_id, DEDALO_SECTION_ID_TEMP)!==false))
				&& !isset(section::$active_section_id) ) {

					// fix active_section_id
						section::$active_section_id = $this->get_section_id();
			}

		// pagination
			$this->pagination = new stdClass();
				$this->pagination->offset	= 0; // default
				$this->pagination->limit	= isset($properties->list_max_records)
					? (int)$properties->list_max_records
					: ($modo==='list' ? 10 : 1);

		// debug
			if(SHOW_DEBUG===true) {
				#global$TIMER;$TIMER[__METHOD__.'_' .$section_name.'_OUT_'.$tipo.'_'.$modo.'_'.$section_id.'_'.start_time()]=start_time();
			}

		return true;
	}//end __construct



	/**
	* GET DATO
	* @return object $dato
	*/
	public function get_dato() : object {

		// check valid call
			if ( abs(intval($this->section_id))<1 && strpos($this->section_id, DEDALO_SECTION_ID_TEMP)===false ) {
				if(SHOW_DEBUG===true) {
					if ($this->section_id==='result') {
						throw new Exception("Error Processing Request. 'result' is not valid section_id. Maybe you are using foreach 'ar_list_of_values' incorrectly", 1);
					};
				}
				throw new Exception("Error Processing Request. get_component_data of section section_id <1 is not allowed (section_id:'$this->section_id')", 1);
			}

		// save_handler. If section_id have a temporal string the save handier will be 'session' the section will save into the menory NOT to database
			if( strpos($this->section_id, DEDALO_SECTION_ID_TEMP)!==false ){
				$this->save_handler = 'session';
			}

		// save_handler session
			// Sometimes we need use section as temporal element without save real data to database. Is this case
			// data is saved to session as temporal data and can be recovered from $_SESSION['dedalo']['section_temp_data'] using key '$this->tipo.'_'.$this->section_id'
			if (isset($this->save_handler) && $this->save_handler==='session') {
				if (!isset($this->dato)) {
					$temp_data_uid = $this->tipo.'_'.$this->section_id;
					# Fix dato as object
					$this->dato = isset($_SESSION['dedalo']['section_temp_data'][$temp_data_uid])
						? clone $_SESSION['dedalo']['section_temp_data'][$temp_data_uid]
						: new stdClass();
				}
				return $this->dato;
			}

		// data is not loaded. Load once
			if($this->bl_loaded_matrix_data!==true) {

				// if virtual section have section_tipo "real" in properties, change the tipo of the section to the real
					$tipo = (isset($this->properties->section_tipo) && $this->properties->section_tipo==='real')
						? $this->get_section_real_tipo()
						: $this->tipo;

				$section_tipo			= $this->tipo;
				$matrix_table			= common::get_matrix_table_from_tipo($section_tipo);
				$JSON_RecordObj_matrix	= new JSON_RecordObj_matrix($matrix_table, $this->section_id, $tipo);

				// load dato from db
					$dato = $JSON_RecordObj_matrix->get_dato();
					// dump(null, ' dato from DB ++ ->->->->->->->->->->->->->->->->->->->->->->->->->->->->->->->->->-> '.to_string($this->tipo.'-'.$this->section_id.'-'. RecordObj_dd::get_termino_by_tipo($this->tipo) ));

				// fix dato (force object)
					$this->dato = (object)$dato;

				// modificar esta verificación. con secciones virtuales no funciona..
					// if ( !empty($this->section_id) && (!property_exists($this->dato, 'section_tipo') || $this->dato->section_tipo!=$this->tipo) ) {
					// 	if(SHOW_DEBUG===true) {
					// 		dump($this->dato->section_tipo, "dato->section_tipo/tipo: ".$this->dato->section_tipo."/$this->tipo");
					// 	}
					// 	throw new Exception("Error Processing Request. Section tipo inconsistency detected!", 1);
					// }

				// set as loaded
					$this->bl_loaded_matrix_data = true;
			}//end if($this->bl_loaded_matrix_data!==true)

		// debug
			if(SHOW_DEBUG===true) {
				#$start_time = start_time();
				#global$TIMER;$TIMER[__METHOD__.'_OUT_'.$this->tipo.'_'.$this->modo.'_'.start_time()]=start_time();
			}


		return $this->dato;
	}//end get_dato



	/**
	* SET_DATO
	* Set whole section data as raw object
	* Fix section relations and components to prevent save issues
	* @return bool true
	*/
	public function set_dato($dato) {

		// call common->set_dato (!) fix var 'bl_loaded_matrix_data' as true
		return parent::set_dato($dato);
	}//end set_dato



	/**
	* GET_COMPONENT_DATO
	* Extrae del contenedor de la sección, el dato específico de cada componente en el idioma requerido
	* will be deprecated with the get_all_component_data (08-2017)
	*/
	public function get_component_dato(string $component_tipo, string $lang, bool $lang_fallback=false) {

		$all_component_data = $this->get_all_component_data($component_tipo);

		if ($lang_fallback===true) { // case mode list (see component common)

			if (isset($all_component_data->dato->{$lang}) && !empty($all_component_data->dato->{$lang})) {
				// lang data exists
				$component_dato = $all_component_data->dato->{$lang};
			}else{
				// fallback to default lang
				$component_dato = ($lang!==$lang_default && !empty($all_component_data->dato->{$lang_default}))
					? $all_component_data->dato->{$lang_default}
					: null;
			}

		}else{

			$component_dato = isset($all_component_data->dato->{$lang})
				? $all_component_data->dato->{$lang}
				: null;
		}

		return $component_dato;
	}//end get_component_dato



	/**
	* GET_ALL_COMPONENT_DATA
	* Get all data of the component, with dato, valor, valor_list and dataframe
	* this function will be the only communication with the component for get the information (08-2017)
	* @param string $component_tipo
	* @return object|null component_data
	*/
	public function get_all_component_data(string $component_tipo) : ?object {

		$section_data = $this->get_dato();

		if (!is_object($section_data)) {
			trigger_error("[get_all_component_data] Error on read component_data component_tipo: $component_tipo" );
		}

		$component_data = isset($section_data->components->{$component_tipo})
			? $section_data->components->{$component_tipo}
			: null;

		return $component_data;
	}//end get_all_component_data



	/**
	* SAVE_COMPONENT_DATO
	* Salva el dato del componente recibido en el contenedor JSON de la sección
	* Reconstruye el objeto global de la sección (de momento no se puede salvar sólo una parte del objeto json en postgresql)
	* procesa los datos indirectos del componente (valor y valor_list) y guarda el nuevo objeto global reemplazando el anterior
	* @param object $component_obj
	* @param string $component_data_type
	* @param bool $save_to_database
	* @return
	*/
	public function save_component_dato(object $component_obj, string $component_data_type, bool $save_to_database) {

		// La sección es necesaria antes de gestionar el dato del componente. Si no existe, la crearemos previamente
			if (abs(intval($this->get_section_id()))<1  && strpos((string)$this->get_section_id(), DEDALO_SECTION_ID_TEMP)===false) {
				$section_id = $this->Save();
				trigger_error("Se ha creado una sección ($section_id) disparada por el salvado del componente ".$component_obj->get_tipo());
				if(SHOW_DEBUG===true) {
					throw new Exception("Warning : Trying save component in section without section_id. Created section and saved", 1);
				}
			}

		// des
			// $this->get_dato();
			// dump($this, ' this ++ '.to_string()); return;

			// SECTION GLOBAL DATO : Dato objeto global de la sección
			// #$dato = $this->get_dato();
			// #	if (!is_object($dato)) {
			// #		throw new Exception("Error Processing Request. Section Dato is not object", 1);
			// #	}

		// set self section_obj to component. (!) Important to prevent cached and not cached versions of
		// current section conflicts (and for speed)
			$component_obj->set_section_obj($this);

		// component_global_dato : Extrae la parte del componente desde el objeto global de la sección
			$component_tipo				= $component_obj->get_tipo();
			$component_lang				= $component_obj->get_lang();
			##$component_valor_lang		= $component_obj->get_valor_lang();
			##$component_modelo_name	= get_class($component_obj);	#RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);
			##$component_traducible		= $component_obj->get_traducible();

			if (empty($component_tipo)) {
				throw new Exception("Error Processing Request: component_tipo is empty", 1);
			}

		// set dato
			if ($component_data_type==='relation') {

				// relation components
					$this->set_component_relation_dato( $component_obj );

			}else{

				// direct components
					$this->set_component_direct_dato( $component_obj );
		}

		// diffusion_info
			$this->dato->diffusion_info = null;	// Always reset section diffusion_info on save components

		// optional stop the save process to delay ddbb access
			if($save_to_database===false) {
				# Stop here (remember make a real section save later!)
				# No component time machine data will be saved when section saves later
				#debug_log(__METHOD__." Stopped section save process component_obj->save_to_database = true ".to_string(), logger::ERROR);
				return $this->section_id;
			}

		// time machine data. We save only current component lang 'dato' in time machine
			$save_options = new stdClass();
				$save_options->time_machine_data	= $component_obj->get_dato_unchanged();
				$save_options->time_machine_lang	= $component_lang;
				$save_options->time_machine_tipo	= $component_tipo;

		// save section result
			$result = $this->Save( $save_options );

		// stopped diffusion_info_propagate_changes
			// #
			// # DIFFUSION_INFO
			// # Note that this process can be very long if there are many inverse locators in this section
			// # To optimize save process in scripts of importation, you can dissable this option if is not really necessary
			// #
			// #$dato->diffusion_info = null;	// Always reset section diffusion_info on save components
			// #register_shutdown_function( array($this, 'diffusion_info_propagate_changes') ); // exec on __destruct current section
			// if ($component_obj->update_diffusion_info_propagate_changes===true) {
			// 	$this->diffusion_info_propagate_changes();
			// 	# debug_log(__METHOD__." Deleted diffusion_info data for section $this->tipo - $this->section_id ", logger::DEBUG);
			// }

		// post_save_component_processes
			$this->post_save_component_processes((object)[
				'component' => $component_obj
			]);


		return $result;
	}//end save_component_dato



	/**
	* SET_COMPONENT_DIRECT_DATO
	* @param object $component_obj
	* @return object $this->dato
	*/
	public function set_component_direct_dato( object $component_obj ) : object {

		// set self section_obj to component. (!) Important to prevent cached and not cached versions of
		// current section conflicts (and for speed)
			$component_obj->set_section_obj($this);

		// component short vars
			$component_tipo 		= $component_obj->get_tipo();
			$component_lang 		= $component_obj->get_lang();
			$component_valor_lang 	= $component_obj->get_valor_lang();
			$component_modelo_name 	= get_class($component_obj);
			$component_traducible 	= $component_obj->get_traducible();

		// section dato
			$dato = $this->get_dato();
			if (!is_object($dato)) {
				// $dato = $this->dato = new stdClass();
				throw new Exception("Error Processing Request. Section Dato is not as expected type (object). type: ".gettype($dato), 1);
			}

		# SELECT COMPONENT IN SECTION DATO
		if (isset($dato->components->{$component_tipo})) {

			// component dato already exists in section object. Only select it
				$component_global_dato = $dato->components->{$component_tipo};

		}else{

			// component dato NOT exists in section object. We build a new one with current info
				#$obj_global 						= new stdClass();
				#$obj_global->$component_tipo 		= new stdClass();
				#$component_global_dato 			= new stdClass();
				#$component_global_dato				= $obj_global->$component_tipo;

				$component_global_dato = new stdClass();

					// INFO : Creamos la info del componente actual
						// $component_global_dato->info 		= new stdClass();
						// 	$component_global_dato->info->label = RecordObj_dd::get_termino_by_tipo($component_tipo,null,true);
						// 	$component_global_dato->info->modelo= $component_modelo_name;
						$inf = RecordObj_dd::get_termino_by_tipo($component_tipo,null,true) .' ['.$component_modelo_name.']';
						$component_global_dato->inf = $inf;


					$component_global_dato->dato = new stdClass();
					// $component_global_dato->valor		= new stdClass();
					// $component_global_dato->valor_list	= new stdClass();
					// $component_global_dato->dataframe	= new stdClass();
		}

		# DATO OBJ
			if (!isset($component_global_dato->dato->{$component_lang})) {
				$component_global_dato->dato->{$component_lang} = new stdClass();
			}

		# VALOR OBJ
			// if (!isset($component_global_dato->valor)) {
			// 	$component_global_dato->valor = new stdClass();
			// }
			// if (!isset($component_global_dato->valor->$component_lang)) {
			// 	$component_global_dato->valor->$component_lang = new stdClass();
			// }

		# VALOR LIST OBJ
			// if (!isset($component_global_dato->valor_list)) {
			// 	$component_global_dato->valor_list = new stdClass();
			// }
			// if (!isset($component_global_dato->valor_list->$component_lang)) {
			// 	$component_global_dato->valor_list->$component_lang = new stdClass();
			// }

		# DATAFRAME  OBJ
			// if (!isset($component_global_dato->dataframe)) {
			// 	$component_global_dato->dataframe = new stdClass();
			// }

		#
		# DATO : Actualizamos el dato en el idioma actual
			$component_dato = $component_obj->get_dato_unchanged(); ## IMPORTANT !!!!! (NO usar get_dato() aquí ya que puede cambiar el tipo fijo establecido por set_dato)
				$component_global_dato->dato->{$component_lang} = $component_dato;

		#
		# VALOR : Actualizamos el valor en el idioma actual
			// switch ($component_modelo_name) {
			// 	case 'component_security_access':
			// 	case 'component_filter_records':
			// 	case 'component_security_tools':
			// 		$component_global_dato->valor->$component_lang = ''; // Don't save valor'
			// 		break;

			// 	default:
			// 		if($component_lang===$component_valor_lang && $component_traducible==='si') {
			// 			$component_global_dato->valor->$component_lang = $component_obj->get_valor();
			// 		}else{
			// 			$component_global_dato->valor->$component_lang = $component_obj->get_dato_unchanged();
			// 		}
			// 		break;
			// }

		#
		# DATO_SEARCH
			/*
			switch ($component_modelo_name) {
				case 'component_autocomplete_ts':
					if (!isset($component_global_dato->dato_search)) {
						$component_global_dato->dato_search = new stdClass();
						}
						if (!isset($component_global_dato->dato_search->$component_lang)) {
							$component_global_dato->dato_search->$component_lang = new stdClass();
						}
					$component_global_dato->dato_search->$component_lang = $component_obj->get_dato_search();
					break;
				default: // Nothing to do
			}*/


		# DATAFRAME
			$dataframe = $component_obj->get_dataframe();
			if (isset($component_global_dato->dataframe)) {
				// already exists property dataframe. Add always
				$component_global_dato->dataframe = $dataframe;
			}else{
				// not exists property. Add only if dataframe is not empty
				if (!empty($dataframe)) {
					$component_global_dato->dataframe = $dataframe;
				}
			}


		#
		# REPLACE COMPONENT PORTION OF GLOBAL OBJECT :  Actualizamos todo el componente en el objeto global
			if (!isset($dato->components->{$component_tipo})) {
				if (!isset($dato->components)) {
					$dato->components = new stdClass();
				}
				$dato->components->{$component_tipo} = new stdClass();
			}
			$dato->components->{$component_tipo} = $component_global_dato;

		// update section full data object
			$this->set_dato($dato);


		return $this->dato;
	}//end set_component_direct_dato



	/**
	* SET_COMPONENT_RELATION_DATO
	* @return object $this->dato
	*/
	public function set_component_relation_dato( object $component_obj ) : object {

		// set self section_obj to component. (!) Important to prevent cached and not cached versions of
		// current section conflicts (and for speed)
			$component_obj->set_section_obj($this);

		// component short vars
			$component_tipo			= $component_obj->get_tipo();
			$component_dato			= $component_obj->get_dato_full();
			$relation_type			= $component_obj->get_relation_type();
			$from_component_tipo	= $component_tipo;

		# Remove all previous locators of current component tipo
		$this->remove_relations_from_component_tipo( $component_tipo, 'relations' );

		# Remove all existing search locators of current component tipo
		$this->remove_relations_from_component_tipo( $component_tipo, 'relations_search' );


		if (!empty($component_dato)) {

			# ADD_RELATION . Add locator one by one
			foreach ((array)$component_dato as $key => $current_locator) {

				# Add relation
				$add_relation = $this->add_relation( $current_locator, 'relations' );
				// If something fail, advise
				if($add_relation===false) {
					debug_log(__METHOD__." ERROR ON ADD LOCATOR:  ".to_string($current_locator), logger::ERROR);
					#$result = false;
				}
			}

			# SEARCH_RELATIONS . If component have search_relations, add too
			if ($relations_search_value = $component_obj->get_relations_search_value()) {

				foreach ($relations_search_value as $current_search_locator) {
					# Add relation
					$add_relation = $this->add_relation( $current_search_locator, 'relations_search' );
					// If something fail, advise
					if($add_relation===false) {
						debug_log(__METHOD__." ERROR ON ADD SEARCH LOCATOR:  ".to_string($current_search_locator), logger::ERROR);
					}
				}
			}
		}//end if (!empty($component_dato))


		return $this->dato;
	}//end set_component_relation_dato



	/**
	* BUILD_AR_SECTION_CREATOR
	* Construye el array con los datos de creación de la sección
	* Se utilizará para filtrar las secciones virtuales en recuperación, que quedarán 'vinculadas' al portal desde donde se crearon
	* Principalmente, se plantea para gestionar eficientemente los recursos compartidos (imágenes, documentos, etc.) y poder filtrarlos por 'creador'
	* al acceder a los listados desde los mismos.
	* Ej. Las fotos de informantes creadas desde el portal de informantes, debería acceder (botón '+ Existente') a los listados de las imágenes creadas
	* desde ese portal para no mezclarlas con las imágenes de investigadores u otras secciones que acceden a la misma tipología de imagen
	*
	* @var top_tipo
	*		Tipo de la sección principal en la que estamos trabajando 'top'. Se guarda por html_page en TOP_TIPO
	*		Será el mismo que el de la propia sección, salvo que se cree desde un portal
	*
	* @var portal_section_tipo
	*		Tipo de la sección del portal desde donde se crea esta sección (si se crea desde un portal)
	*
	* @var portal_tipo
	*		Tipo del portal desde donde se crea esta sección (si se crea desde un portal)
	*/
	public static function build_ar_section_creator(string $top_tipo=null, string $portal_section_tipo=null, string $portal_tipo=null) : array {

		# top_tipo
		if (is_null($top_tipo) || empty($top_tipo) || !$top_tipo) {
			if(SHOW_DEBUG===true) {
				debug_log(__METHOD__." empty received top_tipo. Fallback made to top_tipo = $top_tipo - ".TOP_TIPO." - tipe: ".gettype($top_tipo) , logger::DEBUG);
			}
			$top_tipo = TOP_TIPO;
		}

		return array(
			'top_tipo'				=> $top_tipo,
			'portal_section_tipo'	=> $portal_section_tipo,
			'portal_tipo'			=> $portal_tipo
		);
	}//end build_section_locator



	/**
	* SAVE
	* Create or update a section record in matrix
	* @param object $save_options
	* @return int|null $section_id
	*/
	public function Save( object $save_options=null ) : ?int {

		// options
			$options = new stdClass();
				$options->main_components_obj		= false;
				$options->main_relations			= false;
				$options->new_record				= false;
				$options->forced_create_record		= false;
				$options->component_filter_dato		= false;
				// $options->is_portal				= false;
				// $options->portal_tipo			= false;
				// $options->top_tipo				= TOP_TIPO;
				// $options->top_id					= TOP_ID;

				# Time machine options (overwrite when save component)
				$options->time_machine_data			= false;
				$options->time_machine_lang			= false;
				$options->time_machine_tipo			= false;
				$options->time_machine_section_id	= (int)$this->section_id; // always

			// save_options overwrite defaults
			if (!empty($save_options)) {
				foreach ((object)$save_options as $key => $value) {
					if (property_exists($options, $key)) { $options->$key = $value; }
				}
			}

		// tipo. Current section tipo
			$tipo = $this->get_tipo();

			# If the section virtual have the section_tipo "real" in properties, change the tipo of the section to the real
			if(isset($this->properties->section_tipo) && $this->properties->section_tipo==='real'){
				$tipo = $this->get_section_real_tipo();
			}

			# Verify tipo is structure data
			if( !(bool)verify_dedalo_prefix_tipos($tipo) ) throw new Exception("Current tipo is not valid: $tipo", 1);

			# SECTION VIRTUAL . Correct tipo
			# Si estamos en una sección virtual, despejaremos el tipo real (la sección de destino) y
			# trabajaremos con el tipo real a partir de ahora
			$section_real_tipo = ($tipo===DEDALO_ACTIVITY_SECTION_TIPO)
				? $tipo
				: $this->get_section_real_tipo();

		// user id. Current logged user id
			$user_id  = (int)navigator::get_user_id();

		// date now
			$date_now = component_date::get_timestamp_now_for_db();

		// Save_handler different to database case
			// Sometimes we need use section as temporal element without save real data to database. Is this case
			// data is saved to session as temporal data and can be recovered from $_SESSION['dedalo']['section_temp_data'] using key '$this->tipo.'_'.$this->section_id'
			if (isset($this->save_handler) && $this->save_handler==='session') {

				$temp_data_uid 		= $this->tipo.'_'.$this->section_id;
				$section_temp_data 	= (object)$this->dato;

				# Set value to session
				# Always encode and decode data before store in session to avoid problems on unserialize not loaded classes
				$_SESSION['dedalo']['section_temp_data'][$temp_data_uid] = json_decode( json_encode($section_temp_data) );

				return (int)$this->section_id;
			}

		// matrix table
			$matrix_table = common::get_matrix_table_from_tipo($tipo); // This function fallback to real section if virtal section don't have table defined


		if (!empty($this->section_id) && (int)$this->section_id>=1 && $options->forced_create_record===false) { # UPDATE RECORD

			################################################################################
			# UPDATE RECORD : Update current matrix section record triggered by one component

			if ($this->save_modified===false) {
				// section dato only
					$section_dato = (object)$this->get_dato();

			}else{
				// update_modified_section_data . Resolve and add modification date and user to current section dato
					$this->update_modified_section_data((object)[
						'mode' => 'update_record'
					]);

				// section dato
					$section_dato = (object)$this->get_dato();

				// dato add modification info
					# Section modified by userID
					$section_dato->modified_by_userID	= (int)$user_id;
					# Section modified date
					$section_dato->modified_date		= (string)$date_now;	# Format 2012-11-05 19:50:44
			}

			# Save section dato
				$JSON_RecordObj_matrix	= new JSON_RecordObj_matrix( (string)$matrix_table, (int)$this->section_id, (string)$tipo );
				$JSON_RecordObj_matrix->set_datos($section_dato);
				$saved_id_matrix		= $JSON_RecordObj_matrix->Save( $options );
				if (false===$saved_id_matrix || $saved_id_matrix < 1) { //  && $tipo!==DEDALO_ACTIVITY_SECTION_TIPO
					trigger_error("Error on trying save->update record. Nothing is saved!");
					if(SHOW_DEBUG===true) {
						throw new Exception("Error Processing Request. Returned id_matrix on save (update) section is mandatory. Received id_matrix: $saved_id_matrix ", 1);
					}
				}

		}else{ # NEW RECORD

			################################################################################
			# NEW RECORD . Create and save matrix section record in correct table

			// prevent to save non authorized/valid section_id
				if ($this->section_id=='-1') {
					debug_log(__METHOD__." Trying to save invalid section_id: ".to_string($this->section_id), logger::ERROR);
					return null;
				}

			##
			# COUNTER : Counter table. Default is ¡matrix_counter¡
			# Preparamos el id del contador en función de la tabla sobre la que estamos trabajando (matrix, matrix_dd, etc.)
			# Por defecto será 'matrix_counter', peri si nuestra tabla de sección es distinta de 'matrix' usaremos una tabla de
			# contador distinta formateada como 'matrix_counter' + substr($matrix_table, 6). Por ejemplo 'matrix_counter_dd' para matrix_dd
				if ($options->forced_create_record===false) {

					// Use normal incremental counter
					$matrix_table_counter	= (substr($matrix_table, -3)==='_dd') ? 'matrix_counter_dd' : 'matrix_counter';
					$current_id_counter		= (int)counter::get_counter_value($tipo, $matrix_table_counter);

					// Create a counter if not exists
						if ($current_id_counter===0 && $tipo!==DEDALO_ACTIVITY_SECTION_TIPO) {
							$consolidate_counter = counter::consolidate_counter( $tipo, $matrix_table, $matrix_table_counter );
							// Re-check counter value
							$current_id_counter = (int)counter::get_counter_value($tipo, $matrix_table_counter);
						}

					$section_id_counter = $current_id_counter+1;

					# section_id. Fix section_id (Non return point, next calls to Save will be updates)
					$this->section_id = (int)$section_id_counter;
				}

			##
			# SECTION JSON DATA
			# Store section dato

				# SECTION_OBJ
				# When section is created at first time, section_obj is created wit basic data to write a 'empty section'
				# In some cases, before save at first time, data exits in section object. Take care of this data is added to
				# current first section data or not

					// section dato
						$section_dato = isset($this->dato) ? (object)$this->dato : new stdClass();

					// Section id
						$section_dato->section_id		 = (int)$this->section_id;

					// Section tipo
						$section_dato->section_tipo 	 = (string)$tipo;

					// Section real tipo
						$section_dato->section_real_tipo = (string)$section_real_tipo;

					// Section label
						$section_dato->label 			 = (string)RecordObj_dd::get_termino_by_tipo($tipo,null,true);

					// Section created by userID
						$section_dato->created_by_userID = (int)$user_id;

					// Section created date
						$section_dato->created_date 	 = (string)$date_now;	# Format 2012-11-05 19:50:44

					// diffusion_info
						$section_dato->diffusion_info 	 = array(); // Empty array by default

					// Section creator info
						// switch (true) {
						// 	# ACTIVITY CASE
						// 	case ($tipo===DEDALO_ACTIVITY_SECTION_TIPO):
						// 		# Nothing to do
						// 		break;

						// 	# PORTAL CASE
						// 	case ($options->is_portal===true):
						// 		$ar_section_creator	= section::build_ar_section_creator($options->top_tipo, $tipo, $options->portal_tipo);
						// 		# Section creator
						// 		$section_dato->section_creator_top_tipo 			= (string)$ar_section_creator['top_tipo'];
						// 		$section_dato->section_creator_portal_section_tipo 	= (string)$ar_section_creator['portal_section_tipo'];
						// 		$section_dato->section_creator_portal_tipo 			= (string)$ar_section_creator['portal_tipo'];
						// 		break;

						// 	# DEFAULT CASE (Normal sections)
						// 	default:
						// 		$ar_section_creator	= section::build_ar_section_creator($options->top_tipo);
						// 		# Section creator
						// 		$section_dato->section_creator_top_tipo 			= (string)$ar_section_creator['top_tipo'];
						// 		$section_dato->section_creator_portal_section_tipo 	= (string)$ar_section_creator['portal_section_tipo'];
						// 		$section_dato->section_creator_portal_tipo 			= (string)$ar_section_creator['portal_tipo'];
						// 		break;
						// }

					// Update modified section data . Resolve and add creation date and user to current section dato
						$this->update_modified_section_data((object)[
							'mode' => 'new_record'
						]);

					// Components container
						if (!empty($options->main_components_obj)) {
							// Main components obj : When creating a section, you can optionally pass the component data directly
							$section_dato->components = $options->main_components_obj;	# Añade el dato de todos los componentes de una sola vez (activity)
						}else{
							// components container (empty when insert)
							$section_dato->components = isset($this->dato->components) ? $this->dato->components : new stdClass();
						}

					// Relations container
						if (!empty($options->main_relations)) {
							// Main relations : When creating a section, you can optionally pass the data of the relationships directly
							$section_dato->relations = $options->main_relations;	# Añade el dato de todas las relaciones de una sola vez (activity)
						}else{
							// relations container
							$section_dato->relations = isset($this->dato->relations) ? (array)$this->dato->relations : [];
						}

					// update section dato with final object. Important
						$this->dato = $section_dato;


					// Set as loaded
						$this->bl_loaded_matrix_data = true;

			// Real data save
				// Time machine data. We save only current new section in time machine once (section info not change, only components changes)
					$time_machine_data = clone $section_dato;
						unset($time_machine_data->components); 	# Remove unnecessary empty 'components' object
						unset($time_machine_data->relations); 	# Remove unnecessary empty 'relations' object
					$save_options = new stdClass();
						$save_options->time_machine_data = $time_machine_data;
						$save_options->time_machine_lang = DEDALO_DATA_NOLAN;	# Always nolan for section
						$save_options->time_machine_tipo = $tipo;
						$save_options->new_record		 = true;

				// Save JSON_RecordObj
					$JSON_RecordObj_matrix = new JSON_RecordObj_matrix((string)$matrix_table, (int)$this->section_id, (string)$tipo);
					$JSON_RecordObj_matrix->set_datos($section_dato);
					#$JSON_RecordObj_matrix->set_section_id($this->section_id);
					#$JSON_RecordObj_matrix->set_section_tipo($tipo);
					$saved_id_matrix = $JSON_RecordObj_matrix->Save( $save_options );
					if (false===$saved_id_matrix || $saved_id_matrix < 1) { //  && $tipo!==DEDALO_ACTIVITY_SECTION_TIPO
						trigger_error("Error on trying save->insert record. Nothing is saved!");
						if(SHOW_DEBUG===true) {
							throw new Exception("Error Processing Request. Returned id_matrix on save section is mandatory. Received id_matrix: $saved_id_matrix ", 1);
						}
					}


			if($this->tipo===DEDALO_ACTIVITY_SECTION_TIPO) {

				# (!) Note that value returned by Save action, in case of activity, is the section_id auto created by table sequence 'matrix_activity_section_id_seq', not by counter
				$this->section_id = (int)$saved_id_matrix;

			}else{

				// Counter update : If all is ok, update section counter (counter +1) in structure 'properties:section_id_counter'
				if ($saved_id_matrix > 0) {
					if ($options->forced_create_record!==false) {
						# CONSOLIDATE COUNTER VALUE
						# Search last section_id for current section and set counter to this value (when user later create a new record manually, counter will be ok)
						counter::consolidate_counter($tipo, $matrix_table);

					}else{
						# Counter update
						counter::update_counter($tipo, $matrix_table_counter, $current_id_counter);
					}
				}

				// Store in cached sections . (!) Important
					# key for cache
					$key = $this->section_id .'_'. $tipo;
					self::$ar_section_instances[$key] = $this;


				// TOP_ID : Si se crea desde un portal, el top_id está fijado en sesion "TOP_ID". Si no, es el propio section_id de la sección creada
					// $top_id = ($options->is_portal===true) ? TOP_ID : $this->section_id;

				// Logger activity
					logger::$obj['activity']->log_message(
						'NEW',
						logger::INFO,
						$this->tipo,
						null,
						array(
							'msg'			=> 'Created section record',
							'section_id'	=> $this->section_id,
							'section_tipo'	=> $this->tipo,
							'tipo'			=> $this->tipo,
							'table'			=> $matrix_table
							// "is_portal"	=> intval($options->is_portal),
							// "top_id"		=> $top_id,
							// "top_tipo"	=> TOP_TIPO,
							// "tm_id"		=> 'desactivo',#$time_machine_last_id,
							// "counter"	=> counter::get_counter_value($this->tipo, $matrix_table_counter),
						)
					);

				##
				# FILTER DEFAULTS SET
				if ($this->tipo===DEDALO_SECTION_PROJECTS_TIPO) {

					##
					# AUTO AUTHORIZE THIS PROYECT FOR CURRENT USER
					# Si esta sección recien creada es un proyecto, se agrega este proyecto como autorizado al usuario que lo creó
					# Usuario logeado actualmente
						$component_filter_master = component_common::get_instance(
							'component_filter_master',
							DEDALO_FILTER_MASTER_TIPO,
							$user_id,
							'edit',
							DEDALO_DATA_NOLAN,
							DEDALO_SECTION_USERS_TIPO
						);
						$dato_filter_master = $component_filter_master->get_dato();

						$filter_master_locator = new locator();
							$filter_master_locator->set_section_id($this->section_id);
							$filter_master_locator->set_section_tipo(DEDALO_FILTER_SECTION_TIPO_DEFAULT);
							$filter_master_locator->set_type(DEDALO_RELATION_TYPE_FILTER);
							$filter_master_locator->set_from_component_tipo(DEDALO_FILTER_MASTER_TIPO);
						$dato_filter_master[] = $filter_master_locator; // Add locator to dato

						$component_filter_master->set_dato($dato_filter_master);
						$component_filter_master->Save();
						debug_log(__METHOD__." Added locator from section save to component_filter_master: ".to_string($filter_master_locator), logger::DEBUG);

				}else{

					# Filter defaults. Note that portal already saves inherited project to new created section
					# To avoid saves twice, only set default project when not is a portal call to create new record


					##
					# DEFAULT PROJECT FOR CREATE STANDAR SECTIONS
					# Cuando se crea un registro de sección, se auto asigna el proyecto por defecto (definido en config DEDALO_DEFAULT_PROJECT)
					# cuando la sección tiene definido un 'component_filter'
					$ar_tipo_component_filter = section::get_ar_children_tipo_by_modelo_name_in_section(
						$section_real_tipo,
						['component_filter'],
						true, // from_cache
						false, // resolve_virtual
						true // cache
					);
					if (empty($ar_tipo_component_filter[0])) {

						if(SHOW_DEBUG===true) {
							#throw new Exception("Error Processing Request. Too much component_filter elements found", 1);
						}
						debug_log(__METHOD__." Ignored set filter default in section without filter: $this->tipo ".to_string(), logger::WARNING);

					}else{

						if (!empty($options->component_filter_dato)) {
							// set the component_filter with the dato sended by the caller (portals)
							$component_filter 	= component_common::get_instance(
								'component_filter',
								$ar_tipo_component_filter[0],
								$this->section_id,
								'list', // Important 'list' to avoid auto save default value !!
								DEDALO_DATA_NOLAN,
								$tipo
							);
							$component_filter->set_dato($options->component_filter_dato);
							$component_filter->Save();

						}else{
							# When component_filter is called in edit mode, the component check if dato is empty and if is,
							# add default user project and save it
							// (!) Note that construct component_filter in edit mode, saves default value too. Here, current section is saved again
							$component_filter = component_common::get_instance(
								'component_filter',
								$ar_tipo_component_filter[0],
								$this->section_id,
								'edit', // Important edit !! # Already saves default project when load in edit mode
								DEDALO_DATA_NOLAN,
								$tipo
							);
							// note that section is auto-saved here
						}
					}//end if (empty($ar_tipo_component_filter[0]))

				}//end if ($this->tipo===DEDALO_SECTION_PROJECTS_TIPO)


				// component state defaults set. Set default values on component_state when is present
					$ar_component_state = section::get_ar_children_tipo_by_modelo_name_in_section(
						$section_real_tipo, // section_tipo
						['component_state'], // ar_modelo_name_required
						true, // from_cache
						false, // resolve_virtual
						true // recursive
					);
					if (isset($ar_component_state[0])) {
						$component_state = component_common::get_instance(
							'component_state',
							$ar_component_state[0],
							$this->section_id,
							'edit',
							DEDALO_DATA_NOLAN,
							$tipo
						);
						// (!) Note that set_defaults saves too. Here, current section is saved again if component_state is founded
						$component_state->set_defaults();
					}//end if (isset($ar_component_state[0]))

			}//end if($this->tipo!==DEDALO_ACTIVITY_SECTION_TIPO)
		}//end if ($this->id >= 1)


		// debug
			if(SHOW_DEBUG===true) {
				// global$TIMER;$TIMER[__METHOD__.'_OUT_'.$this->tipo.'_'.$this->modo.'_'.start_time()]=start_time();
			}


		return $this->section_id;
	}//end Save



	/**
	* DELETE (SECTION)
	* Delete section with options
	* @param string $delete_mode data|record
	* @return bool
	*/
	public function Delete( string $delete_mode ) : bool {

		// section_id
			// force type int
			$section_id = intval($this->section_id);
			// prevent delete <1 records
			if($section_id<1) {
				debug_log(__METHOD__." Invalid section_id: $section_id. Delete action is aborted ".to_string(), logger::WARNING);
				return false;
			}

		// section_tipo
			$section_tipo = $this->tipo;
			// section_real_tipo. If the section virtual have the section_tipo "real" in properties change the tipo of the section to the real
			if(isset($this->properties->section_tipo) && $this->properties->section_tipo === "real"){
				$section_tipo = $this->get_section_real_tipo();
			}
			// user id
			$user_id = navigator::get_user_id();
			// matrix_table
			$matrix_table = common::get_matrix_table_from_tipo($section_tipo);


		// delete_mode based actions
			switch($delete_mode) {

				case 'delete_data' :

					# CHILDREN : Calculate components children of current section
					$ar_component_tipo = section::get_ar_children_tipo_by_modelo_name_in_section(
						$section_tipo ,
						['component_'],
						true, // from_cache
						true, // resolve virtual
						true, // recursive
						false, // search exact
					);

					// don't delete some components
					$ar_components_modelo_no_delete_dato = [
						'component_section_id'
					];

					$ar_models_of_media_components = section::get_components_with_media_content();

					$ar_deleted_tipos = [];
					foreach ($ar_component_tipo as $current_component_tipo) {

						$current_model_name = RecordObj_dd::get_modelo_name_by_tipo($current_component_tipo, true);

						if (in_array($current_model_name, $ar_components_modelo_no_delete_dato)){
							continue;
						}

						$translatable	= RecordObj_dd::get_translatable($current_component_tipo);
						$ar_lang		= ($translatable === false)
							? [DEDALO_DATA_NOLAN]
							: DEDALO_PROJECTS_DEFAULT_LANGS;

						foreach ($ar_lang as $current_lang) {

							$current_component = component_common::get_instance(
								$current_model_name,
								$current_component_tipo,
								$section_id,
								'list',
								$current_lang,
								$section_tipo,
								false
							);

							$current_component_dato = $current_component->get_dato();
							if(empty($current_component_dato)){
								continue;
							}

							$dato_empty = ($current_model_name === 'component_filter')
								? $current_component->get_default_dato_for_user($user_id)
								: null;

							$current_component->set_dato($dato_empty);
							$current_component->Save();
						}

						if(in_array($current_model_name, $ar_models_of_media_components)){
							$current_component->remove_component_media_files();
						}

						$ar_deleted_tipos[] = $current_component_tipo;
					}

					// remove component inside section data in DDBB
						$section_data = $this->get_dato();
						foreach($ar_deleted_tipos as $current_component_tipo){
							if(isset($section_data->components->$current_component_tipo)){
								unset($section_data->components->$current_component_tipo);
							}
						}
						$this->Save();

					$logger_msg = "Deleted section and children data";
					break;

				case 'delete_record' :

					#
					# TIME MACHINE : prepare matrix_time_machine data for recover this section later
					# Get time machine id based on section tipo and section_id
					$ar_id_time_machine = (array)RecordObj_time_machine::get_ar_time_machine_of_this($section_tipo, $this->section_id, 'lg-nolan', $section_tipo); // $tipo, $parent, $lang=NULL, $section_tipo
					if (empty($ar_id_time_machine[0])) {
						#return "Error on delete record. Time machine version of this record not exists. Please contact with your admin to delete this record";
						$RecordObj_time_machine_new = new RecordObj_time_machine(null);
							$RecordObj_time_machine_new->set_section_id((int)$this->section_id);
							$RecordObj_time_machine_new->set_section_tipo((string)$section_tipo);
							$RecordObj_time_machine_new->set_tipo((string)$section_tipo);
							$RecordObj_time_machine_new->set_lang((string)$this->get_lang());
							$RecordObj_time_machine_new->set_timestamp((string)component_date::get_timestamp_now_for_db());	# Format 2012-11-05 19:50:44
							$RecordObj_time_machine_new->set_userID((int)navigator::get_user_id());
							$RecordObj_time_machine_new->set_dato((object)$this->dato);
						$id_time_machine = (int)$RecordObj_time_machine_new->Save();
					}else{
						$id_time_machine = (int)$ar_id_time_machine[0];
					}
					if ($id_time_machine<1) {
						throw new Exception("Error Processing Request. id_time_machine is empty", 1);
					}
					# Update time machine record
					$RecordObj_time_machine = new RecordObj_time_machine($id_time_machine);
						$RecordObj_time_machine->set_dato($this->get_dato());	// Update dato with the last data stored in this section before is deleted
						$RecordObj_time_machine->set_state('deleted');			// Mark state as 'deleted' for fast recovery
					$tm_save = (int)$RecordObj_time_machine->Save();			// Expected int id_time_machine returned if all is ok
					# Verify time machine is updated properly before delete this section
					if ($tm_save!==$id_time_machine) {
						# Something failed in time machine save
						if(SHOW_DEBUG===true) {
							dump($tm_save, " tm_save is distinct: tm_save:$tm_save - id_time_machine:$id_time_machine");
						}
						trigger_error("ERROR: Failed save update data for time machine record $id_time_machine [Section:Delete]. Record is NOT deleted (2)");
						return false;
					}
					$dato_time_machine 	= $RecordObj_time_machine->get_dato();
					$dato_section 		= $this->get_dato();

					// before compare, encode and decode the objects to avoid comparison errors
						// $dato_time_machine_compare	= json_decode( json_encode($dato_time_machine) );
						// $dato_section_compare		= json_decode( json_encode($dato_section) );

					if ($dato_time_machine != $dato_section) {
						if(SHOW_DEBUG===true) {
							dump($dato_time_machine,"SHOW_DEBUG COMPARE ERROR dato_time_machine");
							dump($dato_section,"SHOW_DEBUG COMPARE ERROR dato_section");
						}
						#trigger_error("ERROR: Failed compare data of time machine record $id_time_machine [Section:Delete]. Record is NOT deleted (3)");
						throw new Exception("ERROR: Failed compare data of time machine record $id_time_machine [Section:Delete]. Record is NOT deleted (3)", 1);

						return false;
					}


					#
					# SECTION DELETE
					# Delete matrix record
					$JSON_RecordObj_matrix	= new JSON_RecordObj_matrix($matrix_table, $this->section_id, $section_tipo);
					$JSON_RecordObj_matrix->MarkForDeletion();


					#
					# INVERSE REFERENCES
					# Remove all inverse references to this section
					$this->remove_all_inverse_references();


					#
					# RELATION REFERENCES
					# Remove all relation references (children, model, etc.)
					# $this->remove_all_relation_references();


					#
					# MEDIA
					# Remove media files associated to this section
					$this->remove_section_media_files();


					$logger_msg = "DEBUG INFO ".__METHOD__." Deleted section and children records. delete_mode $delete_mode";
					break;

				default:

					debug_log(__METHOD__." Delete mode not defined ".to_string(), logger::ERROR);
					return false;
			}
			debug_log(__METHOD__." Deleted section '$this->section_id' and their 'children'. delete_mode:'$delete_mode'", logger::DEBUG);

		// publication . Remove published records in mysql, etc.
			try {
				diffusion::delete_record($this->tipo, $this->section_id);
			} catch (Exception $e) {
				debug_log(__METHOD__." Error on diffusion::delete_record: ".$e->getMessage(), logger::WARNING);
			}

		// log
			$is_portal = (TOP_TIPO!==$this->tipo);
			# LOGGER ACTIVITY : QUE(action normalized like 'LOAD EDIT'), LOG LEVEL(default 'logger::INFO'), TIPO(like 'dd120'), DATOS(array of related info)
			logger::$obj['activity']->log_message(
				'DELETE',
				logger::INFO,
				$this->get_tipo(),
				null,
				array(
					'msg'			=> $logger_msg,
					'section_id'	=> $this->section_id,
					'tipo'			=> $this->tipo,
					'is_portal'		=> intval($is_portal),
					'top_id'		=> TOP_ID,
					'top_tipo'		=> TOP_TIPO,
					'table'			=> $matrix_table,
					'delete_mode'	=> $delete_mode,
					'section_tipo'	=> $this->tipo
				)
			);

		// DEDALO_CACHE_MANAGER : get_ar_filter_cache
			if( DEDALO_CACHE_MANAGER===true ) {
				cache::del_contains( $this->tipo );
			}


		return true;
	}//end Delete



	/**
	* GET_SECTION_CACHE_KEY_NAME
	* @return string
	*/
	public function get_section_cache_key_name() : string {
		$pageNum='';

		$var_requested = common::get_request_var('m');
		if(!empty($var_requested)) $pageNum = $var_requested;

		return DEDALO_DATABASE_CONN.'_section_get_html_'.$this->get_identificador_unico().'_'.$pageNum;
	}//end get_section_cache_key_name



	/**
	* GET_SECTION_REAL_TIPO
	* @return string $section_real_tipo
	*/
	public function get_section_real_tipo() : string {

		if(isset($this->section_real_tipo)) return $this->section_real_tipo;

		$section_real_tipo = section::get_section_real_tipo_static( $this->tipo );
		if ($section_real_tipo!==$this->tipo) {
			# Fix section_real_tipo
			$this->section_real_tipo = $section_real_tipo;
			$this->section_virtual 	 = true;
		}else{
			# Fix section_real_tipo
			$this->section_real_tipo = $section_real_tipo;
			$this->section_virtual 	 = false;
		}

		return $section_real_tipo;
	}//end get_section_real_tipo



	/**
	* GET_SECTION_REAL_TIPO_STATIC
	* @param string $section_tipo
	* @return string $section_real_tipo
	*	If not exists related section, returns the same received section_tipo
	*/
	public static function get_section_real_tipo_static(string $section_tipo) : string {

		$ar_related = common::get_ar_related_by_model($modelo_name='section', $section_tipo);
		if (isset($ar_related[0])) {
			$section_real_tipo = $ar_related[0];
		}else{
			$section_real_tipo = $section_tipo;
		}

		return $section_real_tipo;
	}//end get_section_real_tipo_static



	/**
	* GET CHILDRENS OBJS BY MODELO NAME
	*
	* @param $modelo_name_required
	*	Name of desired filtered model. You can use partial name like 'component_' (string position search is made it)
	* @see class.section.php -> get_ar_authorized_areas_for_user
	* @return $ar_section_obj
	*	Array of objects (usually components) filtered by modelo_name_required with parent = current section id matrix
	*/
	public function get_ar_children_objects_by_modelo_name_in_section(string $modelo_name_required, bool $resolve_virtual=true) : array {

		$ar_section_obj = array();

		if(SHOW_DEBUG===true) {
			$start_time = start_time();
			// global$TIMER;$TIMER[__METHOD__.'_IN_'.$modelo_name_required.'_'.$this->tipo.'_'.$this->modo.'_'.start_time()]=start_time();
		}

		$parent  = intval($this->get_section_id());
		$tipo	 = $this->get_tipo();


			# RESOLVE_VIRTUAL : Resolve virtual section to real
			if($resolve_virtual) {

				# ORIGINAL TIPO : always keeps the original type (current)
				$original_tipo = $tipo;

				# SECTION VIRTUAL
				$section_real_tipo = $this->get_section_real_tipo();
				if($section_real_tipo!=$original_tipo) {

					# OVERWRITE CURRENT SECTION TIPO WITH REAL SECTION TIPO
					$tipo = $section_real_tipo;
				}
			}


		# STATIC CACHE
		$uid = $parent .'_'. $tipo .'_'. $modelo_name_required;
		static $ar_children_objects_by_modelo_name_in_section;
		if(isset($ar_children_objects_by_modelo_name_in_section[$uid])) {

			if(SHOW_DEBUG===true) {
				// global$TIMER;$TIMER[__METHOD__.'_OUT_STATIC_'.$modelo_name_required.'_'.$this->tipo.'_'.$this->modo.'_'.start_time()]=start_time();
				#debug_log(__METHOD__." Returned '$modelo_name_required' for tipo:$this->tipo FROM STATIC CACHE");
			}
			return $ar_children_objects_by_modelo_name_in_section[$uid];
		}


		# GET SECTION ELEMENT CHILDRENS - OBTENEMOS LOS ELEMENTOS HIJOS DE ESTA SECCIÓN
		switch (true) {
			# For buttons only need one level
			case (strpos($modelo_name_required, 'button_')!==false):
				$ar_recursive_childrens = (array)RecordObj_dd::get_ar_childrens($tipo);
				break;
			default:
				$ar_recursive_childrens = (array)section::get_ar_recursive_children($tipo);
		}
		if(SHOW_DEBUG===true) {
			#dump($ar_recursive_childrens, 'ar_recursive_childrens tipo:'.$tipo." - modelo_name_required:$modelo_name_required", array()); dump($this," ");
			#debug_log( __METHOD__." get_ar_children_objects_by_modelo_name_in_section: ".json_encode($modelo_name_required) );
		}


		if( empty($ar_recursive_childrens) ) {
			#throw new Exception(__METHOD__." ar_recursive_childrens is empty! This section don't have: '$modelo_name_required' ");
			#debug_log(__METHOD__." ar_recursive_childrens is empty! This section id=$parent don't have: '$modelo_name_required' (tipo:$tipo) 384 ". __METHOD__ );
			return NULL	;
		}

		# Recorremos los elementos hijos de la sección actual en el tesauro
		foreach($ar_recursive_childrens as $terminoID) {

			# Clear obj on every iteration
			$current_obj 		= null;
			$modelo_name		= RecordObj_dd::get_modelo_name_by_tipo($terminoID, true);


			# Filtramos para cargar sólo los del modelo deseado
			if( strpos($modelo_name, $modelo_name_required)===false ) continue; # Skip


			# Construimos el objeto (en función del tipo deseado se construye de forma distinta: component, button, etc..)
			switch(true) {

				# Build component obj
				case (strpos($modelo_name, 'component_')!==false) :

					$current_obj = component_common::get_instance($modelo_name, $terminoID, $parent,'edit', DEDALO_DATA_LANG, $this->tipo ); #$id=NULL, $tipo=NULL, $modo='edit', $parent=NULL, $lang=DEDALO_DATA_LANG
					break;

				# Build button obj
				case (strpos($modelo_name, 'button_')!==false) :

					if ($modelo_name==='button_delete') break; # Skip Delete buttons

					$current_obj = new $modelo_name($terminoID, $target=$parent, $this->tipo);
					$current_obj->set_context_tipo($tipo);
					break;

				default :
					trigger_error("Sorry, element $modelo_name is not defined for build object");
			}


			# Add well formed object to array
				if(is_object($current_obj)) {
					$ar_section_obj[] = $current_obj;
				}
		}

		// STORE CACHE DATA
		$ar_children_objects_by_modelo_name_in_section[$uid] = $ar_section_obj ;


		return $ar_section_obj;
	}//end get_ar_children_objects_by_modelo_name_in_section



	/**
	* GET_SECTION_AR_CHILDREN_TIPO
	* @param string $section_tipo
	* @param array $ar_modelo_name_required
	* @param bool $from_cache
	*	default true
	* @param bool $resolve_virtual
	*	Force resolve section if is virtual section. default false
	*	Name of desired filtered model array. You can use partial name like 'component_' (string position search is made it)
	* @return array $section_ar_children_tipo
	*/
	public static function get_ar_children_tipo_by_modelo_name_in_section(
			string $section_tipo,
			array $ar_modelo_name_required,
			bool $from_cache=true,
			bool $resolve_virtual=false, // (!) keep default resolve_virtual=false
			bool $recursive=true,
			bool $search_exact=false,
			$ar_tipo_exclude_elements=false
		) : array {

		# AR_MODELO_NAME_REQUIRED cast 'ar_modelo_name_required' to array
		$ar_modelo_name_required = (array)$ar_modelo_name_required;

		static $cache_ar_children_tipo;
		$cache_uid = $section_tipo.'_'.serialize($ar_modelo_name_required).'_'.(int)$resolve_virtual.'_'.(int)$recursive;
		if ($from_cache===true) {
			if (isset($cache_ar_children_tipo[$cache_uid])) {
				return $cache_ar_children_tipo[$cache_uid];
			}
			// elseif (isset($_SESSION['dedalo']['config']['ar_children_tipo_by_modelo_name_in_section'][$cache_uid])) {
			// 	return $_SESSION['dedalo']['config']['ar_children_tipo_by_modelo_name_in_section'][$cache_uid];
			// }
		}

		$ar_terminos_relacionados_to_exclude = [];

		#
		# RESOLVE_VIRTUAL : Resolve virtual section to real
		if(true===$resolve_virtual) {

			# ORIGINAL TIPO : always keeps the original type (current)
			$original_tipo = $section_tipo;

			# SECTION VIRTUAL
			$section_real_tipo = section::get_section_real_tipo_static($section_tipo);

			if($section_real_tipo!==$original_tipo) {

				# OVERWRITE CURRENT SECTION TIPO WITH REAL SECTION TIPO
				$section_tipo = $section_real_tipo;

				# EXCLUDE ELEMENTS
				if ($ar_tipo_exclude_elements===false) {
					$ar_tipo_exclude_elements = RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation(
						$original_tipo, // tipo
						$modelo_name='exclude_elements', // modelo_name
						$relation_type='children', // relation_type
						$search_exact // search_exact
					);
				}
				if (!isset($ar_tipo_exclude_elements[0])) {
					#throw new Exception("Error Processing Request. exclude_elements of section $original_tipo not found. Exclude elements is mandatory (1)", 1);
					error_log("Warning. exclude_elements of section $original_tipo not found (1)");
				}else{

					$tipo_exclude_elements = $ar_tipo_exclude_elements[0];

					$ar_terminos_relacionados_to_exclude = RecordObj_dd::get_ar_terminos_relacionados($tipo_exclude_elements, $cache=false, $simple=true);

					foreach ($ar_terminos_relacionados_to_exclude as $key => $component_tipo) {

						$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($component_tipo, true);
						if($modelo_name==='section_group') {
							$ar_recursive_childrens 			 = (array)section::get_ar_recursive_children($component_tipo);
							$ar_terminos_relacionados_to_exclude = array_merge($ar_terminos_relacionados_to_exclude,$ar_recursive_childrens);
						}

					}//end foreach ($ar_terminos_relacionados_to_exclude as $key => $component_tipo) {
				}

			}//end if($section_real_tipo!=$original_tipo) {
		}//end if($resolve_virtual)

		$tipo						= $section_tipo;
		$section_ar_children_tipo	= array();


		# OBTENEMOS LOS ELEMENTOS HIJOS DE ESTA SECCIÓN
		if (count($ar_modelo_name_required)>1) {

			if (true===$recursive) { // Default is recursive
				$ar_recursive_childrens = (array)section::get_ar_recursive_children($tipo);
			}else{
				$RecordObj_dd			= new RecordObj_dd($tipo);
				$ar_recursive_childrens = (array)$RecordObj_dd->get_ar_childrens_of_this();
			}

		}else{

			switch (true) {
				// Components are searched recursively
				case (strpos($ar_modelo_name_required[0], 'component')!==false && $recursive!==false):
					$ar_recursive_childrens = (array)section::get_ar_recursive_children($tipo);
					break;
				// Others (section_xx, buttons, etc.) are in the first level
				default:
					$RecordObj_dd			= new RecordObj_dd($tipo);
					$ar_recursive_childrens = (array)$RecordObj_dd->get_ar_childrens_of_this();
					break;
			}
		}

		/*
			if($ar_modelo_name_required[0]=='section_list') {
				# En los casos en que buscamos modelos 'section_list' no buscaremos recusivamente para posibilitar el anidamiento de secciones
				# como el caso de 'Elementos y procesos' en PCI
				$RecordObj_dd			= new RecordObj_dd($tipo);
				$ar_recursive_childrens = (array)$RecordObj_dd->get_ar_childrens_of_this();
			}else{
				#$RecordObj_dd			= new RecordObj_dd($tipo);
				#$ar_recursive_childrens = (array)$RecordObj_dd->get_ar_recursive_childrens_of_this($tipo);
				$ar_recursive_childrens = (array)section::get_ar_recursive_children($tipo);
			}
			*/

		if( empty($ar_recursive_childrens) ) {
			#throw new Exception(__METHOD__." ar_recursive_childrens is empty! This section don't have: '$modelo_name_required' ");
			#debug_log(__METHOD__." ar_recursive_childrens is empty! This section id=$parent don't have: '$modelo_name_required' ". __METHOD__ );
			return $section_ar_children_tipo; # return empty array
		}

		# UNSET the exclude elements of the virtual section to the original section
		if($resolve_virtual) {
			$ar_recursive_childrens = array_diff($ar_recursive_childrens,$ar_terminos_relacionados_to_exclude);
		}
		# Recorremos los elementos hijos de la sección actual en el tesauro
		foreach($ar_recursive_childrens as $current_terminoID) {

			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_terminoID, true);
			foreach((array)$ar_modelo_name_required as $modelo_name_required) {

				if (strpos($modelo_name, $modelo_name_required)!==false && !in_array($current_terminoID, $section_ar_children_tipo) ) {

					if($search_exact===true && $modelo_name!==$modelo_name_required) {
						# No is accepted model
					}else{
						$section_ar_children_tipo[] = $current_terminoID;
					}
				}

				# COMPONENT_FILTER : Si buscamos 'component_filter', sólo devolveremos el primero, dado que pueden haber secciones anidadas
				if($ar_modelo_name_required[0]==='component_filter' && count($ar_recursive_childrens)>1) {
					if(SHOW_DEBUG===true) {
						#debug_log(__METHOD__." Broken loop for search 'component_filter' in section $section_tipo ".count($ar_recursive_childrens). " " .to_string($ar_modelo_name_required));
						#throw new Exception("Error Processing Request", 1);
					}
					continue;
				}
			}
		}//end foreach($ar_recursive_childrens as $current_terminoID)

		// Cache session store
		$cache_ar_children_tipo[$cache_uid] = $section_ar_children_tipo;
		// $_SESSION['dedalo']['config']['ar_children_tipo_by_modelo_name_in_section'][$cache_uid] = $section_ar_children_tipo;


		return $section_ar_children_tipo;
	}//end get_ar_children_tipo_by_modelo_name_in_section



	/**
	* GET_AR_RECURSIVE_CHILDREN : private alias of RecordObj_dd::get_ar_recursive_childrens
	* Note the use of $ar_exclude_models to exclude not desired section elements, like auxiliary sections in ich
	* @param string $tipo
	* @return array $ar_recursive_children
	*/
	public static function get_ar_recursive_children(string $tipo) : array {

		# AR_EXCLUDE_MODELS
		# Current elements and children are not considerate part of section and must be excluded in children results
		$ar_exclude_models = [
			'box elements',
			'area'
		];

		$ar_recursive_children = RecordObj_dd::get_ar_recursive_childrens(
			$tipo, // string tipo
			false, // bool is recursion
			$ar_exclude_models, // array ar_exclude_models
			'norden' // string order
		);

		return (array)$ar_recursive_children;
	}//end get_ar_recursive_children



	/**
	* GET_PORTAL_TIPO_FROM_COMPONENT
	* Return portal tipo from section and portal inside component
	* @param string $section_tipo
	* @param string $component_tipo_inside_portal
	* @return string|null $portal_tipo
	*/
	public static function get_portal_tipo_from_component(string $section_tipo, string $component_tipo_inside_portal) : ?string {

		$ar_portals = (array)section::get_ar_children_tipo_by_modelo_name_in_section(
			$section_tipo,
			['component_portal'],
			$from_cache=true,
			$resolve_virtual=true,
			$recursive=true,
			$search_exact=true
		);

		// empty case
			if (empty($ar_portals)) {
				return false;
			}

		foreach ($ar_portals as $current_portal_tipo) {
			# portal related terms
			$ar_related = RecordObj_dd::get_ar_terminos_relacionados($current_portal_tipo, true, true);
			if (in_array($component_tipo_inside_portal, $ar_related)) {
				// break and return tipo
				return $current_portal_tipo;
			}
		}

		return null;
	}//end get_portal_tipo_from_component



	/**
	* get_portal_tipo_from_component_in_search_list
	* Return portal tipo from section and portal inside component
	* @param string $section_tipo
	* @param string $component_tipo_inside_portal
	* @return string $portal_tipo / bool false
	*/
	public static function get_portal_tipo_from_component_in_search_list(string $section_tipo, string $component_tipo_inside_portal) : ?string {

		$ar_portals = (array)section::get_ar_children_tipo_by_modelo_name_in_section($section_tipo, ['component_portal'], $from_cache=true, $resolve_virtual=true, $recursive=true, $search_exact=true);

		// empty case
			if (empty($ar_portals)) {
				return null;
			}

		foreach ($ar_portals as $current_portal_tipo) {

			# $portal search list =
			$portal_search_list = RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($current_portal_tipo, 'search_list', 'children', true);

			if (empty($portal_search_list[0])) {
				continue;
			}

			# portal related terms
			$ar_related = RecordObj_dd::get_ar_terminos_relacionados($portal_search_list[0], true, true);
			if (in_array($component_tipo_inside_portal, $ar_related)) {
				return $current_portal_tipo;
			}
		}

		return null;
	}//end get_portal_tipo_from_component_in_search_list



	/**
	* GET_AR_BUTTONS DES
	* resolve the buttons of this section and load it in : $this->ar_buttons
	* Calcula los bonones de esta sección y los deja disponibles como : $this->ar_buttons
	* @see section_records.php modo:list
	*/
		// public function get_ar_buttons() {

		// 	if (isset($this->ar_buttons)) return $this->ar_buttons;

		// 	if(SHOW_DEBUG===true) {
		// 		global$TIMER;$TIMER[__METHOD__.'_IN_'.$this->tipo.'_'.$this->modo.'_'.start_time()]=start_time();
		// 	}

		// 	# SECTION_REAL_TIPO
		// 	$section_real_tipo  = $this->get_section_real_tipo();	# Fija $this->section_real_tipo que es necesario luego

		// 	#
		// 	# VIRTUAL SECTION
		// 	#
		// 	# SECTION VIRTUAL CASE
		// 	if ($this->section_virtual===true ) {
		// 		# Exclude elements of layout edit.
		// 		# Localizamos el elemento de tipo 'exclude_elements' que será hijo de la sección actual
		// 		# $exclude_elements_tipo = section::get_ar_children_tipo_by_modelo_name_in_section($this->tipo, 'exclude_elements')[0];
		// 		$ar_exclude_elements_tipo = section::get_ar_children_tipo_by_modelo_name_in_section($this->tipo, 'exclude_elements');
		// 		$ar_excluded_tipo 		  = array();
		// 		if (!isset($ar_exclude_elements_tipo[0])) {
		// 			#throw new Exception("Error Processing Request. exclude_elements of section $this->tipo not found. Exclude elements is mandatory (2)", 1);
		// 			error_log("Warning. exclude_elements of section $this->tipo not found (2)");
		// 		}else{
		// 			# Localizamos los elementos a excluir que son los términos relacionados con este elemento ('exclude_elements')
		// 			$ar_excluded_tipo = RecordObj_dd::get_ar_terminos_relacionados($ar_exclude_elements_tipo[0], $cache=false, $simple=true);
		// 		}

		// 		$ar_obj_button_all = $this->get_ar_children_objects_by_modelo_name_in_section('button_',true);
		// 		$ar_secton_real_buttons = array();
		// 		foreach ($ar_obj_button_all as $current_obj_button) {
		// 			if(!in_array($current_obj_button->get_tipo(), $ar_excluded_tipo)){
		// 				$ar_secton_real_buttons[] = $current_obj_button;
		// 			}
		// 		}
		// 		#add the specific buttons of the virtual section, if the virtual have buttons add to the list.
		// 		$ar_section_virtual_buttons = $this->get_ar_children_objects_by_modelo_name_in_section('button_',false);
		// 		$ar_buttons = array_merge($ar_section_virtual_buttons,$ar_secton_real_buttons);

		// 	}else{
		// 		#if the section is a real section see the buttons directly
		// 		#$ar_buttons = section::get_ar_children_tipo_by_modelo_name_in_section($this->tipo, 'button_');
		// 		$ar_buttons = $this->get_ar_children_objects_by_modelo_name_in_section('button_',false);
		// 	}//end if ($this->section_virtual==true )

		// 	# Group result by modelo name
		// 	if($ar_buttons) foreach ($ar_buttons as $current_obj_button) {
		// 		$current_modelo_name = get_class($current_obj_button);
		// 		$this->ar_buttons[$current_modelo_name][] = $current_obj_button;
		// 	}

		// 	if(SHOW_DEBUG===true) {
		// 		global$TIMER;$TIMER[__METHOD__.'_OUT_'.$this->tipo.'_'.$this->modo.'_'.start_time()]=start_time();
		// 	}

		// 	return $this->ar_buttons;
		// }//end get_ar_buttons



	/**
	* GET_SECTION_BUTTONS_TIPO
	* Calculates current section buttons tipo considering virtual section cases
	* @return array $ar_buttons_tipo
	*/
	public function get_section_buttons_tipo() : array {

		// section_real_tipo
			$section_real_tipo = $this->get_section_real_tipo();

		// section virtual case
		if ($section_real_tipo!==$this->tipo) {

			// ar_excluded_tipo. Exclude elements of layout edit
			// vars: $section_tipo, $ar_modelo_name_required, $from_cache=true, $resolve_virtual=false, $recursive=true, $search_exact=false, $ar_tipo_exclude_elements=false
				$ar_excluded_tipo			= false;
				$ar_exclude_elements_tipo	= section::get_ar_children_tipo_by_modelo_name_in_section(
					$this->tipo, // section_tipo
					['exclude_elements'], // ar_modelo_name_required
					true // from_cache
				);
				if (!isset($ar_exclude_elements_tipo[0])) {
					error_log("Warning. exclude_elements of section $this->tipo not found (2). All virtual section must has defined exclude_elements");
				}else{
					// locate excluded tipos (related terms) in this virtual section
					$ar_excluded_tipo = RecordObj_dd::get_ar_terminos_relacionados($ar_exclude_elements_tipo[0], $cache=false, $simple=true);
				}

			// real section
				$children_real_tipo = section::get_ar_children_tipo_by_modelo_name_in_section(
					$section_real_tipo, // section_tipo
					['button_'], // ar_modelo_name_required
					true, // from_cache
					false, // resolve_virtual
					false, // recursive
					false, // search_exact
					$ar_excluded_tipo // ar_tipo_exclude_elements
				);

			// virtual section. Add the specific buttons of the virtual section, if the virtual have buttons add to the list.
				$children_virtual_tipo = section::get_ar_children_tipo_by_modelo_name_in_section(
					$this->tipo, // section_tipo
					['button_'], // ar_modelo_name_required
					true, // from_cache
					false, // resolve_virtual
					false, // recursive
					false, // search_exact
					$ar_excluded_tipo // ar_tipo_exclude_elements
				);

			$ar_buttons_tipo = array_merge($children_virtual_tipo, $children_real_tipo);

		}else{

			// if the section is a real section, add the buttons directly
			$ar_buttons_tipo = section::get_ar_children_tipo_by_modelo_name_in_section(
				$this->tipo, // section_tipo
				['button_'], // ar_modelo_name_required
				true, // from_cache
				false, // resolve_virtual
				false, // recursive
				false, // search_exact
				false //ar_tipo_exclude_elements
			);

		}//end if ($this->section_virtual==true )


		return $ar_buttons_tipo;
	}//end get_section_buttons_tipo




	/**
	* GET_BUTTON
	* @return object|null $button_object
	*/
	public function get_button(string $modelo_name) : ?object {

		$ar_buttons = (array)$this->get_ar_children_objects_by_modelo_name_in_section($modelo_name,false);
		foreach ($ar_buttons as $current_button_object) {
			return $current_button_object;	# Only first element
		}

		return null;
	}//end get_button



	/**
	* GET_AR_ALL_PROJECT_LANGS
	* Alias of static method common::get_ar_all_project_langs
	* @return array $ar_all_project_langs
	*	(like lg-spa, lg-eng)
	*/
	public function get_ar_all_project_langs() : array {

		$ar_all_project_langs = common::get_ar_all_langs();

		return (array)$ar_all_project_langs;
	}//end get_ar_all_project_langs



	/**
	* GET_AR_PROJECTS_BY_SECTION DES
	*/
		// private function get_ar_projects_by_section() {

		// 	# "NO ESTA ACABADO.. !";
		// 	die("Stopped secuence get_ar_projects_by_section");

		// 	# Obtenemos los hijos de esta seccion
		// 	$section	 	= self::get_tipo();
		// 	$modelo_name	= 'filter_';

		// 	# Obtenemos el filtro (terminoID)
		// 	$filtroID		= RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($tipo=$section , $modelo_name, $relation_type='children');

		// 	# Obtenemos su filtro relacionado
		// 	$filtroID_rel	= RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($tipo=$filtroID, $modelo_name, $relation_type='termino_relacionado');

		// 	# Buscamos el termino relacionado con el filtro encontrado
		// 	$filtroID_rel2	= RecordObj_dd::get_ar_terminoID_by_modelo_name_and_relation($tipo=$filtroID_rel, $modelo_name, $relation_type='termino_relacionado');

		// 	/*
		// 	# los recorremos para filtrar por modelo
		// 	if(is_array($ar_childrens)) foreach($ar_childrens as $terminoID) {

		// 		$RecordObj_dd	= new RecordObj_dd($terminoID);
		// 		$modelo			= $RecordObj_dd->get_modelo();
		// 		$modelo_name	= $RecordObj_dd->get_termino_by_tipo($modelo);

		// 		if(strpos($modelo_name,'filter_') !== false) {
		// 			$filter_tipo = $terminoID;
		// 			break;
		// 		}
		// 	}
		// 	if(empty($filter_tipo)) return false;
		// 	*/

		// 	# del filtro, sacamos los términos relacionados
		// 	#$ar_terminos_relacionados = RecordObj_dd::get_ar_terminos_relacionados($filter_tipo, $cache=true, $simple=true);

		// 	return false;
		// }//end get_ar_projects_by_section



	/**
	* GET_AR_SECTION_CREATOR DES
	*/
		// public function get_ar_section_creator() {
		// 	die("REHACER");
		// 	$dato = $this->get_dato();
		// 	if( isset($dato->created_date->ar_section_creator) )  return $dato->created_date->ar_section_creator;

		// 	return false;
		// }//end get_ar_section_creator



	/**
	* GET_SECTION_TIPO : alias of $this->get_tipo()
	*/
	public function get_section_tipo() : string {

		return $this->get_tipo();
	}//end get_section_tipo



	/**
	* SET_CREATED_DATE
	* @param string $timestamp
	*	$date is timestamp as "2016-06-15 20:01:15" or "2016-06-15"
	* This method is used mainly in importations
	*/
	public function set_created_date(string $timestamp) : void {

		$date = dd_date::get_date_with_format( $timestamp, $format="Y-m-d H:i:s" );

		$dato = $this->get_dato(); // Force load
		$dato->created_date = $date;
		$this->set_dato($dato); // Force update
	}//end set_created_date



	/**
	* SET_MODIFIED_DATE
	* @param string $timestamp
	*	$date is timestamp as "2016-06-15 20:01:15" or "2016-06-15"
	* This method is used mainly in importations
	*/
	public function set_modified_date(string $timestamp) : void {

		$date = dd_date::get_date_with_format( $timestamp, $format="Y-m-d H:i:s" );

		$dato = $this->get_dato(); // Force load
		$dato->modified_date = $date;
		$this->set_dato($dato); // Force update
	}//end set_modified_date



	/**
	* GET_CREATED_DATE
	* @return string|null $valor_local
	*/
	public function get_created_date() : ?string {

		$dato = $this->get_dato();
		if( !isset($dato->created_date) ){
			return false;
		}

		$valor_local = component_date::timestamp_to_date($dato->created_date, $full=true);

		return $valor_local;
	}//end get_created_date



	/**
	* GET_MODIFIED_DATE
	* @return string|null $valor_local
	*/
	public function get_modified_date() : ?string {

		$dato = $this->get_dato();
		if( !isset($dato->modified_date) ){
			return false;
		}

		$valor_local = component_date::timestamp_to_date($dato->modified_date, $full=true);

		return $valor_local;
	}//end get_modified_date



	/**
	* GET_CREATED_BY_USERID
	* Get section dato property 'created_by_userID'
	* @return int|null $created_by_userID
	*/
	public function get_created_by_userID() : ?int {

		$dato = $this->get_dato();
		if( isset($dato->created_by_userID) )  {
			return $dato->created_by_userID;
		}

		return false;
	}//end get_created_by_userID



	/**
	* GET_CREATED_BY_USER_NAME
	*/
	public function get_created_by_user_name(bool $full_name=false) : ?string {

		$dato = $this->get_dato();

		if( !isset($dato->created_by_userID) ) {
			return null;
		}
		$user_id = $dato->created_by_userID;
		if( !$user_id ) {
			return null;
		}

		$username_tipo = ($full_name===true)
			? DEDALO_FULL_USER_NAME_TIPO
			: DEDALO_USER_NAME_TIPO;

		$component_input_text = component_common::get_instance(
			'component_input_text',
			$username_tipo,
			$user_id,
			'edit',
			DEDALO_DATA_NOLAN,
			DEDALO_SECTION_USERS_TIPO
		);
		$user_name = $component_input_text->get_valor();

		return $user_name;
	}//end get_created_by_user_name



	/**
	* GET_MODIFIED_BY_USER_NAME
	* @return string|null $user_name
	*/
	public function get_modified_by_user_name() : ?string {

		$dato = $this->get_dato();
		if( !isset($dato->modified_by_userID) ){
			return null;
		}
		$user_id = $dato->modified_by_userID;
		if( !$user_id ) {
			return null;
		}

		$component_input_text = component_common::get_instance('component_input_text',DEDALO_USER_NAME_TIPO, $user_id, 'edit', DEDALO_DATA_NOLAN, DEDALO_SECTION_USERS_TIPO);
		$user_name = $component_input_text->get_valor();

		return $user_name;
	}//end get_modified_by_user_name



	/**
	* GET_USER_NAME_BY_USERID
	* @return string $usesr_name
	*/
	public static function get_user_name_by_userID(int $userID) : string {

		if($userID==DEDALO_SUPERUSER){
			$user_name = 'Admin debuger';
		}else{
			$username_model = RecordObj_dd::get_modelo_name_by_tipo(DEDALO_FULL_USER_NAME_TIPO,true);
			$obj_user_name	= component_common::get_instance($username_model, // 'component_input_text',
															 DEDALO_FULL_USER_NAME_TIPO,
															 $userID,
															 'list',
															 DEDALO_DATA_NOLAN,
															 DEDALO_SECTION_USERS_TIPO);
			$user_name = $obj_user_name->get_valor();
		}

		return $user_name;
	}//end get_user_name_by_userID



	/**
	* GET_SECTION_INFO
	* @param string $format
	* @return object|string|null
	*/
	public function get_section_info(string $format='json') {

		$section_info = new stdClass();

			$section_info->created_date 			= (string)$this->get_created_date();
			$section_info->created_by_user_name		= (string)$this->get_created_by_user_name();
			$section_info->modified_date 			= (string)$this->get_modified_date();
			$section_info->modified_by_user_name	= (string)$this->get_modified_by_user_name();

			$section_info->label					= (string)rawurlencode($this->get_label());
			$section_info->section_id				= (string)$this->get_section_id();

		// publication info
			$section_info->publication_first		= array(
				'label' => RecordObj_dd::get_termino_by_tipo(diffusion::$publication_first_tipo, DEDALO_DATA_LANG, true, true),
				'value' => $this->get_publication_date(diffusion::$publication_first_tipo)
			);
			$section_info->publication_last			= array(
				'label' => RecordObj_dd::get_termino_by_tipo(diffusion::$publication_last_tipo, DEDALO_DATA_LANG, true, true),
				'value' => $this->get_publication_date(diffusion::$publication_last_tipo)
			);
			$section_info->publication_first_user	= array(
				'label' => null, // RecordObj_dd::get_termino_by_tipo(diffusion::$publication_first_user_tipo, DEDALO_DATA_LANG, true, true),
				'value' => $this->get_publication_user(diffusion::$publication_first_user_tipo)
			);
			$section_info->publication_last_user	= array(
				'label' => null, // RecordObj_dd::get_termino_by_tipo(diffusion::$publication_last_user_tipo, DEDALO_DATA_LANG, true, true),
				'value' => $this->get_publication_user(diffusion::$publication_last_user_tipo)
			);

		switch ($format) {
			case 'json':
				return json_handler::encode($section_info);
				break;

			default:
				return $section_info;
				break;
		}

		return null;
	}//end get_section_info



	/**
	* GET_PUBLICATION_DATE
	* @param string $component_tipo
	* @return string|null $local_date
	*/
	public function get_publication_date(string $component_tipo) : ?string {

		// tipos
			$section_id		= $this->section_id;
			$section_tipo	= $this->tipo;

		// component
			$modelo_name	= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);
			$component		= component_common::get_instance(
				$modelo_name,
				$component_tipo,
				$section_id,
				'list',
				DEDALO_DATA_NOLAN,
				$section_tipo
			);
			$dato = $component->get_dato();

		// local_date
			if (empty($dato)) {

				$local_date = null;

			}else{

				$current_date	= reset($dato);
				$dd_date		= new dd_date($current_date->start);
				$timestamp		= $dd_date->get_dd_timestamp();
				$local_date		= component_date::timestamp_to_date($timestamp, true); // string|null
			}


		return $local_date;
	}//end get_publication_date



	/**
	* GET_PUBLICATION_USER
	* @param string $component_tipo
	* @return string|null $user_name
	*/
	public function get_publication_user(string $component_tipo) : ?string {

		// tipos
			$section_id		= $this->section_id;
			$section_tipo	= $this->tipo;

		// component
			$modelo_name	= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);
			$component		= component_common::get_instance(
				$modelo_name,
				$component_tipo,
				$section_id,
				'list',
				DEDALO_DATA_NOLAN,
				$section_tipo
			);
			$dato = $component->get_dato();

		// user name
			if (empty($dato)) {

				$user_name = null;

			}else{
				$user_id	= reset($dato)->section_id;
				// $user_name	= section::get_user_name_by_userID($user_id);
				$component_input_text = component_common::get_instance('component_input_text',DEDALO_USER_NAME_TIPO, $user_id, 'edit', DEDALO_DATA_NOLAN, DEDALO_SECTION_USERS_TIPO);
				$user_name = $component_input_text->get_valor();
			}

		return $user_name;
	}//end get_publication_user



	/**
	* GET_AR_CHILDRENS_BY_MODEL
	* Get the children of the section by modelo_name required
	* children like relation_list or time machine_list
	* @param string $section_tipo
	* @param array $ar_modelo_name_required
	* @return string|null $first_child
	*/
	public static function get_ar_childrens_by_model(string $section_tipo, array $ar_modelo_name_required) : ?string {

		if(SHOW_DEBUG) $start_time = start_time();

		$current_section_tipo = $section_tipo;

		// $ar_modelo_name_required = [$modelo_name];

		// Locate children element in current section (virtual ot not)
		$ar_childrens = section::get_ar_children_tipo_by_modelo_name_in_section(
			$current_section_tipo,
			$ar_modelo_name_required, // ar_modelo_name_required
			$from_cache=true,
			false, // resolve_virtual
			$recursive=false,
			$search_exact=true
		);

		// If not found children, try resolving real section
		if (empty($ar_childrens)) {
			$resolve_virtual = true;
			$ar_childrens = section::get_ar_children_tipo_by_modelo_name_in_section(
				$current_section_tipo,
				$ar_modelo_name_required,
				$from_cache=true,
				true, // resolve_virtual
				$recursive=false,
				$search_exact=true
			);
		}// end if (empty($ar_childrens))

		if(isset($ar_childrens[0])){
			$first_child = $ar_childrens[0];
			return $first_child;
		}

		return null;
	}//end get_ar_childrens_by_model



	/**
	* GET_AR_ALL_SECTION_RECORDS_UNFILTERED
	* @see diffusion::build_table_data_recursive
	*
	* @param string $section_tipo
	* @return array $ar_records
	*/
	public static function get_ar_all_section_records_unfiltered( string $section_tipo ) : array {

		$result = section::get_resource_all_section_records_unfiltered($section_tipo);

		if(SHOW_DEBUG===true) {
			$n_rows = pg_num_rows($result);
			if ($n_rows>1000) {
				debug_log(__METHOD__." WARNING: TOO MUCH RESULTS IN QUERY. TO OPTIMIZE MEMORY NOT STORE RESULTS IN ARRAY IN THIS SEARCH. BETTER USE 'get_resource_all_section_records_unfiltered' ".to_string(), logger::ERROR);
			}
		}
		$ar_records=array();
		while ($rows = pg_fetch_assoc($result)) {
			$ar_records[] = $rows['section_id'];
		}

		return $ar_records;
	}//end get_ar_all_section_records_unfiltered



	/**
	* GET_RESOURCE_ALL_SECTION_RECORDS_UNFILTERED
	* @param string $section_tipo
	* @param string $select = 'section_id'
	* @return resource $result
	*/
	public static function get_resource_all_section_records_unfiltered( string $section_tipo, string $select='section_id' ) {

		$matrix_table	= common::get_matrix_table_from_tipo($section_tipo);
		$strQuery		= "-- ".__METHOD__." \nSELECT $select FROM \"$matrix_table\" WHERE section_tipo = '$section_tipo' ORDER BY section_id ASC ";
		$result			= JSON_RecordObj_matrix::search_free($strQuery);

		return $result;
	}//end get_resource_all_section_records_unfiltered



	/**
	* GET_COMPONENTS_WITH_MEDIA_CONTENT
	* Return array with model names of defined as 'media components'.
	* Used to locate components to remove media content
	* @return array
	*/
	public static function get_components_with_media_content() : array {

		$components_with_media_content = array_merge(
			component_media_common::get_media_components(), // 'component_av','component_image','component_pdf','component_svg'
			[
				'component_html_file' // component_html_file. Could include user uploaded files
			]
		);

		return $components_with_media_content;
	}//end get_components_with_media_content



	/**
	* REMOVE_SECTION_MEDIA_FILES
	* "Remove" (rename and move files to deleted folder) all media file linked to current section (all quality versions)
	* @see section->Delete
	* @return array|null
	* 	Array of objects (removed components info)
	*/
	protected function remove_section_media_files() : ?array {

		$ar_removed = [];

		// short vars
			$section_tipo		= $this->tipo;
			$section_id			= $this->section_id;
			$section_dato		= $this->get_dato();
			$ar_media_elements	= section::get_components_with_media_content();

		// section components property empty case
			if (!isset($section_dato->components) || empty($section_dato->components)) {
				debug_log(__METHOD__." Nothing to remove ".to_string(), logger::DEBUG);
				return $ar_removed;
			}

		// components into section dato
			foreach ($section_dato->components as $component_tipo => $component_value) {

				$model = RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);
				if (!in_array($model, $ar_media_elements)) continue; # Skip

				$lang		= common::get_element_lang($component_tipo, DEDALO_DATA_LANG);
				$component	= component_common::get_instance(
					$model,
					$component_tipo,
					$section_id,
					'edit',
					$lang,
					$section_tipo
				);
				if ( false===$component->remove_component_media_files() ) {
					debug_log(__METHOD__." Error on remove_section_media_files: model:$model, tipo:$component_tipo, section_id:$section_id, section_tipo:$section_tipo", logger::ERROR);
					continue;
				}

				$ar_restored[] = (object)[
					'tipo'	=> $component_tipo,
					'model'	=> $model
				];

				debug_log(__METHOD__." removed media files from  model:$model, tipo:$component_tipo, section_id:$section_id, section_tipo:$section_tipo", logger::WARNING);
			}//end foreach


		return $ar_removed;
	}//end remove_section_media_files



	/**
	* RESTORE_DELETED_SECTION_MEDIA_FILES
	* Use when recover section from time machine. Get files "deleted" (renamed in 'deleted' folder) and move and rename to the original media folder
	* @return array|null
	* 	Array of objects (restored components info)
	*/
	public function restore_deleted_section_media_files() : ?array {

		$ar_restored = [];

		// short vars
			$section_tipo		= $this->tipo;
			$section_id			= $this->section_id;
			$section_dato		= $this->get_dato();
			$ar_media_elements	= section::get_components_with_media_content();

		// section components property empty case
			if (!isset($section_dato->components) || empty($section_dato->components)) {
				debug_log(__METHOD__." Nothing to restore ".to_string(), logger::DEBUG);
				return $ar_restored;
			}

		// components into section dato
			foreach ($section_dato->components as $component_tipo => $component_value) {

				$model = RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);
				if (!in_array($model, $ar_media_elements)) continue; # Skip

				$lang		= common::get_element_lang($component_tipo, DEDALO_DATA_LANG);
				$component	= component_common::get_instance(
					$model,
					$component_tipo,
					$section_id,
					'edit',
					$lang,
					$section_tipo
				);
				if ( false===$component->restore_component_media_files() ) {
					debug_log(__METHOD__." Error on restore_deleted_section_media_files: model:$model, tipo:$component_tipo, section_id:$section_id, section_tipo:$section_tipo", logger::ERROR);
					continue;
				}

				$ar_restored[] = (object)[
					'tipo'	=> $component_tipo,
					'model'	=> $model
				];

				debug_log(__METHOD__." restored media files from  model:$model, tipo:$component_tipo, section_id:$section_id, section_tipo:$section_tipo", logger::WARNING);
			}//end foreach


		return $ar_restored;
	}//end restore_deleted_section_media_files



	/**
	* FORCED_CREATE_RECORD :
	* Check if the section exists in the DB, if the section exist, return true, else create new section with
	* the section_id and section_tipo into the database and return true.
	* Default value component filter is saved too for maintain accessibility
	* @return bool true is insert / false if not
	*/
	public function forced_create_record() : bool {

		$start_time = start_time();

		if(is_null($this->section_id)) {

			// Save to obtain a new incremental section_id
			#debug_log(__METHOD__." == SECTION : Record already exists ($this->section_id, $section_tipo) ".to_string(), logger::DEBUG);
			$this->Save();
			return true;

		}else{

			// Check if section_id already exists
				$section_tipo = $this->tipo;
				$matrix_table = common::get_matrix_table_from_tipo($section_tipo);

				$strQuery = "SELECT section_id FROM \"$matrix_table\" WHERE section_id = $this->section_id AND section_tipo = '$section_tipo' ";
				$result	  = JSON_RecordObj_matrix::search_free($strQuery);
				$num_rows = pg_num_rows($result);

				# Record already exists. Not continue
				if($num_rows>0) {
					debug_log(__METHOD__." == SECTION : Record already exists ($this->section_id, $section_tipo) ".to_string(), logger::ERROR);
					return false;
				}

			// section_id not exists. Create a new section record // ADDED 27-12-2018
				#debug_log(__METHOD__." == SECTION : Creating new forced record ($this->section_id, $section_tipo) ".to_string(), logger::DEBUG);
				$save_options = new stdClass();
					$save_options->forced_create_record = $this->section_id;
				$this->Save($save_options);
		}

		/* REMOVED 27-12-2018
		// datos for new section
			$datos = new stdClass();
				$datos->section_id 			= (int)$this->section_id;
				$datos->section_tipo 		= (string)$this->tipo;
				$datos->label 				= (string)RecordObj_dd::get_termino_by_tipo($this->tipo,null,true);
				$datos->created_by_userID 	= (int)navigator::get_user_id();
				$datos->created_date 		= (string)component_date::get_timestamp_now_for_db();	# Format 2012-11-05 19:50:44

			// Creation section info
				// Update modified section data . Resolve and add creation date and user to current section dato
					$this->update_modified_section_data(array(
						'mode' => 'new_record'
					));

				// Components container
					// components container (empty when insert)
					$datos->components = isset($this->dato->components) ? $this->dato->components : new stdClass();


				// Relations container
					// relations container
					$datos->relations  = isset($this->dato->relations) ? (array)$this->dato->relations : [];

				// update section dato with final object. Important
					$this->dato = $datos;

		// database manual insert
			$strQuery = "INSERT INTO \"$matrix_table\" (section_id, section_tipo, datos) VALUES ($1, $2, $3)";
			$result   = pg_query_params(DBi::_getConnection(), $strQuery, array( $this->section_id, $this->tipo, json_encode($datos) ));
			if(!$result) {
				debug_log(__METHOD__."Error Processing Save Insert Request: strQuery section_id:$section_id, section_tipo:$this->tipo ".to_string($strQuery), logger::DEBUG);
				if(SHOW_DEBUG===true) {
					throw new Exception("Error Processing Save Insert Request ". pg_last_error(DBi::_getConnection()), 1);;
				}
				return "!!! Error: sorry an error ocurred on INSERT record. Data is not saved";
			}

			// Store in cached sections . Important
				# key for CACHE
				$key = $this->section_id .'_'. $this->tipo;
				self::$ar_section_instances[$key] = $this;

		// Save current section
		#$this->Save();


		#
		# FILTER always save default project
			// $section_tipo, $ar_modelo_name_required, $from_cache=true, $resolve_virtual=false, $recursive=true, $search_exact=false
			$ar_filter_tipo = section::get_ar_children_tipo_by_modelo_name_in_section($this->tipo, ['component_filter'], true, true, true, true);
			if (!isset($ar_filter_tipo[0])) {

				debug_log(__METHOD__." Error Processing Request. component_filter not found in this section ($this->tipo - $this->section_id) ".to_string(), logger::DEBUG);

			}else{

				$filter_tipo 	  = $ar_filter_tipo[0];

				# Filter can be component_filter or component_filter_master in user section case
				# When you are in import users context, you need use 'component_filter_master' as projects data
				$filter_modelo_name = RecordObj_dd::get_modelo_name_by_tipo($filter_tipo,true);
				$component_filter 	= component_common::get_instance($filter_modelo_name,
																	 $filter_tipo,
																	 $this->section_id,
																	 'edit', # mode 'edit' already autosave default dato
																	 DEDALO_DATA_NOLAN,
																	 $this->tipo);
				#
				# FILTER always save default project
				# Get current user projects
				#$user_id 				= navigator::get_user_id();
				#$default_dato_for_user = $this->get_default_dato_for_user($user_id);
				#$component_filter->set_dato($default_dato_for_user);
				#$component_filter->Save();
			}

		#
		# COUNTER
		# CONSOLIDATE COUNTER VALUE
		# Search last section_id for current section and set counter to this value (when user later create a new record manually, counter will be ok)
		counter::consolidate_counter( $this->tipo, $matrix_table );
		*/

		return true;
	}//end forced_create_record



	### /DIFFUSION INFO #####################################################################################



	/**
	* GET_DIFFUSION_INFO
	* Get property 'diffusion_info' from section dato
	* @return object|null $diffusion_info
	*/
	public function get_diffusion_info() : ?object {

		$dato = $this->get_dato();
		if(is_object($dato) && property_exists($dato, 'diffusion_info')) {
			return $dato->diffusion_info;
		}

		return null;
	}//end get_diffusion_info



	/**
	* DIFFUSION_INFO_ADD
	* @param string $diffusion_element_tipo
	* @return bool
	*/
	public function diffusion_info_add(string $diffusion_element_tipo) : bool {

		$dato = $this->get_dato();

		if (!isset($dato->diffusion_info) || !is_object($dato->diffusion_info)) {	// property_exists($dato, 'diffusion_info')
			$dato->diffusion_info = new stdClass();
		}
		if (!isset($dato->diffusion_info->$diffusion_element_tipo)) {

			$diffusion_element_data = new stdClass();
				$diffusion_element_data->date 	 = date('Y-m-d H:i:s');;
				$diffusion_element_data->user_id = $_SESSION['dedalo']['auth']['user_id'];

			$dato->diffusion_info->$diffusion_element_tipo = $diffusion_element_data;

			$this->set_dato($dato); // Force update section dato
		}

		return true;
	}//end diffusion_info_add



	/**
	* DIFFUSION_INFO_PROPAGATE_CHANGES
	* Resolve section caller to current section (from inverse locators)
	* and set every diffusion info as null to set publication as Outdated
	* @return bool
	*/
	public function diffusion_info_propagate_changes() : bool {

		$inverse_locators = $this->get_inverse_locators();

		foreach((array)$inverse_locators as $locator) {

			$current_section_tipo = $locator->from_section_tipo;
			$current_section_id   = $locator->from_section_id;

			$section = section::get_instance($current_section_id, $current_section_tipo, $modo='list');
			$dato 	 = $section->get_dato();

			if (!empty($dato->diffusion_info)) {

				// Unset section diffusion_info in section dato
				$dato->diffusion_info = null; // Default value

				// Update section whole dato
				$section->set_dato($dato);

				// Save section with updated dato
				$section->Save();
				debug_log(__METHOD__." Propagated diffusion_info changes to section  $current_section_tipo, $current_section_id ".to_string(), logger::DEBUG);
			}else{
				debug_log(__METHOD__." Unnecessary do diffusion_info changes to section  $current_section_tipo, $current_section_id ".to_string(), logger::DEBUG);
			}
		}

		return true;
	}//end diffusion_info_propagate_changes



	### INVERSE LOCATORS / REFERENCES #####################################################################################



	/**
	* GET_INVERSE_LOCATORS
	* Alias of section->get_inverse_references
	* @return array $inverse_locators
	*/
	public function get_inverse_locators() : array {

		return $this->get_inverse_references();
	}//end get_inverse_locators



	/**
	* GET_INVERSE_REFERENCES
	* Get calculated inverse locators for all matrix tables
	* @see search::calculate_inverse_locator
	* @return array $inverse_locators
	*/
	public function get_inverse_references() : array {

		if (empty($this->section_id)) {
			# Section not exists yet. Return empty array
			return array();
		}

		#$inverse_locators = search::get_inverse_relations_from_relations_table($this->tipo, $this->section_id);

		# Create a minimal locator based on current section
		$reference_locator = new locator();
			$reference_locator->set_section_tipo($this->tipo);
			$reference_locator->set_section_id($this->section_id);

		# Get calculated inverse locators for all matrix tables
		$ar_inverse_locators = search_related::get_referenced_locators( $reference_locator );


		return (array)$ar_inverse_locators;
	}//end get_inverse_references



	/**
	* REMOVE_ALL_INVERSE_REFERENCES
	* @return array $removed_locators
	*/
	public function remove_all_inverse_references() : array {

		$removed_locators = [];

		$inverse_locators = $this->get_inverse_locators();
		foreach ((array)$inverse_locators as $current_locator) {

			$component_tipo = $current_locator->from_component_tipo;
			$section_tipo 	= $current_locator->from_section_tipo;
			$section_id 	= $current_locator->from_section_id;

			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo( $component_tipo, true );
			#if ($modelo_name!=='component_portal' && $modelo_name!=='component_autocomplete' && $modelo_name!=='component_relation_children') {
			if ('component_relation_common' !== get_parent_class($modelo_name)) {
				if(SHOW_DEBUG===true) {
					trigger_error("ERROR (remove_all_inverse_references): Only portals are supported!! Ignored received: $modelo_name");
				}
				continue;
			}

			$component = component_common::get_instance(
				$modelo_name,
				$component_tipo,
				$section_id,
				'edit',
				DEDALO_DATA_NOLAN,
				$section_tipo
			);

			$locator_to_remove = new locator();
				$locator_to_remove->set_section_tipo($this->tipo);
				$locator_to_remove->set_section_id($this->section_id);
				$locator_to_remove->set_type($component->get_relation_type());
				$locator_to_remove->set_from_component_tipo($component_tipo);

			if (true === $component->remove_locator_from_dato( $locator_to_remove )) {
				// Save component dato
				$component->Save();

				$removed_locators[] = [
					"removed_from" 		=> $current_locator,
					"locator_to_remove" => $locator_to_remove
				];

				if(SHOW_DEBUG===true) {
					debug_log(__METHOD__." !!!! Removed inverse reference to tipo:$this->tipo, section_id:$this->section_id in $modelo_name: tipo:$current_locator->from_component_tipo, section_id:$current_locator->from_section_id, section_tipo:$current_locator->from_section_tipo ", logger::DEBUG);
				}
			}else{
				debug_log(__METHOD__." Error on remove reference to current_locator ".json_encode($current_locator), logger::ERROR);
			}
		}


		return $removed_locators;
	}//end remove_all_inverse_references



	/**
	* GET_RELATION_LIST
	* get the relation_list tipo for the section
	* @return string|null $relation_list_tipo
	*/
	public function get_relation_list() : ?string {

		$section_tipo		= $this->tipo;
		$relation_list_tipo	= section::get_ar_childrens_by_model($section_tipo, ['relation_list']) ?? null;

		$permissions = !empty($relation_list_tipo)
			? common::get_permissions($section_tipo, $relation_list_tipo)
			: null;

		if(isset($permissions) && $permissions >0 ){
			return $relation_list_tipo;
		}

		return null;
	}//end get_relation_list



	### RELATIONS #####################################################################################



	/**
	* GET_RELATIONS
	* Ver de fijar la variable en la sección al construir el objeto ......
	*
	* @param string $relations_container = 'relations'
	* @return array $relations
	*/
	public function get_relations( string $relations_container='relations' ) : array {

		# Default array empty
		$relations = [];

		if (empty($this->section_id)) {
			# Section not exists yet. Return empty array
			return $relations;
		}

		$dato = $this->get_dato(); // Force load data
		if( isset($dato->{$relations_container}) )  {
			$relations = (array)$dato->{$relations_container};
		}

		return $relations;
	}//end get_relations



	/**
	* ADD_RELATION
	* @param object $locator
	*	locator with valid 'type' property defined mandatory
	* @param string $relations_container = 'relations'
	* @return bool
	*/
	public function add_relation( object $locator, string $relations_container='relations' ) : bool {

		if(empty($locator)) {
			debug_log(__METHOD__." Invalid empty locator is received to add. Locator was ignored (type:".gettype($locator).") ".to_string($locator), logger::ERROR);
			return false;
		}

		if (!is_object($locator) || !isset($locator->type)) {
			debug_log(__METHOD__." Invalid locator is received to add. Locator was ignored (type:".gettype($locator).") ".to_string($locator), logger::ERROR);
			if(SHOW_DEBUG===true) {
				throw new Exception("Error Processing Request. var 'locator' not contains property 'type' ", 1);
			}
			return false;
		}

		$current_type	= $locator->type;
		$relations		= $this->get_relations( $relations_container );

		# DATA INTEGRITY: Clean possible bad format locators (old and beta errors)
		foreach ((array)$relations as $key => $current_relation) {
			if (!is_object($current_relation) || !isset($current_relation->section_id) || !isset($current_relation->section_tipo) || !isset($current_relation->type)) {
				//unset($relations[$key]);
				throw new Exception("Error Processing Request. !! FOUNDED BAD FORMAT RELATION LOCATOR IN SECTION_RELATION DATA: (type:".gettype($current_relation).") ".to_string($current_relation), 1);
			}
			#if ($remove_previous_of_current_type && $current_relation->type===$current_type) {
			#	debug_log(__METHOD__." Removing locator of type $current_type from relation locator: ".to_string($current_relation), logger::DEBUG);
			#	unset($relations[$key]);
			#}
		}
		# maintain array index after unset value. ! Important for encode json as array later (if keys are not correlatives, undesired object is created)
		$relations = array_values($relations);

		# Test if already exists
		/*$ar_properties=array('section_id','section_tipo','type');
		if (isset($locator->from_component_tipo)) 	$ar_properties[] = 'from_component_tipo';
		if (isset($locator->tag_id)) 		 		$ar_properties[] = 'tag_id';
		if (isset($locator->component_tipo)) 		$ar_properties[] = 'component_tipo';
		if (isset($locator->section_top_tipo))		$ar_properties[] = 'section_top_tipo';
		if (isset($locator->section_top_id)) 		$ar_properties[] = 'section_top_id';*/
		$object_exists = locator::in_array_locator( $locator, $ar_locator=$relations);
		if ($object_exists===false) {

			array_push($relations, $locator);
			//$relations[] = $locator;

			# Force load 'dato' if not exists / loaded
			if ( empty($this->dato) && $this->section_id>0 ) {
				$this->get_dato();
			}
			if ( empty($this->dato) || !is_object($this->dato) ) {
				$this->dato = new stdClass();
			}

			# Add to container
			$this->dato->{$relations_container} = (array)$relations;
			//$this->set_relations($relations);

			return true;
		}else{
			debug_log(__METHOD__.' Ignored add locator action: locator already exists: '.json_encode($locator), logger::ERROR);
		}

		return false;
	}//end add_relation



	/**
	* REMOVE_RELATION
	* @param object $locator
	* @param string $relations_container = 'relations'
	* @return bool
	*/
	public function remove_relation( object $locator, string $relations_container='relations' ) : bool {

		$removed = false;

		$relations = $this->get_relations( $relations_container );

		$ar_properties=array('section_id','section_tipo','type');
		if (isset($locator->from_component_tipo))	$ar_properties[] = 'from_component_tipo';
		if (isset($locator->tag_id))				$ar_properties[] = 'tag_id';
		if (isset($locator->component_tipo))		$ar_properties[] = 'component_tipo';
		if (isset($locator->section_top_tipo))		$ar_properties[] = 'section_top_tipo';
		if (isset($locator->section_top_id))		$ar_properties[] = 'section_top_id';


		$new_relations = [];
		foreach ($relations as $key => $current_locator_obj) {

			# Test if already exists
			$equal = locator::compare_locators( $current_locator_obj, $locator, $ar_properties );
			if ( $equal===true ) {

				#unset($relations[$key]);
				#debug_log(__METHOD__." Removed key $key ".to_string($locator), logger::DEBUG);

				# maintain array index after unset value. ! Important for encode json as array later (if keys are not correlatives, object is created)
				#$relations = array_values($relations);
				#$this->dato->relations = $relations;

				#return true;
				#break;
				$removed = true;

			}else{

				$new_relations[] = $current_locator_obj;
			}
		}

		# Updates current dato relations with clean array of locators
		if ($removed===true) {

			#$this->dato->relations = $new_relations;
			$this->dato->{$relations_container} = $new_relations;
		}


		return (bool)$removed;
	}//end remove_relation



	/**
	* REMOVE_RELATIONS_FROM_COMPONENT_TIPO
	* Delete all locators of type requested from section relation dato
	* (!) Note that this method do not save
	* @param string $component_tipo
	* @param string $relations_container = 'relations'
	* @return array $ar_deleted_locators
	*/
	public function remove_relations_from_component_tipo( string $component_tipo, string $relations_container='relations' ) : array {

		$relations = $this->get_relations( $relations_container );

		$removed				= false;
		$ar_deleted_locators	= [];
		$new_relations			= [];
		foreach ($relations as $key => $current_locator) {

			# Test if from_component_tipo
			if (isset($current_locator->from_component_tipo) && $current_locator->from_component_tipo===$component_tipo) {
				# Ignored locator
				$ar_deleted_locators[] = $current_locator;
				$removed = true;
				if(SHOW_DEBUG===true) {
					$c_section_label	= RecordObj_dd::get_termino_by_tipo($current_locator->section_tipo);
					$c_scomponent_label	= RecordObj_dd::get_termino_by_tipo($component_tipo);
					// debug_log(__METHOD__." Deleted locator in '$relations_container'. component_tipo:$component_tipo - section_tipo:$current_locator->section_tipo - $c_section_label - $c_scomponent_label " . PHP_EOL . to_string($current_locator), logger::DEBUG);
				}
			}else{
				# Add normally
				$new_relations[] = $current_locator;
			}
		}

		if ($removed===true) {
			# maintain array index after unset value. ! Important for encode JSON as array later (if keys are not correlatives, object is created)
			#$relations = array_values($relations);

			# Update section dato relations on finish
			$this->dato->{$relations_container} = $new_relations;
		}


		return (array)$ar_deleted_locators;
	}//end remove_relations_from_component_tipo




	### /RELATIONS #####################################################################################



	/**
	* GET_SECTION_MAP
	* Section map data is stored in 'properties' of element of model 'section_map' placed in first level of section
	* @param string $section_tipo
	* @return object|null $setion_map
	*/
	public static function get_section_map( string $section_tipo ) : ?object {

		// cache
			static $section_map_cache;
			if(isset($section_map_cache[$section_tipo])) return $section_map_cache[$section_tipo];

		$ar_modelo_name_required	= array('section_map');
		$resolve_virtual			= false;

		// Locate section_map element in current section (virtual or not)
		$ar_children = section::get_ar_children_tipo_by_modelo_name_in_section(
			$section_tipo,
			$ar_modelo_name_required,
			$from_cache=true,
			$resolve_virtual,
			$recursive=false,
			$search_exact=true
		);

		// If not found children, try resolving real section
		if (empty($ar_children)) {
			$resolve_virtual = true;
			$ar_children = section::get_ar_children_tipo_by_modelo_name_in_section(
				$section_tipo,
				$ar_modelo_name_required,
				$from_cache=true,
				$resolve_virtual,
				$recursive=false,
				$search_exact=true
			);
		}

		$section_map = null;
		if( isset($ar_children[0]) ) {

			$tipo			= $ar_children[0];
			$RecordObj_dd	= new RecordObj_dd($tipo);
			$section_map	= $RecordObj_dd->get_properties() ?? null;
		}

		// cache. Store in cache for speed
			$section_map_cache[$section_tipo] = $section_map;


		return $section_map;
	}//end get_section_map



	/**
	* PROPAGATE_TEMP_SECTION_DATA
	* @param object $temp_section_data
	* @param object $current_section
	*/
		// public static function propagate_temp_section_data($temp_section_data, $section_tipo, $section_id) {

		// 	# COMPONENTS
		// 	if (isset($temp_section_data->components)) {
		// 		foreach ($temp_section_data->components as $current_tipo => $current_component) {

		// 			if ($current_tipo==='relations') {
		// 				trigger_error("BAD SECTION DATA IN temp_section_data !! ".json_encode($temp_section_data));
		// 				continue;
		// 			}

		// 			$RecordObj_dd 	= new RecordObj_dd($current_tipo);
		// 			$traducible  	= $RecordObj_dd->get_traducible();
		// 			$current_lang   = $traducible!=='si' ? DEDALO_DATA_NOLAN : DEDALO_DATA_LANG;

		// 			$component_dato_current_lang = $current_component->dato->$current_lang;

		// 			if (!isset($component_dato_current_lang)) {
		// 				if(SHOW_DEBUG===true) {
		// 					dump($current_component, ' $current_component ++ '.to_string());
		// 					trigger_error("Error: element $current_tipo without dato");
		// 				}
		// 				continue;
		// 			}

		// 			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
		// 			$component 	 = component_common::get_instance(	$modelo_name,
		// 															$current_tipo,
		// 															$section_id,
		// 															'edit',
		// 															$current_lang,
		// 															$section_tipo);
		// 			$component->set_dato( $component_dato_current_lang );
		// 			$component->Save();

		// 		}//end foreach ($temp_section_data as $key => $value) {
		// 	}//end if (isset($temp_section_data->components))


		// 	# RELATION COMPONENTS
		// 	if (isset($temp_section_data->relations)) {
		// 		$ar_locators = [];
		// 		# Group locator by from_component_tipo
		// 		foreach ($temp_section_data->relations as $current_locator) {
		// 			$current_tipo = $current_locator->from_component_tipo;
		// 			$ar_locators[$current_tipo][] = $current_locator;
		// 		}
		// 		# iterate relation components and set dato/save
		// 		foreach ($ar_locators as $current_tipo => $ar_current_locator) {
		// 			$modelo_name = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
		// 			$component 	 = component_common::get_instance(	$modelo_name,
		// 															$current_tipo,
		// 															$section_id,
		// 															'edit',
		// 															DEDALO_DATA_NOLAN,
		// 															$section_tipo,
		// 															false);
		// 			$component->get_dato();
		// 			$component->set_dato( $ar_current_locator );
		// 			$component->Save();

		// 			#debug_log(__METHOD__." Saved $modelo_name $current_tipo ($section_tipo - $section_id) dato: ".json_encode($ar_current_locator), logger::DEBUG);
		// 		}
		// 	}//end if (isset($temp_section_data->relations))


		// 	return true;
		// }//nd propagate_temp_section_data



	/**
	* BUILD_SEARCH_QUERY_OBJECT DES
	* @return object $query_object
	*/
		// public function build_search_query_object_DES( $request_options=array() ) {
		// 		$start_time=start_time();
		// 		$options = new stdClass();
		// 		$options->q 	 			= null;
		// 		$options->limit  			= 10;
		// 		$options->order  			= null;
		// 		$options->offset 			= 0;
		// 		$options->lang 				= DEDALO_DATA_LANG;
		// 		$options->id 				= $this->tipo . '_' .$this->modo;
		// 		$options->section_tipo		= $this->tipo;
		// 		$options->select_fields		= 'default';
		// 		$options->filter_by_id		= false;
		// 		$options->full_count		= true;
		// 		$options->remove_distinct	= false;
		// 			#$options->forced_matrix_table = false;
		// 		if ($options->section_tipo===DEDALO_ACTIVITY_SECTION_TIPO) {
		// 				#$order_obj = new stdClass();
		// 			#	$order_obj->direction 	= "DESC";
		// 			#	$order_obj->path 		= json_decode('[{"component_tipo": "section_id"}]');
		// 			# Defaults for activity
		// 			$options->limit  		= 30;
		// 			#$options->order  		= [$order_obj];
		// 		}
		// 		foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}
		// 		# SELECT
		// 		$select_group = [];
		// 		if ($options->select_fields===false) {
		// 			# No fields are required
		// 		}else{
		// 			# Default case
		// 			$layout_map = component_layout::get_layout_map_from_section( $this );
		// 				if (!empty($layout_map)) {
		// 				$ar_component_tipo = reset($layout_map);
		// 				foreach ($ar_component_tipo as $key => $component_tipo) {
		// 					if (empty($component_tipo)) {
		// 						debug_log(__METHOD__." Ignored empty component tipo (key:$key) received from layout map: ".json_encode($layout_map, JSON_PRETTY_PRINT), logger::ERROR);
		// 						continue;
		// 					}
		// 					$select_element = new stdClass();
		// 						$select_element->path = search::get_query_path($component_tipo, $options->section_tipo, false);
		// 					# Add to group
		// 					$select_group[] = $select_element;
		// 				}
		// 			}
		// 		}
		// 		# FILTER
		// 		$filter_group = null;
		// 		if ($options->filter_by_id!==false) {
		// 				// Is an array of objects
		// 			$ar_section_id = [];
		// 			foreach ((array)$options->filter_by_id as $locator) {
		// 				$ar_section_id[] = (int)$locator->section_id;
		// 			}
		// 				$filter_element = new stdClass();
		// 				$filter_element->q 		= json_encode($ar_section_id);
		// 				$filter_element->path 	= json_decode('[
		// 					{
		// 						"section_tipo": "'.$options->section_tipo.'",
		// 						"component_tipo": "section_id",
		// 						"modelo": "component_section_id",
		// 						"name": "section_id"
		// 					}
		// 				]');
		// 				$op = '$and';
		// 			$filter_group = new stdClass();
		// 				$filter_group->$op = [$filter_element];
		// 		}//end if ($options->filter_by_id!==false)
		// 			# QUERY OBJECT
		// 	$query_object = new stdClass();
		// 		$query_object->id  	   		= $options->id;
		// 		$query_object->section_tipo = [$options->section_tipo];
		// 		$query_object->limit   		= $options->limit;
		// 		$query_object->order   		= $options->order;
		// 		$query_object->offset  		= $options->offset;
		// 		$query_object->full_count  	= $options->full_count;
		// 		# Used only for time machine list
		// 		#if ($options->forced_matrix_table!==false) {
		// 			# add forced_matrix_table (time machine case)
		// 		#	$query_object->forced_matrix_table = $options->forced_matrix_table;
		// 		#}
		// 		$query_object->filter  		= $filter_group;
		// 		$query_object->select  		= $select_group;
		// 			return (object)$query_object;
		// }



	/**
	* GET_DATO_IN_PATH
	* (!) Not used
	* @param array $ar_path
	*/
		// public function get_dato_in_path( array $ar_path ) {

		// 	$dato_in_path = null;

		// 	$path_exists = true;
		// 	$raw_path 	 = clone $this->get_dato();
		// 	foreach ($ar_path as $value) {
		// 		if (!property_exists($raw_path, $value)) {
		// 			$path_exists = false;
		// 			break;
		// 		}
		// 		$raw_path = $raw_path->{$value};
		// 	}

		// 	if ($path_exists===true) {
		// 		$dato_in_path = $raw_path;
		// 	}


		// 	return $dato_in_path;
		// }//end get_dato_in_path



	/**
	* GET_SEARCH_QUERY
	* Used for compatibility of search queries when need filter by section_tipo inside filter (thesaurus case for example)
	* @param object $query_object
	* @return array $ar_query_object
	*/
	public static function get_search_query(object $query_object) : array {

		// component path default
			$query_object->component_path = ['section_tipo'];

		// component class name calling here
			$called_class = get_called_class();

		// component lang
			if (!isset($query_object->lang)) {
				# default
				$query_object->lang = 'all';
			}

		// current_query_object default
			$current_query_object = $query_object;

		// conform each object
			if (search::is_search_operator($current_query_object)===true) {
				foreach ($current_query_object as $operator => $ar_elements) {
					foreach ($ar_elements as $c_query_object) {
						// Inject all resolved query objects
						$c_query_object = $called_class::resolve_query_object_sql($c_query_object);
					}
				}
			}else{
				$current_query_object = $called_class::resolve_query_object_sql($current_query_object);
			}

		// convert to array always
			$ar_query_object = is_array($current_query_object)
				? $current_query_object
				: [$current_query_object];

		return $ar_query_object;
	}//end get_search_query



	/**
	* RESOLVE_QUERY_OBJECT_SQL
	* @param object $query_object
	* @return object $query_object
	*/
	public static function resolve_query_object_sql( object $query_object ) : object {

		# Always set fixed values
		$query_object->type = 'string';

		# Always set format to column
		$query_object->format = 'column';

		$q = $query_object->q;
		$q = pg_escape_string(DBi::_getConnection(), stripslashes($q));

		$operator = '=';
		$q_clean  = str_replace('\"', '', $q);
		$query_object->operator = $operator;
		$query_object->q_parsed	= '\''.$q_clean.'\'';


		return $query_object;
	}//end resolve_query_object_sql



	/**
	* GET_MODIFIED_SECTION_TIPOS
	* @return array $ar_tipos
	*/
	public static function get_modified_section_tipos() : array {

		$ar_tipos = array(
			array('name'=>'created_by_user', 'tipo'=>'dd200', 'model'=>'component_select'),
			array('name'=>'created_date', 	 'tipo'=>'dd199', 'model'=>'component_date'),
			array('name'=>'modified_by_user','tipo'=>DEDALO_SECTION_INFO_MODIFIED_BY_USER, 'model'=>'component_select'), 	// 'dd197'
			array('name'=>'modified_date', 	 'tipo'=>DEDALO_SECTION_INFO_MODIFIED_DATE, 'model'=>'component_date') 			// 'dd201'
		);

		return $ar_tipos;
	}//end get_modified_section_tipos



	/**
	* GET_MODIFIED_SECTION_TIPOS_BASIC
	* @return
	*/
	public static function get_modified_section_tipos_basic() : array {

		$ar_tipos = array(
			'dd200',
			'dd199',
			DEDALO_SECTION_INFO_MODIFIED_BY_USER,
			DEDALO_SECTION_INFO_MODIFIED_DATE
		);

		return $ar_tipos;
	}//end get_modified_section_tipos_basic



	/**
	* UPDATE_MODIFIED_SECTION_DATA
	* @param object $options
	* @return bool
	*/
	public function update_modified_section_data(object $options) : bool {

		if ($this->tipo===DEDALO_ACTIVITY_SECTION_TIPO) {
			return false;
		}

		// options
			$mode = $options->mode;

		// Fixed private tipos
			$modified_section_tipos = section::get_modified_section_tipos();
				$created_by_user 	= array_filter($modified_section_tipos, function($item){ return $item['name']==='created_by_user'; }); 	// array('tipo'=>'dd200', 'model'=>'component_select');
				$created_date 		= array_filter($modified_section_tipos, function($item){ return $item['name']==='created_date'; }); 		// array('tipo'=>'dd199', 'model'=>'component_date');
				$modified_by_user 	= array_filter($modified_section_tipos, function($item){ return $item['name']==='modified_by_user'; }); 	// array('tipo'=>'dd197', 'model'=>'component_select');
				$modified_date 		= array_filter($modified_section_tipos, function($item){ return $item['name']==='modified_date'; }); 		// array('tipo'=>'dd201', 'model'=>'component_date');

		// Current user locator
			$user_locator = new locator();
				$user_locator->set_section_tipo(DEDALO_SECTION_USERS_TIPO); // dd128
				$user_locator->set_section_id($_SESSION['dedalo']['auth']['user_id']); // logged user
				$user_locator->set_type(DEDALO_RELATION_TYPE_LINK);

		// Current date
			$base_date  = component_date::get_date_now();
			$dd_date  	= new dd_date($base_date);
			$time 		= dd_date::convert_date_to_seconds($dd_date);
			$dd_date->set_time($time);
			$date_now 	= new stdClass();
				$date_now->start = $dd_date;


		switch ($mode) {

			case 'new_record': // new record

				// Created by user
					$created_by_user	= reset($created_by_user);
					$component			= component_common::get_instance(
						$created_by_user['model'],
						$created_by_user['tipo'],
						$this->section_id,
						'list',
						DEDALO_DATA_NOLAN,
						$this->tipo // section_tipo
					);
					$component->set_dato($user_locator);
					#$dato = $component->get_dato();
					#$this->add_relation( reset($dato) );
					$this->set_component_relation_dato($component);
					#$component->save_to_database = false; // Avoid exec db real save
					#$component->Save(); // Only updates section data

				// Creation date
					$created_date 	= reset($created_date);
					$component 		= component_common::get_instance(
						$created_date['model'],
						$created_date['tipo'],
						$this->section_id,
						'list',
						DEDALO_DATA_NOLAN,
						$this->tipo // section_tipo
					);
					$component->set_dato($date_now);
					#$component->save_to_database = false; // Avoid exec db real save
					#$component->Save(); // Only updates section data
					$this->set_component_direct_dato($component);
					#$dato = $component->get_dato();
					#$this->add_relation( reset($dato) );
				break;

			case 'update_record': // update_record (record already exists)

				// Modified by user
					$modified_by_user	= reset($modified_by_user);
					$component			= component_common::get_instance(
						$modified_by_user['model'],
						$modified_by_user['tipo'],
						$this->section_id,
						'list',
						DEDALO_DATA_NOLAN,
						$this->tipo // section_tipo
					);
					$component->set_dato($user_locator);
					#$component->save_to_database = false; // Avoid exec db real save
					#$component->Save(); // Only updates section data
					$this->set_component_relation_dato($component);

				// Modification date
					$modified_date	= reset($modified_date);
					$component		= component_common::get_instance(
						$modified_date['model'],
						$modified_date['tipo'],
						$this->section_id,
						'list',
						DEDALO_DATA_NOLAN,
						$this->tipo // section_tipo
					);
					$component->set_dato($date_now);
					#$component->save_to_database = false; // Avoid exec db real save
					#$component->Save(); // Only updates section data
					$this->set_component_direct_dato($component);
				break;
		}


		return true;
	}//end update_modified_section_data



	/**
	* GET_AR_GROUPER_MODELS
	* @return array $ar_groupers_models
	*/
	public static function get_ar_grouper_models() : array {

		$ar_groupers_models = ['section_group','section_group_div','section_tab','tab'];

		return $ar_groupers_models;
	}//end get_ar_grouper_models



	/**
	* GET_SECTION_MODEL
	* Resolve section component_relation_model value (hierarchy sections only)
	* Gets component_relation_model value (dato and value) in current section (by locator)
	* @return object $model_obj
	*/
		// public static function get_section_model_DES($locator, $lang=DEDALO_DATA_LANG) {

		// 	$parent 		= $locator->section_id;
		// 	$section_tipo	= $locator->section_tipo;
		// 	$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo(DEDALO_THESAURUS_RELATION_MODEL_TIPO, true);
		// 	$component 		= component_common::get_instance($modelo_name,
		// 													 DEDALO_THESAURUS_RELATION_MODEL_TIPO,
		// 													 $parent,
		// 													 'list',
		// 													 $lang,
		// 													 $section_tipo);
		// 	$dato  = (array)$component->get_dato();
		// 	$value = $component->get_valor($lang);

		// 	$model_obj = new stdClass();
		// 		$model_obj->name 	= $value ?? '';
		// 		$model_obj->locator = reset($dato);

		// 	return $model_obj;
		// }//end get_section_model



	/**
	* GET_TM_CONTEXT
	* Build specific context when section is in 'tm' (time machine) mode
	* @param int permissions
	* @return array $context
	*/
	public function get_tm_context(int $permissions) : array {

		// short vars
			$rqo			= dd_core_api::$rqo; // from current client request
			$source			= $rqo->source;
			$sqo			= $rqo->sqo;
			$ddo_map		= $rqo->show->ddo_map;
			$component_ddo	= end($ddo_map);
			$component_tipo	= $component_ddo->tipo;
			$component_lang	= $source->lang;
			$section_tipo	= $this->tipo;

		$context = [];

		// fixed columns
			// // ddo matrix id
			// $context[] = (object)[
			// 	'typo'			=> 'ddo',
			// 	'type'			=> 'component',
			// 	'model'			=> 'component_section_id',
			// 	'tipo'			=> 'dd784', // fake tipo only used to match ddo with data 'dd784'
			// 	'section_tipo'	=> $this->tipo,
			// 	'label'			=> 'matrix ID',
			// 	'mode'			=> 'list',
			// 	'parent'		=> $this->tipo
			// ];
			// // ddo modification date
			// $context[] = (object)[
			// 	'typo'			=> 'ddo',
			// 	'type'			=> 'component',
			// 	'model'			=> 'component_date',
			// 	'tipo'			=> DEDALO_SECTION_INFO_MODIFIED_DATE,
			// 	'section_tipo'	=> $this->tipo,
			// 	'label'			=> RecordObj_dd::get_termino_by_tipo(DEDALO_SECTION_INFO_MODIFIED_DATE, DEDALO_DATA_LANG, true, true),
			// 	'mode'			=> 'list',
			// 	'parent'		=> $this->tipo
			// ];
			// // ddo modification user id
			// $context[] = (object)[
			// 	'typo'			=> 'ddo',
			// 	'type'			=> 'component',
			// 	'model'			=> 'component_select',
			// 	'tipo'			=> DEDALO_SECTION_INFO_MODIFIED_BY_USER,
			// 	'section_tipo'	=> $this->tipo,
			// 	'label'			=> RecordObj_dd::get_termino_by_tipo(DEDALO_SECTION_INFO_MODIFIED_BY_USER, DEDALO_DATA_LANG, true, true),
			// 	'mode'			=> 'list',
			// 	'parent'		=> $this->tipo
			// ];

		// component
			// $component_context = (function($tipo, $section_tipo, $lang) {

			// 	$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			// 	$component 		= component_common::get_instance($modelo_name,
			// 													 $tipo,
			// 													 null,
			// 													 'list',
			// 													 $lang,
			// 													 $section_tipo);
			// 	// get component json
			// 		$get_json_options = new stdClass();
			// 			$get_json_options->get_context	= true;
			// 			$get_json_options->get_data		= false;
			// 		$element_json = $component->get_json($get_json_options);

			// 	// edit section_id to match section locator data item
			// 		$current_item_context = $element_json->context;


			// 	return $current_item_context;
			// })($component_tipo, $this->tipo, $component_lang);
			// $context = array_merge($context, $component_context);

		$fixed_components = [
			'dd784', // fake tipo only used to match ddo with data 'dd784' (matrix ID)
			// DEDALO_SECTION_INFO_MODIFIED_DATE, // dd201 (component_date)
			// DEDALO_SECTION_INFO_MODIFIED_BY_USER, // dd197 (component_select)
			'dd547', // when
			'dd543', // who
			'dd546' // where
		];
		foreach ($ddo_map as $key => $item) {
			if (in_array($item->tipo, $fixed_components)) {

				$label = isset($item->label)
					? $item->label
					: RecordObj_dd::get_termino_by_tipo(
						$item->tipo, // string terminoID
						DEDALO_DATA_LANG, // string lang
						true, // bool from_cache
						true // bool fallback
					);

				// fixed components
				$current_dd_object = new dd_object(
					(object)[
						'typo'			=> $item->typo, // 'ddo',
						'type'			=> $item->type, // 'component',
						'model'			=> $item->model,
						'tipo'			=> $item->tipo,
						'section_tipo'	=> $item->section_tipo, // this tipo
						'label'			=> $label,
						'mode'			=> $item->mode, // 'list',
						'parent'		=> $item->parent, // this tipo
						'translatable'	=> RecordObj_dd::get_translatable($item->tipo)
					]
				);
				$context[] = $current_dd_object;

			}else{

				// current time machine component/s
				$component_context = (function($tipo, $section_tipo, $lang) {

					$model		= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
					$component	= component_common::get_instance(
						$model,
						$tipo,
						null,
						'list',
						$lang,
						$section_tipo
					);

					// get component json
						$get_json_options = new stdClass();
							$get_json_options->get_context	= true;
							$get_json_options->get_data		= false;
						$element_json = $component->get_json($get_json_options);

					// edit section_id to match section locator data item
						$current_item_context = $element_json->context;

					return $current_item_context;
				})($item->tipo, $section_tipo, $component_lang);
				$context = array_merge($context, $component_context);
			}
		}


		return (array)$context;
	}//end get_tm_context



	/**
	* GET_TM_AR_SUBDATA
	* Resolve current context ddo_map components data from DB matrix-time_machine record
	* @return array $data
	*/
	public function get_tm_ar_subdata() : array {

		$data = [];

		// DDBB record. This is the whole matrix_time_machine record as
			// Deleted section case:
				// {
				// 	"id": "1502293",
				// 	"id_matrix": null,
				// 	"section_id": "127",
				// 	"section_tipo": "oh1",
				// 	"tipo": "oh1",
				// 	"lang": "lg-nolan",
				// 	"timestamp": "2022-05-18 22:19:56",
				// 	"userID": "-1",
				// 	"state": "deleted                         ",
				// 	"dato": {
				// 	"label": "Historia Oral",
				// 	"relations": [...],
				// 	"components": {...},
				// 	 "section_id": 127,
				// 	"created_date": "2022-05-18 22:19:56",
				// 	"section_tipo": "oh1",
				// 	"modified_date": "2022-05-18 22:20:12",
				// 	"diffusion_info": null,
				// 	"created_by_userID": -1,
				// 	"section_real_tipo": "oh1",
				// 	"modified_by_userID": -1
				// }
			// Deleted component case:
				// {
				// 	"id": "1504869",
				// 	"id_matrix": null,
				// 	"section_id": "1",
				// 	"section_tipo": "rsc170",
				// 	"tipo": "rsc21",
				// 	"lang": "lg-nolan",
				// 	"timestamp": "2022-05-31 11:03:44",
				// 	"userID": "-1",
				// 	"state": null,
				// 	"dato": [
				// 		"code 1-c"
				// 	]
				// }
			$db_record = $this->get_record();
			// empty record case catch
				if (empty($db_record)) {
					debug_log(__METHOD__." Empty record was received ! ".to_string(), logger::ERROR);
					return $data;
				}

			// sub-data time machine from record columns
				$section_id		= $db_record->section_id;
				$section_tipo	= $db_record->section_tipo;
				$lang			= $db_record->lang;
				$id				= $db_record->id;
				$timestamp		= $db_record->timestamp;
				$user_id		= $db_record->userID;
				$tipo			= $db_record->tipo;
				$dato			= $db_record->dato;

		// short vars
			$source_model				= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			$components_with_relations	= component_relation_common::get_components_with_relations();
			$mode						= 'list';

		// ddo_map
			$request_config = (array)$this->context->request_config;
			$ddo = array_find($request_config, function($el){
				return $el->api_engine==='dedalo';
			});
			$ddo_map = (array)$ddo->show->ddo_map;

		// build data from elements
			foreach ($ddo_map as $item) {

				// short vars
					$current_tipo	= $item->tipo;
					$model			= RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
					$is_relation	= in_array($model, $components_with_relations);
					$lang			= $is_relation===true
						? DEDALO_DATA_NOLAN
						: ((bool)RecordObj_dd::get_translatable($current_tipo) ? DEDALO_DATA_LANG : DEDALO_DATA_NOLAN);

				// switch by ddo tipo
					switch ($current_tipo) {

						case 'dd1573': // id (model: component_section_id)
							$current_item = (object)[
								'section_id'			=> $section_id,
								'section_tipo'			=> $section_tipo,
								'tipo'					=> $current_tipo,  // fake tipo only used to match ddo with data
								'lang'					=> DEDALO_DATA_NOLAN,
								'from_component_tipo'	=> $current_tipo,  // fake tipo only used to match ddo with data
								'value'					=> $id,
								'debug_model'			=> 'component_section_id',
								'debug_label'			=> 'matrix ID',
								'debug_mode'			=> 'list',
								'matrix_id'				=> $id
							];
							$data[] = $current_item;
							break;

						case 'dd547': // When (model: component_date) from activity section

							$timestamp_tipo	= $current_tipo;
							$modelo_name	= RecordObj_dd::get_modelo_name_by_tipo($timestamp_tipo,true);
							$component		= component_common::get_instance(
								$modelo_name,
								$timestamp_tipo,
								$section_id,
								'list',
								DEDALO_DATA_NOLAN,
								$section_tipo
							);

							// dato
								$dd_date = new dd_date();
									$date = $dd_date->get_date_from_timestamp( $timestamp );
								$date_value = new stdClass();
									$date_value->start = $date;
								$component_dato = [$date_value];
								$component->set_dato($component_dato);
								$component->set_permissions(1);

							// get component json
								$get_json_options = new stdClass();
									$get_json_options->get_context	= false;
									$get_json_options->get_data		= true;
								$element_json = $component->get_json($get_json_options);

							// edit section_id to match section locator data item
								$current_item = reset($element_json->data);
									$current_item->matrix_id = $id;

							$data[] = $current_item;
							break;

						case 'dd543': // Who (model: component_autocomplete) from activity section
							// des
								// $user_id_tipo	= $current_tipo; // 'dd197' Modified by user
								// $modelo_name	= 'component_portal';// RecordObj_dd::get_modelo_name_by_tipo($user_id_tipo,true); // component_select
								// $component		= component_common::get_instance(
								// 	$modelo_name,
								// 	$user_id_tipo,
								// 	$section_id,
								// 	'list',
								// 	DEDALO_DATA_NOLAN,
								// 	$section_tipo
								// );
								// // dato
								// 	$locator = new locator();
								// 		$locator->set_section_tipo(DEDALO_SECTION_USERS_TIPO);
								// 		$locator->set_section_id($user_id);
								// 		$locator->set_type(DEDALO_RELATION_TYPE_LINK);
								// 	$component_dato = [$locator];
								// 	$component->set_dato($component_dato);
								// 	$component->set_permissions(1);

								// // get component json
								// 	$get_json_options = new stdClass();
								// 		$get_json_options->get_context	= false;
								// 		$get_json_options->get_data		= true;
								// 	$element_json = $component->get_json($get_json_options);
								// 		dump($element_json, ' element_json ++ '.to_string());

								// // edit section_id to match section locator data item
								// 	$current_item = reset($element_json->data);
								// 		$current_item->matrix_id = $id;

							$locator = new locator();
								$locator->set_section_tipo(DEDALO_SECTION_USERS_TIPO);
								$locator->set_section_id($user_id);
								$locator->set_type(DEDALO_RELATION_TYPE_LINK);
							$ar_values = component_relation_common::get_locator_value(
								$locator,
								DEDALO_DATA_LANG, // lang
								false, // show_parents
								['dd132'], // array|bool ar_components_related
								true // bool include_self
							);
							$current_item = (object)[
								'section_id'			=> $section_id,
								'section_tipo'			=> $section_tipo,
								'tipo'					=> $current_tipo,
								'lang'					=> DEDALO_DATA_NOLAN,
								'from_component_tipo'	=> $current_tipo,
								'value'					=> $ar_values,
								'debug_model'			=> 'component_select',
								'debug_label'			=> 'modified by user',
								'debug_mode'			=> 'list',
								'matrix_id'				=> $id
							];

							$data[] = $current_item;
							break;

						case 'dd546': // Where (model: component_input_text)
							// component_label
								$component_label = RecordObj_dd::get_termino_by_tipo(
									$tipo, // string terminoID
									DEDALO_DATA_LANG, // string lang
									true, // bool from_cache
									true // bool fallback
								);
								// on tool_time_machine prepend section label
								$rqo = dd_core_api::$rqo ?? null;
								if ( $rqo && $rqo->source->tipo!==$rqo->source->section_tipo ) {
									// section_label
										$section_label = RecordObj_dd::get_termino_by_tipo(
											$section_tipo, // string terminoID
											DEDALO_DATA_LANG, // string lang
											true, // bool from_cache
											true // bool fallback
										);
										$component_label = $section_label.': '.$component_label;
								}
							$current_value = $component_label;
							$current_item = (object)[
								'section_id'			=> $section_id,
								'section_tipo'			=> $section_tipo,
								'tipo'					=> $current_tipo,  // fake tipo only used to match ddo with data
								'lang'					=> DEDALO_DATA_LANG,
								'from_component_tipo'	=> $current_tipo,  // fake tipo only used to match ddo with data
								'value'					=> [$current_value], // .' ['.$section_tipo.']'
								'debug_model'			=> 'component_input_text',
								'debug_label'			=> 'Where',
								'debug_mode'			=> 'list',
								'matrix_id'				=> $id
							];
							$data[] = $current_item;
							break;

						default:

							// component
								$current_element = component_common::get_instance(
									$model,
									$current_tipo,
									$section_id,
									$mode,
									$lang,
									$section_tipo
								);

							// null component case. When the data is not correct or the tipo don't mach with the ontology (ex:time machine data of old components)
								if($current_element===null) {
									$item = $this->get_data_item(null);
										$item->parent_tipo			= $section_tipo;
										$item->parent_section_id	= $section_id;

									$data = [$item];
									continue 2;
								}//end if($current_element===null)

							// properties
								// if (isset($dd_object->properties)){
								// 	$current_element->set_properties($dd_object->properties);
								// }

							// Inject this tipo as related component from_component_tipo
								$current_element->from_component_tipo	= $current_tipo;
								$current_element->from_section_tipo		= $section_tipo;

							// dato. inject dato
								$current_dato = ($source_model!=='section')
									? $dato // from deleted component case
									: (($is_relation===false) // from deleted section case
										? $dato->components->{$current_tipo}->dato->{$lang} ?? null
										: array_values(array_filter($dato->relations, function($el) use($current_tipo) {
											return $el->from_component_tipo===$current_tipo;
										  })));

								// empty dato case
									if (empty($current_dato) && $model!=='component_section_id') {
										$item = $current_element->get_data_item(null);
											$item->parent_tipo			= $section_tipo;
											$item->parent_section_id	= $section_id;

										$data[] = $item;
										continue 2;
									}

								// inject current_dato
									$current_element->set_dato($current_dato);
									$current_element->set_permissions(1);

							// valor as plain text
								// $valor = $current_element->get_valor();
								// dump($valor, ' get_tm_ar_subdata valor +--------------/////////----------------------+ '.to_string());

							// get component JSON data
								$get_json_options = new stdClass();
									$get_json_options->get_context	= false;
									$get_json_options->get_data		= true;
								$element_json = $current_element->get_json($get_json_options);

							// add matrix_id
								$component_data	= array_map(function($value_obj) use($id, $section_id){
									$value_obj->matrix_id			= $id; // (!) needed to match context and data in tm mode section
									// $value_obj->row_section_id	= $section_id; // they are not necessary here !
									// $value_obj->parent_tipo		= $this->tipo; // they are not necessary here !
									// $value_obj->row_section_id	= $id;

									// matrix_id column case
									if ($value_obj->tipo==='dd1573') {
										$value_obj->value = $id;
									}

									return $value_obj;
								}, $element_json->data);

							// dd_info, additional information to the component, like parents
								// $value_with_parents = $dd_object->value_with_parents ?? false;
								// if ($value_with_parents===true) {
								// 	$dd_info = common::get_ddinfo_parents($current_locator, $this->tipo);
								// 	$ar_final_subdata[] = $dd_info;
								// }

							// data add
								$data = array_merge($data, $component_data);
							break;
					}//end switch ($current_tipo)
			}//end foreach ($ddo_map as $item)


		return $data;

		/* OLD
		// matrix ID (component_section_id)
			$data[] = (function($tipo, $section_tipo, $section_id, $lang, $id) {

				$fake_tipo = 'dd1573';
				$current_item = (object)[
					'section_id'			=> $section_id,
					'section_tipo'			=> $section_tipo,
					'tipo'					=> $fake_tipo,  // fake tipo only used to match ddo with data
					'lang'					=> DEDALO_DATA_NOLAN,
					'from_component_tipo'	=> $fake_tipo,  // fake tipo only used to match ddo with data
					'value'					=> $id,
					'debug_model'			=> 'component_section_id',
					'debug_label'			=> 'matrix ID',
					'debug_mode'			=> 'list',
					'matrix_id'				=> $id
				];

				return $current_item;
			})($tipo, $section_tipo, $section_id, $lang, $id);

		// timestamp (component_date)
			$data[] = (function($tipo, $section_tipo, $section_id, $lang, $id, $timestamp) {

				$timestamp_tipo	= DEDALO_SECTION_INFO_MODIFIED_DATE; // 'dd201' Modification date
				$modelo_name	= RecordObj_dd::get_modelo_name_by_tipo($timestamp_tipo,true);
				$component		= component_common::get_instance($modelo_name,
																 $timestamp_tipo,
																 $section_id,
																 'list',
																 DEDALO_DATA_NOLAN,
																 $section_tipo);
				// dato
					$dd_date = new dd_date();
						$date = $dd_date->get_date_from_timestamp( $timestamp );
					$date_value = new stdClass();
						$date_value->start = $date;
					$component_dato = [$date_value];
					$component->set_dato($component_dato);

				// get component json
					$get_json_options = new stdClass();
						$get_json_options->get_context	= false;
						$get_json_options->get_data		= true;
					$element_json = $component->get_json($get_json_options);

				// edit section_id to match section locator data item
					$current_item = reset($element_json->data);
						$current_item->matrix_id = $id;

				return $current_item;
			})($tipo, $section_tipo, $section_id, $lang, $id, $timestamp);

		// user_id (component_select)
			$data[] = (function($tipo, $section_tipo, $section_id, $lang, $id, $user_id) {

				$user_id_tipo	= DEDALO_SECTION_INFO_MODIFIED_BY_USER; // 'dd197' Modified by user
				$modelo_name	= RecordObj_dd::get_modelo_name_by_tipo($user_id_tipo,true); // select
				$component		= component_common::get_instance($modelo_name,
																 $user_id_tipo,
																 $section_id,
																 'list',
																 DEDALO_DATA_NOLAN,
																 $section_tipo);
				// dato
					$locator = new locator();
						$locator->set_section_tipo(DEDALO_SECTION_USERS_TIPO);
						$locator->set_section_id($user_id);
						$locator->set_type(DEDALO_RELATION_TYPE_LINK);
					$component_dato = [$locator];

					$component->set_dato($component_dato);

				// get component json
					$get_json_options = new stdClass();
						$get_json_options->get_context	= false;
						$get_json_options->get_data		= true;
					$element_json = $component->get_json($get_json_options);

				// edit section_id to match section locator data item
					$current_item = reset($element_json->data);
						$current_item->matrix_id = $id;

				return $current_item;
			})($tipo, $section_tipo, $section_id, $lang, $id, $user_id);

		// component. Data of actual component to show in section list
			$element_json = (function($tipo, $section_tipo, $section_id, $lang, $id, $element_dato='no_value') {

				$model	= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
				$mode	= 'list';

				// component
					$current_element = component_common::get_instance(
						$model,
						$tipo,
						$section_id,
						$mode,
						$lang,
						$section_tipo
					);

				// null component case. When the data is not correct or the tipo don't mach with the ontology (ex:time machine data of old components)
					if($current_element===null) {

						$value = false;

						// data item
						$item = $this->get_data_item($value);
							$item->parent_tipo			= $this->get_tipo();
							$item->parent_section_id	= $this->get_section_id();

						$data = [$item];

						$element_json = new stdClass();
							$element_json->context 	= [];
							$element_json->data 	= $data;

						return $element_json;
					}//end if($current_element===null)

				// properties
					// if (isset($dd_object->properties)){
					// 	$current_element->set_properties($dd_object->properties);
					// }

				// Inject this tipo as related component from_component_tipo
					if (strpos($model, 'component_')===0){
						$current_element->from_component_tipo	= $tipo;
						$current_element->from_section_tipo		= $section_tipo;
					}

				// inject dato if is received
					if ($element_dato!=='no_value') {
						$current_element->set_dato($element_dato);
					}

				// valor as plain text
					// $valor = $current_element->get_valor();
					// dump($valor, ' get_tm_ar_subdata valor +--------------/////////----------------------+ '.to_string());

				// placeholder component for mixed component tipo


				// get component json
					$get_json_options = new stdClass();
						$get_json_options->get_context 	= false;
						$get_json_options->get_data 	= true;
					$element_json = $current_element->get_json($get_json_options);

				// dd_info, additional information to the component, like parents
					// $value_with_parents = $dd_object->value_with_parents ?? false;
					// if ($value_with_parents===true) {
					// 	$dd_info = common::get_ddinfo_parents($locator, $this->tipo);
					// 	$ar_subdata[] = $dd_info;
					// }

				return $element_json;
			})($tipo, $section_tipo, $section_id, $lang, $id, $element_dato);

			// add matrix_id
				$component_data	= array_map(function($value_obj) use($id, $section_id){
					$value_obj->matrix_id			= $id; // (!) needed to match context and data in tm mode section
					// $value_obj->row_section_id	= $section_id; // they are not necessary here !
					// $value_obj->parent_tipo		= $this->tipo; // they are not necessary here !
					// $value_obj->row_section_id	= $id;
					return $value_obj;
				}, $element_json->data);

			// dd_info, additional information to the component, like parents
				// $value_with_parents = $dd_object->value_with_parents ?? false;
				// if ($value_with_parents===true) {
				// 	$dd_info = common::get_ddinfo_parents($current_locator, $this->tipo);
				// 	$ar_final_subdata[] = $dd_info;
				// }


		// data add
			$data = array_merge($data, $component_data);
				// dump($data, ' data ++++++++++++++++++++ '.to_string($id));


		return $data; */
	}//end get_tm_ar_subdata



	/**
	* GET_TIME_MACHINE_LIST
	* Get the time machine list tipo for the section
	* @return string|null $time_machine_list_tipo
	*/
	public function get_time_machine_list() : ?string {

		$section_tipo			= $this->tipo;
		$time_machine_list_tipo	= section::get_ar_childrens_by_model($section_tipo, ['time_machine_list']) ?? null;

		$permissions = isset($time_machine_list_tipo)
			? common::get_permissions( $section_tipo, $time_machine_list_tipo)
			: null;

		if(isset($permissions) && $permissions >0 ){
			return $time_machine_list_tipo;
		}

		return null;
	}//end get_time_machine_list



	/**
	* POST_SAVE_COMPONENT_PROCESSES
	* Executed on component save (when save script is complete)
	* @param object $options
	* @return bool
	*/
	public function post_save_component_processes(object $options) : bool {

		// options
			$component = $options->component;

		// short vars
			$section_tipo	= $this->tipo;
			$section_id		= $this->section_id;
			$lang			= $component->get_lang();
			$component_tipo = $component->get_tipo();

		// ontology sync. Synchronize this section values with equivalents in table 'matrix_descriptors_dd'. Only master server
			if (// defined('STRUCTURE_IS_MASTER') && STRUCTURE_IS_MASTER===true &&
				defined('ONTOLOGY_SECTION_TIPOS') && ONTOLOGY_SECTION_TIPOS['section_tipo']===$section_tipo) {

				$ar_update_tipos = [
					ONTOLOGY_SECTION_TIPOS['term'],
					// ONTOLOGY_SECTION_TIPOS['definition']
				];

				if (in_array($component_tipo, $ar_update_tipos)) {

					// term_id
						$term_id = (function() use($section_id, $section_tipo){

							$component_tipo	= ONTOLOGY_SECTION_TIPOS['term_id'];
							$modelo_name	= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);
							$component		= component_common::get_instance(
								$modelo_name,
								$component_tipo,
								$section_id,
								'list',
								DEDALO_DATA_NOLAN,
								$section_tipo
							);
							$dato		= $component->get_dato();
							$term_id	= reset($dato);

							return $term_id;
						})();

					if (empty($term_id)) {
						debug_log(__METHOD__." term_id value is mandatoy. Nothing is propagated to descriptors ".to_string($term_id), logger::ERROR);
					}else{

						$dato_tipo = (function() use($component_tipo){
							switch ($component_tipo) {
								case ONTOLOGY_SECTION_TIPOS['term']:		return 'termino';	break;
								// case ONTOLOGY_SECTION_TIPOS['definition']:	return 'def';		break;
								// case ONTOLOGY_SECTION_TIPOS['observations']:return 'obs';		break;
							}
							return null;
						})();

						if (!empty($dato_tipo)) {

							$value = $component->get_valor();

							// set and save the value to descriptors dd
								$RecordObj = new RecordObj_descriptors_dd(RecordObj_descriptors_dd::$descriptors_matrix_table, null, $term_id, $lang, $dato_tipo);
								$RecordObj->set_dato($value);
								$result = $RecordObj->Save();

								debug_log(__METHOD__." Updated descriptors_dd 'termino' [$term_id] - dato_tipo : $dato_tipo - with value: ".to_string($value), logger::DEBUG);
						}
					}
				}
			}//end ontology sync


		return true;
	}//end post_save_component_processes



}//end class section
