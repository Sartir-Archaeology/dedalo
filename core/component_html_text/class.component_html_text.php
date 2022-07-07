<?php
/*
* CLASS COMPONENT_HTML_TEXT
*
*
*/
class component_html_text extends component_common {



	/**
	* GET_DATO
	*/
	public function get_dato() {

		$dato = parent::get_dato();

		return (array)$dato;
	}//end get_dato



	/**
	* SET_DATO
	*/
	public function set_dato($dato) {
		// if ($dato==='""') {
		// 	$dato = "";
		// }

		if (is_string($dato)) { # Tool Time machine case, dato is string

			//check the dato for determinate the original format and if the $dato is correct.
			$dato_trim				= trim($dato);
			$dato_first_character 	= substr($dato_trim, 0, 1);
			$dato_last_character  	= substr($dato_trim, -1);

			if ($dato_first_character==='[' && $dato_last_character===']') {
				# dato is json encoded
				$dato = json_handler::decode($dato_trim);
			}else{
				# dato is string plain value
				$dato = array($dato);
				#debug_log(__METHOD__." Warning. [$this->tipo,$this->parent] Dato received is a plain string. Support for this type is deprecated. Use always an array to set dato. ".to_string($dato), logger::DEBUG);
			}
		}

		if(SHOW_DEBUG===true) {
			if (!is_array($dato)) {
				debug_log(__METHOD__." Warning. [$this->tipo,$this->parent]. Received dato is NOT array. Type is '".gettype($dato)."' and dato: '".to_string($dato)."' will be converted to array", logger::DEBUG);
			}
			#debug_log(__METHOD__." dato [$this->tipo,$this->parent] Type is ".gettype($dato)." -> ".to_string($dato), logger::ERROR);
		}

		$safe_dato=array();
		foreach ((array)$dato as $key => $value) {
			if (!is_string($value)) {
				$safe_dato[] = to_string($value);
			}else{
				$safe_dato[] = $value;
			}
		}
		$dato = $safe_dato;

		parent::set_dato( (array)$dato );
	}//end set_dato



	/**
	* SAVE OVERRIDE
	* Overwrite component_common method to set always lang to config:DEDALO_DATA_NOLAN before save
	* @return int|null $section_id
	*/
	public function Save() : ?int {

		# Dato current assigned
			$dato_current = $this->dato;

		# Clean dato
			$dato_clean	= $this->clean_text($dato_current);

		# Set dato again (cleaned)
			$this->dato	= $dato_clean;

		// From here, we save as standard
		return parent::Save();
	}//end Save



	/**
	* GET DATO DEFAULT
	* Overwrite common_function
	*/
	public function get_dato_default_lang() {

		$dato = parent::get_dato_default_lang();
		return $dato;
	}//end get_dato_default_lang



	/**
	* GET VALOR
	* Overwrite common_function
	*/
	public function get_valor() {

		switch ($this->modo) {
			case 'dummy':
			case 'diffusion':
				#$dato = $this->get_dato();
				$dato = parent::get_dato();
				break;

			default:
				$dato = parent::get_dato();
				#dump($dato,'dato');

				//$dato = $this->clean_text($dato);
					#dump($dato ,'$dato ');
				break;
		}

		return $dato;
	}//end get_valor



	/**
	* CLEAN_TEXT
	* Anchor for future preprocessing of the text. At the moment we will only do a trim
	*/
	public function clean_text(string $string) : string {

		# Desactivo porque elimina el '<mar>'
		#$string = filter_var($string, FILTER_UNSAFE_RAW );	# FILTER_SANITIZE_STRING
		#$string = stripslashes($string);

		// $clean_dato=array();
		// foreach ((array)$dato as $key => $value) {
		// 	$clean_dato [] = trim($value);
		// }

		// $dato = $clean_dato;

		// return (array)$dato;//trim($string);
		return $string;
	}//end clean_text



