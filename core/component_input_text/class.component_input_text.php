<?php
/*
* CLASS COMPONENT_INPUT_TEXT
* Manage specific component input text logic
* Common components properties and method are inherited of component_common class that are inherited from common class
*/
class component_input_text extends component_common {



	/**
	* GET DATO
	*/
	public function get_dato() : array {

		$dato = parent::get_dato();

		return (array)$dato;
	}//end get_dato



	/**
	* SET_DATO
	* @param array|null $dato
	* 	Dato now is multiple. Because this, expected type is array
	*	but in some cases can be an array JSON encoded or some rare times a plain string
	* @return bool
	*/
	public function set_dato($dato) : bool {


		// remove data when data is null
		if(is_null($dato)){
			return parent::set_dato(null);
		}


		// string case. (Tool Time machine case, dato is string)
			if (is_string($dato)) {

				// check the dato for determinate the original format and if the $dato is correct.
				$dato_trim				= trim($dato);
				$dato_first_character	= substr($dato_trim, 0, 1);
				$dato_last_character	= substr($dato_trim, -1);

				if ($dato_first_character==='[' && $dato_last_character===']') {
					# dato is JSON encoded
					$dato = json_handler::decode($dato_trim);
				}else{
					# dato is string plain value
					$dato = array($dato);
					#debug_log(__METHOD__." Warning. [$this->tipo,$this->parent] Dato received is a plain string. Support for this type is deprecated. Use always an array to set dato. ".to_string($dato), logger::DEBUG);
				}
			}

		// debug
			if(SHOW_DEBUG===true) {
				if (!is_array($dato)) {
					debug_log(__METHOD__." Warning. [$this->tipo,$this->parent]. Received dato is NOT array. Type is '".gettype($dato)."' and dato: '".to_string($dato)."' will be converted to array", logger::DEBUG);
				}
				#debug_log(__METHOD__." dato [$this->tipo,$this->parent] Type is ".gettype($dato)." -> ".to_string($dato), logger::ERROR);
			}

		// safe dato
			$safe_dato = array();
			foreach ((array)$dato as $value) {
				if($this->is_empty($value)){
					$safe_dato[] = null;
				}else{
					$safe_dato[] = (!is_string($value))
						? to_string($value)
						: $value;
					}
			}
			$dato = $safe_dato;


		return parent::set_dato( (array)$dato );
	}//end set_dato



	/**
	* IS_EMPTY
	* @param string $value
	* @return bool
	*/
	public function is_empty($value) {

		if(is_null($value)){
			return true;
		}

		$value = trim($value);

		if(empty($value)){
			return true;
		}

		return false;

	}//end is_empty



	/**
	* GET_GRID_VALUE
	* Get the value of the components. By default will be get_dato().
	* overwrite in every different specific component
	* Some the text components can set the value with the dato directly
	* the relation components need to process the locator to resolve the value
	* @param object|null $ddo = null
	*
	* @return dd_grid_cell_object $value
	*/
	public function get_grid_value( object $ddo=null) : dd_grid_cell_object {

		// column_obj. Set the separator if the ddo has a specific separator, it will be used instead the component default separator
			if(isset($this->column_obj)){
				$column_obj = $this->column_obj;
			}else{
				$column_obj = new stdClass();
					$column_obj->id = $this->section_tipo.'_'.$this->tipo;
			}

		// dato
			$dato			= $this->get_dato();
			$fallback_value	= component_common::extract_component_dato_fallback(
				$this, // component instance this
				$this->get_lang(), // string lang
				DEDALO_DATA_LANG_DEFAULT // string main_lang
			);

		// records_separator
				$properties			= $this->get_properties();
				$records_separator	= isset($ddo->records_separator)
				? $ddo->records_separator
				: (isset($properties->records_separator)
					? $properties->records_separator
					: ' | ');

		// class_list
			$class_list = $ddo->class_list ?? null;

		// label
			$label = $this->get_label();

		// value
			$value = new dd_grid_cell_object();
				$value->set_type('column');
				$value->set_label($label);
				$value->set_ar_columns_obj([$column_obj]);
				$value->set_cell_type('text');
				if(isset($class_list)){
					$value->set_class_list($class_list);
				}
				$value->set_records_separator($records_separator);
				$value->set_value($dato);
				$value->set_fallback_value($fallback_value);


		return $value;
	}//end get_grid_value



