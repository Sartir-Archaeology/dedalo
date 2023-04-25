<?php
/**
* AREA_DEVELOPMENT
*
*
*/
class area_development extends area_common {



	static $ar_tables_with_relations = [
		'matrix_users',
		'matrix_projects',
		'matrix',
		'matrix_list',
		'matrix_activities',
		'matrix_hierarchy',
		'matrix_hierarchy_main',
		'matrix_langs',
		'matrix_layout',
		'matrix_notes',
		'matrix_profiles',
		'matrix_test',
		'matrix_indexations',
		'matrix_structurations',
		'matrix_dd',
		'matrix_layout_dd',
		'matrix_activity',
		'matrix_tools'
	];//end ar_tables_with_relations



	/**
	* GET_AR_WIDGETS
	* @return array $data_items
	*	Array of widgets object
	*/
	public function get_ar_widgets() : array {

		$ar_widgets = [];

		$DEDALO_PREFIX_TIPOS = get_legacy_constant_value('DEDALO_PREFIX_TIPOS');


		// make_backup
			$item = new stdClass();
				$item->id		= 'make_backup';
				$item->typo		= 'widget';
				$item->label	= label::get_label('make_backup') ?? 'Make backup';
				$item->value	= (object)[
					'dedalo_db_management'	=> DEDALO_DB_MANAGEMENT,
					'backup_path'			=> DEDALO_BACKUP_PATH_DB,
					'file_name'				=> date("Y-m-d_His") .'.'. DEDALO_DATABASE_CONN .'.'. DEDALO_DB_TYPE .'_'. $_SESSION['dedalo']['auth']['user_id'] .'_forced_dbv' . implode('-', get_current_version_in_db()).'.custom.backup'
				];
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// regenerate_relations . Delete and create again table relations records
			$item = new stdClass();
				$item->id		= 'regenerate_relations';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'REGENERATE TABLE RELATIONS DATA';
				$item->info		= null;
				$item->body		= 'Delete and create again table relations records based on locators data of sections in current table';
				$item->run[]	= (object)[
					'fn'		=> 'init_form',
					'options'	=> (object)[
						'inputs' => [
							(object)[
								'type'		=> 'text',
								'name'		=> 'tables',
								'label'		=> 'Table name/s like "matrix,matrix_hierarchy" or "*" for all',
								'mandatory'	=> true
							]
						],
						'confirm_text' => label::get_label('sure') ?? 'Sure?'
					]
				];
				$item->trigger 	= (object)[
					'dd_api'	=> 'dd_utils_api',
					'action'	=> 'regenerate_relations',
					'options'	=> null
				];
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// update_ontology
			$item = new stdClass();
				$item->id		= 'update_ontology';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->info		= null;
				$item->label	= label::get_label('update_ontology');

				if (defined('ONTOLOGY_DB')) {
					$item->body = 'Disabled update Ontology. You are using config ONTOLOGY_DB !';
				}else{
					$item->body		= (defined('STRUCTURE_FROM_SERVER') && STRUCTURE_FROM_SERVER===true && !empty(STRUCTURE_SERVER_URL)) ?
						'Current: <b>' . RecordObj_dd::get_termino_by_tipo(DEDALO_ROOT_TIPO,'lg-spa') .'</b>'.
						'<hr>TLD: <tt>' . implode(', ', $DEDALO_PREFIX_TIPOS).'</tt>' :
						label::get_label('update_ontology')." is a disabled for ".DEDALO_ENTITY;
					$item->body 	.= "<hr>url: ".STRUCTURE_SERVER_URL;
					$item->body 	.= "<hr>code: ".STRUCTURE_SERVER_CODE;
					$confirm_text	 = '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! WARNING !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'.PHP_EOL;
					$confirm_text	.= '!!!!!!!!!!!!!! DELETING ACTUAL DATABASE !!!!!!!!!!!!!!!!'.PHP_EOL;
					$confirm_text	.= 'Are you sure to IMPORT and overwrite current ontology data with LOCAL FILE: ';
					$confirm_text	.= '"dedalo4_development_str.custom.backup" ?'.PHP_EOL;
					$item->run[]	= (object)[
						'fn' 	  => 'init_form',
						'options' => (object)[
							'inputs' => [
								(object)[
									'type'		=> 'text',
									'name'		=> 'dedalo_prefix_tipos',
									'label'		=> 'Dédalo prefix tipos to update',
									'value'		=> implode(',', $DEDALO_PREFIX_TIPOS),
									'mandatory'	=> true
								]
							],
							'confirm_text' => $confirm_text
						]
					];
					$item->trigger 	= (object)[
						'dd_api'	=> 'dd_utils_api',
						'action'	=> 'update_ontology',
						'options'	=> null
					];
				}
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// register_tools
			$item = new stdClass();
				$item->id		= 'register_tools';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= label::get_label('register_tools');
				$list = array_map(function($path){
					// ignore folders with name different from pattern 'tool_*'
					if (1!==preg_match('/tools\/tool_*/', $path, $output_array) || 1===preg_match('/tools\/tool_dev_template/', $path, $output_array)) {
						return null;
					}else{
						$tool_name = str_replace(DEDALO_TOOLS_PATH.'/', '', $path);
						// skip tool common
						if ($tool_name==='tool_common') return null;
						// check file register is ready
						$register_contents = file_get_contents($path.'/register.json');
						if($register_contents===false) {
							debug_log(__METHOD__." Invalid register.json file from tool ".to_string($tool_name), logger::ERROR);
							$tool_name .= ' <danger>(!) Invalid register.json file from tool</danger>';
						}else{
							// compare register.json file. WORKING HERE (!)
							$ar_tool_info = tool_common::get_client_registered_tools([$tool_name]);
							if(!isset($ar_tool_info[0])) {
								debug_log(__METHOD__." Tool '$tool_name' not found in client_registered_tools.".to_string(), logger::WARNING);
								$tool_name .= ' <danger>(!) Not registered tool</danger>';
							}
						}

						return $tool_name;
					}
				}, glob(DEDALO_TOOLS_PATH . '/*', GLOB_ONLYDIR));
				$item->body		= '<strong>Read tools folder and update the tools register in database</strong><br><br>';
				$item->body		.= implode('<br>', array_filter($list));
				$item->run[]	= (object)[
					'fn' 	  => 'init_form',
					'options' => (object)[
						'confirm_text' => label::get_label('sure') ?? 'Sure?'
					]
				];
				$item->trigger 	= (object)[
					'dd_api'	=> 'dd_utils_api',
					'action'	=> 'register_tools',
					'options'	=> null
				];
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// export_structure_to_json
			$item = new stdClass();
				$item->id		= 'export_structure_to_json';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= label::get_label('export_json_ontology');
				$item->info		= null;

				$file_name		= 'structure.json';
				$file_path		= 'Target: '.(defined('STRUCTURE_DOWNLOAD_JSON_FILE') ? STRUCTURE_DOWNLOAD_JSON_FILE : STRUCTURE_DOWNLOAD_DIR) . '/' . $file_name;
				// $file_url		= DEDALO_PROTOCOL . $_SERVER['HTTP_HOST'] . DEDALO_LIB_BASE_URL . '/backup/backups_structure/srt_download' . '/' . $file_name;
				$item->body		= $file_path;
				$confirm_text	= label::get_label('sure') ?? 'Sure?';
				$item->run[]	= (object)[
					'fn'		=> 'init_form',
					'options'	=> (object)[
						'inputs' => [
							(object)[
								'type'		=> 'text',
								'name'		=> 'dedalo_prefix_tipos',
								'label'		=> 'Dédalo prefix tipos to export',
								'value'		=> implode(',', $DEDALO_PREFIX_TIPOS),
								'mandatory'	=> true
							]
						],
						'confirm_text' => $confirm_text
					]
				];
				$item->trigger 	= (object)[
					'dd_api'	=> 'dd_utils_api',
					'action'	=> 'structure_to_json',
					'options'	=> null
				];
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// import_structure_from_json
			$item = new stdClass();
				$item->id		= 'import_structure_from_json';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= label::get_label('import_json_ontology') ?? 'Import JSON ontology';
				$item->info		= null;

				if (defined('ONTOLOGY_DB')) {
					$item->body	= 'Disabled update Ontology. You are using config ONTOLOGY_DB !';
				}else{
					$file_name		= 'structure.json';
					$file_path		= 'Source: '.(defined('STRUCTURE_DOWNLOAD_JSON_FILE') ? STRUCTURE_DOWNLOAD_JSON_FILE : STRUCTURE_DOWNLOAD_DIR) . '/' . $file_name;
					// $file_url	= DEDALO_PROTOCOL . $_SERVER['HTTP_HOST'] . DEDALO_LIB_BASE_URL . '/backup/backups_structure/srt_download' . '/' . $file_name;
					$item->body		= $file_path;
					$confirm_text	= label::get_label('sure') ?? 'Sure?';

					$item->run[]	= (object)[
						'fn'		=> 'init_form',
						'options'	=> (object)[
							'inputs' => [
								(object)[
									'type'		=> 'text',
									'name'		=> 'dedalo_prefix_tipos',
									'label'		=> 'Dédalo prefix tipos to import',
									'value'		=> implode(',', $DEDALO_PREFIX_TIPOS),
									'mandatory'	=> false
								]
							],
							'confirm_text' => $confirm_text
						]
					];
					$item->trigger 	= (object)[
						'dd_api'	=> 'dd_utils_api',
						'action'	=> 'import_structure_from_json',
						'options'	=> null
					];
				}
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// build_structure_css
			// $item = new stdClass();
			// 	$item->id		= 'build_structure_css';
			// 	$item->typo		= 'widget';
			// 	$item->tipo		= $this->tipo;
			// 	$item->parent	= $this->tipo;
			// 	$item->label	= label::get_label('build_structure_css');
			// 	$item->body		= 'Regenerate css from actual structure (Ontology)';
			// 	$item->run[]	= (object)[
			// 		'fn'		=> 'init_form',
			// 		'options'	=> (object)[
			// 			'confirm_text' => label::get_label('sure') ?? 'Sure?'
			// 		]
			// 	];
			// 	$item->trigger 	= (object)[
			// 		'dd_api'	=> 'dd_utils_api',
			// 		'action'	=> 'build_structure_css',
			// 		'options'	=> null
			// 	];
			// $widget = $this->widget_factory($item);
			// $ar_widgets[] = $widget;


		// build_install_version
			$item = new stdClass();
				$item->id		= 'build_install_version';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= label::get_label('build_install_version');
				$item->body		= 'Clone the current database '.DEDALO_DATABASE_CONN.' to "'.install::$db_install_name.'" and export it to file: /install/db/'.install::$db_install_name.'.pgsql.gz ';
				$item->run[]	= (object)[
					'fn'		=> 'init_form',
					'options'	=> (object)[
						'confirm_text' => label::get_label('sure') ?? 'Sure?'
					]
				];
				$item->trigger 	= (object)[
					'dd_api'	=> 'dd_utils_api',
					'action'	=> 'build_install_version',
					'options'	=> null
				];
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// update data version
			include_once DEDALO_CORE_PATH . '/base/update/class.update.php';
			$updates		= update::get_updates();
			$update_version	= update::get_update_version();
			if(empty($update_version)) {

				$item = new stdClass();
					$item->id		= 'update_data_version';
					$item->class	= 'success with_100';
					$item->typo		= 'widget';
					$item->tipo		= $this->tipo;
					$item->parent	= $this->tipo;
					$item->label	= label::get_label('update').' '.label::get_label('data');
					$item->info		= null;
					$item->body		= '<span style="color:green">Data format is updated: '.implode(".", get_current_version_in_db()).'</span>';
					$item->trigger	= (object)[
					];

				$widget = $this->widget_factory($item);
				$ar_widgets[] = $widget;

			}else{

				$current_dedalo_version	= implode(".", get_dedalo_version());
				$current_version_in_db	= implode(".", get_current_version_in_db());
				$update_version_plain	= implode('', $update_version);

				$item = new stdClass();
					$item->id		= 'update_data_version';
					$item->class	= 'danger with_100';
					$item->typo		= 'widget';
					$item->tipo		= $this->tipo;
					$item->parent	= $this->tipo;
					$item->label	= label::get_label('update').' '.label::get_label('data');
					// $item->info		= 'Click to update dedalo data version';
					$item->body		= '<span style="color:red">Current data version: '.$current_version_in_db . '</span> -----> '. implode('.', $update_version);
					// Actions list
						#dump($updates->$update_version_plain, '$updates->$update_version_plain ++ '.to_string());
						if (isset($updates->$update_version_plain)) {
							foreach ($updates->$update_version_plain as $key => $value) {

								if (is_object($value) || is_array($value)) {
									$i=0;
									foreach ($value as $vkey => $vvalue) {
										if($key==='alert_update') {

											$item->body .= '<div class="alert_update"><h2 class="vkey_value">'. $vvalue->command . '</h2></div>';
											// continue;
										}else{
											if($i===0) $item->body .= "<h6>$key</h6>";
											if(is_string($vvalue)) $vvalue = trim($vvalue);
											$item->body .= '<div class="command"><span class="vkey">'.($vkey+1).'</span><span class="vkey_value">'. print_r($vvalue, true) .'</span></div>';
											$i++;
										}
									}
								}
							}
						}
					$item->run[]	= (object)[
						'fn' 	  => 'init_form',
						'options' => (object)[
							'confirm_text' => label::get_label('sure') ?? 'Sure?'
						]
					];
					$item->trigger 	= (object)[
						'dd_api'	=> 'dd_utils_api',
						'action'	=> 'update_version',
						'options'	=> null
					];

				$widget = $this->widget_factory($item);
				$ar_widgets[] = $widget;
			}


		// update_code
			$item = new stdClass();
				$item->id		= 'update_code';
				$item->typo		= 'widget';
				$item->label	= label::get_label('update') .' '. label::get_label('code');
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// publication_api
			$item = new stdClass();
				$item->id		= 'publication_api';
				$item->typo		= 'widget';
				$item->label	= 'Publication server API';
				$item->value	= (object)[
					'dedalo_diffusion_domain'			=> DEDALO_DIFFUSION_DOMAIN,
					'dedalo_diffusion_resolve_levels'	=> DEDALO_DIFFUSION_RESOLVE_LEVELS,
					'api_web_user_code_multiple'		=> API_WEB_USER_CODE_MULTIPLE,
					'dedalo_diffusion_langs'			=> DEDALO_DIFFUSION_LANGS,
					'diffusion_map'						=> diffusion::get_diffusion_map(
						DEDALO_DIFFUSION_DOMAIN,
						true // bool connection_status
					),
				];
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// Dédalo API test environment
			$item = new stdClass();
				$item->id		= 'dedalo_api_test_environment';
				$item->class	= 'blue';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'DÉDALO API TEST ENVIRONMENT';
				$item->info		= null;
				$item->body		= '<textarea id="json_editor_api" class="hide"></textarea>';
				$item->body		.= '<label>API send RQO (Request Query Object) default dd_api is "dd_core_api"</label>';
				$item->body		.= '<label></label> <button id="submit_api" class="border light">OK</button>';
				$item->body		.= '<div id="json_editor_api_container" class="editor_json"></div>';
				$item->run[]	= (object)[
					'fn'		=> 'init_json_editor_api',
					'options'	=> (object)[
						'editor_id'	=> 'json_editor_api'
					]
				];
				$item->trigger 	= (object)[
					'dd_api'	=> 'get_input_value:dd_api_base',
					'action'	=> 'get_input_value:dd_api_fn',
					'options'	=> null
				];
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// search query object test environment
			$item = new stdClass();
				$item->id		= 'search_query_object_test_environment';
				$item->class	= 'blue';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'SEARCH QUERY OBJECT TEST ENVIRONMENT';
				$item->info		= null;
				$item->body		= '<textarea id="json_editor" class="hide"></textarea>';
				$item->body		.= '<div id="json_editor_container" class="editor_json"></div>';
				$item->run[]	= (object)[
					'fn'		=> 'init_json_editor',
					'options'	=> (object)['editor_id' => "json_editor"]
				];
				$item->trigger	= (object)[
					'dd_api'	=> 'dd_utils_api',
					'action'	=> 'convert_search_object_to_sql_query',
					'options'	=> null
				];
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// dedalo version
			$item = new stdClass();
				$item->id		= 'dedalo_version';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'DEDALO VERSION';
				$item->info		= null;
				$item->body		= 'Version '.DEDALO_VERSION;
				$item->body		.= '<pre>v '.DEDALO_VERSION .' | Build: '.DEDALO_BUILD.'</pre>';
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// database_info
			$info = pg_version(DBi::_getConnection());
			$info['host'] = to_string(DEDALO_HOSTNAME_CONN);
			$item = new stdClass();
				$item->id		= 'database_info';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'DATABASE INFO';
				$item->info		= null;
				$item->body		= 'Database '.$info['IntervalStyle']. " ". $info['server']. " ".DEDALO_HOSTNAME_CONN;
				$item->body		.= '<pre>'.json_encode($info, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).'</pre>';
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// php_user
			$info = (function(){
				try {
					if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
						$info = posix_getpwuid(posix_geteuid());
					}else{
						$name			= get_current_user();
						$current_user	= trim(shell_exec('whoami'));
						$info = [
							'name'			=> $name,
							'current_user'	=> $current_user
						];
					}
				} catch (Exception $e) {
					debug_log(__METHOD__." Exception:".$e->getMessage(), logger::ERROR);
				}
				return $info;
			})();
			$item = new stdClass();
				$item->id		= 'php_user';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'PHP USER';
				$item->info		= null;
				if (empty($info)) {
					$item->body	= 'PHP user unavailable';
				}else{
					$item->body	= 'PHP user '. $info['name'];
					$item->body	.= '<pre>'.json_encode($info, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).'</pre>';
				}
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// unit test (alpha)
			$item = new stdClass();
				$item->id		= 'unit_test';
				$item->typo		= 'widget';
				$item->label	= 'Unit test area';
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// sequences_status
			require(DEDALO_CORE_PATH.'/db/class.data_check.php');
			$data_check = new data_check();
			$response 	= $data_check->check_sequences();
			$item = new stdClass();
				$item->id		= 'sequences_status';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'DB SEQUENCES STATUS';
				$item->info		= null;
				$item->body		= $response->msg;
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// counters_status
			$response = counter::check_counters();
			$item = new stdClass();
				$item->id		= 'counters_status';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'DEDALO COUNTERS STATUS';
				$item->info		= null;
				$item->body		= $response->msg;
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		// php info
			$item = new stdClass();
				$item->id		= 'php_info';
				$item->typo		= 'widget';
				$item->tipo		= $this->tipo;
				$item->parent	= $this->tipo;
				$item->label	= 'PHP INFO';
				$item->info		= null;
				// $item->body	= '<iframe class="php_info_iframe" src="'.DEDALO_CORE_URL.'/area_development/php_info.php" onload="this.height=this.contentWindow.document.body.scrollHeight+50+\'px;\'"></iframe>';
				$item->body		= '<iframe class="php_info_iframe" src="'.DEDALO_CORE_URL.'/area_development/php_info.php"></iframe>';
			$widget = $this->widget_factory($item);
			$ar_widgets[] = $widget;


