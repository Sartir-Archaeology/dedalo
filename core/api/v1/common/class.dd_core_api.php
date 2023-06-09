<?php
/**
* DD_CORE_API
* Manage API REST data with Dédalo
*
*/
final class dd_core_api {



	// Version. Important!
		static $version = "1.0.0";  // 05-06-2019

	// ar_dd_objects . store current ar_dd_objects received in context to allow external access (portals, etc.)
		// static $ar_dd_objects;

	// $request_ddo_value . store current ddo items added by get_config_context methods (portals, etc.)
		// static $request_ddo_value = [];

	// rqo . store current rqo received in context to allow external access (portals, etc.)
		static $rqo;

	// context_dd_objects . store calculated context dd_objects
		static $context_dd_objects;

	// context . Whole calculated context
		// static $context;
		static $ddo_map = []; // fixed in get_structure_context()

	// static debug sql_query search
		static $sql_query_search = [];



	/**
	* START
	* Builds the start page minimum context.
	* Normally is a menu and a section (based on url vars)
	* This function tells to page what must to be request, based on given url vars
	* Note that a full context is calculate for each element
	* @param object $options
	* sample:
	* {
	*	"action": "start",
	*	"prevent_lock": true,
	* 	"options" : {
	* 		"search_obj": {
	*			"t": "oh1",
	*			"m": "edit"
	*		 },
	* 		"menu": true
	* 	}
	* }
	* @return object $response
	*/
	public static function start(object $rqo) : object {

		// options
			$options	= $rqo->options ?? new StdClass();
			$search_obj	= $options->search_obj ?? new StdClass(); // url vars
			$menu		= $options->menu ?? false;

		// response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
				$response->error	= null;

		// install check
		// check if Dédalo was installed, if not, run the install process
		// else start the normal behavior
			// check constant DEDALO_TEST_INSTALL (config.php) Default value is true.
			// Change manually to false after install to prevent to do this check on every start call
			if (!defined('DEDALO_TEST_INSTALL') || DEDALO_TEST_INSTALL===true) {
				// check the dedalo install status (config_auto.php)
				// When install is finished, it will be set automatically to 'installed'
				if(!defined('DEDALO_INSTALL_STATUS') || DEDALO_INSTALL_STATUS!=='installed') {

					// run install process
						$install = new install();

					// get the install context, client only need context of the install to init the install instance
						$context[] = $install->get_structure_context();

					// response to client
						$response->result = (object)[
							'context'	=> $context,
							'data'		=> []
						];
						$response->msg = 'OK. Request done ['.__FUNCTION__.']';

						return $response;
				}
			}

		// Notify invalid rqo->options if it happens (after install check)
			if (!isset($rqo->options)) {
				debug_log(__METHOD__
					. " start rqo options is mandatory! " . PHP_EOL
					. ' rqo: '.to_string($rqo)
					, logger::ERROR
				);
			}

		// page mode and tipo
			$default_section_tipo = MAIN_FALLBACK_SECTION; // 'test38';
			if (isset($search_obj->tool)) {

				// tool case
				$tool_name = $search_obj->tool;

			}else if (isset($search_obj->locator)) {

				// locator case (pseudo locator)
				$locator		= is_string($search_obj->locator) ? json_decode($search_obj->locator) : $search_obj->locator;
				$tipo			= $locator->tipo ?? $default_section_tipo;
				$section_tipo	= $locator->section_tipo ?? $tipo;
				$section_id		= $locator->section_id ?? null;
				$mode			= $locator->mode ?? 'list';
				$lang			= $search_obj->lang	?? $search_obj->lang ?? DEDALO_DATA_LANG;
				$view			= $search_obj->view ?? null;

			}else{

				// default and fallback case
				$tipo			= $search_obj->t	?? $search_obj->tipo			?? $default_section_tipo; // MAIN_FALLBACK_SECTION;
				$section_tipo	= $search_obj->st	?? $search_obj->section_tipo	?? $tipo;
				$section_id		= $search_obj->id	?? $search_obj->section_id		?? null;
				$mode			= $search_obj->m	?? $search_obj->mode			?? 'list';
				$lang			= $search_obj->lang	?? $search_obj->lang			?? DEDALO_DATA_LANG;
				$view			= $search_obj->view ?? null;
			}

		// context
			$context = [];
			if (true!==login::is_logged()) {

				// check_basic_system (lang and structure files)
					$is_system_ready = check_basic_system();
					if ($is_system_ready->result===false) {
						$msg = 'System is not ready. check_basic_system returns errors';
						$response->result	= false;
						$response->error	= $msg;
						$response->msg		= $msg;

						return $response;
					}

				// page context elements [login]
					$login = new login();

				// add to page context
					try {
						$login_context = $login->get_structure_context();
					} catch (Exception $e) {
						debug_log(__METHOD__
							." Caught exception: Error on get login context: " . PHP_EOL
							. ' exception message: '. $e->getMessage()
							, logger::ERROR
						);
					}
					if (empty($login_context) ||
						empty($login_context->properties->login_items) // indicates table matrix_descriptors serious problem
						) {

						// Warning: running with database problems. Load installer context instead login context
							if(defined('DEDALO_INSTALL_STATUS') &&  DEDALO_INSTALL_STATUS==='installed') {

								// status is 'installed' but database it's not available
								$msg = "Error. Your installation is set as 'installed' (DEDALO_INSTALL_STATUS) but the ontology tables are not available";
								debug_log(__METHOD__
									. " $msg " . PHP_EOL
									. ' rqo: '.to_string($rqo)
									, logger::ERROR
								);
								$response->result	= false;
								$response->error	= $msg;
								$response->msg		= $msg;
								return $response;

							}else{

								// run install process
								$install = new install();
								$context[] = $install->get_structure_context();
							}

					}else{

						// all is OK.

						$context[] = $login_context;
					}

			}else{

				// already logged case

				// menu. Add the menu element context when is required
					if ($menu===true) {

						$menu = new menu();
						$menu->set_lang(DEDALO_DATA_LANG);

						// add to page context
							$context[] = $menu->get_structure_context();
								// dump($context, ' MENU $context ++ '.to_string());
					}

				// section/area/section_tool. Get the page element from get URL vars
					$model = $tool_name ?? RecordObj_dd::get_modelo_name_by_tipo($tipo, true);
					switch (true) {
						// Section_tool is depended of section, the order of the cases are important, section_tool need to be first, before section,
						// because section_tool depends of the section process and this case only add the config from properties.
						case ($model==='section_tool'):

							$section_tool_tipo = $tipo;

							$RecordObj_dd	= new RecordObj_dd($section_tool_tipo);
							$properties		= $RecordObj_dd->get_properties();

							// overwrite (!)
								$model	= 'section';
								$tipo	= $properties->config->target_section_tipo ?? $tipo;
								$config	= $properties->config ?? null;

							// tool_context
								$tool_name = isset($properties->tool_config) && is_object($properties->tool_config)
									? array_key_first(get_object_vars($properties->tool_config))
									: false;
								if ($tool_name) {
									$ar_tool_object	= tool_common::get_client_registered_tools([$tool_name]);
									if (empty($ar_tool_object)) {
										debug_log(__METHOD__
											." ERROR. No tool found for tool '$tool_name' in section_tool_tipo: ".to_string($section_tool_tipo)
											, logger::ERROR
										);
									}else{
										$tool_config	= $properties->tool_config->{$tool_name} ?? false;
										$tool_context	= tool_common::create_tool_simple_context($ar_tool_object[0], $tool_config);
										$config->tool_context = $tool_context;
										// dump($current_area->config, ' ++++++++++++++++++++++++++++++++++++++ current_area->config ++ '.to_string($section_tool_tipo));
									}
								}
							// (!) note non break switch here. It will continue with section normally.
							// section_tool don't load the section by itself.

						case ($model==='section'):

							$section = section::get_instance($section_id, $tipo, $mode);
							$section->set_lang(DEDALO_DATA_LANG);

							$current_context = $section->get_structure_context(
								1, // permissions
								true // add_request_config
							);
							// section_tool config
							// the config is used by section_tool to set the tool to open, if is set inject the config into the context.
							if (isset($config)) {
								$current_context->config = $config;
							}

							// section_id given case. If is received section_id, we build a custom sqo with the proper filter
							// and override default request_config sqo into the section context
							if (!empty($section_id)) {

								$current_context->mode			= 'edit'; // force edit mode
								$current_context->section_id	= $section_id; // set section_id in context

								// request_config
									$request_config = array_find($current_context->request_config, function($el){
										return $el->api_engine==='dedalo';
									});
									if (!empty($request_config)) {
										// sqo
										$sqo = new search_query_object();
										$sqo->set_section_tipo([(object)[
											'tipo'	=> $tipo,
											'label'	=> ''
										]]);
										$sqo->set_filter_by_locators([(object)[
											'section_tipo'	=> $tipo,
											'section_id'	=> $section_id
										]]);

										// overwrite default sqo
										$request_config->sqo = $sqo;
									}
							}//end if (!empty($section_id))

							// add to page context
								$context[] = $current_context;
							break;

						case ($model==='area_thesaurus'):

							$area = area::get_instance($model, $tipo, $mode);
							$area->set_lang(DEDALO_DATA_LANG);

							// add to page context
								$current_context = $area->get_structure_context(1, true);

							// set properties with received vars
								if (isset($search_obj->thesaurus_mode)) {
									$current_context->properties->thesaurus_mode = $search_obj->thesaurus_mode;
								}
								if (isset($search_obj->hierarchy_types)) {
									$current_context->properties->hierarchy_types = json_decode($search_obj->hierarchy_types);
								}
								if (isset($search_obj->hierarchy_sections)) {
									$current_context->properties->hierarchy_sections = json_decode($search_obj->hierarchy_sections);
								}
								if (isset($search_obj->hierarchy_terms)) {
									$current_context->properties->hierarchy_terms = json_decode($search_obj->hierarchy_terms);
								}

							// add to page context
								$context[] = $current_context;
							break;

						case (strpos($model, 'tool_')===0):

							// resolve tool from name and user
								$user_id			= (int)navigator::get_user_id();
								$registered_tools	= tool_common::get_user_tools($user_id);
								$tool_found = array_find($registered_tools, function($el) use($model){
									return $el->name===$model;
								});
								if (empty($tool_found)) {
									debug_log(__METHOD__
										." Tool $model not found in tool_common::get_client_registered_tools "
										, logger::ERROR
									);
								}else{
									$section_tipo	= $tool_found->section_tipo;
									$section_id		= $tool_found->section_id;

									$element = new $model($section_id, $section_tipo);
									// element JSON
									$get_json_options = new stdClass();
										$get_json_options->get_context	= true;
										$get_json_options->get_data		= false;
									$element_json = $element->get_json($get_json_options);

									// context add
									$context[] = $element_json->context[0];
								}
							break;

						case (strpos($model, 'area')===0):

							$area = area::get_instance($model, $tipo, $mode);
							$area->set_lang(DEDALO_DATA_LANG);

							$current_context = $area->get_structure_context(1, true);

							// add to page context
								$context[] = $current_context;
							break;

						case (strpos($model, 'component_')===0):

							$component_lang	= (RecordObj_dd::get_translatable($tipo)===true)
								? $lang
								: DEDALO_DATA_NOLAN;

							// component
								$element = component_common::get_instance(
									$model,
									$tipo,
									$section_id,
									$mode,
									$component_lang,
									$section_tipo
								);

							// element JSON
								$get_json_options = new stdClass();
									$get_json_options->get_context	= true;
									$get_json_options->get_data		= false;
								$element_json = $element->get_json($get_json_options);

							// component_context
								$component_context = $element_json->context[0];
								$component_context->section_id = $section_id; // section_

							// view. Overwrite default if is passed
								if (!empty($view)) {
									$component_context->view = $view;
								}

							// test minimal context
								// $component_context = (object)[
								// 	'typo'			=> 'source',
								// 	'model'			=> $model,
								// 	'tipo'			=> $tipo,
								// 	'section_tipo'	=> $section_tipo,
								// 	'section_id'	=> $section_id,
								// 	'mode'			=> $mode,
								// 	'lang'			=> $component_lang
								// ];

							// context add
								$context[] = $component_context;
							break;

						default:
							// ..
							break;
					}//end switch (true)


				// unlock user components. Normally this occurs when user force reload the page
					if (DEDALO_LOCK_COMPONENTS===true) {
						lock_components::force_unlock_all_components( navigator::get_user_id() );
					}
			}//end if (login::is_logged()!==true)

		// response OK
			$response->result = (object)[
				'context'	=> $context,
				'data'		=> []
			];
			$response->msg = 'OK. Request done ['.__FUNCTION__.']';


		return $response;
	}//end start



