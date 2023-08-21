<?php
/**
* CLASS COMPONENT_JSON
*
*
*/
class component_json extends component_common {



	/**
	* __CONSTRUCT
	*/
	protected function __construct(string $tipo=null, $parent=null, string $mode='list', string $lang=DEDALO_DATA_NOLAN, string $section_tipo=null, bool $cache=true) {

		// Force always DEDALO_DATA_NOLAN
		$this->lang = DEDALO_DATA_NOLAN;

		parent::__construct($tipo, $parent, $mode, $this->lang, $section_tipo, $cache);
	}//end __construct



	/**
	* GET_DATO
	* @return array|null $dato
	*/
	public function get_dato() : ?array {

		$dato = parent::get_dato();

		// OLD
			// if(!empty($dato) && !is_array($dato)) {
			// 	try {

			// 		$data_string = !is_string($dato)
			// 			? json_encode($dato)
			// 			: $dato;

			// 		$data_object = json_decode($data_string);
			// 		$new_data = ($data_object)
			// 			? [$data_object]
			// 			: [];

			// 	} catch (Exception $e) {
			// 		debug_log(__METHOD__
			// 			. " Exception on read dato. Applying default data: [] " . PHP_EOL
			// 			. ' exception: ' . $e->getMessage()
			// 			, logger::ERROR
			// 		);
			// 		$new_data = [];
			// 	}

			// 	$dato = $new_data;

			// 	// update
			// 	$this->set_dato($dato);
			// 	$this->Save();
			// }

		if (!is_null($dato) && !is_array($dato)) {
			$type = gettype($dato);
			debug_log(__METHOD__
				. " Expected dato type array or null, but type is: $type. Converted to array and saving " . PHP_EOL
				. ' tipo: ' . $this->tipo . PHP_EOL
				. ' section_tipo: ' . $this->section_tipo . PHP_EOL
				. ' section_id: ' . $this->section_id
				, logger::ERROR
			);
			dump($dato, ' dato ++ '.to_string());

			$dato = !empty($dato)
				? [$dato]
				: null;

			dump($dato, ' dato_to_save ++ '.to_string());

			// update
			$this->set_dato($dato);
			$this->Save();
		}


		return $dato;
	}//end get_dato



	/**
	* SET_DATO
	* @return bool
	*/
	public function set_dato($dato) : bool {

		if (!empty($dato)) {

			if (is_string($dato)) {
				if (!$dato = json_decode($dato)) {
					trigger_error("Error. Only valid JSON is accepted as dato");
					return false;
				}
			}

			if(!is_object($dato) && !is_array($dato)) {
				trigger_error("Error. Stopped set_dato because is not as expected object. ". gettype($dato));
				return false;
			}
		}

		return parent::set_dato( $dato );
	}//end set_dato



	/**
	* GET_VALOR
	*/
	public function get_valor() {
		$dato  = $this->get_dato();
		//$valor = json_encode($dato);

		$valor = $dato;

		return $valor;
	}//end get_valor



	/**
	* GET_ALLOWED_EXTENSIONS
	* @return array $allowed_extensions
	*/
	public function get_allowed_extensions() {

		$allowed_extensions = ['json'];

		return $allowed_extensions;
	}//end get_allowed_extensions



	/**
	* GET_DIFFUSION_VALUE
	* Calculate current component diffusion value for target field (usually a MYSQL field)
	* Used for diffusion_mysql to unify components diffusion value call
	* @return string $diffusion_value
	*
	* @see class.diffusion_mysql.php
	*/
	public function get_diffusion_value( ?string $lang=null, ?object $option_obj=null ) : ?string {

		# Default behavior is get value
		$dato = $this->get_dato();

		$value = $dato[0] ?? null;
		if (is_string($value)) {
			// do not encode here
			debug_log(__METHOD__
				. ' Expected value type is NOT string ' . PHP_EOL
				. ' type ' . gettype($value) . PHP_EOL
				. ' value: ' . to_string($value)
				, logger::WARNING
			);
		}else{
			$value = json_handler::encode($value);
		}

		// diffusion_value
		$diffusion_value = !empty($value)
			? $value
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
			$options->update_version 	= null;
			$options->dato_unchanged 	= null;
			$options->reference_id 		= null;
			$options->tipo 				= null;
			$options->section_id 		= null;
			$options->section_tipo 		= null;
			$options->context 			= 'update_component_dato';
			foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}

