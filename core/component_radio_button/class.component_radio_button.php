<?php
/*
* CLASS COMPONENT RADIO BUTTON
*
*
*/
class component_radio_button extends component_relation_common {



	// relation_type defaults
	protected $default_relation_type		= DEDALO_RELATION_TYPE_LINK;
	protected $default_relation_type_rel	= null;

	# test_equal_properties is used to verify duplicates when add locators
	public $test_equal_properties = array('section_tipo','section_id','type','from_component_tipo');



	/**
	* GET_VALOR
	* Get resolved value in requested lang
	* Note that no value is fixed here because 'valor' depends of requested lang
	*/
	public function get_valor( string $lang=DEDALO_DATA_LANG ) {

		$dato = $this->get_dato();
		if (empty($dato)) {
			return null;
		}

		// Test dato format (b4 changed to object)
			foreach ($dato as $key => $value) {
				if (!is_object($value)) {
					if(SHOW_DEBUG===true) {
						dump($dato," dato");
						trigger_error(__METHOD__." Wrong dato format. OLD format dato in $this->label $this->tipo .Expected object locator, but received: ".gettype($value) .' : '. print_r($value,true) );
					}
					return null;
				}
			}

		switch ($this->mode) {

			case 'diffusion':
				// dd64 case
				$object_si = new stdClass();
					$object_si->section_id   = (string)NUMERICAL_MATRIX_VALUE_YES;
					$object_si->section_tipo = (string)DEDALO_SECTION_SI_NO_TIPO; // 'dd64'

				$valor = ($dato[0]===$object_si) ? 'si' : 'no';
				break;

			default:
				// list_of_values. Always run list of values. (!) Get values only in requested lang
				$ar_list_of_values = $this->get_ar_list_of_values($lang);
				$valor = '';
				foreach ($ar_list_of_values->result as $key => $item) {

					$locator = $item->value;
					if ( true===locator::in_array_locator($locator, $dato, array('section_id','section_tipo')) ) {
						$valor = $item->label;
						break;
					}
				}
				break;
		}//end switch


		return $valor;
	}//end get_valor



	/**
	* GET_DIFFUSION_VALUE
	* Overwrite component common method
	* Calculate current component diffusion value for target field (usually a mysql field)
	* Used for diffusion_mysql to unify components diffusion value call
	* @return string|null $diffusion_value
	*
	* @see class.diffusion_mysql.php
	*/
	public function get_diffusion_value( ?string $lang=null, ?object $option_obj=null ) : ?string {

		$diffusion_value = $this->get_valor(
			$lang ?? DEDALO_DATA_LANG
		);

		$diffusion_value = !empty($diffusion_value)
			? strip_tags($diffusion_value)
			: null;

		return $diffusion_value;
	}//end get_diffusion_value



	/**
	* GET_DIFFUSION_DATO
	* @return string $diffusion_value
	*/
	public function get_diffusion_dato() : ?string {

		$dato = $this->get_dato();
		if (is_array($dato)) {
			$ar_id = array();
			foreach ($dato as $current_locator) {
				$ar_id[] = $current_locator->section_id;
			}
			$final_dato = $ar_id;
		}

		$diffusion_value = !empty($final_dato)
			? json_encode($final_dato)
			: null;

		return (string)$diffusion_value;
	}//end get_diffusion_dato



	/**
	* GET_SORTABLE
	* @return bool
	* 	Default is false. Override when component is sortable
	*/
	public function get_sortable() : bool {

		return true;
	}//end get_sortable


}//end class component_radio_button
