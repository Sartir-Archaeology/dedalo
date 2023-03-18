<?php
/*
* CLASS COMPONENT_INVERSE
*
*
*/
class component_inverse extends component_common {



	/**
	* GET_DATO
	* This component don't store data, only access to section inverse_locators data
	* @return array $dato
	*/
	public function get_dato() {

		$section	= $this->get_my_section();
		$dato		= $section->get_inverse_locators();

		return $dato;
	}//end get_dato



				if (is_null($item->datalist)) {
					$item->datalist = [];
				}

				$dato[] = $item;
			}

		return (array)$dato;
	}//end get_dato



	/**
	* GET_VALOR
	* @return string $valor
	*/
	public function get_valor() {

		$dato = $this->get_dato();

		return (string)json_encode($dato);
	}//end get_valor



	/**
	* GET_VALOR_EXPORT
	* Return component value sended to export data
	* @return string $valor
	*/
	public function get_valor_export($valor=null, $lang=DEDALO_DATA_LANG, $quotes=null, $add_id=null) {

		# When is received 'valor', set as dato to avoid trigger get_dato against DB
		# Received 'valor' is a json string (array of locators) from previous database search
		if (!is_null($valor)) {
			$dato = json_decode($valor);
			$this->set_dato($dato);
		}else{
			$dato = $this->get_dato();
		}


		$inverse_show = $this->get_properties()->inverse_show;

		$ar_lines = [];
		foreach ($dato as $key => $current_locator) {

			$section_id   	= $current_locator->from_section_id;
			$section_tipo 	= $current_locator->from_section_tipo;
			$component_tipo = $current_locator->from_component_tipo;

			$line = '';
			foreach ($inverse_show as $ikey => $ivalue) {
				if ($ivalue===false) continue;

				# section_id
				if ($ikey === 'section_id') {
					if(strlen($line)>0) $line .= ' ';
					#$line .= $current_locator->section_id;
					$line .= $section_id;
				}

				# section_tipo
				if ($ikey === 'section_tipo') {
					if(strlen($line)>0) $line .= ' ';
					#$line .= $current_locator->section_tipo;
					$line .= $section_tipo;
				}

				# section_label
				if ($ikey === 'section_label') {
					if(strlen($line)>0) $line .= ' ';
					$label = RecordObj_dd::get_termino_by_tipo($section_tipo, $lang);
					$line .= $label;
				}

				# component_tipo
				if ($ikey === 'component_tipo' || $ikey === 'from_component_tipo') {
					if(strlen($line)>0) $line .= ' ';
					$line .= $component_tipo;
				}

				# component_label
				if ($ikey === 'component_label') {
					if(strlen($line)>0) $line .= ' ';
					$label = RecordObj_dd::get_termino_by_tipo($component_tipo, $lang);
					$line .= $label;
				}
			}

			$ar_lines[] = $line;
		}
		$lines = implode(PHP_EOL, $ar_lines);


		return $lines;
	}//end get_valor_export



	/**
	* BUILD_SEARCH_COMPARISON_OPERATORS
	* Note: Override in every specific component
	* @param array $comparison_operators . Like array('=','!=')
	* @return object stdClass $search_comparison_operators
	*/
	public function build_search_comparison_operators($comparison_operators=array('=','!=')) {
		return (object)parent::build_search_comparison_operators($comparison_operators);
	}//end build_search_comparison_operators



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
		// public static function get_search_query_old($json_field, $search_tipo, $tipo_de_dato_search, $current_lang, $search_value, $comparison_operator='=') {
		// 	debug_log(__METHOD__." DISABLED OPTION !!! ".to_string(), logger::ERROR);
		// }//end get_search_query_old



	/**
	* GET_INVERSE_VALUE
	* @return array|null $inverse_value
	*/
	public function get_inverse_value(object $locator) : ?array {

		$tipo = $this->get_tipo();

		$ar_look_section_tipo = common::get_ar_related_by_model('section', $tipo);
		if (!isset($ar_look_section_tipo[0])) {
			return null;
		}
		$look_section_tipo = $ar_look_section_tipo[0];
		if ($locator->from_section_tipo!==$look_section_tipo) {
			//debug_log(__METHOD__." Ignored section tipo ".to_string(), logger::DEBUG);
			return null;
		}

		$ar_value=array();
		$ar_related = $this->RecordObj_dd->get_relaciones();
		foreach ($ar_related as $key => $value) {
			$current_tipo = reset($value);
			$model_name  = RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);
				//dump($model_name, ' model_name ++ '.to_string());
			if ($model_name!=='section') {
				# Components
				$component = component_common::get_instance(
					$model_name,
					$current_tipo,
					$locator->from_section_id,
					'list',
					DEDALO_DATA_LANG,
					$locator->from_section_tipo
				);

				$list_item = new stdClass();
					$list_item->label	= $component->get_label();
					$list_item->value	= $component->get_valor();

				$ar_value[] = $list_item;
			}
		}

		return (array)$ar_value;
	}//end get_inverse_value



}//end class component_inverse
