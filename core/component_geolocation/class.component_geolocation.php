<?php
/*
* CLASS COMPONENT_GEOLOCATION
*
*
*/
class component_geolocation extends component_common {



	/**
	* __CONSTRUCT
	*/
	function __construct(string $tipo, $section_id=null, string $mode='list', string $lang=null, string $section_tipo=null) {


		# Force always DEDALO_DATA_NOLAN
		$lang = DEDALO_DATA_NOLAN;

		# Build the component
		parent::__construct($tipo, $section_id, $mode, $lang, $section_tipo);

		# Dato verification, if the dato is empty, build the standard view of the map
		$dato = $this->get_dato();

		// # if the section_id is not empty and the dato is empty create the basic and standard dato
		// $need_save=false;
		// if((!isset($dato[0]->lat) || !isset($dato[0]->lon)) && $this->parent>0) {
		// 	#####################################################################################################
		// 	# DEFAULT VALUES
		// 	# Store section dato as array(key=>value)
		// 	$dato_new = new stdClass();
		// 		$dato_new->lat		= 39.462571;
		// 		$dato_new->lon		= -0.376295;	# Calle Denia
		// 		$dato_new->zoom		= 12;
		// 		$dato_new->alt		= 16;
		// 		#$dato_new->coordinates	= array();
		// 	# END DEFAULT VALUES
		// 	######################################################################################################

		// 	# Dato
		// 	$this->set_dato([$dato_new]);
		// 	$need_save=true;
		// }

		// #
		// # CONFIGURACIÓN NECESARIA PARA PODER SALVAR
		// # Nothing to do here

		// if ($need_save===true) {
		// 	$result = $this->Save();
		// 	# debug_log(__METHOD__."  Added default component_geolocation data $section_id with: ($tipo, $lang) dato: ".to_string($dato_new), logger::DEBUG);
		// }

		// debug
			// if(SHOW_DEBUG) {
			// 	$traducible = $this->RecordObj_dd->get_traducible();
			// 	if ($traducible==='si') {
			// 		#throw new Exception("Error Processing Request. Wrong component lang definition. This component $tipo (".get_class().") is not 'traducible'. Please fix this ASAP", 1);
			// 		trigger_error("Error Processing Request. Wrong component lang definition. This component $tipo (".get_class().") is not 'traducible'. Please fix this ASAP");
			// 	}
			// }


		return true;
	}//end __construct



	# GET DATO : Format [{lat: 39.462571354311095, lon: -0.3763031959533692, zoom: 15, alt: 16}]
	public function get_dato() {
		$dato = parent::get_dato();

		if (!empty($dato) && !is_array($dato)) {
			$dato = [$dato];
		}

		return $dato;
	}//end get_dato



	# SET_DATO
	public function set_dato($dato) {

		# json encoded dato
		if (is_string($dato)) {
			$dato = json_decode($dato);
		}

		// if (!isset($dato->zoom)) {
		// 	$dato->zoom = 12;
		// }

		parent::set_dato( (array)$dato );
	}//end set_dato



	/**
	* GET VALOR
	* LIST:
	* GET VALUE . DEFAULT IS GET DATO . OVERWRITE IN EVERY DIFFERENT SPECIFIC COMPONENT
	*/
	public function get_valor() {

		$valor = (array)self::get_dato();

		$separator = ' ,  ';
		if($this->mode==='list') $separator = '<br>';

		if (is_object($valor)) {
			$valor = array($valor); # Convert json obj to array
		}

		if (is_array($valor)) {
			# return "Not string value";
			$string  	= '';
			$n 			= count($valor);
			foreach ($valor as $key => $value) {

				if(is_array($value)) $value = print_r($value,true);
				$string .= "$key : ". to_string($value) . $separator;
			}
			$string = substr($string, 0,-4);
			return $string;

		}else{

			return $valor;
		}
	}//end get_valor



	/**
	* GET_VALUE
	* Alias of component_common->get_value
	* @param string $lang = DEDALO_DATA_LANG
	* @param object|null $ddo = null
	*
	* @return dd_grid_cell_object $dd_grid_cell_object
	*/
		// public function get_value(string $lang=DEDALO_DATA_LANG, object $ddo=null) : dd_grid_cell_object {

