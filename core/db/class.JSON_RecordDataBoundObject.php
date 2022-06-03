<?php
/**
* JSON_RecordDataBoundObject
*
*
*/
abstract class JSON_RecordDataBoundObject {

	protected $ID;		# id matrix of current table
	protected $datos;	# Field 'datos' in table matrix
	protected $strTableName;
	public $arRelationMap;
	protected $strPrimaryKeyName ;	# usually id
	protected $blForDeletion;
	protected $blIsLoaded;
	public $arModifiedRelations;

	public $use_cache = false;
	public $use_cache_manager = false;

	#protected static $ar_RecordDataObject_query;
	#protected static $ar_RecordDataObject_query_search_cache;
	protected $force_insert_on_save = false;

	abstract protected function defineTableName();
	abstract protected function defineRelationMap();
	abstract protected function definePrimaryKeyName();



	# __CONSTRUCT
	public function __construct(int $id=null) {

		$this->strTableName 		= $this->defineTableName();
		$this->strPrimaryKeyName	= $this->definePrimaryKeyName();
		$this->arRelationMap		= $this->defineRelationMap();

		$this->blIsLoaded			= false;
		if(isset($id)) {
			$this->ID 				= intval($id);
		}
		$this->arModifiedRelations	= array();


		return true;
	}//end __construct



	# GET_DATO : GET DATO UNIFICADO (JSON)
	public function get_dato() {
		if($this->blIsLoaded!==true) {
			$this->Load();
		}
		// if(!isset($this->dato)) return null;
		return $this->dato ?? null;
	}//end get_dato



	# SET_DATO : SET DATO UNIFICADO (JSON)
	public function set_dato($dato) {

		# Always set dato as modified
		$this->arModifiedRelations['dato'] = 1;

		$this->dato = $dato;
	}//end set_dato



