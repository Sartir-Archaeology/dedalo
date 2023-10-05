<?php
/**
* CLASS COMPONENT_SECTION_ID
*
*
*/
class component_section_id extends component_common {



	/**
	* GET_DATO
	* @return int $dato
	*/
	public function get_dato() {

		$dato = (int)$this->section_id;

		// Set as loaded
			$this->bl_loaded_matrix_data = true;

		return $dato;
	}//end get_dato



	/**
	* SET_DATO
	* @param int|null $dato
	* @return bool
	*/
	public function set_dato($dato) : bool {

		// dato format check
			if (!is_null($dato) && !is_integer($dato)) {

				debug_log(__METHOD__ . ' '
					. '[SET] RECEIVED DATO IS NOT AS EXPECTED TYPE integer|null. type: '. gettype($dato) .' - dato: '. to_string($dato) . PHP_EOL
					. 'model: '. get_called_class() .PHP_EOL
					. 'tipo: ' . $this->tipo . ' - section_tipo: ' . $this->section_tipo . ' - section_id: ' . $this->section_id
					, logger::ERROR
				);

			}

		// unset previous calculated valor
			if (isset($this->valor)) {
				unset($this->valor);
			}

		// set dato
			$this->dato = $dato;

		// resolved set
			$this->dato_resolved = $dato;


		return true;
	}//end get_dato



	/**
	* GET_VALOR
	*/
	public function get_valor() {

		return $this->get_dato();
	}//end get_valor



	/**
	* GET_DATO_FULL
	*/
	public function get_dato_full() {

		return $this->get_dato();
	}//end get_dato_full



	/**
	* GET_GRID_VALUE
	* Get the value of the components. By default will be get_dato().
	* overwrite in every different specific component
	* The direct components can set the value with the dato directly
	* The relation components will separate the locator in rows
	* @param object|null $ddo = null
	*
	* @return object $value
	*/
	public function get_grid_value(object $ddo=null) : dd_grid_cell_object {

		// column_obj
			if(isset($this->column_obj)){
				$column_obj = $this->column_obj;
			}else{
				$column_obj = new stdClass();
					$column_obj->id = $this->section_tipo.'_'.$this->tipo;
			}

		$data	= $this->get_dato();
		$label	= $this->get_label();

	// value
		$value = new dd_grid_cell_object();
			$value->set_type('column');
			$value->set_label($label);
			$value->set_ar_columns_obj([$column_obj]);
			$value->set_cell_type('section_id');
			$value->set_row_count(1);
			$value->set_value($data);


		return $value;
	}//end get_grid_value



	/**
	* GET_TOOLS
	* Catch get_tools call to prevent load tools sections
	* @return array $tools
	*/
	public function get_tools() : array {

		return [];
	}//end get_tools