		// 	$dd_grid_cell_object = parent::get_value($lang, $ddo);

		// 	// map values to JOSN to allow render it in list
		// 		if (!empty($dd_grid_cell_object->value)) {
		// 			$dd_grid_cell_object->value = array_map(function($el){
		// 				return json_encode($el);
		// 			}, $dd_grid_cell_object->value);
		// 		}


		// 	return $dd_grid_cell_object;
		// }//end get_value



	/**
	* GET_DIFFUSION_VALUE
	* Overwrite component common method
	* Calculate current component diffusion value for target field (usually a MYSQL field)
	* Used for diffusion_mysql to unify components diffusion value call
	* @return string|null $diffusion_value
	*
	* @see class.diffusion_mysql.php
	*/
	public function get_diffusion_value( ?string $lang=null, ?object $option_obj=null ) : ?string {

		$diffusion_value = null;

		$dato = $this->get_dato();
		if (empty($dato)) {
			return $diffusion_value;
		}

		$value = is_array($dato) ? reset($dato) : $dato;
		$diffusion_value = !empty($value)
			? json_encode($value)
			: null;

		return $diffusion_value;
	}//end get_diffusion_value



	/**
	* BUILD_GEOLOCATION_TAG_STRING
	* Example
	* [geo-n-1-data:{'type':'FeatureCollection','features':[{'type':'Feature','properties':{},'geometry':{'type':'Point','coordinates':[2.304362542927265,41.82053505145308]}}]}:data]
	* {
	*	"type": "FeatureCollection",
	*	"features": [
	*	    {
	*	      "type": "Feature",
	*	      "properties": {},
	*	      "geometry": {
	*	        "type": "Point",
	*	        "coordinates": [
	*	          2.304362542927265,
	*	          41.82053505145308
	*	        ]
	*	      }
	*	    }
	*	]
	* }
	*
	* @return string $result
	*/
	public static function build_geolocation_tag_string(string $tag_id, $lon, $lat) : string {
		/*
		$geometry = new stdClass();
			$geometry->type 		= "Point";
			$geometry->coordinates 	= array($lon, $lat)

		$feature = new stdClass();
			$feature->type 		 = "Feature";
			$feature->properties = new stdClass();
			$feature->geometry 	 = $geometry

		$data = new stdClass();
			$data->type 	= 'FeatureCollection';
			$data->features = array( $feature );
		*/
		$result = "[geo-n-".$tag_id."-data:{'type':'FeatureCollection','features':[{'type':'Feature','properties':{},'geometry':{'type':'Point','coordinates':[".$lon.",".$lat."]}}]}:data]";

		return (string)$result;
	}//end build_geolocation_tag_string



	/**
	* GET_DIFFUSION_VALUE_SOCRATA
	* Calculate current component diffusion value for target field in socrata
	* Used for diffusion_mysql to unify components diffusion value call to publish in socrata
	* @return object $diffusion_value_socrata
	*
	* @see class.diffusion_mysql.php
	*/
	public function get_diffusion_value_socrata() : object {

		$dato 			= $this->get_dato();
		$socrata_data 	= 'POINT ('.$dato->lat.', '.$dato->lon.')';

		# {
		#   "type": "Point",
		#   "coordinates": [
		#     -87.653274,
		#     41.936172
		#   ]
		# }

		$geo_json_point = new stdClass();
			$geo_json_point->type 		 = 'Point';
			$geo_json_point->coordinates = [
				floatval($dato->lon),
				floatval($dato->lat)
			];

		#$point = new stdClass();
		#	$point->latitude  = 47.59815;
		#	$point->longitude = -122.334540;


		$diffusion_value_socrata = $geo_json_point;// json_encode($geo_json_point, JSON_UNESCAPED_SLASHES); // json_encode($socrata_data, JSON_UNESCAPED_SLASHES);

		return $diffusion_value_socrata;
	}//end get_diffusion_value_socrata



	/**
	* GET_SORTABLE
	* @return bool
	* 	Default is true. Override when component is sortable
	*/
	public function get_sortable() : bool {

		return false;
	}//end get_sortable



}//end class component_geolocation