	/**
	* LOAD
	* Load a record from database corresponding to actual section_id and section_tipo
	* @return bool
	*/
	public function Load() : bool {

		// debug info showed in footer
			if(SHOW_DEBUG===true) {
				$start_time=start_time();
			}

		// verify mandatory vars
			// section_tipo. Verify section_tipo
			if( empty($this->section_tipo) ) {
				// throw new Exception(__METHOD__.' Error: section_tipo is mandatory to load data', 1);
				trigger_error(__METHOD__.' Error: section_tipo is mandatory to load data');
				return false;
			}
			# section_tipo. Safe tipo test. Check valid section_tipo for safety
			if (!$section_tipo = safe_tipo($this->section_tipo)) {
				// throw new Exception(__METHOD__.' Error: Bad tipo '.htmlentities($this->section_tipo), 1);
				trigger_error(__METHOD__.' Error: Bad section_tipo: '.htmlentities($this->section_tipo));
				return false;
			}
			// section_id. Not load if $this->section_id is not set
			if( empty($this->section_id) ) {
				trigger_error(__METHOD__." Warning: Trying to load without section_id. section_tipo: ($this->section_tipo)");
				return false;
			}

		// Section_id is always int
			$section_id = intval($this->section_id);

		// sql query
			$strQuery = 'SELECT "datos" FROM "'. $this->strTableName .'" WHERE "section_id" = '. $section_id .' AND "section_tipo" = \''. $section_tipo .'\';';

		// cache
		// Si se le pasa un query que ya ha sido recibido, no se conecta con la db y se le devuelve el resultado del query idéntico ya calculado
		// que se guarda en un array estático
		static $ar_JSON_RecordDataObject_load_query_cache;

		# CACHE RUN-IN
		$use_cache = $this->use_cache;
		if($use_cache===true && isset($ar_JSON_RecordDataObject_load_query_cache[$strQuery])) {	// USING CACHE RUN-IN

			$dato = $ar_JSON_RecordDataObject_load_query_cache[$strQuery];

		}else{

			// DEFAULT. GET DB DATA

			// Synchronous query
				// $result = pg_query(DBi::_getConnection(), $strQuery);	#or die("Cannot execute query: $strQuery\n". pg_last_error());
				// # $result  = pg_query_params(DBi::_getConnection(), $strQuery, array( $this->section_id, $this->section_tipo ));

			// pg_query
				$result = pg_query(DBi::_getConnection(), $strQuery) ;//or die("Cannot (2) execute query: $strQuery <br>\n". pg_last_error());
				if ($result===false) {
					trigger_error("Error Processing Request Load");
					if(SHOW_DEBUG===true) {
						throw new Exception("Error Processing Request Load: (".DEDALO_DATABASE_CONN.") ".pg_last_error()." <hr>$strQuery", 1);
					}
				}

			// With prepared statement
				// $stmtname  = ''; //md5($strQuery); //'search_free_stmt';
				// $statement = pg_prepare(DBi::_getConnection(), $stmtname, $strQuery);
				// if ($statement===false) {
				// 	trigger_error(__METHOD__.' Error: Error when pg_prepare statemnt for strQuery');
				// 	if(SHOW_DEBUG===true) {
				// 		debug_log(__METHOD__." Error when pg_prepare statemnt for strQuery: ".to_string($strQuery), logger::ERROR);
				// 	}
				// 	return false;
				// }
				// $result = pg_execute(DBi::_getConnection(), $stmtname, array());
				// if ($result===false) {
				// 	if(SHOW_DEBUG===true) {
				// 		// throw new Exception("Error Processing Request Load: ".pg_last_error()." <hr>$strQuery", 1);
				// 		trigger_error("Error Processing Request Load: ".pg_last_error()." <hr>$strQuery");
				// 	}else{
				// 		trigger_error("Error Processing Request Load");
				// 	}
				// 	return false;
				// }

			// arRow. Note that pg_fetch_assoc could return 'false' when query return empty value. This is not an error.
				$arRow = pg_fetch_assoc($result);
				// if($arRow===false)	{
				// 	if(SHOW_DEBUG===true) {
				// 		#dump($this,"WARNING: No result on Load arRow : strQuery:".$strQuery);
				// 		#throw new Exception("Error Processing Request (".DEDALO_DATABASE_CONN.") strQuery:$strQuery", 1);
				// 		dump($result, ' result ++ '.to_string());
				// 		dump($arRow, ' arRow ++ '.to_string());
				// 	}
				// 	trigger_error('Warning Processing Request. pg_fetch_assoc arRow is false. $strQuery:' .PHP_EOL. $strQuery);
				// 	// return false;
				// }

			// dato
				$dato = isset($arRow['datos'])
					? json_handler::decode($arRow['datos'])
					: null;

			// cache results. Note: Avoid use cache in long imports (memory overloads)
				if($use_cache===true) {
					// store value
					$ar_JSON_RecordDataObject_load_query_cache[$strQuery] = $dato;
				}

			// debug
				if(SHOW_DEBUG===true) {
					$total_time_ms = exec_time_unit($start_time,'ms');
					#$_SESSION['debug_content'][__METHOD__][] = "". str_replace("\n",'',$strQuery) ." [$total_time_ms ms]";
					$n_records = is_countable($dato) ? sizeof($dato) : 0;
					if($total_time_ms>SLOW_QUERY_MS) error_log($total_time_ms." ms - LOAD_SLOW_QUERY: $strQuery - records:".$n_records);
				}
		}

		# Set returned dato (decoded) to object
		$this->dato = $dato;

		# Fix loaded state
		$this->blIsLoaded = true;

		// debug
			if(SHOW_DEBUG===true) {
				// $totaltime = exec_time_unit($start_time,'ms');
				// static $totaltime_static2;
				// $totaltime_static2 = $totaltime_static2 + $totaltime;
				// debug_log(__METHOD__." Total: $totaltime ms - $strQuery + sum ms: $totaltime_static2 ".to_string(), logger::DEBUG);
			}


		return true;
	}//end load