	/**
	* READ
	* Get context and data from given source
	* Different modes are available using source->action value:
	* @see dd_core_api::build_json_rows()
	* 	search			// Used by section and service autocomplete
	* 	related_search	// Used to get the related sections that call to the source section
	* 	get_data		// Used by components and areas to get basic context and data
	* 	resolve_data	// Used by components in search mode like portals to resolve locators data
	* @see self::build_json_rows
	*
	* @param object $rqo
	* sample:
	* {
	*    "id": "section_rsc167_rsc167_edit_lg-eng",
	*    "action": "read",
	*    "source": {
	*        "typo": "source",
	*        "action": "search",
	*        "model": "section",
	*        "tipo": "rsc167",
	*        "section_tipo": "rsc167",
	*        "section_id": null,
	*        "mode": "edit",
	*        "lang": "lg-eng"
	*    },
	*    "sqo": {
	*        "section_tipo": [
	*            "rsc167"
	*        ],
	*        "offset": 0,
	*        "select": [],
	*        "full_count": false,
	*        "limit": 1
	*    }
	* }
	* @return object $response
	* sample:
	*  $response->result = {
	* 		context : array
	* 		data : array
	*  }
	*/
	public static function read(object $rqo) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
			$response->error	= null;

		// validate input data
			if (empty($rqo->source->section_tipo)) {
				$response->msg = 'Trigger Error: ('.__FUNCTION__.') Empty source \'section_tipo\' (is mandatory)';
				debug_log(__METHOD__
					." $response->msg " . PHP_EOL
					.' rqo: ' . to_string($rqo)
					, logger::ERROR
				);
				return $response;
			}

		// ignore_user_abort
			// ignore_user_abort(true);

		// build rows (context & data)
			$json_rows = self::build_json_rows($rqo);

		// response success
			$response->result	= $json_rows;
			$response->msg		= 'OK. Request done';

		// debug
			if(SHOW_DEBUG===true) {
				$response->debug = new stdClass();
				if (!empty(dd_core_api::$sql_query_search)) {
					$response->debug->sql_query_search = dd_core_api::$sql_query_search;
				}
			}


