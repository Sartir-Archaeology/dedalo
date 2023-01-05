<?php
// includes. Include another files if need
	// include( dirname(__FILE__) . '/additional/class.additional.php');



/**
* CLASS TOOL_IMPORT_DEDALO_CSV
* This tool is intended to be used as a base build for new tools. Do not use as a production tool.
*
*/
class tool_import_dedalo_csv extends tool_common {



	/**
	* GET_CSV_FILES
	* Read requested dir and return all files of request extension found
	* @return object $response
	*/
	public static function get_csv_files(object $request_options) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';
			$response->error	= null;

		// short vars
			$dir = DEDALO_TOOL_IMPORT_DEDALO_CSV_FOLDER_PATH;

		// read_files
			$files_list	= tool_common::read_files($dir, $valid_extensions=['csv']);

		// files info
			$files_info = [];
			foreach ($files_list as $current_file_name) {

				$file = $dir .'/'. $current_file_name;

				try {

					// data . extract csv data from file
					// $file, $skip_header=false, $csv_delimiter=';', $enclosure='"', $escape='"'
					$ar_data = tool_common::read_csv_file_as_array(
						$file, // file string
						false, // skip_header bool
						';', // csv_delimiter string
						'"', // enclosure string
						'"' // escape string
					);

					if (empty($ar_data) || empty($ar_data[0])) {
						debug_log(__METHOD__." Error on read file 1 : ".to_string($file).' The file will be ignored', logger::ERROR);
						continue;
					}

					$file_info	= (array)$ar_data[0];
					$n_records	= count($ar_data)-1;
					$n_columns	= count($file_info);

					// ar_columns_map
						$ar_columns_map = array_map(function($el){
							return (object)[
								'tipo'	=> $el,
								'label'	=> RecordObj_dd::get_termino_by_tipo($el, DEDALO_APPLICATION_LANG, true),
								'model'	=> $el!=='section_id' ? RecordObj_dd::get_modelo_name_by_tipo($el, true) : $el
							];
						}, $file_info);

				} catch (Exception $e) {

					$response->error =  'Error on read file: '.to_string($file);

					debug_log(__METHOD__." Error on read file 2: ".to_string($file).' The file will be ignored', logger::ERROR);
					continue;
				}

				// sample data of first n rows to verify is valid
					$sample_data		= [];
					$sample_data_errors	= [];
					$preview_max		= 10;
					foreach ($ar_data as $dkey => $current_line) {

						if (empty($current_line)) {
							continue;
						}

						foreach ($current_line as $key => $value) {
							if (empty($value)) {
								continue;
							}

							$value = str_replace('U+003B', ';', $value);

							# Test valid JSON
							if (strpos($value,'[')===0 || strpos($value,'{')===0) {

								$test = json_decode($value);
								if ($test===null) {
									debug_log(__METHOD__." ERROR!! BAD JSON FORMAT  ".to_string($value), logger::ERROR);

									// $current_line  = '<span class="error">ERROR!! BAD JSON FORMAT</span>';
									// $current_line .= '<div>'.$value.'</div>';

									$sample_data_errors[] = $current_line;
								}

								if(json_last_error()!==JSON_ERROR_NONE){
									debug_log(__METHOD__." JSON decode error has occurred: ".json_last_error_msg(), logger::ERROR);
								}
							}
						}

						// add skipping header line
							if ($dkey>0) {
								$sample_data[] = $current_line;
							}

						// Stop on reach limit
						if ($dkey>=$preview_max) break;
					}//end foreach ($ar_data as $dkey => $current_line)

				// files_info
					$item = (object)[
						'dir'					=> $dir,
						'name'					=> $current_file_name,
						'data'					=> $ar_data, // $ar_data,
						'n_records'				=> $n_records,
						'n_columns'				=> $n_columns,
						'file_info'				=> $file_info,
						'ar_columns_map'		=> $ar_columns_map,
						'sample_data'			=> '$sample_data',
						'sample_data_errors'	=> '$sample_data_errors'
					];

					$files_info[] = $item;
			}//end foreach ($files_list as $current_file_name)

			// dump($response, ' response 2 ++ '.to_string());
			// error_log( 'Type: '.gettype($files_info) );
			// error_log( 'Count: '.count($files_info) );
			// error_log( 'Type[0]: '.gettype($files_info[0]) );
			// trigger_error( 'get_object_vars:'. get_object_vars($files_info[0]));
			// error_log( json_encode($files_info) );