	/**
	* SAVE
	* Updates current record
	* @param object $save_options
	* @return int|null
	*/
	public function Save( object $save_options=null ) : ?int {

		# SECTION_TIPO. Check valid section_tipo for safety
		if (!$section_tipo = safe_tipo($this->section_tipo)) {
			trigger_error("Stop Save Bad tipo ".htmlentities($this->section_tipo));
			// return false;
			return null;
		}

		// datos : JSON ENCODE ALWAYS !!!
			$datos = json_handler::encode($this->datos);

		// prevent null encoded errors
			$datos = str_replace(['\\u0000','\u0000'], ' ', $datos);

		// section_id. Section_id is always int
			$section_id = intval($this->section_id);

		#
		# SAVE UPDATE : Record already exists
		if( $save_options->new_record!==true && $section_id>0 && $this->force_insert_on_save!==true ) {

			# Si no se ha modificado nada, ignoramos la orden de salvar
			// if(!isset($this->arRelationMap['datos'])) {
			// 	debug_log(__METHOD__." Ignored save. Nothing was changed in 'datos' ! ".to_string(), logger::DEBUG);
			// 	return false;
			// }

			$strQuery	= 'UPDATE '.$this->strTableName.' SET datos = $1 WHERE section_id = $2 AND section_tipo = $3 RETURNING id';
			$params		= [$datos, $section_id, $section_tipo];
			$result		= pg_query_params(DBi::_getConnection(), $strQuery, $params);
			if($result===false) {
				if(SHOW_DEBUG===true) {
					dump($datos,"strQuery:$strQuery, section_id:$section_id, section_tipo:$section_tipo");
					throw new Exception("Error Processing Save Update Request ". pg_last_error(), 1);
				}else{
					trigger_error("Error: sorry an error occurred on UPDATE record '$this->ID'. Data is not saved");
				}
				// return false; // "Error: sorry an error occurred on UPDATE record '$this->ID'. Data is not saved";
				return null;
			}

			// debug
				if(SHOW_DEBUG===true) {
					$debug_strQuery = preg_replace_callback(
						'/\$(\d+)\b/',
						function($match) use ($params) {
							$key=($match[1]-1); return ( is_null($params[$key])?'NULL':pg_escape_literal(DBi::_getConnection(), $params[$key]) );
						},
						$strQuery
					);
					dump($result, ' Save result ++ '.to_string($debug_strQuery));
				}

			// test 3-5-2022
				$id = pg_fetch_result($result, 0, 'id');
				if ($id===false) {
					if(SHOW_DEBUG===true) {
						dump($strQuery,"strQuery");
						throw new Exception("Error Processing Request: ".pg_last_error(), 1);
					}else{
						trigger_error("Error Processing Request. ".pg_last_error());
					}
					// return false;
					return null;
				}
				# Fix new received id (id matrix)
				if (!isset($this->ID)) {
					$this->ID = $id;
				}elseif($this->ID!=$id) {
					throw new Exception('Error. ID received after update is different from current ID. this ID: '.$this->ID.' received id: '.$id , 1);
				}

				# Return always current existing or updated id
				return (int)$id;
		#
		# SAVE INSERT : Record not exists and create one
		}else{

			switch($this->strTableName) {

				# MATRIX_ACTIVITY INSERT (async pg_send_query)
				case 'matrix_activity':
					$strQuery = 'INSERT INTO "'.$this->strTableName.'" (datos) VALUES (\''.$datos.'\') RETURNING section_id';
					# PG_SEND_QUERY is async query
					pg_send_query(DBi::_getConnection(), $strQuery);
					$result = pg_get_result(DBi::_getConnection()); # RESULT (pg_get_result for pg_send_query is needed)

					// Return sequence auto created section_id
					$section_id = pg_fetch_result($result,0,'section_id');
					if ($section_id===false) {
						if(SHOW_DEBUG===true) {
							dump(null, "strQuery : ".PHP_EOL.to_string($strQuery));
							throw new Exception("Error Processing Request: ".pg_last_error(), 1);
						}else{
							trigger_error('Error Processing Request strQuery:' . PHP_EOL . $strQuery);
						}
						// return false;
						return null;
					}
					return (int)$section_id;
					break;

				# DEFAULT INSERT (sync pg_query_params)
				default:

					if(empty($section_id) || empty($section_tipo)) {
						#throw new Exception("Error Processing Request. section_id and section_tipo", 1);
						error_log("Error Processing Request. section_id:$section_id and section_tipo:$section_tipo, table:$this->strTableName - $this->ID");
					}

					# Insert record datos and receive a new id
					$strQuery = 'INSERT INTO "'.$this->strTableName.'" (section_id, section_tipo, datos) VALUES ($1, $2, $3) RETURNING id';

					$result   = pg_query_params(DBi::_getConnection(), $strQuery, array( $section_id, $section_tipo, $datos ));
					if($result===false) {
						if(SHOW_DEBUG===true) {
							dump($strQuery,"strQuery section_id:$section_id, section_tipo:$section_tipo, datos:".to_string($datos));
							throw new Exception("Error Processing Save Insert Request (2) error: ". pg_last_error(), 1);;
						}else{
							trigger_error("Error: sorry an error ocurred on INSERT record. Data is not saved. ".pg_last_error());
						}
						// return false;
						return null;
					}

					$id = pg_fetch_result($result,0,'id');
					if ($id===false) {
						if(SHOW_DEBUG===true) {
							dump($strQuery,"strQuery");
							throw new Exception("Error Processing Request: ".pg_last_error(), 1);
						}else{
							trigger_error("Error Processing Request. ".pg_last_error());
						}
						// return false;
						return null;
					}
					# Fix new received id (id matrix)
					$this->ID = $id;

					# Return always current existing or created id
					return (int)$this->ID;
					break;
			}//end switch($this->strTableName)

		}//end if( $save_options->new_record!==true && $section_id>0 && $this->force_insert_on_save!==true )

		// return false;
		return null;
	}//end Save