	/**
	* GET_VALOR
	* Return array dato as comma separated elements string by default
	* If index var is received, return dato element corresponding to this index if exists
	* @return string $valor
	*/
	public function get_valor( $lang=DEDALO_DATA_LANG, $index='all' ) : string {

		$valor ='';

		$dato = $this->get_dato();
		if(empty($dato)) {
			return (string)$valor;
		}

		if ($index==='all') {
			$ar = array();
			foreach ($dato as $value) {

				if (is_string($value)) {
					$value = trim($value);
				}

				if (!empty($value)) {
					$ar[] = $value;
				}
			}
			if (count($ar)>0) {
				// $valor = implode(',',$ar);
				$valor = implode(' | ', $ar);
			}
		}else{
			$index = (int)$index;
			$valor = isset($dato[$index]) ? $dato[$index] : null;
		}


		return (string)$valor;
	}//end get_valor



	/**
	* LOAD TOOLS (DEPRECATED)
	*/
		// public function load_tools( bool $check_lang_tools=true ) : array {

		// 	$properties = $this->get_properties();
		// 	if (isset($properties->with_lang_versions) && $properties->with_lang_versions===true) {
		// 		# Allow tool lang on non translatable components
		// 		$check_lang_tools = false;
		// 	}

		// 	return parent::load_tools( $check_lang_tools );
		// }//end load_tools



	/**
	* GET_VALOR_EXPORT
	* Return component value sent to export data
	* @return string $valor
	*/
	public function get_valor_export($valor=null, $lang=DEDALO_DATA_LANG, $quotes=null, $add_id=null) {

		if (empty($valor)) {

			$valor = $this->get_valor($lang);

		}else{

			# Add value of current lang to nolan data
			$properties = $this->get_properties();
			if (isset($properties->with_lang_versions) && $properties->with_lang_versions===true) {

				$component = $this;
				$component->set_lang($lang);
				$add_value = $component->get_valor($lang);
				if (!empty($add_value) && $add_value!==$valor) {
					$valor .= ' ('.$add_value.')';
				}
			}
		}

		if (empty($valor)) {
			$valor = component_common::extract_component_value_fallback($this, $lang=DEDALO_DATA_LANG, $mark=true, $main_lang=DEDALO_DATA_LANG_DEFAULT);
		}

		return to_string($valor);
	}//end get_valor_export



	/**
	* UPDATE_DATO_VERSION
	* @param object $request_options
	* @return object $response
	*	$response->result = 0; // the component don't have the function "update_dato_version"
	*	$response->result = 1; // the component do the update"
	*	$response->result = 2; // the component try the update but the dato don't need change"
	*/
	public static function update_dato_version(object $request_options) : object {

		$options = new stdClass();
			$options->update_version	= null;
			$options->dato_unchanged	= null;
			$options->reference_id		= null;
			$options->tipo				= null;
			$options->section_id		= null;
			$options->section_tipo		= null;
			$options->context			= 'update_component_dato';
			foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}

			$update_version	= $options->update_version;
			$dato_unchanged	= $options->dato_unchanged;
			$reference_id	= $options->reference_id;

		$update_version = implode(".", $update_version);
		switch ($update_version) {

			case '4.0.21':
				#$dato = $this->get_dato_unchanged();

				# Compatibility old dedalo instalations
				if (!empty($dato_unchanged) && is_string($dato_unchanged)) {

					$new_dato = (array)$dato_unchanged;

					$response = new stdClass();
						$response->result	= 1;
						$response->new_dato	= $new_dato;
						$response->msg		= "[$reference_id] Dato is changed from ".to_string($dato_unchanged)." to ".to_string($new_dato).".<br />";

				}else if(is_array($dato_unchanged)){

					$response = new stdClass();
						$response->result	= 1;
						$response->new_dato	= $dato_unchanged;
						$response->msg		= "[$reference_id] Dato is array ".to_string($dato_unchanged)." only save .<br />";

				}else{

					$response = new stdClass();
						$response->result	= 2;
						$response->msg		= "[$reference_id] Current dato don't need update.<br />";	// to_string($dato_unchanged)."
				}
				break;

			default:
				$response = new stdClass();
					$response->result	= 0;
					$response->msg		= "This component ".get_called_class()." don't have update to this version ($update_version). Ignored action";
				break;
		}