		return $response;
	}//end read



	/**
	* READ_RAW
	* Get full record data of section
	* @param object $rqo
	* sample:
	* {
	*    "action": "read_raw",
	*    "source": {
	*        "typo": "source",
	*        "model": "section",
	*        "tipo": "rsc167",
	*        "section_tipo": "rsc167",
	*        "section_id": "1",
	*        "mode": "edit",
	*        "lang": "lg-eng"
	*    }
	* }
	* @return object $response
	*/
	public static function read_raw(object $rqo) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
			$response->error	= null;

		// validate input data
			if (empty($rqo->source->section_tipo)) {
				$response->msg = 'API Error: ('.__FUNCTION__.') Empty source \'section_tipo\' (is mandatory)';
				return $response;
			}

		// short vars
			$section_tipo	= $rqo->source->section_tipo;
			$section_id		= $rqo->source->section_id;

		// section data raw
			$section	= section::get_instance($section_id, $section_tipo);
			$dato		= $section->get_dato();

		// response success
			$response->result	= $dato;
			$response->msg		= 'OK. Request done';


		return $response;
	}//end read_raw



	/**
	* CREATE
	* Creates a new database record of given section tipo
	* and returns the new section_id assigned by the counter
	* @param object $json_data
	* sample:
	* {
	*    "action": "create",
	*    "source": {
	*        "section_tipo": "oh1"
	*    }
	* }
	* @return object $response
	*/
	public static function create(object $rqo) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
			$response->error	= null;

		// short vars
			$source			= $rqo->source;
			$section_tipo	= $source->section_tipo;

		// section_tipo
			if (empty($section_tipo)) {
				$response->msg = 'API Error: ('.__FUNCTION__.') Empty section_tipo (is mandatory)';
				return $response;
			}

		// section
			$section	= section::get_instance(null, $section_tipo);
			$section_id	= $section->Save(); // Section save, returns the created section_id

		// OJO : Aquí, cuando guardemos las opciones de búsqueda, resetearemos el count para forzar a recalculat el total
			//   esto está ahora en 'section_records' pero puede cambiar..
			// Update search_query_object full_count property
				// $search_options = section_records::get_search_options($section_tipo);
				// if (isset($search_options->search_query_object)) {
				// 	$search_options->search_query_object->full_count = true; // Force re-count records
				// }

		$response->result	= $section_id;
		$response->msg		= 'OK. Request done ['.__FUNCTION__.']';


		return $response;
	}//end create



	/**
	* DUPLICATE
	* duplicate a section record of given section tipo and section_id
	* and returns the new section_id assigned by the counter
	* @param object $json_data
	* sample:
	* {
	*    "action": "duplicate ",
	*    "source": {
	*        "section_tipo": "oh1"
	* 		"section_id": 2 // integer
	*    }
	* }
	* @return array $result
	*/
	public static function duplicate(object $rqo) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
			$response->error	= null;

		// short vars
			$source			= $rqo->source;
			$section_tipo	= $source->section_tipo;
			$section_id		= $source->section_id;

		// section_tipo
			if (empty($section_tipo)) {
				$response->msg = 'API Error: ('.__FUNCTION__.') Empty section_tipo (is mandatory)';
				return $response;
			}

		// section
		// section duplicate current.Returns the section_id created
			$section	= section::get_instance($section_id, $section_tipo);
			$section_id	= $section->duplicate_current_section();


		$response->result	= $section_id;
		$response->msg		= 'OK. Request done ['.__FUNCTION__.']';


		return $response;
	}//end duplicate



	/**
	* DELETE
	* Removes one or more section records from database
	* If sqo is received, it will be used to search target sections,
	* else a new sqo will be created based on current section_tipo, section_id
	* Note that 'delete_mode' must be declared (delete_data|delete_record)
	* @param object $rqo
	* sample:
	* {
	*    "action": "delete",
	*    "source": {	*
	*        "action": "delete",
	*        "model": "section",
	*        "tipo": "oh1",
	*        "section_tipo": "oh1",
	*        "section_id": null,
	*        "mode": "list",
	*        "lang": "lg-eng",
	*        "delete_mode": "delete_record"
	*    },
	*    "sqo": {
	*        "section_tipo": [
	*            "oh1"
	*        ],
	*        "filter_by_locators": [
	*            {
	*                "section_tipo": "oh1",
	*                "section_id": "127"
	*            }
	*        ],
	*        "limit": 1
	*    }
	* }
	* @return object $response
	*/
	public static function delete(object $rqo) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed. ';
			$response->error	= null;

		// ddo_source
			$ddo_source = $rqo->source;

		// source vars
			$delete_mode	= $ddo_source->delete_mode ?? 'delete_data'; // delete_record|delete_data*
			$section_tipo	= $ddo_source->section_tipo ?? $ddo_source->tipo;
			$section_id		= $ddo_source->section_id ?? null;
			$tipo			= $ddo_source->tipo;
			$model			= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			if($model!=='section') {
				$response->error = 1;
				$response->msg 	.= '[1] Model is not expected section: '.$model;
				debug_log(__METHOD__
					." $response->msg " . PHP_EOL
					.' rqo: '.to_string($rqo)
					, logger::ERROR
				);
				return $response;
			}
			$caller_dataframe = $ddo_source->caller_dataframe ?? null;

		// permissions
			$permissions = common::get_permissions($section_tipo, $section_tipo);
			debug_log(__METHOD__." permissions: $permissions ".to_string($section_tipo), logger::DEBUG);
			if ($permissions<2) {
				$response->error = 2;
				$response->msg 	.= '[2] Insufficient permissions: '.$permissions;
				debug_log(__METHOD__
					." $response->msg " . PHP_EOL
					.' rqo: '.to_string($rqo)
					, logger::ERROR
				);
				return $response;
			}

		// dataframe section case
			if ($delete_mode==='delete_dataframe' && !empty($section_id)) {
				$section 	= section::get_instance(
					$section_id,
					$section_tipo,
					'list',
					false,
					$caller_dataframe
				);
				$deleted 	= $section->Delete($delete_mode);

				if ($deleted!==true) {
					$errors[] = (object)[
						'section_tipo'	=> $section_tipo,
						'section_id'	=> $section_id
					];
				}

				$response->result		= [$section_id];
				$response->error		= !empty($errors) ? $errors : null;
				$response->delete_mode	= $delete_mode;
				$response->msg			= !empty($errors)
					? 'Some errors occurred when delete sections. delete_mode:' . $delete_mode
					: 'OK. Request done successfully.';

				debug_log(__METHOD__
					." $response->msg " . PHP_EOL
					.' rqo: '.to_string($rqo)
					, logger::ERROR
				);

				return $response;
			}

		// sqo. search_query_object. If empty, we will create a new one with default values
			$sqo = $rqo->sqo ?? null;
			if(empty($sqo)){
				// we build a new sqo based on the current source section_id

				// section_id check (is mandatory when no sqo is received)
					if (empty($section_id)) {
						$response->error = 3;
						$response->msg 	.= '[3] section_id = null and $sqo = null, impossible to determinate the sections to delete. ';
						debug_log(__METHOD__
							." $response->msg " . PHP_EOL
							.' rqo: '.to_string($rqo)
							, logger::ERROR
						);
						return $response;
					}

				// sqo to create new one
					$self_locator = new locator();
						$self_locator->set_section_tipo($section_tipo);
						$self_locator->set_section_id($section_id);
					$sqo = new search_query_object();
						$sqo->set_section_tipo([$section_tipo]);
						$sqo->set_filter_by_locators([$self_locator]);
			}

		// search the sections to delete
			$sqo->offset	= 0;
			$sqo->limit		= 0; // prevent pagination affects to deleted records
			$search			= search::get_instance($sqo);
			$rows_data		= $search->search();
			$ar_records		= $rows_data->ar_records;
			// check empty records
			if (empty($ar_records)) {
				$response->result = [];
				$response->msg 	.= 'No records found to delete ';
				debug_log(__METHOD__
					." $response->msg " . PHP_EOL
					.' rqo: '.to_string($rqo)
					, logger::ERROR
				);
				return $response;
			}

		// check delete multiple
		// only global admins can perform multiple deletes
			$records_len = count($ar_records);
			if($records_len > 1 && security::is_global_admin(navigator::get_user_id()) === false){
				$response->result = [];
				$response->msg 	.= 'forbidden delete multiple for this user';
				debug_log(__METHOD__
					." $response->msg " . PHP_EOL
					.' rqo: '.to_string($rqo)
					, logger::ERROR);
				return $response;
			}

		// normal delete use
			$errors = [];
			foreach ($ar_records as $record) {

				$current_section_tipo	= $record->section_tipo;
				$current_section_id		= $record->section_id;

				# Delete method
				$section 	= section::get_instance($current_section_id, $current_section_tipo);
				$deleted 	= $section->Delete($delete_mode);
				if ($deleted!==true) {
					$errors[] = (object)[
						'section_tipo'	=> $current_section_tipo,
						'section_id'	=> $current_section_id
					];
				}
			}

		// ar_delete section_id
			$ar_delete_section_id = array_map(function($record){
				return $record->section_id;
			}, $ar_records);

		// check deleted all found sections. Exec the same search again expecting to obtain zero records
			if ($delete_mode==='delete_record') {

				$check_search		= search::get_instance($sqo);
				$check_rows_data	= $check_search->search();
				$check_ar_records	= $check_rows_data->ar_records;
				if(count($check_ar_records)>0) {

					$check_ar_section_id = array_map(function($record){
						return $record->section_id;
					}, $check_ar_records);

					$response->error = 4;
					$response->msg 	.= '[4] Some records were not deleted: '.json_encode($check_ar_section_id, JSON_PRETTY_PRINT);
					debug_log(__METHOD__
						." $response->msg " . PHP_EOL
						.' rqo: '.to_string($rqo)
						, logger::ERROR
					);
					return $response;
				}
			}

		// response OK
			$response->result		= $ar_delete_section_id;
			$response->error		= !empty($errors) ? $errors : null;
			$response->delete_mode	= $delete_mode;
			$response->msg			= !empty($errors)
				? 'Some errors occurred when delete sections.'
				: 'OK. Request done successfully.';


		return $response;
	}//end delete



	/**
	* SAVE
	* Saves the given value to the component data into the database.
	* @see $component_common->update_data_value
	* save actions:
	* 	insert		// add given value in dato
	* 	update		// updates given value selected by key in dato
	* 	remove		// removes a item value from the component data array
	* 	set_data	// set the whole data sent by the client without check the array key (bulk insert or update)
	* 	sort_data	// re-organize the whole component data based on target key given. Used by portals to sort rows
	* @param object $json_data
	* sample:
	* {
	*    "action": "save",
	*    "source": {
	*        "typo": "source",
	*        "type": "component",
	*        "action": null,
	*        "model": "component_input_text",
	*        "tipo": "oh16",
	*        "section_tipo": "oh1",
	*        "section_id": "124",
	*        "mode": "edit",
	*        "lang": "lg-eng"
	*    },
	*    "data": {
	*        "section_id": "124",
	*        "section_tipo": "oh1",
	*        "tipo": "oh16",
	*        "lang": "lg-eng",
	*        "from_component_tipo": "oh16",
	*        "value": [
	*            "title2"
	*        ],
	*        "parent_tipo": "oh1",
	*        "parent_section_id": "124",
	*        "fallback_value": [
	*            "title"
	*        ],
	*        "debug_model": "component_input_text",
	*        "debug_label": "Title",
	*        "debug_mode": "edit",
	*        "row_section_id": "124",
	*        "changed_data": [{
	*            "action": "update",
	*            "key": 0,
	*            "value": "title2"
	*        }]
	*    }
	* }
	* @return object $response
	*/
	public static function save(object $rqo) : object {
		$start_time = start_time();

		// response. Create the default save response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
				$response->error	= null;

		// rqo vars
			$source	= $rqo->source;
			$data	= $rqo->data ?? new stdClass();

		// short vars
			$tipo				= $source->tipo;
			$model				= $source->model ?? RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			$section_tipo		= $source->section_tipo;
			$section_id			= $source->section_id;
			$mode				= $source->mode ?? 'list';
			$view				= $source->view ?? null;
			$lang				= $source->lang;
			$type				= $source->type; // the type of the dd_object that is calling to update like 'component'
			$changed_data		= $data->changed_data ?? null;
			$caller_dataframe	= $source->caller_dataframe ?? null;

		// switch by the element context type (component, section)
		switch ($type) {
			case 'component':

				// get the component information
					$component_lang	= (RecordObj_dd::get_translatable($tipo)===true)
						? $lang
						: DEDALO_DATA_NOLAN;

				// build the component
					$component = component_common::get_instance(
						$model,
						$tipo,
						$section_id,
						$mode,
						$component_lang,
						$section_tipo,
						true,
						$caller_dataframe ?? null
					);

				// view
					if (!empty($view)) {
						$component->set_view($view);
					}

				// permissions. Get the component permissions and check if the user can update the component
					$permissions = $component->get_component_permissions();
					if($permissions < 2) {
						$response->error	= 1;
						$response->msg		= 'Error. You don\'t have enough permissions to edit this component ('.$tipo.'). permissions:'.to_string($permissions);
						debug_log(__METHOD__
							. " $response->msg " . PHP_EOL
							. " model:$model (tipo:$tipo - section_tipo:$section_tipo - section_id:$section_id) "
							, logger::ERROR
						);
						return $response;
					}

				// changed_data is array always. Check to safe value
					if (!is_array($changed_data)) {
						$changed_data = [$changed_data];
						debug_log(__METHOD__
							." ERROR. var 'changed_data' expected to be array. Received type: ". gettype($changed_data)
							, logger::ERROR
						);
					}

				if ($mode==='search') {

					// force same changed_data (whole dato)
						$changed_data_item	= $changed_data[0] ?? null;
						$value				= !empty($changed_data_item) && isset($changed_data_item->value)
							? $changed_data_item->value
							: null;
						$component->set_dato([$value]);

				}else{

					// changed_data is array always. Update items
						foreach ($changed_data as $changed_data_item) {
							// update the dato with the changed data sent by the client
							$update_result = (bool)$component->update_data_value($changed_data_item);
							if ($update_result===false) {
								$response->error	 = 2;
								$response->msg		.= ' Error on update_data_value. New data it\'s not saved! ';
								debug_log(__METHOD__
									. " $response->msg " . PHP_EOL
									. " model:$model (tipo:$tipo - section_tipo:$section_tipo - section_id:$section_id) " . PHP_EOL
									.' rqo: '.to_string($rqo)
									, logger::ERROR
								);
								return $response;
							}
						}

					// save
						debug_log(__METHOD__
							." --> API ready to save record $model ($tipo - $section_tipo - $section_id): "
							.' exec time: '.exec_time_unit($start_time).' ms'
							, logger::DEBUG
						);
						$component->Save();
					// force recalculate dato
						$component->get_dato();
				}

				// pagination. Update offset based on save request (portals)
					if (isset($data->pagination) && isset($data->pagination->offset)) {
						$component->pagination->offset = $data->pagination->offset;
					}
					if (isset($data->pagination) && isset($data->pagination->limit)) {
						$component->pagination->limit = $data->pagination->limit;
					}

				// datalist. if is received, inject to the component for recycle
					if (isset($data->datalist)) {
						$component->set_datalist($data->datalist);
					}

				// force recalculate dato
					$component->set_dato_resolved(null);

				// element JSON
					$get_json_options = new stdClass();
						$get_json_options->get_context	= true;
						$get_json_options->get_data		= true;
					$element_json = $component->get_json($get_json_options);

				// observers_data
					if (isset($component->observers_data)) {
						$element_json->data = array_merge($element_json->data, $component->observers_data);
					}

				// context and data set
					$result = $element_json;

				break;

			default:
				debug_log(__METHOD__
					. " Error. This type '$type' is not defined and will be ignored. Use 'component' as type if you are saving a component data" . PHP_EOL
					. " model:$model (tipo:$tipo - section_tipo:$section_tipo - section_id:$section_id) " . PHP_EOL
					.' rqo: '.to_string($rqo)
					, logger::ERROR
				);
				break;
		}//end switch ($type)

		// result. If the process is successful, we return the $element_json as result to client
			$response->result = $result ?? false;
			if (empty($response->error)) {
				$response->msg = 'OK. Request save done successfully';
			}


		return $response;
	}//end save



	/**
	* COUNT
	* Exec a SQL records count of given SQO
	* @param object $json_data
	* sample:
	* {
	*    "action": "count",
	*    "source": {
	*        "typo": "source",
	*        "type": "tm",
	*        "action": null,
	*        "model": "service_time_machine",
	*        ..
	*    },
	*    "sqo": {
	*        "id": "tmp",
	*        "mode": "tm",
	*        "section_tipo": [
	*            "oh1"
	*        ]
	*    },
	*    "prevent_lock": true
	* }
	* @return object $response
	*
	*/
	public static function count(object $rqo) : object {

		// rqo vars
			$tipo	= $rqo->source->tipo;
			$model	= $rqo->source->model ?? RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			$sqo	= $rqo->sqo;

		// prevent_lock. Close session if not already closed
			if (!isset($rqo->prevent_lock)) {
				session_write_close();
			}

		// response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
				$response->error	= null;

		// permissions check. If user don't have access to any section, set total to zero and prevent search
			$ar_section_tipo = $sqo->section_tipo;
			foreach ($ar_section_tipo as $current_section_tipo) {
				$permissions	= common::get_permissions($current_section_tipo, $current_section_tipo);
				if($permissions<1){
					$result = (object)[
						'total' => 0
					];
				}
			}

		// session filter check
			// If session filter exists from current section, add to the sqo
			// to be consistent with the last search
			$sqo_id			= ($model==='section') ? implode('_', ['section', $tipo]) : 'undefined';
			$sqo_session	= $_SESSION['dedalo']['config']['sqo'][$sqo_id] ?? null;
			if ( !isset($sqo->filter) && isset($sqo_session) && isset($sqo_session->filter) ) {
				$sqo->filter = $sqo_session->filter;
			}

		// search
			if (!isset($result)) {
				$search	= search::get_instance($sqo);
				$result	= $search->count();
			}

		// response OK
			$response->result	= $result;
			$response->msg		= empty($response->error)
				? 'OK. Request done successfully'
				: $response->msg;


		return $response;
	}//end count



	/**
	* GET_ELEMENT_CONTEXT
	* Used by search.get_component(source) calling data_manager
	* @param object $json_data
	* @return object $response
	*/
	public static function get_element_context(object $rqo) : object {

		session_write_close();

		// rqo vars
			$source			= $rqo->source;
			$tipo			= $source->tipo ?? null;
			$section_tipo	= $source->section_tipo ?? $source->tipo ?? null;
			$model			= $source->model ?? RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			$lang			= $source->lang ?? DEDALO_DATA_LANG;
			$mode			= $source->mode ?? 'list';
			$section_id		= $source->section_id ?? null; // only used by tools (it needed to load the section_tool record to get the context )

		// response
			$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
			$response->error	= null;

		// build element
			switch (true) {
				case $model==='section':
					$element = section::get_instance(null, $section_tipo);
					break;

				// case $model==='section_tm':
					// 	$section_id 	= $source->section_id;
					// 	$element 		= section_tm::get_instance($section_id, $section_tipo);
					// 	// set rqo (source)
					// 	$element->set_rqo([$source]); // inject whole source
					// 	break;

				case strpos($model, 'area')===0:
					$element = area::get_instance($model, $tipo, $mode);
					break;

				case strpos($model, 'component_')===0:

					$component_lang	= (RecordObj_dd::get_translatable($tipo)===true)
						? $lang
						: DEDALO_DATA_NOLAN;

					$element = component_common::get_instance(
						$model,
						$tipo,
						null, // string section_id
						$mode,
						$component_lang,
						$section_tipo
					);
					break;

				case strpos($model, 'tool_')===0:

					// tool section_tipo and section_id can be resolved from model if is necessary
						// if (empty($section_id) || empty($section_id)) {
						// 	// resolve
						// 	$registered_tools = tool_common::get_client_registered_tools();
						// 	$tool_found = array_find($registered_tools, function($el) use($model){
						// 		return $el->name===$model;
						// 	});
						// 	if (!empty($tool_found)) {
						// 		$section_tipo	= $tool_found->section_tipo;
						// 		$section_id		= $tool_found->section_id;
						// 	}else{
						// 		debug_log(__METHOD__." Tool $model not found in tool_common::get_client_registered_tools ".to_string(), logger::ERROR);
						// 	}
						// }

					// resolve tool from name and user
						$user_id			= (int)navigator::get_user_id();
						$registered_tools	= tool_common::get_user_tools($user_id);
						$tool_found = array_find($registered_tools, function($el) use($model){
							return $el->name===$model;
						});
						if (empty($tool_found)) {
							debug_log(__METHOD__
								." Tool $model not found in tool_common::get_client_registered_tools " .PHP_EOL
								.' rqo: '.to_string($rqo)
								, logger::ERROR
							);
						}else{
							$section_tipo	= $tool_found->section_tipo;
							$section_id		= $tool_found->section_id;
						}

					$element = new $model($section_id, $section_tipo);
					break;

				default:

					// others

					try {
						$element = new $model($mode);
					} catch (Exception $e) {
						// throw new Exception("Error Processing Request", 1);
						debug_log(__METHOD__
							." invalid element. exception msg: ".$e->getMessage()
							, logger::ERROR
						);
						$response->msg = 'Error. model not found: '.$model;
						return $response;
					}
					break;
			}

		// element JSON
			$get_json_options = new stdClass();
				$get_json_options->get_context	= true;
				$get_json_options->get_data		= false;
			$element_json = $element->get_json($get_json_options);

		// context add
			$context = $element_json->context;

		// response
			$response->result	= $context;
			$response->msg		= 'OK. Request done successfully';


		return $response;
	}//end get_element_context



	/**
	* GET_SECTION_ELEMENTS_CONTEXT
	* Get all components of current section (used in section search filter and tool export)
	* Used by filter and tool_export
	* @param object $rqo
	*	{
	*		action			: 'get_section_elements_context',
	*		prevent_lock	: true,
	*		"source": {
	*	        "typo": "source",
	*	        "type": "filter",
	*	        "action": null,
	*	        "model": "search",
	*	        "section_tipo": "numisdata4",
	*	        "section_id": 0,
	*	        "mode": "list",
	*	        "view": null,
	*	        "lang": "lg-eng"
	*	    },
	*		options			: {
	*			context_type			: 'simple',
	*			ar_section_tipo			: section_tipo,
	*			ar_components_exclude	: ar_components_exclude
	*		}
	*	}
	* @return object $response
	*/
	public static function get_section_elements_context(object $rqo) : object {

		// options
			$options				= $rqo->options;
			$ar_section_tipo		= (array)$options->ar_section_tipo;
			$context_type			= $options->context_type;
			$ar_components_exclude	= $options->ar_components_exclude ?? null;

		// response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
				$response->error	= null;

		// section_elements_context_options
			$section_elements_context_options = (object)[
				'ar_section_tipo'	=> $ar_section_tipo,
				'context_type'		=> $context_type
			];
			if (isset($ar_components_exclude)) {
				$section_elements_context_options->ar_components_exclude = $ar_components_exclude;
			}

		// filtered_components
			$filtered_components = common::get_section_elements_context(
				$section_elements_context_options
			);


		// response
			$response->result	= $filtered_components;
			$response->msg		= 'OK. Request done';


		return $response;
	}//end get_section_elements_context



	// search methods ///////////////////////////////////



	/**
	* FILTER_SET_EDITING_PRESET (!) Deactivated 01-30-2023 because nobody uses it
	* Saves given filter in temp preset section
	* @param object $options
	* @return object $response
	*/
		// public static function filter_set_editing_preset(object $options) : object {

		// 	// options
		// 		$section_tipo	= $options->section_tipo;
		// 		$filter_obj		= $options->filter_obj;

		// 	$response = new stdClass();
		// 		$response->result	= false;
		// 		$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
		// 		$response->error	= null;

		// 	// save_temp_preset
		// 		$result = search::save_temp_preset(
		// 			navigator::get_user_id(),
		// 			$section_tipo,
		// 			$filter_obj
		// 		);

		// 	// response
		// 		$response->result	= $result;
		// 		$response->msg		= 'OK. Request done';


		// 	return $response;
		// }//end filter_set_editing_preset



	/**
	* ONTOLOGY_GET_CHILDREN_RECURSIVE (!) Deactivated 01-30-2023 because nobody uses it
	* Calculate recursively the children of given term
	* @param object $options
	* @return object $response
	*/
		// public static function ontology_get_children_recursive(object $options) : object {

		// 	// session_write_close();

		// 	// options
		// 		$target_tipo = $options->target_tipo;

		// 	$response = new stdClass();
		// 		$response->result	= false;
		// 		$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
		// 		$response->error	= null;

		// 	// ontology call
		// 		$children = ontology::get_children_recursive($target_tipo);

		// 	// response
		// 		$response->result	= $children;
		// 		$response->msg		= 'OK. Request done';


		// 	return $response;
		// }//end ontology_get_children_recursive



	// private methods ///////////////////////////////////



	/**
	* BUILD_JSON_ROWS
	* Gets context and data from given element (section, component, area)
	* @see class.request_query_object.php
	* @param object $rqo
	* @return object $result
	*/
	private static function build_json_rows(object $rqo) : object {
		$start_time	= start_time();

		// default result
			$result = new stdClass();
				$result->context	= [];
				$result->data		= [];

		// fix rqo
			dd_core_api::$rqo = $rqo;

		// des
			// // ar_dd_objects . Array of all dd objects in requested context
			// 	$ar_dd_objects = array_values( array_filter($rqo, function($item) {
			// 		 if($item->typo==='ddo') return $item;
			// 	}) );
			// 	// set as static to allow external access
			// 	dd_core_api::$ar_dd_objects = array_values($ar_dd_objects);

		// ddo_source
			$ddo_source = $rqo->source;
			// 	$ar_source = array_filter($rqo, function($item) {
			// 		 if(isset($item->typo) && $item->typo==='source') return $item;
			// 	});
			// 	if (count($ar_source)!==1) {
			// 		throw new Exception("Error Processing Request. Invalid number of 'source' items in context. Only one is allowed. Found: ".count($ar_source), 1);
			// 		return $result;
			// 	}
			// 	$ddo_source = reset($ar_source);


		// source vars
			$action				= $ddo_source->action ?? 'search';
			$mode				= $ddo_source->mode ?? 'list';
			$view				= $ddo_source->view ?? null;
			$lang				= $ddo_source->lang ?? null;
			$tipo				= $ddo_source->tipo ?? null;
			$section_tipo		= $ddo_source->section_tipo ?? $ddo_source->tipo;
			$section_id			= $ddo_source->section_id ?? null;
			$model				= $ddo_source->model ?? RecordObj_dd::get_modelo_name_by_tipo($ddo_source->tipo,true);
			$caller_dataframe	= $ddo_source->caller_dataframe ?? null;
			$properties			= $ddo_source->properties ?? null;

		// sqo (search_query_object)
			// If empty, we look at the session, and if not exists, we will create a new one with default values
			$sqo_id			= ($model==='section') ? implode('_', ['section', $tipo]) : 'undefined'; // cache key sqo_id
			$sqo_session	= $_SESSION['dedalo']['config']['sqo'][$sqo_id] ?? null;
			if ( !empty($rqo->sqo) ) {

				// received case

				$sqo = clone $rqo->sqo;
				// add filter from session if not defined (and session yes)
				if ( !isset($sqo->filter) && isset($sqo_session) && isset($sqo_session->filter) ) {
					$sqo->filter = $_SESSION['dedalo']['config']['sqo'][$sqo_id]->filter;
				}
			}else{

				// non received case

				if ( $model==='section' && ($mode==='edit' || $mode==='list') && isset($sqo_session) ) {

					// use session already set sqo
					$sqo = $sqo_session;

				}else{

					// create a new sqo from scratch

					// limit. get the limit from the show
						$limit = (isset($rqo->show) && isset($rqo->show->sqo_config->limit))
							? $rqo->show->sqo_config->limit
							: (function() use($tipo, $section_tipo, $mode){
								// user preset check (defined sqo limit)
								$user_preset = request_config_presets::search_request_config(
									$tipo,
									$section_tipo,
									navigator::get_user_id(), // int $user_id
									$mode,
									null // view
								);
								if (!empty($user_preset[0])) {
									$user_preset_rqo = $user_preset[0]->rqo;
									if (isset($user_preset_rqo) && isset($user_preset_rqo->show->sqo_config->limit)) {
										$limit = $user_preset_rqo->show->sqo_config->limit;
									}
								}
								return $limit ?? ($mode==='list' ? 10 : 1);
							  })();

					// offset . reset to zero
						$offset	= 0;

					// sqo create
						$sqo = new search_query_object();
							$sqo->set_id($sqo_id);
							$sqo->set_mode($mode);
							$sqo->set_section_tipo([$section_tipo]);
							$sqo->set_limit($limit);
							$sqo->set_offset($offset);

							if (!empty($section_id)) {
								$self_locator = new locator();
									$self_locator->set_section_tipo($section_tipo);
									$self_locator->set_section_id($section_id);
								$sqo->set_filter_by_locators([$self_locator]);
							}
				}
			}//end if (!empty($rqo->sqo))

		// DATA
			switch ($action) {

				case 'search': // Used by section and service autocomplete

					// DES resolve limit before use sqo
						// if ( (property_exists($sqo, 'limit') && $sqo->limit===null)
						// 	&& isset($_SESSION['dedalo']['config']['sqo'][$sqo_id])
						// 	&& isset($_SESSION['dedalo']['config']['sqo'][$sqo_id]->limit)
						// ) {
						// 	$sqo->limit = $_SESSION['dedalo']['config']['sqo'][$sqo_id]->limit;
						// 	debug_log(__METHOD__." Set limit from session to $sqo->limit ".to_string(), logger::DEBUG);
						// }

					// sections instance
						$element = sections::get_instance(
							null, // ?array $ar_locators
							$sqo, // object $search_query_object = null
							$tipo, //  string $caller_tipo = null
							$mode, // string $mode = 'list'
							$lang // string $lang = DEDALO_DATA_NOLAN
						);

					// store sqo section in session
						if ($model==='section' && ($mode==='edit' || $mode==='list')) {
							$_SESSION['dedalo']['config']['sqo'][$sqo_id] = $sqo;
							debug_log(__METHOD__
								. " -> saved in session sqo sqo_id: '$sqo_id'" . PHP_EOL
								. ' sqo:' . to_string($sqo)
								, logger::DEBUG
							);
						}

					// data_source. Used by time machine as 'tm' to force component to load data from different sources. data_source='tm'
						if (isset($ddo_source->data_source)) {
							$element->data_source = $ddo_source->data_source;
						}

					// properties optional. If received, overwrite element properties
						if (!empty($properties)){
							$element->set_properties($properties);
						}

					// unlock user components. Normally this occurs when user navigate across sections or paginate
						if (DEDALO_LOCK_COMPONENTS===true) {
							lock_components::force_unlock_all_components( navigator::get_user_id() );
						}
					break;

				case 'related_search': // Used to get the related sections that call to the source section

					// sections
						$element = sections::get_instance(
							null,
							$sqo,
							$tipo,
							$mode,
							$lang ?? DEDALO_DATA_LANG
						);

					// store sqo section
						if ($model==='section' && ($mode==='edit' || $mode==='list')) {
							$_SESSION['dedalo']['config']['sqo'][$sqo_id] = $sqo;
						}
					break;

				case 'get_data': // Used by components and areas

					if (strpos($model, 'component_')===0) {

						if ($section_id<1) {
							// invalid call
							debug_log(__METHOD__
								. " WARNING data:get_data invalid section_id: "
								. to_string($section_id)
								, logger::WARNING
							);
						}else{
							// component
								$component_lang	= (RecordObj_dd::get_translatable($tipo)===true)
									? $lang
									: DEDALO_DATA_NOLAN;

								$element = component_common::get_instance(
									$model,
									$tipo,
									$section_id,
									$mode,
									$component_lang,
									$section_tipo,
									true, // cache
									$caller_dataframe ?? null
								);

							// time machine matrix_id.
								// if ($mode==='tm') {
								if (isset($ddo_source->matrix_id)) {
									// set matrix_id value to component to allow it search dato in
									// matrix_time_machine component function 'get_dato' will be
									// overwritten to get time machine dato instead the real dato
									$element->matrix_id = $ddo_source->matrix_id;
								}

							// data_source. Used by time machine as 'tm' to force component to load data from different sources. data_source='tm'
								if (isset($ddo_source->data_source)) {
									$element->data_source = $ddo_source->data_source;
								}

							// view optional
								if (!empty($view)) {
									$element->set_view($view);
								}

							// properties optional
								if (!empty($properties)){
									$element->set_properties($properties);
								}

							// pagination. Fix pagination vars (defined in class component_common)
								if (isset($rqo->sqo->limit) || isset($rqo->sqo->offset)) {
									$pagination = new stdClass();
										$pagination->limit	= $rqo->sqo->limit;
										$pagination->offset	= $rqo->sqo->offset;

									$element->pagination = $pagination;
								}

						}//end if ($section_id>=1)

					}else if (strpos($model, 'area')===0) {

						// areas
							$element = area::get_instance($model, $tipo, $mode);
							$element->properties = $element->get_properties() ?? new stdClass();

						// thesaurus_mode
							if (isset($ddo_source->properties->thesaurus_mode)) {
								$element->properties->thesaurus_mode = $ddo_source->properties->thesaurus_mode;
							}

						// search_action
							$search_action = $ddo_source->search_action ?? 'show_all';

								$element->properties->action = $search_action;
								$element->properties->sqo	 = $sqo;
								if (isset($ddo_source->properties->hierarchy_types)) {
									$element->properties->hierarchy_types = $ddo_source->properties->hierarchy_types;
								}
								if (isset($ddo_source->properties->hierarchy_sections)) {
									$element->properties->hierarchy_sections = $ddo_source->properties->hierarchy_sections;
								}
								if (isset($ddo_source->properties->hierarchy_terms)) {
									$element->properties->hierarchy_terms = $ddo_source->properties->hierarchy_terms;
								}

					}else if ($model==='section') {

						// $element = section::get_instance($section_id, $section_tipo);
						// (!) Not used anymore
						debug_log(__METHOD__." WARNING data:get_data model section skip. Use action 'search' instead.", logger::WARNING);

					}else if (class_exists($model)) {

						// case menu and similar generic elements

						$element = new $model();

					}else{

						// others
							// get data model not defined
							debug_log(__METHOD__." WARNING data:get_data model not defined for tipo: $tipo - model: $model", logger::WARNING);
					}
					break;

				case 'resolve_data': // Used by components in search mode like portals to resolve locators data

					if (strpos($model, 'component')===0) {

						// component
							$component_lang	= (RecordObj_dd::get_translatable($tipo)===true)
								? $lang
								: DEDALO_DATA_NOLAN;
							$element = component_common::get_instance(
								$model,
								$tipo,
								$section_id,
								$mode,
								$component_lang,
								$section_tipo
							);
						// inject custom value to the component (usually an array of locators)
							$value = $rqo->source->value ?? [];
							$element->set_dato($value);

						// pagination. fix pagination vars (defined in class component_common)
							if (isset($rqo->sqo->limit) || isset($rqo->sqo->offset)) {
								$pagination = new stdClass();
									$pagination->limit	= $rqo->sqo->limit;
									$pagination->offset	= $rqo->sqo->offset;

								$element->pagination = $pagination;
							}

					}else{

						// others
							// resolve_data model not defined
							debug_log(__METHOD__." WARNING data:resolve_data model not defined for tipo: $tipo - model: $model", logger::WARNING);
					}
					break;

				case 'get_relation_list': // Used by relation list only (legacy compatibility)

					$element = new relation_list(
						$tipo,
						$section_id,
						$section_tipo,
						$mode
					);
					$element->set_sqo($sqo);
					break;

				default:
					// not defined model from context / data
					debug_log(__METHOD__." 1. Ignored action '$action' - tipo: $tipo ", logger::WARNING);
					break;
			}//end switch($action)

			// add if exists
				if (isset($element)) {

					// build_options
						$build_options = $ddo_source->build_options ?? null;
						$element->set_build_options($build_options);

					// element JSON
						$get_json_options = new stdClass();
							$get_json_options->get_context	= true;
							$get_json_options->get_data		= true;
						$element_json = $element->get_json($get_json_options);

					// data add
						// $data = array_merge($data, $element_json->data);

					// context and data add
						$context	= $element_json->context;
						$data		= $element_json->data;

					// ar_all_section_id (experimental)
						// $ar_all_section_id = $element->get_ar_all_section_id();
						// 	dump($ar_all_section_id, ' ar_all_section_id ++ '.to_string());

				}//end if (isset($element))
				else {
					debug_log(__METHOD__." Ignored action '$action' - tipo: $tipo (No element was generated) ", logger::WARNING);
					$context = $data = [];
				}

		// result. Set result object
			$result->context	= $context;
			$result->data		= $data;

		// permissions check. Prevent mistaken data resolutions
			$permissions = common::get_permissions($section_tipo, $tipo);
			if (!empty($result->data) && $permissions<1 && $element->get_model()!=='menu') {

				// $result->data = [];

				debug_log(__METHOD__
					.' Catching non enough permissions call' . PHP_EOL
					.' User: '. navigator::get_user_id() . PHP_EOL
					.' tipo: '. $tipo . PHP_EOL
					.' section_tipo: '. $section_tipo . PHP_EOL
					.' Permissions: ' .$permissions . PHP_EOL
					.' rqo: '.to_string($rqo)
					, logger::ERROR
				);
			}

		// debug
			if(SHOW_DEBUG===true) {
				// dump($context, ' context ++ '.to_string());
				// dump($data, ' data ++ '.to_string());
				$debug = new stdClass();
					$debug->sqo				= $sqo ?? null;
					// $debug->rqo			= $rqo;
					$debug->exec_time		= exec_time_unit($start_time,'ms').' ms';
					$debug->memory_usage	= dd_memory_usage();
				$result->debug = $debug;
			}


		return $result;
	}//end build_json_rows



	/**
	* SMART_REMOVE_DATA_DUPLICATES
	* @param array $data
	* @return array $clean_data
	*/
	private static function smart_remove_data_duplicates(array $data) : array {

		$clean_data = [];
		foreach ($data as $value_obj) {
			#if (!in_array($value_obj, $clean_data, false)) {
			#	$clean_data[] = $value_obj;
			#}
			$found = array_filter($clean_data, function($item) use($value_obj){
				if (
					isset($item->section_tipo) && isset($value_obj->section_tipo) && $item->section_tipo===$value_obj->section_tipo &&
					isset($item->section_id) && isset($value_obj->section_id) && $item->section_id===$value_obj->section_id &&
					isset($item->tipo) && isset($value_obj->tipo) && $item->tipo===$value_obj->tipo &&
					isset($item->from_component_tipo) && isset($value_obj->from_component_tipo) && $item->from_component_tipo===$value_obj->from_component_tipo &&
					isset($item->lang) && isset($value_obj->lang) && $item->lang===$value_obj->lang
				){
					return $item;
				}
			});

			if (empty($found)) {
				$clean_data[] = $value_obj;
			}
		}

		#$clean_data = array_unique($data, SORT_REGULAR);
		#$clean_data = array_values($clean_data);

		return $clean_data;
	}//end smart_remove_data_duplicates



	/**
	* SMART_REMOVE_CONTEXT_DUPLICATES
	* @param array $data
	* @return array $clean_data
	*/
	private static function smart_remove_context_duplicates(array $context) : array {

		$clean_context = [];
		foreach ($context as $value_obj) {
			#if (!in_array($value_obj, $clean_context, false)) {
			#	$clean_context[] = $value_obj;
			#}
			$found = array_filter($clean_context, function($item) use($value_obj){
				if (
					$item->section_tipo===$value_obj->section_tipo &&
					$item->tipo===$value_obj->tipo &&
					$item->lang===$value_obj->lang
				){
					return $item;
				}
			});

			if (empty($found)) {
				$clean_context[] = $value_obj;
			}
		}

		#$clean_context = array_unique($context, SORT_REGULAR);
		#$clean_context = array_values($clean_context);

		return $clean_context;
	}//end smart_remove_context_duplicates



	// end private methods ///////////////////////////////////



	/**
	* GET_INDEXATION_GRID
	* @see class.request_query_object.php
	* @param object $rqo
	* {
	*	action	: 'get_indexation_grid',
	*	source	: {
	*		section_tipo	: section_tipo,
	*		section_id		: section_id,
	*		tipo			: "test25", component_tipo
	*		value			: value // ["oh1",] array of section_tipo \ used to filter the locator with specific section_tipo (like 'oh1')
	*	}
	* }
	* @return object $response
	*/
	public static function get_indexation_grid(object $rqo) : object {

		// rqo vars
			// ddo_source
			$ddo_source		= $rqo->source;
			// source vars
			$section_tipo	= $ddo_source->section_tipo ?? $ddo_source->tipo;
			$section_id		= $ddo_source->section_id ?? null;
			$tipo			= $ddo_source->tipo ?? null;
			$value			= $ddo_source->value ?? null; // ["oh1",] array of section_tipo \ used to filter the locator with specific section_tipo (like 'oh1')

		// response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
				$response->error	= null;

		// validate input data
			if (empty($rqo->source->section_tipo) || empty($rqo->source->tipo) || empty($rqo->source->section_id)) {
				$response->msg .= ' Trigger Error: ('.__FUNCTION__.') Empty source properties (section_tipo, section_id, tipo are mandatory)';
				$response->error = 1;

				debug_log(__METHOD__
					. " $response->msg " .PHP_EOL
					. ' source: '. to_string($rqo->source)
					, logger::ERROR
				);

				return $response;
			}

		// diffusion_index_ts
			$indexation_grid	= new indexation_grid($section_tipo, $section_id, $tipo, $value);
			$index_grid			= $indexation_grid->build_indexation_grid();

		// response OK
			$response->msg		= 'OK. Request done successfully';
			$response->result	= $index_grid;


		return $response;
	}//end get_indexation_grid



	/**
	* SERVICE_REQUEST
	* Call to service method given and return and object with the response
	*
	* Class file of current service must be exists in path: DEDALO_SERVICES_PATH / my_service / class.service.php
	* Method must be static and accept a only one object argument
	* Method must return an object like { result: mixed, msg: string }
	*
	* @param object $rqo
	* sample:
	* {
	* 	action: "service_request"
	* 	dd_api: "dd_core_api"
	* 	source: {typo: "source", action: "build_subtitles_text", model: "subtitles", arguments: {
	*   	sourceText: "rsc860"
	*		maxCharLine: 90
	*		type: "srt"
	*		tc_in_secs: 10
	*		tc_out_secs: 35
	*   }}
	* }
	* @return object $response
	* {
	* 	result : mixed,
	* 	msg : string,
	* 	error : int|null
	* }
	*/
		// public static function service_request(object $rqo) : object {

		// 	$response = new stdClass();
		// 		$response->result	= false;
		// 		$response->msg		= 'Error. Request failed ['.__METHOD__.']. ';
		// 		$response->error	= null;

		// 	// short vars
		// 		$source			= $rqo->source;
		// 		$service_name	= $source->model;
		// 		$service_method	= $source->action;
		// 		$arguments		= $source->arguments ?? new stdClass();

		// 	// load services class file
		// 		$class_file = DEDALO_CORE_PATH . '/services/' .$service_name. '/class.' . $service_name .'.php';
		// 		if (!file_exists($class_file)) {
		// 			$response->msg = 'Error. services class_file do not exists. Create a new one in format class.my_service_name.php ';
		// 			if(SHOW_DEBUG===true) {
		// 				$response->msg .= '. file: '.$class_file;
		// 			}
		// 			return $response;
		// 		}
		// 		require $class_file;

		// 	// method (static)
		// 		if (!method_exists($service_name, $service_method)) {
		// 			$response->msg = 'Error. services method \''.$service_method.'\' do not exists ';
		// 			return $response;
		// 		}
		// 		try {

		// 			$fn_result = call_user_func(array($service_name, $service_method), $arguments);

		// 		} catch (Exception $e) { // For PHP 5

		// 			trigger_error($e->getMessage());

		// 			$fn_result = new stdClass();
		// 				$fn_result->result	= false;
		// 				$fn_result->msg		= 'Error. Request failed on call_user_func service_method: '.$service_method;

		// 		}

		// 		$response = $fn_result;


		// 	return $response;
		// }//end service_request



	/**
	* GET_ENVIRONMENT -> WORK IN PROGRESS
	* Calculate the minimum Dédalo environment to work
	* Note that the value is different from logged and not logged cases
	* @return object $environment
	*/
	public static function get_environment() : object {

		// page_globals
			$page_globals = (function() {

				$mode				= $_GET['m'] ?? $_GET['mode'] ?? (!empty($_GET['id']) ? 'edit' : 'list');
				$user_id			= $_SESSION['dedalo']['auth']['user_id'] ?? null;
				$username			= $_SESSION['dedalo']['auth']['username'] ?? null;
				$full_username		= $_SESSION['dedalo']['auth']['full_username'] ?? null;
				$is_global_admin	= $_SESSION['dedalo']['auth']['is_global_admin'] ?? null;
				$is_root			= $user_id==DEDALO_SUPERUSER;

				$obj = new stdClass();
					// logged informative only
					$obj->is_logged							= login::is_logged();
					$obj->is_global_admin					= $is_global_admin;
					$obj->is_root							= $is_root;
					$obj->user_id							= $user_id;
					$obj->username							= $username;
					$obj->full_username						= $full_username;
					// version
					$obj->dedalo_entity						= DEDALO_ENTITY;
					// version
					$obj->dedalo_version					= DEDALO_VERSION;
					// build
					$obj->dedalo_build						= DEDALO_BUILD;
					// mode
					$obj->mode								= $mode ?? null;
					// lang
					$obj->dedalo_application_langs_default	= DEDALO_APPLICATION_LANGS_DEFAULT;
					$obj->dedalo_application_lang			= DEDALO_APPLICATION_LANG;
					$obj->dedalo_data_lang					= DEDALO_DATA_LANG;
					$obj->dedalo_data_nolan					= DEDALO_DATA_NOLAN;
					// dedalo_projects_default_langs
					if ($obj->is_logged===true && defined('DEDALO_INSTALL_STATUS') && DEDALO_INSTALL_STATUS==='installed') {
						$obj->dedalo_projects_default_langs	= array_map(function($current_lang) {
							$lang_obj = new stdClass();
								$lang_obj->label = lang::get_name_from_code($current_lang);
								$lang_obj->value = $current_lang;
							return $lang_obj;
						}, DEDALO_PROJECTS_DEFAULT_LANGS);
					}
					// quality defaults
					$obj->dedalo_image_quality_default	= DEDALO_IMAGE_QUALITY_DEFAULT;
					$obj->dedalo_av_quality_default		= DEDALO_AV_QUALITY_DEFAULT;
					$obj->dedalo_image_thumb_default	= DEDALO_IMAGE_THUMB_DEFAULT;

					// tag_id
					$obj->tag_id						= isset($_REQUEST['tag_id']) ? safe_xss($_REQUEST['tag_id']) : null;
					// dedalo_protect_media_files
					$obj->dedalo_protect_media_files	= (defined('DEDALO_PROTECT_MEDIA_FILES') && DEDALO_PROTECT_MEDIA_FILES===true) ? 1 : 0;
					// notifications
					$obj->DEDALO_NOTIFICATIONS			= defined("DEDALO_NOTIFICATIONS") ? (int)DEDALO_NOTIFICATIONS : 0;
					// float_window_features
					// $obj->float_window_features		= json_decode('{"small":"menubar=no,location=no,resizable=yes,scrollbars=yes,status=no,width=600,height=540"}');
					$obj->fallback_image				= DEDALO_CORE_URL . '/themes/default/0.jpg';
					$obj->locale						= DEDALO_LOCALE;
					$obj->DEDALO_DATE_ORDER				= DEDALO_DATE_ORDER;
					$obj->component_active				= null;
					// debug only
					if(SHOW_DEBUG===true) {
						$obj->dedalo_db_name	= DEDALO_DATABASE_CONN;
						if ($obj->is_logged===true && defined('DEDALO_INSTALL_STATUS') && DEDALO_INSTALL_STATUS==='installed') {
							$obj->pg_version = (function() {
								try {
									$conn = DBi::_getConnection() ?? false;
									if ($conn) {
										return pg_version(DBi::_getConnection())['server'];
									}
									return 'Failed!';
								}catch(Exception $e){
									debug_log(__METHOD__
										." Exception Error: " . PHP_EOL
										. $e->getMessage()
										, logger::ERROR
									);
									return 'Failed with Exception!';
								}
							})();
						}
						$obj->php_version		= PHP_VERSION;
						// $obj->php_version	.= ' jit:'. (int)(opcache_get_status()['jit']['enabled'] ?? false);
						$obj->php_memory		= to_string(ini_get('memory_limit'));
					}


				return $obj;
			})();

		// lang labels
			$lang_file_content = file_get_contents(DEDALO_CORE_PATH . '/common/js/lang/'.DEDALO_APPLICATION_LANG.'.js');

		// environment object
			$environment = (object)[
				// page_globals
				'page_globals'						=> $page_globals,
				// plain global vars
				'DEDALO_ENVIRONMENT'				=> true,
				// 'DEDALO_API_URL'					=> defined('DEDALO_API_URL') ? DEDALO_API_URL : (DEDALO_CORE_URL . '/api/v1/json/'),
				'DEDALO_CORE_URL'					=> DEDALO_CORE_URL,
				'DEDALO_ROOT_WEB'					=> DEDALO_ROOT_WEB,
				'DEDALO_TOOLS_URL'					=> DEDALO_TOOLS_URL,
				'SHOW_DEBUG'						=> SHOW_DEBUG,
				'SHOW_DEVELOPER'					=> SHOW_DEVELOPER,
				'DEVELOPMENT_SERVER'				=> DEVELOPMENT_SERVER,
				'DEDALO_SECTION_ID_TEMP'			=> DEDALO_SECTION_ID_TEMP,
				'DEDALO_UPLOAD_SERVICE_CHUNK_FILES'	=> DEDALO_UPLOAD_SERVICE_CHUNK_FILES,
				'DEDALO_LOCK_COMPONENTS'			=> DEDALO_LOCK_COMPONENTS,
				// DD_TIPOS . Some useful dd tipos (used in client by tool_user_admin for example)
				'DD_TIPOS' => [
					// 'DEDALO_SECTION_USERS_TIPO'			=> DEDALO_SECTION_USERS_TIPO,
					// 'DEDALO_USER_PROFILE_TIPO'			=> DEDALO_USER_PROFILE_TIPO,
					// 'DEDALO_FULL_USER_NAME_TIPO'			=> DEDALO_FULL_USER_NAME_TIPO,
					// 'DEDALO_USER_EMAIL_TIPO'				=> DEDALO_USER_EMAIL_TIPO,
					// 'DEDALO_FILTER_MASTER_TIPO'			=> DEDALO_FILTER_MASTER_TIPO,
					// 'DEDALO_USER_IMAGE_TIPO'				=> DEDALO_USER_IMAGE_TIPO,
					'DEDALO_RELATION_TYPE_INDEX_TIPO'		=> DEDALO_RELATION_TYPE_INDEX_TIPO,
					'DEDALO_SECTION_INFO_INVERSE_RELATIONS'	=> DEDALO_SECTION_INFO_INVERSE_RELATIONS
				],
				// labels
				// 'get_label' => include DEDALO_CORE_PATH . '/common/js/lang/'.DEDALO_APPLICATION_LANG.'.js'
				'get_label' => json_decode($lang_file_content)
			];

		$response = new stdClass();
			$response->result	= $environment;
			$response->msg		= 'OK. Successful request';
			$response->error	= null;


		return $response;
	}//end get_environment



}//end dd_core_api