	/**
	* MARKFORDELETION
	* Delete record on destruct
	*/
	public function MarkForDeletion() {
		$this->blForDeletion = true;
	}
	# DELETE. ALIAS OF MarkForDeletion
	public function Delete() {
		$this->MarkForDeletion();
	}



	/**
	* GET_AR_EDITABLE_FIELDS
	*/
	public function get_ar_editable_fields() {

		static $ar_editable_fields;

		if(isset($ar_editable_fields)) {
			return($ar_editable_fields);
		}

		if(is_array($this->arRelationMap)) {

			foreach($this->arRelationMap as $field_name => $property_name) {

				if($property_name!=='ID') $ar_editable_fields[] = $field_name ;
			}
			return $ar_editable_fields ;
		}

		return false;
	}//end get_ar_editable_fields



	/**
	* SEARCH_FREE
	* Perform a simple free sql query and exec in db return result resource
	* @param string $strQuery Full SQL query like "SELECT id FROM table WHERE id>0"
	* @param bool $wait to set syc/async exec. Default us true
	* @return resource (PHP<8) OR object (PHP>=8) | false $result
	* 	Database resource/object from exec query
	*/
	public static function search_free(string $strQuery, bool $wait=true) {

		// debug
			if(SHOW_DEBUG===true) {
				$start_time = start_time();
				if (isset(debug_backtrace()[1]['function'])) {
					$strQuery = '-- search_free : '.debug_backtrace()[1]['function']."\n" . $strQuery;
				}
			}

		// $result = pg_query(DBi::_getConnection(), $strQuery);

		// exec With prepared statement
			$stmtname  = ''; //md5($strQuery); //'search_free_stmt';
			$statement = pg_prepare(DBi::_getConnection(), $stmtname, $strQuery);
			if ($statement===false) {
				debug_log(__METHOD__." Error when pg_prepare statement for strQuery: ".to_string($strQuery), logger::ERROR);
				return false;
			}

			if ($wait===false) {
				// async case
				pg_send_execute(DBi::_getConnection(), $stmtname, array());
				$result = pg_get_result(DBi::_getConnection());
			}else{
				// default sync case
				$result = pg_execute(DBi::_getConnection(), $stmtname, array());
			}

		// check result
			if($result===false) {
				if(SHOW_DEBUG===true) {
					dump(pg_last_error()," error on strQuery: ".to_string( PHP_EOL.$strQuery.PHP_EOL ));
				}
				trigger_error("Error Processing SEARCH_FREE Request. pg_last_error: ". pg_last_error());
				return false;
			}

		// Reference extract records
			// while ($rows = pg_fetch_assoc($result)) {
			// 	$ar_records[] = $rows['id'];
			// 		dump($ar_records,"ar_records");
			// }

		// debug
			if(SHOW_DEBUG===true) {
				$total_time_ms = exec_time_unit($start_time,'ms');
				#$_SESSION['debug_content'][__METHOD__][] = " ". str_replace("\n",'',$strQuery) ." [$total_time_ms ms]";
				if($total_time_ms>SLOW_QUERY_MS) {
					#dump(debug_backtrace()[2],' DEBUG_BACKTRACE  ') ;
					error_log($total_time_ms."ms. SEARCH_SLOW_QUERY: $strQuery ");
				}
				#global$TIMER;$TIMER[__METHOD__.'_'.$strQuery.'_TOTAL:'.count($ar_records).'_'.start_time()]=start_time();
				#error_log("search_free - Loaded: $total_time_ms ms ");
			}


		return $result; # resource
	}//end search_free