			$update_version	= $options->update_version;
			$dato_unchanged	= $options->dato_unchanged;
			$reference_id	= $options->reference_id;


		$update_version = implode(".", $update_version);
		switch ($update_version) {

			case '6.0.0':
				if (!empty($dato_unchanged) && is_string($dato_unchanged)) {
					// update search presets of component_json (temp and user presets has the same component_tipo)
					if($options->tipo==='dd625'){
						// replace the sqo of search to new component models for v6
						$dato_unchanged = str_replace(
							[
								'"modelo"',
								'"component_autocomplete"',
								'"component_autocomplete_hi"',
								'"component_input_text_large"',
								'"component_html_text"'
							],
							[
								'"model"',
								'"component_portal"',
								'"component_portal"',
								'"component_text_area"',
								'"component_text_area"'
							],
							$dato_unchanged);
					}

					// decode old string data to json
					$new_dato = json_decode($dato_unchanged);
					$new_dato = [$new_dato];

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
					$response->msg		= "This component ".get_called_class()." don't have update to this version ($update_version). Ignored action";
				break;
		}


		return $response;
	}//end update_dato_version



	/**
	* ADD_FILE
	* Receive a file info object from tool upload with data properties as:
	* {
	* 	"name": "mydata.json",
	*	"type": "text/json",
	*	"tmp_name": "/private/var/tmp/php6nd4A2",
	*	"error": 0,
	*	"size": 132898
	* }
	* @return object $response
	*/
	public function add_file($file_data) {

		$response = new stdClass();
			$response->result 	= false;
			$response->msg 		= 'Error. Request failed ['.__METHOD__.'] ';

		// file info
			$file_extension = strtolower(pathinfo($file_data->name, PATHINFO_EXTENSION));

		// validate extension
			$allowed_extensions = $this->get_allowed_extensions();
			if (!in_array($file_extension, $allowed_extensions)) {
				$response->msg  = "Error: " .$file_extension. " is an invalid file type ! ";
				$response->msg .= "Allowed file extensions are: ". implode(', ', $allowed_extensions);
				return $response;
			}

		// read the uploaded file
			$file_content = file_get_contents($file_data->tmp_name);

		// remove it after store
			unlink($file_data->tmp_name);

		// read content
			if ($value = json_decode($file_content)) {

				// uploaded ready file info
				$response->ready 	= (object)[
					'imported_parsed_data' => $value
				];

			}else{

				$response->msg  = "Error: " .$file_data->name. " content is an invalid json data";
				return $response;
			}

		// all is ok
			$response->result 	= true;
			$response->msg 		= 'Ok. Request done ['.__METHOD__.'] ';


		return $response;
	}//end add_file



	/**
	* PROCESS_UPLOADED_FILE
	* @return object $response
	*/
	public function process_uploaded_file(object $file_data) : object {

		$response = new stdClass();
			$response->result 	= false;
			$response->msg 		= 'Error. Request failed ['.__METHOD__.'] ';

		// imported_data. (Is JSON decoded data from raw uploaded file content)
			$imported_data = $file_data->imported_parsed_data;

		// wrap data with array to maintain component data format
			$dato = [$imported_data];
			$this->set_dato($dato);

		// save full dato
			$this->Save();

		$response = new stdClass();
			$response->result 	= true;
			$response->msg 		= 'OK. Request done';

		return $response;
	}//end process_uploaded_file



}//end class component_json
