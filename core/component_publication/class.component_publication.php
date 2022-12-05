<?php
/*
* CLASS COMPONENT_PUBLICATION
*
*
*/
class component_publication extends component_relation_common {



	// relation_type defaults
	protected $default_relation_type		= DEDALO_RELATION_TYPE_LINK;
	protected $default_relation_type_rel	= null;

	# test_equal_properties is used to verify duplicates when add locators
	public $test_equal_properties = ['section_tipo','section_id','type','from_component_tipo'];



	/**
	* __CONSTRUCT
	* @return bool
	*/
	function __construct(string $tipo=null, $parent=null, string $mode='list', string $lang=DEDALO_DATA_NOLAN, string $section_tipo=null) {

		# Force always DEDALO_DATA_NOLAN
		$lang = DEDALO_DATA_NOLAN;

		# construct the component normally
		return parent::__construct($tipo, $parent, $mode, $lang, $section_tipo);
	}//end __construct



	/**
	* GET_VALOR
	* Get value . default is get dato . overwrite in every different specific component
	*/
	public function get_valor( $lang=DEDALO_DATA_LANG ) {

		$dato = $this->get_dato();

		# Test dato format (b4 changed to object)
			if(SHOW_DEBUG===true) {
				if (!empty($dato)) foreach ($dato as $key => $value) {
					if (!empty($value) && !is_object($value)) {
						if(SHOW_DEBUG===true) {
							dump($dato," +++ dato Wrong dato format. OLD format dato in $this->label $this->tipo .Expected object locator, but received: ".gettype($value));
						}
						debug_log(__METHOD__." Wrong dato format. OLD format dato in $this->label $this->tipo .Expected object locator, but received: ".gettype($value) .' : '. print_r($value,true), logger::ERROR);
						return null;
					}
				}
			}

		// mode changes value result
		switch ($this->mode) {
			case 'diffusion':
				$valor = 'no';
				if (!empty($dato)) {

					$object_si = new stdClass();
						$object_si->section_id   = (string)NUMERICAL_MATRIX_VALUE_YES;
						$object_si->section_tipo = (string)"dd64";

					$component_locator = reset($dato);
					$compare_locators  = locator::compare_locators( $component_locator, $object_si, $ar_properties=['section_id','section_tipo']);

					if ($compare_locators===true) {
						$valor = 'si';
					}
				}
				break;

			default:
				$valor = null;
				if (!empty($dato)) {

					# Always run list of values
					$ar_list_of_values	= $this->get_ar_list_of_values($lang); # Importante: Buscamos el valor en el idioma actual
					$component_locator  = reset($dato);
					foreach ($ar_list_of_values->result as $key => $item) {

						$locator = $item->value;
						if (true===locator::compare_locators( $component_locator, $locator, $ar_properties=['section_id','section_tipo'])) {
							$valor = $item->label;
							break;
						}
					}
				}
				break;
		}#end switch


		return $valor;
	}//end get_valor



	/**
	* BUILD_SEARCH_COMPARISON_OPERATORS
	* Note: Override in every specific component
	* @param array $comparison_operators = ['=','!=']
	* @return object stdClass $search_comparison_operators
	*/
		// public function build_search_comparison_operators( array $comparison_operators=['=','!='] ) {

		// 	return (object)parent::build_search_comparison_operators($comparison_operators);
		// }//end build_search_comparison_operators



	/**
	* GET_SEARCH_QUERY_OLD
	* Build search query for current component . Overwrite for different needs in other components
	* (is static to enable direct call from section_records without construct component)
	* Params
	* @param string $json_field . JSON container column Like 'dato'
	* @param string $search_tipo . Component tipo Like 'dd421'
	* @param string $tipo_de_dato_search . Component dato container Like 'dato' or 'valor'
	* @param string $current_lang . Component dato lang container Like 'lg-spa' or 'lg-nolan'
	* @param string $search_value . Value received from search form request Like 'paco'
	* @param string $comparison_operator . SQL comparison operator Like 'ILIKE'
	*
	* @see class.section_records.php get_rows_data filter_by_search
	* @return string $search_query . POSTGRE SQL query (like 'datos#>'{components, oh21, dato, lg-nolan}' ILIKE '%paco%' )
	*/
		// public static function get_search_query_old(string $json_field, string $search_tipo, string $tipo_de_dato_search, string $current_lang, string $search_value, string $comparison_operator='=') : string {

		// 	$search_query='';
		// 	if ( empty($search_value) ) {
		// 		return $search_query;
		// 	}

		// 	$json_field = 'a.'.$json_field; // Add 'a.' for mandatory table alias search

		// 	switch (true) {
		// 		case $comparison_operator==='=':
		// 			$search_query = " $json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}' @> '[$search_value]'::jsonb ";
		// 			break;
		// 		case $comparison_operator==='!=':
		// 			$search_query = " ($json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}' @> '[$search_value]'::jsonb)=FALSE ";
		// 			break;
		// 	}

		// 	if(SHOW_DEBUG===true) {
		// 		$search_query = " -- filter_by_search $search_tipo ". get_called_class() ." \n".$search_query;
		// 		#dump($search_query, " search_query for search_value: ".to_string($search_value)); #return '';
		// 	}

		// 	return $search_query;
		// }//end get_search_query_old



	/**
	* GET_SORTABLE
	* @return bool
	* 	Default is true. Override when component is sortable
	*/
	public function get_sortable() : bool {

		return true;
	}//end get_sortable



}//end class component_publication