	/**
	* SEARCH
	* Generic search engine. You need array key-value with field,value
	* Sample: $arguments['parent'] = 14 ...
	* @param array $ar_arguments
	* @param string $matrix_table
	* @return array $ar_records
	*/
	public function search(array $ar_arguments=null, string $matrix_table=null) : array {

		# DEBUG INFO SHOWED IN FOOTER
		if(SHOW_DEBUG===true) $start_time = start_time();

		$ar_records = array();

		# TABLE . Optionally change table temporally for search
		if (!empty($matrix_table)) {
			$this->strTableName = $matrix_table;
		}

		$strPrimaryKeyName	= $this->strPrimaryKeyName;
		$strQuery			= '';
		$strQuery_limit		= '';

		if(is_array($ar_arguments)) foreach($ar_arguments as $key => $value) {

			switch(true) {	#"AND dato LIKE  '%\"{$area_tipo}\"%' ";

				# SI $key ES 'strPrimaryKeyName', LO USAREMOS COMO strPrimaryKeyName A BUSCAR
				case ($key==='strPrimaryKeyName'):
					// # If is json selection, strPrimaryKeyName is literal as 'selection'
					// if ( strpos($value, '->') ) {
					// 	$strPrimaryKeyName = $value;
					// }
					// # Else (default) is a column key and we use '"column_name"'
					// else{
					// 	$strPrimaryKeyName = '"'.$value.'"';
					// }
					$strPrimaryKeyName = (strpos($value, '->')!==false)
						? $value // If is json selection, strPrimaryKeyName is literal as 'selection'
						: '"'.$value.'"'; // Else (default) is a column key and we use '"column_name"'
					break;

				# LIMIT
				case ($key==='sql_limit'):
					$strQuery_limit = 'LIMIT '.(int)$value.''; // (!) no space at end
					break;

				# NOT
				case (strpos($key,':!=')!==false):
					$campo = substr($key, 0, strpos($key,':!='));
					$strQuery .= 'AND "'.$campo.'" != \''.$value.'\' ';
					break;

				# SI $key ES 'sql_code', INTERPRETAMOS $value LITERALMENTE, COMO SQL
				case ($key==='sql_code'):
					$strQuery .= $value.' ';
					break;

				# OR (formato lang:or= array('DEDALO_DATA_LANG',DEDALO_DATA_NOLAN))
				case (strpos($key,':or')!==false):
					$campo = substr($key, 0, strpos($key,':or'));
					$strQuery_temp ='';
					foreach ($value as $value_string) {
						// $strQuery_temp .= "$campo = '$value_string' OR ";
						$strQuery_temp .= '"'.$campo.'" = \''.$value_string.'\' OR ';
					}
					$strQuery .= 'AND ('. substr($strQuery_temp, 0,-4) .') ';
					break;

				# DEFAULT . CASO GENERAL: USAREMOS EL KEY COMO CAMPO Y EL VALUE COMO VALOR TIPO 'campo = valor'
				default :
					if(is_int($value) && strpos($key, 'datos')===false) {	// changed  from is_numeric to is_int (06-06-2016)
						// $strQuery .= 'AND "'.$key.'"='.$value.' ';
						$strQuery .= 'AND '.$key.'='.$value.' ';
					}else{
						if(SHOW_DEBUG===true) {
							if( !is_string($value) ) {
								dump(debug_backtrace(), 'debug_backtrace() ++ '.to_string());
								dump($value, ' value ++ '.to_string());
							}
						}
						$value = pg_escape_string(DBi::_getConnection(), $value);
						// $strQuery .= 'AND "'.$key.'"=\''.$value.'\' ';
						$strQuery .= 'AND '.$key.'=\''.$value.'\' ';
					}
					break;
			}#end switch(true)
		}//end foreach($ar_arguments as $key => $value)

		# Seguridad
		#if(strpos(strtolower($strQuery), 'update')!=='false' || strpos(strtolower($strQuery), 'delete')!=='false') die("SQL Security Error ". strtolower($strQuery) );

		// Verify query format
			if(strpos($strQuery, 'AND')===0) {
				$strQuery = substr($strQuery, 4);
			}else if( strpos($strQuery, ' AND')===0 ) {
				$strQuery = substr($strQuery, 5);
			}

		if(SHOW_DEBUG===true) {
			$strQuery = "\n-- search : ".debug_backtrace()[1]['function']."\n".$strQuery;
		}

		$strQuery = 'SELECT '.$strPrimaryKeyName.' AS key FROM "'.$this->strTableName.'" WHERE '. $strQuery .' '. $strQuery_limit;
			// error_log('---- $strQuery:'.$strQuery);
			#debug_log(__METHOD__." $strQuery ".to_string($strQuery), logger::DEBUG);

		# CACHE : Static var
		# SI SE LE PASA UN QUERY QUE YA HA SIDO RECIBIDO, NO SE CONECTARÁ CON LA DB Y SE LE DEVUELVE EL RESULTADO DEL QUERY IDÉNTICO YA CALCULADO
		# QUE SE GUARDA EN UN ARRAY ESTÁTICO
		static $ar_RecordDataObject_query_search_cache;

		# CACHE_MANAGER : Using external cache manager (like redis)
		// $use_cache = false; # Experimental (cache true for search)
		$use_cache = $this->use_cache; # Default use class value
		if ($use_cache===true && isset($ar_RecordDataObject_query_search_cache[$strQuery])) {

			# DATA IS IN CACHE . Return value form memory

			$ar_records	= $ar_RecordDataObject_query_search_cache[$strQuery];

			# DEBUG
			if(SHOW_DEBUG===true) {
				error_log(__METHOD__." --> Used cache run-in for query: $strQuery");
			}

		}else{

			# DATA IS NOT IN CACHE . Searching real data in DB

			$result = pg_query(DBi::_getConnection(), $strQuery);// or die("Cannot execute query: $strQuery\n". pg_last_error());
			if ($result===false) {
				if(SHOW_DEBUG===true) {
					dump(pg_last_error(), ' pg_last_error() ++ '.to_string($strQuery));
					throw new Exception("Error Processing Request . ".pg_last_error(), 1);
				}else{
					trigger_error("Error on DB query");
				}
				return [];
			}
			while ($rows = pg_fetch_assoc($result)) {
				$ar_records[] = $rows['key'];
			}

			# CACHE
			# SI SE LE PASA UN QUERY QUE YA HA SIDO RECIBIDO, NO SE CONECTA CON LA DB Y SE LE DEVUELVE EL RESULTADO DEL QUERY IDÉNTICO YA CALCULADO
			# QUE SE GUARDA EN UN ARRAY ESTÁTICO
			# IMPORTANT Only store in cache positive results, NOT EMPTY RESULTS
			# (Store empty results is problematic for example with component_common::get_id_by_tipo_parent($tipo, $parent, $lang) when matrix relation record is created and more than 1 call is made,
			# the next results are 0 and duplicate records are built in matrix)
			$n_records = is_countable($ar_records) ? sizeof($ar_records) : 0;
			if($use_cache===true && $n_records>0) {
				# CACHE RUN-IN
				$ar_RecordDataObject_query_search_cache[$strQuery] = $ar_records;
			}

			// debug
				if(SHOW_DEBUG===true) {
					$total_time_ms = exec_time_unit($start_time,'ms');
					#$_SESSION['debug_content'][__METHOD__][] = " ". str_replace("\n",'',$strQuery) ." count:".count($ar_records)." [$total_time_ms ms]";
					if($total_time_ms>SLOW_QUERY_MS) error_log($total_time_ms."ms. SEARCH_SLOW_QUERY: $strQuery - records:".$n_records);
					#global$TIMER;$TIMER[__METHOD__.'_'.$strQuery.'_TOTAL:'.count($ar_records).'_'.start_time()]=start_time();
				}
		}


		return $ar_records;
	}//end search



