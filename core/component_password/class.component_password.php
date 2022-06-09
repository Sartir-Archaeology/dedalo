<?php
/**
* CLASS COMPONENT PASSWORD
*
*
*/
class component_password extends component_common {



	// Overwrite __construct var lang passed in this component
	protected $lang = DEDALO_DATA_NOLAN;



	/**
	* GET_DATO
	* @return array|null $dato
	*/
	public function get_dato() {

		$dato = parent::get_dato();
		if (!is_array($dato)) {
			$dato = [$dato];
		}

		return (array)$dato;
	}//end get_dato



	/**
	* SET_DATO
	* @param array|null $dato
	* (!) do not encrytp this var
	*/
	public function set_dato($dato) {

		parent::set_dato( (array)$dato );
	}//end set_dato



	/**
	* GET_VALOR
	* Return array dato as comma separated elements string by default
	* If index var is received, return dato element corresponding to this index if exists
	* @return string $valor
	*/
	public function get_valor($index='all' ) {

		$valor ='';

		$dato = $this->get_dato();
		if(empty($dato)) {
			return (string)$valor;
		}

		if ($index==='all') {
			$ar = array();
			foreach ($dato as $key => $value) {
				$value = trim($value);
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

		return (string)$valor;
	}//end get_valor



	/**
	* SAVE OVERRIDE
	* Overwrite component_common method to set always lang to config:DEDALO_DATA_NOLAN before save
	* @return int|null
	*/
	public function Save() : ?int {

 		if(isset($this->updating_dato) && $this->updating_dato===true) {
			# Dato is saved plain (unencrypted) only for updates
		}else{
			# Encrypt dato with md5 etc..
			$dato = $this->dato;
			foreach ((array)$dato as $key => $value) {
				# code...
				$this->dato[$key] = component_password::encrypt_password($value);		#dump($dato,'dato md5');
			}
		}

		// From here, we save as standard
		return parent::Save();
	}//end Save



	// GET EJEMPLO
		// protected function get_ejemplo() {
		//
		// 	if($this->ejemplo===false) return "example: 'Kp3Myuser9Jt1'";
		// 	return parent::get_ejemplo();
		// }



	/**
	* ENCRYPT_PASSWORD
	*
	* Crypto password
	# Change the mycript lib to OpenSSL in the 4.0.22 update
	# we need the to encriptors for sustain the login of the user before the update to 4.0.22
	# this function will be change to only Open SSl in the 4.5.
	*/
	public static function encrypt_password($stringArray) {

		$encryption_mode = encryption_mode();

		if( $encryption_mode==='openssl' ) {
			return dedalo_encrypt_openssl($stringArray, DEDALO_INFORMACION);
		}else if($encryption_mode==='mcrypt') {
			return dedalo_encryptStringArray($stringArray, DEDALO_INFORMACION);
		}else{
			debug_log(__METHOD__." UNKNOW ENCRYPT MODE !! ".to_string(), logger::ERROR);
		}

		return false;
	}//end encrypt_password



	/**
	* UPDATE_DATO_VERSION
	* @return object
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

			$update_version = $options->update_version;
			$dato_unchanged = $options->dato_unchanged;
			$reference_id 	= $options->reference_id;


		$update_version = implode(".", $update_version);

		switch ($update_version) {
			case '4.0.22':
				#$dato = $this->get_dato_unchanged();

				$section_id = explode('.', $reference_id)[1];
				if((int)$section_id === -1){

					$default = dedalo_decryptStringArray($dato_unchanged, DEDALO_INFORMACION);

					$section = section::get_instance( -1, DEDALO_SECTION_USERS_TIPO );
					$dato = $section->get_dato();
					$tipo = DEDALO_USER_PASSWORD_TIPO;
					$lang = DEDALO_DATA_NOLAN;

					$dato->components->$tipo->dato->$lang = $dato->components->$tipo->valor->$lang = dedalo_encrypt_openssl($default);

					$strQuery 	= "UPDATE matrix_users SET datos = $1 WHERE section_id = $2 AND section_tipo = $3";
					$result 	= pg_query_params(DBi::_getConnection(), $strQuery, array( json_handler::encode($dato), -1, DEDALO_SECTION_USERS_TIPO ));
					if(!$result) {
						if(SHOW_DEBUG) {
							dump($strQuery,"strQuery");
						}
						throw new Exception("Error Processing Save Update Request ". pg_last_error(), 1);;
					}

					$response = new stdClass();
					$response->result = 2;
					$response->msg = "[$reference_id] Dato change for root.<br />";	// to_string($dato_unchanged)."
					return $response;
				}

				# Compatibility old dedalo instalations
				if (!empty($dato_unchanged) && is_string($dato_unchanged)) {

					$old_pw = dedalo_decryptStringArray($dato_unchanged, DEDALO_INFORMACION);
					$new_dato = dedalo_encrypt_openssl($old_pw, DEDALO_INFORMACION);

					debug_log(__METHOD__." changed pw from $dato_unchanged - $new_dato ".to_string($old_pw), logger::DEBUG);

					$response = new stdClass();
					$response->result =1;
					$response->new_dato = $new_dato;
					$response->msg = "[$reference_id] Dato is changed from ".to_string($dato_unchanged)." to ".to_string($new_dato).".<br />";
					return $response;

				}else{
					$response = new stdClass();
					$response->result = 2;
					$response->msg = "[$reference_id] Current dato don't need update.<br />";	// to_string($dato_unchanged)."
					return $response;
				}
		}
	}//end update_dato_version



}//end class component_password