		return $ar_widgets;
	}//end get_ar_widgets



	/**
	* WIDGET_FACTORY
	* Unified way to create an area-development widget
	* @param object $item
	* @return object $widget
	*/
	public function widget_factory(object $item) : object {

		// widget
			$widget = new stdClass();
				$widget->id			= $item->id;
				$widget->class		= $item->class ?? null;
				$widget->typo		= 'widget';
				$widget->tipo		= $item->tipo ?? $this->tipo;
				$widget->parent		= $item->parent ?? $this->tipo;
				$widget->label		= $item->label ?? 'Undefined label for: '.$this->tipo;
				$widget->info		= $item->info ?? null;
				$widget->body		= $item->body  ?? null;
				$widget->run		= $item->run ?? [];
				$widget->trigger	= $item->trigger ?? null;
				$widget->value		= $item->value ?? null;


		return $widget;
	}//end widget_factory



	/**
	* GENERATE_RELATIONS_TABLE_DATA
	* Re-creates relationships between components in given tables (or all of then)
	* All relationships pointers are stored in table 'relations' for easy search. This function
	* deletes the data in that table and and rebuild it from component's locators
	* @param string $tables = '*'
	* @return object $response
	*/
	public static function generate_relations_table_data(string $tables='*') : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= ['Error. Request failed '.__METHOD__];


		// tables to propagate
			$ar_tables = (function($tables) {

				if ($tables==='*') {
					// all relation able tables (implies to truncate relations table)
					return area_development::$ar_tables_with_relations;
				}else{
					// is a list comma separated of tables like matrix,matrix_hierarchy
					$ar_tables = [];
					$items = explode(',', $tables);
					foreach ($items as $key => $table) {
						$ar_tables[] = trim($table);
					}
					return $ar_tables;
				}
			})($tables);

		// truncate relations table on *
			if ($tables==='*') {

				// truncate relations table data
				$strQuery	= 'TRUNCATE "relations";';
				$result		= JSON_RecordDataBoundObject::search_free($strQuery);
				if ($result===false) {
					$response->msg = $response->msg[0].' - Unable to truncate table relations!';
					return $response;
				}
				// restart table sequence
				$strQuery	= 'ALTER SEQUENCE relations_id_seq RESTART WITH 1;';
				$result		= JSON_RecordDataBoundObject::search_free($strQuery);
				if ($result===false) {
					$response->msg = $response->msg[0].' - Unable to alter SEQUENCE relations_id_seq!';
					return $response;
				}
			}


		foreach ($ar_tables as $key => $table) {

			$counter = 1;

			// last id in current table
				$strQuery	= "SELECT id FROM $table ORDER BY id DESC LIMIT 1;";
				$result		= JSON_RecordDataBoundObject::search_free($strQuery);
				if ($result===false) {
					$response->msg[] ='Table \''.$table.'\' not found!';
					$response->msg 	 = implode('<br>', $response->msg);
					return $response;
				}
				$rows = pg_fetch_assoc($result);
				if (!$rows) {
					continue;
				}
				$max = $rows['id'];
				$min = ($table==='matrix_users')
					? -1
					: 1;

			// iterate from 1 to last id
			for ($i=$min; $i<=$max; $i++) {

				$strQuery	= "SELECT section_id, section_tipo, datos FROM $table WHERE id = $i";
				$result		= JSON_RecordDataBoundObject::search_free($strQuery);
				if($result===false) {
					$msg = "Failed Search id $i. Data is not found.";
					debug_log(__METHOD__." ERROR: $msg ".to_string(), logger::ERROR);
					continue;
				}
				$n_rows = pg_num_rows($result);

				if ($n_rows<1) continue; // empty table case

				while($rows = pg_fetch_assoc($result)) {

					$section_id		= $rows['section_id'];
					$section_tipo	= $rows['section_tipo'];
					$datos			= json_decode($rows['datos']);

					if (!empty($datos) && isset($datos->relations)) {

						// component_dato
							$component_dato = [];
							foreach ($datos->relations as $key => $current_locator) {
								if (isset($current_locator->from_component_tipo)) {
									$component_dato[$current_locator->from_component_tipo][] = $current_locator;
								}else{
									debug_log(__METHOD__." Error on get from_component_tipo from locator (table:$table) (ignored) locator:".to_string($current_locator), logger::ERROR);
								}
							}

						// propagate component dato
							foreach ($component_dato as $from_component_tipo => $ar_locators) {

								$propagate_options = new stdClass();
									$propagate_options->ar_locators			= $ar_locators;
									$propagate_options->section_id			= $section_id;
									$propagate_options->section_tipo		= $section_tipo;
									$propagate_options->from_component_tipo	= $from_component_tipo;

								// propagate_component_dato_to_relations_table takes care of delete and insert new relations
								$propagate_response = search::propagate_component_dato_to_relations_table($propagate_options);
							}

					}else{
						debug_log(__METHOD__." ERROR: Empty datos from: $table $section_tipo $section_id ".to_string(), logger::ERROR);
					}
				}

				// debug
					if(SHOW_DEBUG===true) {
						# Show log msg every 100 id
						if ($counter===1) {
							debug_log(__METHOD__." Updated section data table $table $i".to_string(), logger::DEBUG);
						}
						$counter++;
						if ($counter>300) {
							$counter = 1;
						}
					}

			}//end for ($i=$min; $i<=$max; $i++)

			// msg add table
				$response->msg[] = " Updated table data table $table ";

			// debug
				// debug_log(__METHOD__." Updated table data table $table  ", logger::WARNING);

		}//end foreach ($ar_tables as $key => $table)

		// response
			$response->result	= true;
			$response->msg[0]	= 'OK. All data is propagated successfully'; // Override first message
			$response->msg		= $response->msg; // array


		return $response;
	}//end generate_relations_table_data



}//end class area_development