	/**
	* BUILD_PG_FILTER
	* (!) Not used
	*/
		// public static function build_pg_filter(string $modo, string $datos, string $tipo, string $lang, $value) : string {

		// 	if (empty($datos)) {
		// 		$datos = 'datos';
		// 	}

		// 	switch ($modo) {
		// 		case 'gin':
		// 			# ref: datos @>'{"components":{"rsc24":{"dato":{"lg-nolan":"114"}}}}'
		// 			$value = pg_escape_string(DBi::_getConnection(), stripslashes($value));
		// 			#$value = pg_escape_literal(stripslashes($value));
		// 			return "$datos @>'{\"components\":{\"$tipo\":{\"dato\":{\"$lang\":\"$value\"}}}}'::jsonb ";
		// 			break;

		// 		case 'btree':
		// 			$type = gettype($value);
		// 			if(SHOW_DEBUG===true) {
		// 				#dump($type," type for ".print_r($value,true));
		// 			}
		// 			switch ($type ) {
		// 				case 'array':
		// 					foreach ($value as $key => $ar_value) {
		// 						if(SHOW_DEBUG===true) {
		// 							#dump($value," value"); dump($key," key"); dump($ar_value," ar_value");
		// 						}
		// 						$ar_id_matrix[] = key($ar_value);
		// 					}
		// 					$ar_values_string='';
		// 					$end_value = end($ar_id_matrix);
		// 					foreach ($ar_id_matrix as $id_matrix){
		// 						$ar_values_string .= "'{$id_matrix}'";
		// 						if ($id_matrix !== $end_value) $ar_values_string .= ',';
		// 					}
		// 					return "$datos #>'{components,$tipo,dato,$lang}' ?| array[$ar_values_string] ";
		// 					break;

