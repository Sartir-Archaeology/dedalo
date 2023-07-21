<?php
/**
* CLASS COMPONENT_NUMBER
* Manage numbers with specific precision
* types supported : int || float
* data format : [number,xx]
* data example : [6.12]
* example multiple : [6.12,88]
* Default type : float
* Default precision: 2
* Properties can define the type and precision as "type":"float", "precision":4
* Notes: Data storage format does not support internationalization for numbers the float point is always . and does not use thousand separator
* but is possible format it in render->view to accommodate to specific formats as Spanish format 1.234,56 from data 1234.56
*/
class component_number extends component_common {



	/**
	* __CONSTRUCT
	*/
	protected function __construct(string $tipo=null, $parent=null, string $mode='list', string $lang=DEDALO_DATA_NOLAN, string $section_tipo=null, bool $cache=true) {

		$this->lang = DEDALO_DATA_NOLAN;

		parent::__construct($tipo, $parent, $mode, $this->lang, $section_tipo, $cache);
	}//end __construct



	/**
	* GET_DATO
	*/
	public function get_dato() {

		$dato = parent::get_dato();

		$format_dato = [];
		foreach ((array)$dato as $key => $value) {
			$format_dato[] = $this->set_format_form_type($value);
		}

		return (array)$format_dato;
	}//end get_dato



	/**
	* SET_DATO
	* @return bool
	*/
	public function set_dato($dato) : bool {

		$safe_dato = array();
		foreach ((array)$dato as  $value) {
			if (is_null($value) || $value==='') {
				$safe_dato[] = null;
			}elseif (is_numeric($value)) {
				$safe_dato[] = $this->set_format_form_type($value);
			}else{
				// trigger_error("Invalid value! [component_number.set_dato] value: ".json_encode($value));
				debug_log(__METHOD__
					." Invalid value! [component_number.set_dato] value: "
					.to_string($value)
					, logger::ERROR
				);
			}
		}

		return parent::set_dato( $safe_dato );
	}//end set_dato



	/**
	* GET_VALOR
	* Returns int or float number as string formatted
	* @return string|null $valor
	*/
	public function get_valor($index='all') {

		$valor ='';

		$dato = $this->get_dato();

		if(empty($dato)) {
			return (string)$valor;
		}

		if ($index==='all') {
			$ar = array();
			foreach ($dato as $key => $value) {
				$value = component_number::number_to_string($value);
				if (!empty($value)) {
					$ar[] = $value;
				}
			}
			if (count($ar)>0) {
				$valor = implode(',',$ar);
			}
		}else{
			$index = (int)$index;
			$valor = isset($dato[$index]) ? $dato[$index] : null;
		}

		return $valor;
	}//end get_valor



	/*
	* SET_FORMAT_FORM_TYPE
	* Format the dato into the standard format or the properties format of the current instance of the component
	*/
	public function set_format_form_type( $dato_value ) {

		if($dato_value===null || empty($dato_value)){
			return $dato_value;
		}

		$properties = $this->get_properties();
		if(empty($properties->type)){
			return (float)$dato_value;
		}else{
			switch ($properties->type) {

				case 'int':
					return (int)$dato_value;
					break;

				case 'float':
				default:
					$precision = $properties->precision ?? 2;

					$dato_value = (float)round($dato_value, $precision);
					break;
			}
		}//end if(empty($properties->type))

		return $dato_value;
	}//end set_format_form_type


	/*
	* NUMBER_TO_STRING
	* Format the dato into the standard format or the properties format of the current instance of the component
	*/
	public function number_to_string( $dato ) {

		$properties = $this->get_properties();

		// default value
		$string_value = $dato;

		if (!empty($dato) && !empty($properties->type)) {

			switch ($properties->type ) {
				case 'int':
					// nothing to do
					break;

				case 'float':
				default:
					$precision = $properties->precision ?? 2;

					$string_value = number_format($dato,$precision,'.','');
					break;
			}
		}//end if (!empty($dato))

		$string_value = str_replace(',', '.', (string)$string_value);

		return (string)$string_value;
	}//end number_to_string