	/**
	* RESOLVE_QUERY_OBJECT_SQL
	* @param object $query_object
	* @return object $query_object
	*/
	public static function resolve_query_object_sql(object $query_object) : object {

		// reset array value
		$query_object->q = is_array($query_object->q)
			? reset($query_object->q)
			: $query_object->q;

		$q = $query_object->q;
		// if (isset($query_object->type) && $query_object->type==='jsonb') {
		// 	$q = json_decode($q);
		// }

		// Always set fixed values
		$query_object->type = 'number';

		// format. Always set format to column (but in sequence case)
		$query_object->format = 'column';

		$between_separator  = '...';
		$sequence_separator = ',';

		// Case is an array of values
		if (is_array($q)) {
			$q = implode($sequence_separator, $q);
		}

		// component path
		$query_object->component_path = ['section_id'];

		$query_object->unaccent = false;

		// column_name
		$query_object->column_name = 'section_id';

		switch (true) {
			# BETWEEN
			case (strpos($q, $between_separator)!==false):
				// Transform "12...25" to "12 AND 25"
				$ar_parts	= explode($between_separator, $q);
				$first_val	= !empty($ar_parts[0]) ? intval($ar_parts[0]) : 0;
				$second_val	= !empty($ar_parts[1]) ? intval($ar_parts[1]) : $first_val;

				$query_object_one = clone $query_object;
					$query_object_one->operator = '>=';
					$query_object_one->q_parsed	= $first_val;

				$query_object_two = clone $query_object;
					$query_object_two->operator = '<=';
					$query_object_two->q_parsed	= $second_val;

				// Return an array instead object
				#$query_object = [$query_object_one,$query_object_two];

				// Group in a new "AND"
				$current_op = '$and';
				$new_query_object = new stdClass();
					$new_query_object->{$current_op} = [$query_object_one,$query_object_two];

				$query_object = $new_query_object;
				break;
			# SEQUENCE
			case (strpos($q, $sequence_separator)!==false):
				// Transform "12,25,36" to "(12 OR 25 OR 36)"
				$ar_parts	= explode($sequence_separator, $q);
				// $ar_result	= [];
				// $first		= reset($ar_parts);
				// $last		= end($ar_parts);
				// foreach ($ar_parts as $key => $value) {
				// 	$value = (int)$value;
				// 	if ($value<1) continue;
				// 	$query_object_current = clone $query_object;
				// 		$query_object_current->q		= 'sequence from '.$first.' to '.$last;
				// 		$query_object_current->operator	= '=';
				// 		$query_object_current->q_parsed	= $value;
				// 	$ar_result[] = $query_object_current;
				// }
				// // Return an subquery instead object
				// $cop = '$or';
				// $new_object = new stdClass();
				// 	$new_object->{$cop} = $ar_result;
				// $query_object = $new_object;

				$operator = 'IN';
				$q_clean  = array_map(function($el){
					return (int)$el;
				}, $ar_parts);
				$query_object->operator	= $operator;
				$query_object->q_parsed	= implode(',', $q_clean);
				$query_object->format	= 'in_column';
				break;
			# BIGGER OR EQUAL THAN
			case (substr($q, 0, 2)==='>='):
				$operator = '>=';
				$q_clean  = (int)str_replace($operator, '', $q);
				$query_object->operator = $operator;
				$query_object->q_parsed	= $q_clean;
				break;
			# SMALLER OR EQUAL THAN
			case (substr($q, 0, 2)==='<='):
				$operator = '<=';
				$q_clean  = (int)str_replace($operator, '', $q);
				$query_object->operator = $operator;
				$query_object->q_parsed	= $q_clean;
				break;
			# BIGGER THAN
			case (substr($q, 0, 1)==='>'):
				$operator = '>';
				$q_clean  = (int)str_replace($operator, '', $q);
				$query_object->operator = $operator;
				$query_object->q_parsed	= $q_clean;
				break;
			# SMALLER THAN
			case (substr($q, 0, 1)==='<'):
				$operator = '<';
				$q_clean  = (int)str_replace($operator, '', $q);
				$query_object->operator = $operator;
				$query_object->q_parsed	= $q_clean;
				break;
			// EQUAL DEFAULT
			default:
				$operator = '=';
				$q_clean  = (int)str_replace('+', '', $q);
				$query_object->operator = $operator;
				$query_object->q_parsed	= $q_clean;
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
			'...'	=> 'between',
			','		=> 'sequence',
			'>='	=> 'greater_than_or_equal',
			'<='	=> 'less_than_or_equal',
			'>'		=> 'greater_than',
			'<'		=> 'less_than'
		];

		return $ar_operators;
	}//end search_operators_info



	/**
	* EXTRACT_COMPONENT_DATO_FALLBACK
	* Catch extract_component_dato_fallback common method calls
	* @return array $dato_fb
	*/
	public static function extract_component_dato_fallback(object $component, string $lang=DEDALO_DATA_LANG, string $main_lang=DEDALO_DATA_LANG_DEFAULT) : array {
		return [];
	}



	/**
	* EXTRACT_COMPONENT_VALUE_FALLBACK
	* Catch common method calls
	* @return string $value
	*/
	public static function extract_component_value_fallback(object $component, string $lang=DEDALO_DATA_LANG, bool $mark=true, string $main_lang=DEDALO_DATA_LANG_DEFAULT) : string {
		return '';
	}



}//end class component_section_id