		// 				case 'object':
		// 					#$key = key($value);
		// 					#$ar_values_string = "'$key'";
		// 					$ar_values_string='';
		// 					$keys = array_keys((array)$value);
		// 					$end_value = end($keys);
		// 					foreach ($keys as $current_value) {
		// 						$ar_values_string .= "'$current_value'";
		// 						if ($current_value !== $end_value) {
		// 							$ar_values_string .=',';
		// 						}
		// 					}
		// 					#dump($ar_values_string, ' ar_values_string');
		// 					return "$datos #>'{components,$tipo,dato,$lang}' ?| array[$ar_values_string] ";
		// 					#$value = json_encode($value);
		// 					#return  "$datos #>'{components,$tipo,dato,$lang}' @> '[$value]'::jsonb ";
		// 					break;

		// 				default:
		// 					# ref: datos #>> '{components,rsc24,dato,lg-nolan}' = '114'
		// 					return "$datos #>>'{components,$tipo,dato,$lang}'='$value'";
		// 					break;
		// 			}
		// 			break;
		// 	}
		// }//end build_pg_filter



	/**
	* BUILD_PG_SELECT
	* (!) Not used
	*/
		// public static function build_pg_select($modo, $datos='datos', $tipo=null, $key='dato', $lang=DEDALO_DATA_LANG) {