	/**
	* RESOLVE_QUERY_OBJECT_SQL
	* @return object $query_object
	*/
	public static function resolve_query_object_sql( object $query_object) : object {

		$q = is_array($query_object->q) ? reset($query_object->q) : $query_object->q;

		#$q = $query_object->q;
		#if (isset($query_object->type) && $query_object->type==='jsonb') {
		#	$q = json_decode($q);
		#}

    	# Always set fixed values
		$query_object->type = 'number';


		$query_object->component_path[] = 'lg-nolan';

		# Always without unaccent
		$query_object->unaccent = false;

		$between_separator  = '...';
		//$sequence_separator = ',';

		#$q_operator = isset($query_object->q_operator) ? $query_object->q_operator : null;

        switch (true) {

        	# BETWEEN
			case (strpos($q, $between_separator)!==false):
				// Transform "12...25" to "12 AND 25"
				$ar_parts 	= explode($between_separator, $q);
				$first_val  = !empty($ar_parts[0]) ? intval($ar_parts[0]) : 0;
				$second_val = !empty($ar_parts[1]) ? intval($ar_parts[1]) : $first_val;

				$query_object_one = clone $query_object;
					$query_object_one->operator = '>=';
					$first_val  = str_replace(',', '.', (string)$first_val);
					$query_object_one->q_parsed	= '\''.(string)$first_val.'\'';

				$query_object_two = clone $query_object;
					$query_object_two->operator = '<=';
					$second_val  = str_replace(',', '.', (string)$second_val);
					$query_object_two->q_parsed	= '\''.$second_val.'\'';

				// Return an array instead object
				#$query_object = [$query_object_one,$query_object_two];

				// Group in a new "AND"
				$current_op = '$and';
				$new_query_object = new stdClass();
					$new_query_object->{$current_op} = [$query_object_one,$query_object_two];

				$query_object = $new_query_object;
				break;
        	# SEQUENCE
			/*case (strpos($q, $sequence_separator)!==false):
				// Transform "12,25,36" to "(12 OR 25 OR 36)"
				$ar_parts 	= explode($sequence_separator, $q);
				$ar_result  = [];
				foreach ($ar_parts as $key => $value) {
					$value = (int)$value;
					if ($value<1) continue;
					$query_object_current = clone $query_object;
						$query_object_current->operator = '=';
						$query_object_current->q_parsed	= '\''.$value.'\'';
					$ar_result[] = $query_object_current;
				}
				// Return an subquery instead object
				$cop = '$or';
				$new_object = new stdClass();
					$new_object->{$cop} = $ar_result;
				$query_object = $new_object;
				break;
				*/
			# BIGGER OR EQUAL THAN
			case (substr($q, 0, 2)==='>='):
				$operator = '>=';
				$q_clean  = str_replace($operator, '', $q);
				$q_clean  = str_replace(',', '.', $q_clean);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\''.$q_clean.'\'';
				break;
			# SMALLER OR EQUAL THAN
			case (substr($q, 0, 2)==='<='):
				$operator = '<=';
				$q_clean  = str_replace($operator, '', $q);
				$q_clean  = str_replace(',', '.', $q_clean);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\''.$q_clean.'\'';
				break;
			# BIGGER THAN
			case (substr($q, 0, 1)==='>'):
				$operator = '>';
				$q_clean  = str_replace($operator, '', $q);
				$q_clean  = str_replace(',', '.', $q_clean);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\''.$q_clean.'\'';
				break;
			# SMALLER THAN
			case (substr($q, 0, 1)==='<'):
				$operator = '<';
				$q_clean  = str_replace($operator, '', $q);
				$q_clean  = str_replace(',', '.', $q_clean);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\''.$q_clean.'\'';
				break;
			// EQUAL DEFAULT
			default:
				$operator = '=';
				$q_clean  = str_replace('+', '', $q);
				$q_clean  = str_replace(',', '.', $q_clean);
				$query_object->operator = '@>';
				$query_object->q_parsed	= '\''.$q_clean.'\'';
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
			'...' 	=> 'between',
			'>=' 	=> 'greater_than_or_equal',
			'<='	=> 'less_than_or_equal',
			'>' 	=> 'greater_than',
			'<'		=> 'less_than'
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

		$dato				= parent::get_dato();
		$value				= is_array($dato) ? reset($dato) : $dato;
		$diffusion_value	= !empty($value)
			? (string)$value
			: null;

		return $diffusion_value;
	}//end get_diffusion_value



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

		$update_version_string = implode('.', $update_version);
		switch ($update_version_string) {

			case '6.0.0':
				if ( (!empty($dato_unchanged) || $dato_unchanged==='') && !is_array($dato_unchanged) ) {

					//  Change the dato from int|float to array
					// 	From:
					// 		487
					// 	To:
					// 		[487]

					// new dato
						$dato = $dato_unchanged;

					// fix final dato with new format as array
						$new_dato = [$dato];

					$response = new stdClass();
						$response->result	= 1;
						$response->new_dato	= $new_dato;
						$response->msg		= "[$reference_id] Dato is changed from ".to_string($dato_unchanged)." to ".to_string($new_dato).".<br />";
				}else{

					$response = new stdClass();
						$response->result	= 2;
						$response->msg		= "[$reference_id] Current dato don't need update.<br />";	// to_string($dato_unchanged)."
				}
				break;

			default:
				$response = new stdClass();
					$response->result	= 0;
					$response->msg		= "This component ".get_called_class()." don't have update to this version ($update_version_string). Ignored action";
				break;
		}


		return $response;
	}//end update_dato_version



}//end class component_number