		return $response;
	}//end update_dato_version



	/**
	* RESOLVE_QUERY_OBJECT_SQL
	* @param object $query_object
	* @return object $query_object
	*	Edited/parsed version of received object
	*/
	public static function resolve_query_object_sql( object $query_object) : object | array {

		// if (isset($query_object->type) && $query_object->type==='jsonb') {
		// 	$q = json_decode($q);
		// }

		// $q = $query_object->q;
		$q = is_array($query_object->q) ? reset($query_object->q) : $query_object->q;


		# Always set fixed values
		$query_object->type = 'string';

		if (is_string($q)) {
			$q = pg_escape_string(DBi::_getConnection(), stripslashes($q));
		}

		$q_operator = isset($query_object->q_operator) ? $query_object->q_operator : null;

		# Prepend if exists
		#if (isset($query_object->q_operator)) {
		#	$q = $query_object->q_operator . $q;
		#}

		switch (true) {
			# EMPTY VALUE (in current lang data)
			case ($q==='!*'):
				$operator = 'IS NULL';
				$q_clean  = '';
				$query_object->operator = $operator;
				$query_object->q_parsed	= $q_clean;
				$query_object->unaccent = false;
				$query_object->lang 	= 'all';

				$logical_operator = '$or';
				$new_query_json = new stdClass;
					$new_query_json->$logical_operator = [$query_object];

				// Search empty only in current lang
				// Resolve lang based on if is translatable
					$path_end		= end($query_object->path);
					$component_tipo	= $path_end->component_tipo;
					$RecordObj_dd	= new RecordObj_dd($component_tipo);
					$lang			= $RecordObj_dd->get_traducible()!=='si' ? DEDALO_DATA_NOLAN : DEDALO_DATA_LANG;

					$clone = clone($query_object);
						$clone->operator	= '=';
						$clone->q_parsed	= '\'[]\'';
						$clone->lang		= $lang;

					$new_query_json->$logical_operator[] = $clone;

					// legacy data (set as null instead [])
					$clone = clone($query_object);
						$clone->operator	= 'IS NULL';
						$clone->lang		= $lang;

					$new_query_json->$logical_operator[] = $clone;

				// langs check all
					// $ar_query_object = [];
					// $ar_all_langs 	 = common::get_ar_all_langs();
					// $ar_all_langs[]  = DEDALO_DATA_NOLAN; // Added no lang also
					// foreach ($ar_all_langs as $current_lang) {
					// 	// Empty data is blank array []
					// 	$clone = clone($query_object);
					// 		$clone->operator = '=';
					// 		$clone->q_parsed = '\'[]\'';
					// 		$clone->lang 	 = $current_lang;

					// 	$ar_query_object[] = $clone;

					// 	// legacy data (set as null instead [])
					// 	$clone = clone($query_object);
					// 		$clone->operator = 'IS NULL';
					// 		$clone->lang 	 = $current_lang;

					// 	$ar_query_object[] = $clone;
					// }
					// $new_query_json->$logical_operator = array_merge($new_query_json->$logical_operator, $ar_query_object);

				# override
				$query_object = $new_query_json;
				break;
			# NOT EMPTY (in any project lang data)
			case ($q==='*'):
				$operator = 'IS NOT NULL';
				$q_clean  = '';
				$query_object->operator	= $operator;
				$query_object->q_parsed	= $q_clean;
				$query_object->unaccent	= false;

				$logical_operator ='$and';
				$new_query_json = new stdClass;
					$new_query_json->$logical_operator = [$query_object];

				// langs check
					$ar_query_object = [];
					$ar_all_langs 	 = common::get_ar_all_langs();
					$ar_all_langs[]  = DEDALO_DATA_NOLAN; // Added no lang also
					foreach ($ar_all_langs as $current_lang) {
						$clone = clone($query_object);
							$clone->operator	= '!=';
							$clone->q_parsed	= '\'[]\'';
							$clone->lang		= $current_lang;

						$ar_query_object[] = $clone;
					}

					$logical_operator ='$or';
					$langs_query_json = new stdClass;
						$langs_query_json->$logical_operator = $ar_query_object;

				# override
				$query_object = [$new_query_json, $langs_query_json];
				break;
			# IS DIFFERENT
			case (strpos($q, '!=')===0 || $q_operator==='!='):
				$operator = '!=';
				$q_clean  = str_replace($operator, '', $q);
				$query_object->operator	= '!~';
				$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
				$query_object->unaccent	= false;
				break;
			# IS EXACTLY EQUAL ==
			case (strpos($q, '==')===0 || $q_operator==='=='):
				$operator = '==';
				$q_clean  = str_replace($operator, '', $q);
				$query_object->operator = '@>';
				$query_object->q_parsed	= '\'["'.$q_clean.'"]\'';
				$query_object->unaccent = false;
				$query_object->type = 'object';
				if (isset($query_object->lang) && $query_object->lang!=='all') {
					$query_object->component_path[] = $query_object->lang;
				}
				if (isset($query_object->lang) && $query_object->lang==='all') {
					$logical_operator = '$or';
					$ar_query_object = [];
					$ar_all_langs 	 = common::get_ar_all_langs();
					$ar_all_langs[]  = DEDALO_DATA_NOLAN; // Added no lang also
					foreach ($ar_all_langs as $current_lang) {
						// Empty data is blank array []
						$clone = clone($query_object);
							$clone->component_path[] = $current_lang;

						$ar_query_object[] = $clone;
					}
					$query_object = new stdClass();
					$query_object->$logical_operator = $ar_query_object;
				}
				break;
			# IS SIMILAR
			case (strpos($q, '=')===0 || $q_operator==='='):
				$operator = '=';
				$q_clean  = str_replace($operator, '', $q);
				$query_object->operator	= '~*';
				$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
				$query_object->unaccent	= true;
				break;
			# NOT CONTAIN
			case (strpos($q, '-')===0 || $q_operator==='-'):
				$operator = '!~*';
				$q_clean  = str_replace('-', '', $q);
				$query_object->operator	= $operator;
				$query_object->q_parsed	= '\'.*\[".*'.$q_clean.'.*\'';
				$query_object->unaccent	= true;
				break;
			# CONTAIN EXPLICIT
			case (substr($q, 0, 1)==='*' && substr($q, -1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator	= $operator;
				$query_object->q_parsed	= '\'.*\[".*'.$q_clean.'.*\'';
				$query_object->unaccent	= true;
				break;
			# ENDS WITH
			case (substr($q, 0, 1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator	= $operator;
				$query_object->q_parsed	= '\'.*\[".*'.$q_clean.'".*\'';
				$query_object->unaccent	= true;
				break;
			# BEGINS WITH
			case (substr($q, -1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator	= $operator;
				$query_object->q_parsed	= '\'.*\["'.$q_clean.'.*\'';
				$query_object->unaccent	= true;
				break;
			# LITERAL
			case (substr($q, 0, 1)==="'" && substr($q, -1)==="'"):
				$operator = '~';
				$q_clean  = str_replace("'", '', $q);
				$query_object->operator	= $operator;
				$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
				$query_object->unaccent	= true;
				break;
			# DEFAULT CONTAIN
			default:
				$operator = '~*';
				$q_clean  = str_replace('+', '', $q);
				$query_object->operator	= $operator;
				$query_object->q_parsed	= '\'.*\[".*'.$q_clean.'.*\'';
				$query_object->unaccent	= true;
				break;
		}//end switch (true) {

		return $query_object;
	}//end resolve_query_object_sql



	/**
	* SEARCH_OPERATORS_INFO
	* Return valid operators for search in current component
	* @return array $ar_operators
	*/
	public function search_operators_info() : array {

		$ar_operators = [
			'*'			=> 'no_empty', // not null
			'!*'		=> 'empty', // null
			'='			=> 'similar_to',
			'!='		=> 'different_from',
			'-'			=> 'does_not_contain',
			'*text*'	=> 'contains',
			'text*'		=> 'begins_with',
			'*text'		=> 'end_with',
			'\'text\''	=> 'literal'
		];

		return $ar_operators;
	}//end search_operators_info



	/**
	* GET_DIFFUSION_VALUE
	* Calculate current component diffusion value for target field (usually a MYSQL field)
	* Used for diffusion_mysql to unify components diffusion value call
	* @return string|null $diffusion_value
	*
	* @see class.diffusion_mysql.php
	*/
	public function get_diffusion_value( ?string $lang=null, ?object $option_obj=null ) : ?string {

		# Default behavior is get value
		$diffusion_value = $this->get_valor( $lang );

		// Fallback to nolan dato
		if (empty($diffusion_value) && $this->traducible==='no') {
			# try no lang
			$this->set_lang(DEDALO_DATA_NOLAN);
			$diffusion_value = $this->get_valor( DEDALO_DATA_NOLAN );
		}

		# strip_tags all values (remove untranslated mark elements)
		$diffusion_value = !empty($diffusion_value)
			? preg_replace("/<\/?mark>/", '', $diffusion_value)
			: null;


		return $diffusion_value;
	}//end get_diffusion_value



}//end class component_input_text