		// 	if (empty($tipo)) {
		// 		throw new Exception("Error Processing Request. tipo is mandatory !", 1);
		// 	}

		// 	switch ($modo) {
		// 		case 'gin':
		// 			throw new Exception("Error Processing Request. Sorry not implemented...", 1);
		// 			break;
		// 		case 'btree':
		// 			# ref: datos#>>'{components, $terminoID_valor, dato, $lang}' as $terminoID_valor
		// 			return "$datos #>>'{components,$tipo,$key,$lang}' AS $tipo";
		// 			break;
		// 	}
		// }//end build_pg_select



	/**
	* __DESTRUCT
	*/
	public function __destruct() {

		#if( isset($this->ID) ) {
		if( isset($this->section_id) && isset($this->section_tipo)) {

			if($this->blForDeletion === true) {

				# Section_id is always int
				$section_id = intval($this->section_id);

				# Check valid section_tipo for safety
				# Safe tipo test
				if (!$section_tipo = safe_tipo($this->section_tipo)) {
					die("Bad tipo ".htmlentities($this->section_tipo));
				}

				$strQuery 	= 'DELETE FROM "'. $this->strTableName .'" WHERE "section_id" = $1 AND "section_tipo" = $2';
				$result 	= pg_query_params( DBi::_getConnection(), $strQuery, array($section_id, $section_tipo) );

				if($result===false) {
					echo "Error: sorry an error ocurred on DELETE record (section_id:$section_id, section_tipo:$section_tipo). Data is not deleted";
					if(SHOW_DEBUG===true) {
						dump($strQuery,"Delete strQuery");
						throw new Exception("Error Processing Request (result==false): an error ocurred on DELETE record (section_id:$section_id, section_tipo:$section_tipo). Data is not deleted", 1);
					}
				}
			}
		}
		#pg_get_result(DBi::_getConnection()) ;
		# close connection
		#DBi::_getConnection()->close();
	}//end __destruct



	# ACCESSORS CALL
	final public function __call(string $strFunction, array $arArguments) {

		$strMethodType 		= substr($strFunction, 0, 4); # like set or get_
		$strMethodMember 	= substr($strFunction, 4);
		switch($strMethodType) {
			case 'set_' : return($this->SetAccessor($strMethodMember, $arArguments[0]));	break;
			case 'get_' : return($this->GetAccessor($strMethodMember));						break;
		}
		return false;
	}
	# ACCESSORS SET
	private function SetAccessor(string $strMember, $strNewValue) {

		if(property_exists($this, $strMember)) {

			$this->$strMember = $strNewValue;

			$this->arModifiedRelations[$strMember] = 1;

			return true;
		}else{
			return false;
		}
	}
	# ACCESSORS GET
	private function GetAccessor(string $strMember) {

		if($this->blIsLoaded!==true) {
			$this->Load();
		}

		return property_exists($this, $strMember)
			? $this->$strMember
			: false;
	}//end GetAccessor



}//end class JSON_RecordDataBoundObject