	/**
	* RESOLVE_QUERY_OBJECT_SQL
	* @return object $query_object
	*/
	public static function resolve_query_object_sql( object $query_object) : object {

    	# Always set fixed values
		$query_object->type = 'string';

		$q = $query_object->q;
		$q = pg_escape_string(DBi::_getConnection(), stripslashes($q));

        switch (true) {
        	# IS NULL
			case ($q==='!*'):
				$operator = 'IS NULL';
				$q_clean  = '';
				$query_object->operator = $operator;
    			$query_object->q_parsed	= $q_clean;
    			$query_object->unaccent = false;

				$clone = clone($query_object);
	    			$clone->operator = '~*';
	    			$clone->q_parsed = '\'.*""\'';

				$logical_operator = '$or';
    			$new_query_json = new stdClass;
	    			$new_query_json->$logical_operator = [$query_object, $clone];
    			# override
    			$query_object = $new_query_json ;

				break;
			# IS NOT NULL
			case ($q==='*'):
				$operator = 'IS NOT NULL';
				$q_clean  = '';
				$query_object->operator = $operator;
    			$query_object->q_parsed	= $q_clean;
    			$query_object->unaccent = false;

				$clone = clone($query_object);
	    			//$clone->operator = '!=';
	    			$clone->operator = '!~';
	    			$clone->q_parsed = '\'.*""\'';


				$logical_operator ='$and';
    			$new_query_json = new stdClass;
    				$new_query_json->$logical_operator = [$query_object, $clone];

				# override
    			$query_object = $new_query_json ;
				break;
			# IS DIFFERENT
			case (strpos($q, '!=')===0):
				$operator = '!=';
				$q_clean  = str_replace($operator, '', $q);
				$query_object->operator = '!~';
    			$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
    			$query_object->unaccent = false;
				break;
			# IS SIMILAR
			case (strpos($q, '=')===0):
				$operator = '=';
				$q_clean  = str_replace($operator, '', $q);
				$query_object->operator = '~';
    			$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
    			$query_object->unaccent = true;
				break;
			# NOT CONTAIN
			case (strpos($q, '-')===0):
				$operator = '!~*';
				$q_clean  = str_replace('-', '', $q);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*'.$q_clean.'.*\'';
    			$query_object->unaccent = true;
				break;
			# CONTAIN
			case (substr($q, 0, 1)==='*' && substr($q, -1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*".*'.$q_clean.'.*\'';
    			$query_object->unaccent = true;
				break;
			# ENDS WITH
			case (substr($q, 0, 1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*".*'.$q_clean.'".*\'';
    			$query_object->unaccent = true;
				break;
			# BEGINS WITH
			case (substr($q, -1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*"'.$q_clean.'.*\'';
    			$query_object->unaccent = true;
				break;
			# LITERAL
			case (substr($q, 0, 1)==='\"' && substr($q, -1)==='\"'):
				$operator = '~';
				$q_clean  = str_replace('\"', '', $q);
				$query_object->operator = $operator;
				$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
				$query_object->unaccent = false;
				break;
			# CONTAIN
			default:
				$operator = '~*';
				$q_clean  = $q;
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*".*'.$q_clean.'.*\'';
    			$query_object->unaccent = true;
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
			'*' 	 => 'no_vacio', // not null
			'!*' 	 => 'campo_vacio', // null
			'=' 	 => 'similar_a',
			'!=' 	 => 'distinto_de',
			'-' 	 => 'no_contiene',
			'*text*' => 'contiene',
			'text*'  => 'empieza_con',
			'*text'  => 'acaba_con',
			'"text"' => 'literal',
		];

		return $ar_operators;
	}//end search_operators_info



	/**
	* GET_LIST_VALUE
	* (!) Identical to component_text_area method
	* Unified value list output
	* By default, list value is equivalent to dato. Override in other cases.
	* Note that empty array or string are returned as null
	* A param '$options' is added only to allow future granular control of the output
	* @param object $options = null
	* 	Optional way to modify result. Avoid using it if it is not essential
	* @return array|null $list_value
	*/
	public function get_list_value(object $options=null) : ?array {

		return $this->get_list_value_large_text($options);
	}//end get_list_value



}//end class component_html_text