		// response
			$response->result	= $files_info;
			$response->msg		= !empty($files_info)
				? "Found ".count($files_info)." files"
				: "No files found at $dir";
			$response->error	= isset($error)
				? $error
				: null;


		return (object)$response;
	}//end get_csv_files



	/**
	* DELETE_CSV_FILE
	* 	Delete given CSV file from server
	* @param object $request_options
	* @return object $response
	*/
	public static function delete_csv_file(object $request_options) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';

		// options
			$options = new stdClass();
				$options->file_name	= null;
				foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}

		// short vars
			$dir		= DEDALO_TOOL_IMPORT_DEDALO_CSV_FOLDER_PATH; // fixed dir from config
			$file_name	= $options->file_name;

		// remove file is exists
			$file_full_path = $dir .'/'. $file_name;
			if (file_exists($file_full_path)) {
				if( unlink($file_full_path) ) {
					$response->result 	= true;
					$response->msg 		= 'Ok. Request file '.$file_name.' is deleted';
					debug_log(__METHOD__." response->msg: $response->msg".to_string(), logger::DEBUG);
				}else{
					$response->msg 		= 'Error. File exists but you don\'t have permissions to delete this file';
					debug_log(__METHOD__." response->msg: $response->msg".to_string(), logger::ERROR);
				}
			}


		return (object)$response;
	}//end delete_csv_file



	/**
	* IMPORT_FILES
	* 	Import user selected files
	* @param object $request_options
	* @return object $response
	*/
	public static function import_files(object $request_options) : object {
		$start_time = start_time();

		// Ignore user close browser
			ignore_user_abort(true);

		// options
			$options = new stdClass();
				$options->files				= null;
				$options->time_machine_save	= null;
				foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}

		// short vars
			$files				= $options->files;
			$time_machine_save	= $options->time_machine_save;
			$dir				= DEDALO_TOOL_IMPORT_DEDALO_CSV_FOLDER_PATH;

		// import each file
			$import_response=[];
			foreach ((array)$files as $current_file_obj) {



				$current_file	= $current_file_obj->file; // string like 'exported_oral-history_-1-oh1.csv'
				$section_tipo	= $current_file_obj->section_tipo; // string like 'oh1'
				$ar_columns_map	= $current_file_obj->ar_columns_map; // array of objects like [{checked: false, label: "", mapped_to: "", model: "", tipo: "section_id"}]

				# FILE
				$file = $dir . '/' . $current_file;
				if (!file_exists($file)) {
					$current_file_response = new stdClass();
						$current_file_response->result			= false;
						$current_file_response->msg				= "Error. File not found: ".$file;
						$current_file_response->file			= $current_file;
						$current_file_response->section_tipo	= $section_tipo;
					$import_response[] = $current_file_response;
					continue;
				}
				$ar_csv_data = tool_common::read_csv_file_as_array($file, $skip_header=false, ';');
					#dump($ar_csv_data, ' $ar_csv_data ++ '.to_string($file)); die();
					#debug_log(__METHOD__." ar_csv_data ".to_string($ar_csv_data), logger::DEBUG);

				// counter. Consolidate counter. Set counter value to last section_id in section
				counter::consolidate_counter( $section_tipo, common::get_matrix_table_from_tipo($section_tipo) );

				// import exec
				$current_file_response = (object)tool_import_dedalo_csv::import_dedalo_csv_file(
					$section_tipo,
					$ar_csv_data,
					$time_machine_save,
					$ar_columns_map
				);
				$current_file_response->file			= $current_file;
				$current_file_response->section_tipo	= $section_tipo;

				$import_response[] = $current_file_response;
			}
			#dump($result, ' result ++ '.to_string()); exit();

		// response
			$response = new stdClass();
				$response->result	= $import_response;
				$response->msg		= 'Request done';

		// debug
			if(SHOW_DEBUG===true) {
				$debug = new stdClass();
					$debug->exec_time	= exec_time_unit($start_time,'ms')." ms";
					$debug->options		= $options;
				$response->debug = $debug;
			}


		return (object)$response;
	}//end import_files



	/**
	* IMPORT_DEDALO_CSV_FILE
	* 	Import CSV array data to Dédalo
	*
	* @param string $section_tipo
	* @param array $ar_csv_data
	* @param bool $time_machine_save
	* @param array $ar_columns_map
	*
	* @return object $response
	*/
	public static function import_dedalo_csv_file(string $section_tipo, array $ar_csv_data, bool $time_machine_save, array $ar_columns_map) : object {
		$start_time = start_time();

		// Disable logging activity # !IMPORTANT
			logger_backend_activity::$enable_log = false;

		// response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed';

		// csv_map. The CSV file map is always the first row
			// $csv_map = $ar_csv_data[0];
			$csv_map = $ar_columns_map;
				// dump($csv_map, ' csv_map ++ '.to_string());
				// die();

		// Verify csv_map
			$verify_csv_map = self::verify_csv_map($csv_map, $section_tipo);
			if ($verify_csv_map->result!==true) {
				$response->result	= false;
				$response->msg		= 'Error. Current CSV file first row (headers) is invalid (1): '.$verify_csv_map->msg;
				// Restore logging activity # !IMPORTANT
					logger_backend_activity::$enable_log = true;
				return $response;
			}

		// section_id key column
			// $section_id_key = (int)array_search('section_id', $csv_map);
			$columns		= array_column($csv_map, 'model');
			$section_id_key	= array_search('component_section_id', $columns);

		// Fixed private section tipos
			$modified_section_tipos = section::get_modified_section_tipos();
				$created_by_user	= array_filter($modified_section_tipos, function($item){ return $item['name']==='created_by_user'; }); 	// array('tipo'=>'dd200', 'model'=>'component_select');
				$created_date		= array_filter($modified_section_tipos, function($item){ return $item['name']==='created_date'; }); 	// array('tipo'=>'dd199', 'model'=>'component_date');
				$modified_by_user	= array_filter($modified_section_tipos, function($item){ return $item['name']==='modified_by_user'; }); // array('tipo'=>'dd197', 'model'=>'component_select');
				$modified_date		= array_filter($modified_section_tipos, function($item){ return $item['name']==='modified_date'; }); 	// array('tipo'=>'dd201', 'model'=>'component_date');

				$created_by_user	= reset($created_by_user);
				$created_date		= reset($created_date);
				$modified_by_user	= reset($modified_by_user);
				$modified_date		= reset($modified_date);

		# Iterate rows
			$created_rows=array();
			$updated_rows=array();

		# sort ar_csv_data by section_id (first column)
			# uasort($ar_csv_data, function($a, $b) {
			#    return $a[0] > $b[0];
			# });
			# dump($ar_csv_data, ' ar_csv_data ++ '.to_string()); die();
		$counter = 0;
		$csv_head_row = $ar_csv_data[0];
		foreach ((array)$ar_csv_data as $rkey => $row) {
			$row_start_time = start_time();


			if($rkey===0) continue; // Skip first row, the header row

			// section_id (cast to int the section_id of the row)
				$section_id = !empty($row[$section_id_key]) ? (int)$row[$section_id_key] : null;
				if (empty($section_id)) {
					debug_log(__METHOD__." ERROR on get section_id . SKIPPED record (section_tipo:$section_tipo - rkey:$rkey) ".to_string($section_id), logger::ERROR);
					continue;
				}

			// always force create/re use section
				$section		= section::get_instance($section_id, $section_tipo, false);
				$create_record	= $section->forced_create_record();
				$section_id		= $section->get_section_id();

			# Iterate fields/columns
			foreach ($row as $key => $value) {

				$column_map = $csv_map[$key];
				$current_csv_head_column = $csv_head_row[$key];

				if($column_map->model === 'section_id' || $column_map->model === 'component_section_id') continue; # Skip section_id value column
				if(!isset($column_map->checked) || $column_map->checked=== false || !isset($column_map->map_to)) continue;
				// check if the column_map is correct with the current column in the csv file (match needed)
				if($current_csv_head_column !== $column_map->tipo) continue;

				# created_by_userID
				if ($column_map->model==='created_by_user' || $column_map->map_to===$created_by_user['tipo']) {

					$user_locator = self::build_user_locator($value, $created_by_user['tipo']);

					if (!empty($user_locator)) {
						// component build and set dato
							$component 		= component_common::get_instance($created_by_user['model'],
																			 $created_by_user['tipo'],
																			 $section_id,
																			 'list',
																			 DEDALO_DATA_NOLAN,
																			 $section_tipo);
							$component->set_dato($user_locator);
							$section->set_component_relation_dato($component);

						// Set direct property also
							$dato = (object)$section->get_dato();
							$dato->created_by_userID = (int)$user_locator->section_id;

						// Save section
							$section->Save();
					}
					continue;
				# created_date
				}elseif ($column_map->model==='created_date' || $column_map->map_to===$created_date['tipo']) {

					$current_date = self::build_date_from_value($value);

					# Format
					# $current_date = array(
					# 	'component_dato' => $value_obj,
					# 	'timestamp' 	 => $timestamp
					# );

					if (!empty($current_date)) {
						// component build and set dato
							$component 		= component_common::get_instance($created_date['model'],
																			 $created_date['tipo'],
																			 $section_id,
																			 'list',
																			 DEDALO_DATA_NOLAN,
																			 $section_tipo);
							$component->set_dato($current_date->component_dato);
							$section->set_component_direct_dato($component);

						// Set direct property also
							$dato = (object)$section->get_dato();
							$dato->created_date = $current_date->timestamp;

						// Save
							$section->Save();
					}
					continue;
				# modified_by_user
				}elseif ($column_map->model==='modified_by_user' || $column_map->map_to===$modified_by_user['tipo']) {

					$user_locator 	 = self::build_user_locator($value, $modified_by_user['tipo']);
						#dump($user_locator, ' user_locator ++ '.to_string());

					if (!empty($user_locator)) {
						// component build and set dato
							$component 		= component_common::get_instance($modified_by_user['model'],
																			 $modified_by_user['tipo'],
																			 $section_id,
																			 'list',
																			 DEDALO_DATA_NOLAN,
																			 $section_tipo);
							$component->set_dato($user_locator);
							$section->set_component_relation_dato($component);

						// Set direct property also
							$section_dato = (object)$section->get_dato();
							$section_dato->modified_by_userID = (int)$user_locator->section_id;
							$section->set_dato($section_dato);

						// Save section
							$section->save_modified = false;
							$section->Save();
					}
					continue;
				# modified_date
				}elseif ($column_map->model==='modified_date' || $column_map->map_to===$modified_date['tipo']) {

					$current_date 	= self::build_date_from_value($value);

					# Format
					# $current_date = array(
					# 	'component_dato' => $value_obj,
					# 	'timestamp' 	 => $timestamp
					# );

					if (!empty($current_date)) {
						// component build and set dato
							$component 		= component_common::get_instance($modified_date['model'],
																			 $modified_date['tipo'],
																			 $section_id,
																			 'list',
																			 DEDALO_DATA_NOLAN,
																			 $section_tipo);
							$component->set_dato($current_date->component_dato);
							$section->set_component_direct_dato($component);

						// Set direct property also
							$section_dato = (object)$section->get_dato();
							$section_dato->modified_date = $current_date->timestamp;
							$section->set_dato($section_dato);

						// Save
							$section->save_modified = false;
							$section->Save();
					}
					continue;
				}

				// component_tipo. Target component is always the CSV map element with current key
					$component_tipo	= $column_map->map_to;
					if (empty($component_tipo)) {
						debug_log(__METHOD__." !!!!!!!! ignored empty component_tipo on csv_map key: $key - csv_map: ".to_string($csv_map), logger::ERROR);
						continue;
					}

				// component
					$model_name		= RecordObj_dd::get_modelo_name_by_tipo($component_tipo, true);
					$mode			= 'list';
					$RecordObj_dd	= new RecordObj_dd($component_tipo);
					$translate		= RecordObj_dd::get_translatable($component_tipo); //==='si' ? true : false;
					$lang			= $translate===false ? DEDALO_DATA_NOLAN : DEDALO_DATA_LANG;
					$component		= component_common::get_instance(
						$model_name,
						$component_tipo,
						$section_id,
						$mode,
						$lang,
						$section_tipo,
						false
					);
					$properties			= $RecordObj_dd->get_properties(true);
					$with_lang_versions	= isset($properties->with_lang_versions) ? $properties->with_lang_versions : false;

				# Configure component
					# DIFFUSION_INFO
					# Note that this process can be very long if there are many inverse locators in this section
					# To optimize save process in scripts of importation, you can disable this option if is not really necessary
					$component->update_diffusion_info_propagate_changes = false;
					# SAVE_TO_DATABASE
					# Set component to save data but tells section that don save updated section to DDBB for now
					# No component time machine data will be saved when section saves later
					$component->save_to_database = ((bool)$time_machine_save===true)
						? true
						: false; // default is false. To activate, select checkbox on tool import dedalo CSV


				# If value is JSON encoded, decode and set
				# Note: json_decode returns null when no is possible decode the value
				#if(strpos($value, '{"lg-')!==false) {
				#	$value = str_replace(EOL, "\n", $value);
				#}
				$value = trim($value); // Avoid wrong final return problems
				# Remove delimiter escape (U+003B for ;)
				$value = str_replace('U+003B', ';', $value);

				// Check if is a JSON string. Is yes, decode
					if(strpos($value, '[')===0 || strpos($value, '{')===0) {
						if($dato_from_json = json_decode($value)) {	// , false, 512, JSON_INVALID_UTF8_SUBSTITUTE
							$value = $dato_from_json;
						}else{
							// log JSON conversion error
							debug_log(__METHOD__." json_last_error: ".json_last_error(), logger::ERROR);
						}
					}
					# debug_log(__METHOD__." Result decode json: type:".gettype($dato_from_json).' -> value: '.$value.' => decoded: '.to_string($dato_from_json), logger::DEBUG);

				// dataframe. Checks value contains dataframe or dato keys
					if (is_object($value)) {
						# Dataframe
						if (property_exists($value, 'dataframe')) {
							foreach ((array)$value->dataframe as $dtkey => $current_dt_locator) {
								$current_from_key 	= $current_dt_locator->from_key;
								$current_type 		= $current_dt_locator->type;
								$component->update_dataframe_element($current_dt_locator, $current_from_key, $current_type); //$ar_locator, $from_key, $type
								debug_log(__METHOD__." Added dataframe locator [$current_from_key,$current_type] ".to_string($current_dt_locator), logger::DEBUG);
							}
						}
						# Dato
						if (property_exists($value, 'dato')) {
							$value = $value->dato;
						}
					}

				# Elements 'translatables' can be formatted as json values like {"lg-eng":"My value","lg-spa":"Mi valor"}
				if (($traducible===true || $with_lang_versions===true) && is_object($value)) {
					debug_log(__METHOD__." Parsing multilanguaje value [$component_tipo - $section_tipo - $section_id]: ".to_string($value), logger::DEBUG);
					foreach ($value as $v_key => $v_value) {

						if (strpos($v_key, 'lg-')===0) {
							$component->set_lang( $v_key );
							$component->set_dato( $v_value );
							$component->Save();
						}else{
							debug_log(__METHOD__." ERROR ON IMPORT VALUE FROM $model_name [$component_tipo] - VALUE: ".to_string($value), logger::ERROR);
						}
					}
				}else{
					// Inverse locators
					if ($model_name==='component_portal' || $model_name==='component_autocomplete') {
						// This is ONLY for add INVERSE LOCATORS. NOT for save dato !!
						if(!empty($value)) {
							foreach ((array)$value as $pkey => $current_locator) {
								if (!empty($current_locator->section_tipo) && !empty($current_locator->section_id))	{
									$component->add_locator($current_locator);
								}else{
									debug_log(__METHOD__." ERROR ON ADD_LOCATOR TO $model_name tipo:$component_tipo, $section_tipo:$section_tipo. locator type:".gettype($current_locator).", SKIPPED EMPTY OR BAD LOCATOR: ".to_string($current_locator), logger::ERROR);
								}
							}
						}//end if(!empty($value))
					}
					// Nolan optional key check
					if (is_object($value) && property_exists($value, 'lg-nolan')) {
						$nolan = 'lg-nolan';
						$value = $value->{$nolan};
					}

					if (is_object($value) && property_exists($value, 'dataframe') && !property_exists($value, 'dato')) {
						// Element without dato. Only the dataframe is saved
					}else{
						// Always set dato
						$component->set_dato( $value );
					}

					// Save of course
					// Note that $component->save_to_database = false, avoid real save.
					$component->Save();
				}
			}//end foreach ($row as $key => $value)

			// action
				if($create_record) {
					$created_rows[] = $section_id;
					$action = "created";
				}else{
					$updated_rows[] = $section_id;
					$action = "updated";
				}

			// SAVE . ROW SAVE . Save edited by components section once per row
				$section->Save();

			// Forces collection of any existing garbage cycles
				$counter++;
				if ($counter===100) {
					$counter = 0;
					gc_collect_cycles();
				}

			#debug_log(__METHOD__." +++ $action section $section_tipo - $section_id - in ".exec_time_unit($row_start_time,'ms').' ms', logger::ERROR);
		}//end foreach ($ar_csv_data as $key => $value)

		// Restore logging activity # !IMPORTANT
			logger_backend_activity::$enable_log = true;

		// response
			if (!empty($updated_rows) || !empty($created_rows)) {
				$response->result		= true;
				$response->msg			= 'Section: '.$section_tipo.'. Total records created:'.count($created_rows).' - updated:'.count($updated_rows);
				$response->created_rows	= $created_rows;
				$response->updated_rows	= $updated_rows;
			}
			$response->time = exec_time_unit($start_time,'ms');


		return (object)$response;
	}//end import_dedalo_csv_file



	/**
	* VERIFY_CSV_MAP
	* @return mixed true|string
	*/
	public static function verify_csv_map(array $csv_map, string $section_tipo) : object {

		// response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed';

		// ar_section_info
			// $ar_section_info = [
			// 	'dd200',
			// 	'dd199',
			// 	'dd197',
			// 	'dd201',
			// 	'dd271',
			// 	'dd1223',
			// 	'dd1224',
			// 	'dd1225'
			// ];
			$ar_section_info = RecordObj_dd::get_ar_childrens(DEDALO_SECTION_INFO_SECTION_GROUP);

		// ar_component_tipo
			$ar_possible_component_tipo = section::get_ar_children_tipo_by_modelo_name_in_section(
				$section_tipo, // section_tipo
				['component_'], // ar_model_name
				true, // from_cache
				true, // resolve_virtual
				true, // recursive
				false // search_exact
			);
		// check if the csv_map has any "map_to" it's necessary to create any component to mach with the csv columns.
			$map_to = array_column($csv_map, 'map_to');
			$non_empty = array_filter($map_to);
			if(empty($non_empty)) return $response;


		foreach ($csv_map as $column_map) {
			// if the column don't has the checked property or the checked is false or the map_to property is missing the column will not processed
			// this situation is not a error and go ahead with the other columns
			if(!isset($column_map->checked) || $column_map->checked ===false || empty($column_map->map_to) ){
				continue;
			}

			// sample item (from ar_columns_map)
				// {
				// 	"tipo": "dd199",
				// 	"label": "Creation date",
				// 	"model": "component_date",
				// 	"checked": true,
				// 	"map_to": "dd199"
				// }

			$component_tipo = $column_map->map_to;

			if(	   $component_tipo==='section_id'
				|| $component_tipo==='created_by_user'
				|| $component_tipo==='created_date'
				|| $component_tipo==='modified_by_user'
				|| $component_tipo==='modified_date'
				|| in_array($component_tipo, $ar_section_info)
			) continue;

			if (!in_array($component_tipo, $ar_possible_component_tipo)) {
				$model_name = RecordObj_dd::get_modelo_name_by_tipo($component_tipo, true);
				// return "Sorry, component tipo: $component_tipo (model: $model_name) not found in section: $section_tipo";

				$response->result = false;
				$response->msg	= "Sorry, component tipo: $component_tipo (model: $model_name) not found in section: $section_tipo";
				return $response;
			}

			$response->result = true;
		}


		return $response;
	}//end verify_csv_map



	/**
	* BUILD_USER_LOCATOR
	* @param string $value
	* @param string $from_component_tipo
	* Create a safe locator from CSV value.
	* Value can be a int like 2 or an complete locator like {"type": "dd151","section_id": "2","section_tipo": "dd128","from_component_tipo": "dd197"}
	* @return object|null $locator
	*/
	public static function build_user_locator(string $value, string $from_component_tipo) : ?object {

		$value = trim($value);

		if (empty($value)) {
			return null;
		}

		// try to JSON decode (null on not decode)
			$value_json = json_decode($value);

		if (!$value_json) {
			// old format (section_id)
			// is int. Builds complete locator and set section_id from value
			$locator = new locator();
				$locator->set_type(DEDALO_RELATION_TYPE_LINK);
				$locator->set_section_tipo(DEDALO_SECTION_USERS_TIPO);
				$locator->set_from_component_tipo($from_component_tipo);
				$locator->set_section_id($value);
		}else{
			// locator or array of locators is received
			$locator_base = is_array($value_json) ? reset($value_json) : $value_json;

			// is full locator. Inject safe fixed properties to avoid errors
			$locator = new locator($locator_base);
				if (!property_exists($locator_base, 'type')) {
					$locator->set_type(DEDALO_RELATION_TYPE_LINK);
				}
				if (!property_exists($locator_base, 'section_tipo')) {
					$locator->set_section_tipo(DEDALO_SECTION_USERS_TIPO);
				}
				if (!property_exists($locator_base, 'from_component_tipo')) {
					$locator->set_from_component_tipo($from_component_tipo);
				}
		}

		if (!isset($locator) || !isset($locator->section_id)) {
			debug_log(__METHOD__." Error on get user locator value from: ".to_string($value), logger::ERROR);
			return null;
		}

		return $locator;
	}//end build_user_locator



	/**
	* BUILD_DATE_FROM_VALUE
	* @param string $value
	* @return object|null $date
	*/
	public static function build_date_from_value(string $value) : ?object {

		$value = trim($value);

		// empty case
			if (empty($value)) {
				return null;
			}

		if ( strpos($value, '{')===0 || strpos($value, '[')===0 ) {
			// is full date. Check object to avoid errors

			# Format
			# {
			#   "start": {
			#     "day": 24,
			#     "hour": 12,
			#     "time": 64891630498,
			#     "year": 2018,
			#     "month": 12,
			#     "minute": 54,
			#     "second": 58
			#   }
			# }
			if ($value_obj = json_decode($value)) {

				// normalize array and object values as single object always
					$value_obj = is_array($value_obj) ? reset($value_obj) : $value_obj;

				// remove lang
					if (isset($value_obj->{DEDALO_DATA_NOLAN})) {
						$value_obj = is_array($value_obj->{DEDALO_DATA_NOLAN})
							? reset($value_obj->{DEDALO_DATA_NOLAN})
							: $value_obj->{DEDALO_DATA_NOLAN};
					}

				// Add start property if not present
					if (!isset($value_obj->start)) {

						$new_value_obj = new stdClass();
							$new_value_obj->start = $value_obj;

						$value_obj = $new_value_obj; // replace here
						debug_log(__METHOD__." Warning. Added property start to data value ".to_string($value), logger::ERROR);
					}

				// Check object mandatory properties
					$ar_properties = ['year','month','day']; // ,'hour','minute','second'
					foreach ($ar_properties as $name) {
						if (!isset($value_obj->start->{$name})) {
							debug_log(__METHOD__." Error. ignored invalid date value (property $name not found) ".to_string($value), logger::ERROR);
							return null;
						}
					}

				// time property is recalculated always for security
					$dd_date = new dd_date($value_obj->start);
					$time 	 = dd_date::convert_date_to_seconds($dd_date);
					$value_obj->start->time = $time;

				// date in timestamp format
					$timestamp = $dd_date->get_dd_timestamp();

				// result
					$result = array(
						'component_dato' => $value_obj,
						'timestamp' 	 => $timestamp
					);

			}else{
				return null;
			}

		}else{
			// is date timestamp. Builds complete date object from value

			$dd_date = new dd_date();
			$dd_date->get_date_from_timestamp( $value );

			$value_obj = new stdClass();
				$value_obj->start = $dd_date;

			// result
				$result = array(
					'component_dato' => $value_obj,
					'timestamp' 	 => $value
				);

		}

		return (object)$result;
	}//end build_date_from_value



	/**
	* GET_SECTION_COMPONENTS_LIST
	* @param string $value
	* @return object $response
	*/
	public static function get_section_components_list(object $options) : object {

		// options
			$section_tipo = $options->section_tipo;

		// response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed';

		try {

			// model safe
				$model = RecordObj_dd::get_modelo_name_by_tipo($section_tipo, true);
				if ($model!=='section') {
					$response->msg .= ' Invalid model (expected section): '.$model;
					return $response;
				}

			$components_list = section::get_ar_children_tipo_by_modelo_name_in_section(
				$section_tipo, // section_tipo
				['component'], // ar_model_name_required
				true, // from_cache
				true, // resolve_virtual
				true, // recursive
				false, // search_exact
				false // ar_tipo_exclude_elements (on false, look for 'exclude_elements' model in virtaul section and apply)
			);
			// dump($components_list, ' components_list ++ '.to_string($section_tipo));

			if (!empty($components_list)) {

				// section info components
				$section_info_components = RecordObj_dd::get_ar_childrens(DEDALO_SECTION_INFO_SECTION_GROUP);
				foreach ($section_info_components as $tipo) {
					$components_list[] = $tipo;
				}

				$result = [];
				foreach ($components_list as $tipo) {
					$result[] = (object)[
						'label' => RecordObj_dd::get_termino_by_tipo($tipo, DEDALO_APPLICATION_LANG, true),
						'value' => $tipo,
						'model' => RecordObj_dd::get_modelo_name_by_tipo($tipo, true)
					];
				}

				$response->result	= $result;
				$response->label	= RecordObj_dd::get_termino_by_tipo($section_tipo, DEDALO_APPLICATION_LANG, true);
				$response->msg		= 'OK. Request done';
			}

		} catch (Exception $e) {
			$response->msg .= ' ' . $e->getMessage();
		}


		return $response;
	}//end get_section_components_list



	/**
	* PROCESS_UPLOADED_FILE
	* Simply moves previously uploaded temp file to the definitive location and name
	* It's called from tool_import_dedalo after event 'upload_file_' + id is published
	* from 'tool_import_dedalo_csv.js' file
	* @param object $options
	* 	Object $options->file_data
	* Sample:
	* {
	*	error: 0
	*	extension: "tiff"
	*	name: "proclamacio.tiff"
	*	size: 184922784
	*	tmp_name: "/hd/media/upload/service_upload/tmp/image/phpPJQvCp"
	*	type: "image/tiff"
	* }
	* @return object $response
	*/
	public static function process_uploaded_file(object $options) : object {
		$start_time=start_time();

		// response
			$response = new stdClass();
				$response->result 	= false;
				$response->msg 		= 'Error. Request failed. '.__METHOD__.' ';

		// options
			$file_data = $options->file_data;

		// file_data sample
			// {
			// 	"name": "IMG_3007.jpg",
			// 	"type": "image/jpeg",
			// 	"tmp_dir": "DEDALO_UPLOAD_TMP_DIR",
			// 	"resource_type": "tool_upload",
			// 	"tmp_name": "phpJIQq4e",
			// 	"error": 0,
			// 	"size": 22131522,
			// 	"extension": "jpg"
			// }

		// short vars
			$name			= $file_data->name; // string original file name like 'IMG_3007.jpg'
			$resource_type	= $file_data->resource_type; // string upload caller name like 'tool_upload'
			$tmp_dir		= $file_data->tmp_dir; // constant string name like 'DEDALO_UPLOAD_TMP_DIR'
			$tmp_name		= $file_data->tmp_name; // string like 'phpJIQq4e'

		// source_file
			if (!defined($tmp_dir)) {
				$msg = 'constant is not defined!  tmp_dir: '.$tmp_dir;
				debug_log(__METHOD__." $msg", logger::ERROR);
				$response->msg .= $msg;
				return $response;
			}
			$source_file = constant($tmp_dir) .'/'. $resource_type . '/' . $tmp_name;

		// check source file file
			if (!file_exists($source_file)) {
				$response->msg .= ' Source file not found: ' . basename($source_file);
				return $response;
			}

		// target_file
			$target_file = DEDALO_TOOL_IMPORT_DEDALO_CSV_FOLDER_PATH . '/' . $name;

		// check target dir
			$dir = DEDALO_TOOL_IMPORT_DEDALO_CSV_FOLDER_PATH;
			if (!is_dir($dir)) {
				if(!mkdir($dir, 0775,true)) {
					$response->msg .= trim(" Error on read or create default directory. Permission denied ");
					return $response;
				}
				debug_log(__METHOD__." CREATED DIR: $dir  ".to_string(), logger::DEBUG);
			}

		// move file
			$moved = rename($source_file, $target_file);
			if ($moved!==true) {
				debug_log(__METHOD__.' Error on move source file to target_dir', logger::ERROR);
				$response->msg .= ' Error on move source file to target_dir';
				return $response;
			}

		// response ok
			$response->result		= true;
			$response->file_name	= $name;
			$response->msg			= 'OK. Request done successfully';


		// debug
			if(SHOW_DEBUG===true) {
				$debug = new stdClass();
					$debug->exec_time	= exec_time_unit($start_time,'ms')." ms";
				$response->debug = $debug;
			}


		return $response;
	}//end process_uploaded_file



}//end class tool_import_dedalo_csv
