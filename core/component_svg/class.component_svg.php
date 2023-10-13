<?php
/**
* CLASS COMPONENT_SVG
* Manage specific component input text logic
* Common components properties and method are inherited of component_common class that are inherited from common class
*/
class component_svg extends component_media_common {



	/**
	* CLASS VARS
	*/



	/**
	* GET_AR_QUALITY
	* Get the list of defined image qualities in Dédalo config
	* @return array $ar_image_quality
	*/
	public function get_ar_quality() : array {

		$ar_quality = DEDALO_SVG_AR_QUALITY;

		return $ar_quality;
	}//end get_ar_quality



	/**
	* GET_DEFAULT_QUALITY
	* @return string
	*/
	public function get_default_quality() : string {

		return DEDALO_SVG_QUALITY_DEFAULT;
	}//end get_default_quality



	/**
	* GET_ORIGINAL_QUALITY
	* @return $original_quality
	*/
	public function get_original_quality() : string {

		$original_quality = DEDALO_SVG_QUALITY_ORIGINAL;

		return $original_quality;
	}//end get_original_quality



	/**
	* GET_EXTENSION
	* @return string DEDALO_SVG_EXTENSION from config
	*/
	public function get_extension() : string {

		return $this->extension ?? DEDALO_SVG_EXTENSION;
	}//end get_extension



	/**
	* GET_ALLOWED_EXTENSIONS
	* @return array $allowed_extensions
	*/
	public function get_allowed_extensions() : array {

		$allowed_extensions = DEDALO_SVG_EXTENSIONS_SUPPORTED;

		return $allowed_extensions;
	}//end get_allowed_extensions



	/**
	* GET_FILE_CONTENT
	* Get the SVG file data as text
	* @return string|null $file_content
	*/
	public function get_file_content() : ?string {

		$file_path		= $this->get_media_filepath();
		$file_content	= (file_exists($file_path))
			? file_get_contents($file_path)
			: null;

		return $file_content;
	}//end get_file_content



	/**
	* GET_DEFAULT_SVG_URL
	* @return string $url
	*/
	public static function get_default_svg_url() : string {

		$url = DEDALO_CORE_URL . '/themes/default/upload.svg';

		return $url;
	}//end get_default_svg_url



	/**
	* GET_URL
	* Get image url for current quality
	* @param string $quality = null
	*	optional default (bool)false
	* @param bool $test_file = true
	*	Check if file exists. If not use 0.jpg as output. Default true
	* @param bool $absolute = false
	* @param bool $default_add = true
	* @return string|null $image_url
	*	Return relative o absolute url. Default false (relative)
	*/
	public function get_url(?string $quality=null, bool $test_file=false, bool $absolute=false, bool $default_add=true) : ?string {

		// quality fallback to default
			if(empty($quality)) {
				$quality = $this->get_quality();
			}

		// image id
			$image_id = $this->get_id();

		// url
			$additional_path	= $this->additional_path;
			$initial_media_path	= $this->get_initial_media_path();
			$folder				= $this->get_folder(); // like DEDALO_SVG_FOLDER
			$extension			= $this->get_extension();
			$file_name			= $image_id .'.'. $extension;

			$image_url = DEDALO_MEDIA_URL . $folder . $initial_media_path . '/' . $quality . $additional_path . '/' . $file_name;

		// File exists test : If not, show '0' dedalo image logo
			if($test_file===true) {
				$file = $this->get_media_filepath($quality);
				if(!file_exists($file)) {
					if ($default_add===false) {
						return null;
					}
					$image_url = DEDALO_CORE_URL . '/themes/default/icons/dedalo_icon_grey.svg';
				}
			}

		// Absolute (Default false)
			if ($absolute===true) {
				$image_url = DEDALO_PROTOCOL . DEDALO_HOST . $image_url;
			}

		return $image_url;
	}//end get_url



	/**
	* GET_URL_FROM_LOCATOR
	* @param object $locator
	* @return string|null $url
	*/
	public static function get_url_from_locator(object $locator) : ?string {

		$model		= RecordObj_dd::get_modelo_name_by_tipo($locator->component_tipo,true);
		$component	= component_common::get_instance(
			$model,
			$locator->component_tipo,
			$locator->section_id,
			'list',
			DEDALO_DATA_NOLAN,
			$locator->section_tipo
		);
		$url = $component->get_url();

		return $url;
	}//end get_url_from_locator



	/**
	* GET_PREVIEW_URL
	* @return string $url
	*/
	public function get_preview_url() : string {

		$preview_url = $this->get_url(
			null,  // string|null quality
			false, // bool test_file
			false, // bool absolute
			false // bool default_add
		);

		return $preview_url;
	}//end get_preview_url



	/**
	* GET_FOLDER
	* 	Get element directory from config
	* @return string
	*/
	public function get_folder() : string {

		return $this->folder ?? DEDALO_SVG_FOLDER;
	}//end get_folder



	/**
	* PROCESS_UPLOADED_FILE
	* Note that this is the last method called in a sequence started on upload file.
	* The sequence order is:
	* 	1 - dd_utils_api::upload
	* 	2 - tool_upload::process_uploaded_file
	* 	3 - component_media_common::add_file
	* 	4 - component:process_uploaded_file
	* The target quality is defined by the component quality set in tool_upload::process_uploaded_file
	* @param object $file_data
	*	Data from trigger upload file
	* Format:
	* {
	*     "original_file_name": "my_file.svg",
	*     "full_file_name": "test81_test65_2.svg",
	*     "full_file_path": "/mypath/media/svg/standard/test81_test65_2.svg"
	* }
	* @return object $response
	*/
	public function process_uploaded_file(object $file_data) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__METHOD__.'] ';

		// short vars
			$original_file_name			= $file_data->original_file_name;	// kike "my file785.svg"
			$full_file_path				= $file_data->full_file_path;		// like "/mypath/media/svg/standard/test81_test65_2.svg"
			$full_file_name				= $file_data->full_file_name;		// like "test175_test65_1.svg"
			$original_normalized_name	= $full_file_name;

		// debug
			debug_log(__METHOD__
				. " process_uploaded_file " . PHP_EOL
				. ' original_file_name: ' . $original_file_name .PHP_EOL
				. ' full_file_path: ' . $full_file_path
				, logger::WARNING
			);

		try {

			// extension
				$file_ext = pathinfo($original_file_name, PATHINFO_EXTENSION);
				if (empty($file_ext)) {
					// throw new Exception("Error Processing Request. File extension is unknown", 1);
					$response->msg .= ' Error Processing Request. File extension is unknown';
					debug_log(__METHOD__
						. ' '.$response->msg
						, logger::ERROR
					);
					return $response;
				}

			// id (without extension, like 'test81_test65_2')
				$id = $this->get_id();
				if (empty($id)) {
					$response->msg .= ' Error Processing Request. Invalid id';
					debug_log(__METHOD__
						. ' '.$response->msg
						, logger::ERROR
					);
					return $response;
				}

			// copy from original to default quality
				$original_file_path			= $full_file_path;
				$default_quality			= $this->get_default_quality();
				$default_quality_file_path	= $this->get_media_filepath($default_quality);
				if ($original_file_path===$default_quality_file_path) {
					debug_log(__METHOD__
						. " File is already in default quality " . PHP_EOL
						. ' Nothing is moved '
						, logger::WARNING
					);
				}else{

					// target directory check
						$target_dir = dirname($default_quality_file_path);
						if (!is_dir($target_dir)) {
							if(!mkdir($target_dir, 0750, true)) {
								debug_log(__METHOD__
									.' Error creating directory: ' . PHP_EOL
									.' target_dir: ' . $target_dir
									, logger::ERROR
								);
								$response->msg .= ' Error creating directory';
								debug_log(__METHOD__
									. ' '.$response->msg
									, logger::ERROR
								);
								return $response;
							}
						}

					// copy file
						if (!copy($original_file_path, $default_quality_file_path)) {
							debug_log(__METHOD__
								. " Error on copy original file to default quality file " . PHP_EOL
								. 'original_file_path: ' .$original_file_path .PHP_EOL
								. 'default_quality_file_path: ' .$default_quality_file_path
								, logger::ERROR
							);
							$response->msg = 'Error on copy original file to default quality file';
							return $response;
						}
				}

			// upload info
				$original_quality = $this->get_original_quality();
				if ($this->quality===$original_quality) {
					// update upload file info
					$dato = $this->get_dato();
					$key = 0;
					if (!isset($dato[$key])) {
						$dato[$key] = new stdClass();
					}
					$dato[$key]->original_file_name			= $original_file_name;
					$dato[$key]->original_normalized_name	= $original_normalized_name;
					$dato[$key]->original_upload_date		= component_date::get_date_now();

					$this->set_dato($dato);
				}

			// save component dato
				// Note that save action don't change upload info properties,
				// but force updates every quality file info in property 'files_info
				$this->Save();

			// response OK
				$response->result	= true;
				$response->msg		= 'OK. successful request';

		} catch (Exception $e) {
			$msg = 'Exception[process_uploaded_file]: ' .  $e->getMessage() . "\n";
			debug_log(__METHOD__
				." $msg "
				, logger::ERROR
			);
			$response->msg .= ' - '.$msg;
		}


		return $response;
	}//end process_uploaded_file



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

		// short vars
			$update_version	= implode('.', $options->update_version);
			$dato_unchanged	= $options->dato_unchanged;
			$reference_id	= $options->reference_id;

		switch ($update_version) {

			case '6.0.0':
				$is_old_dato = (
					empty($dato_unchanged) || // v5 early case
					isset($dato_unchanged->section_id) || // v5 modern case
					(isset($dato_unchanged[0]) && isset($dato_unchanged[0]->original_file_name)) // v6 alpha case
				);
				// $is_old_dato = true; // force here
				if ($is_old_dato===true) {

					// create the component svg
						$model		= RecordObj_dd::get_modelo_name_by_tipo($options->tipo,true);
						$component	= component_common::get_instance(
							$model, // string 'component_svg'
							$options->tipo,
							$options->section_id,
							'list',
							DEDALO_DATA_NOLAN,
							$options->section_tipo,
							false
						);

					// get existing files data
						$file_name			= $component->get_name();
						$folder				= $component->get_folder();
						$source_quality		= $component->get_original_quality();
						$additional_path	= $component->additional_path;
						$initial_media_path	= $component->initial_media_path;
						$original_extension	= $component->get_original_extension(
							false // bool exclude_converted
						) ?? $component->get_extension(); // 'svg' fallback is expected

						$base_path	= $folder . $initial_media_path . '/' . $source_quality . $additional_path;
						$file		= DEDALO_MEDIA_PATH . $base_path . '/' . $file_name . '.' . $original_extension;

						// no original file found. Use default quality file
							if(!file_exists($file)) {
								// use default quality as original
								$source_quality	= $component->get_default_quality();
								$base_path		= $folder . $initial_media_path . '/' . $source_quality . $additional_path;
								$file			= DEDALO_MEDIA_PATH . $base_path . '/' . $file_name . '.' . $component->get_extension();
							}
							// try again
							if(!file_exists($file)) {
								// reset bad dato
								$response = new stdClass();
									$response->result	= 1;
									$response->new_dato	= null;
									$response->msg		= "[$reference_id] Dato is changed from ".to_string($dato_unchanged)." to ".to_string(null).".<br />";
								// $response = new stdClass();
								// 	$response->result	= 2;
								// 	$response->msg		= "[$reference_id] Current dato don't need update. No files found (original,default)<br />";	// to_string($dato_unchanged)."
								return $response;
							}

					// source_file_upload_date
						$upload_date_timestamp				= date ("Y-m-d H:i:s", filemtime($file));
						$source_file_upload_date			= dd_date::get_dd_date_from_timestamp($upload_date_timestamp);
						$source_file_upload_date->time		= dd_date::convert_date_to_seconds($source_file_upload_date);
						$source_file_upload_date->timestamp	= $upload_date_timestamp;

					// get the source file name
						$source_file_name = pathinfo($file)['basename'];

					// lib_data
						$lib_data = null;

					// get files info
						$files_info	= [];
						$ar_quality = DEDALO_SVG_AR_QUALITY;
						foreach ($ar_quality as $current_quality) {
							if ($current_quality==='thumb') continue;
							// read file if exists to get file_info
							$file_info = $component->get_quality_file_info($current_quality);
							// add non empty quality files data
							if (!empty($file_info)) {
								// Note that source_quality could be original or default
								if ($current_quality===$source_quality) {
									$file_info->upload_info = (object)[
										'file_name'	=> $source_file_name ?? null,
										'date'		=> $source_file_upload_date ?? null,
										'user'		=> null // unknown here
									];
								}
								// add
								$files_info[] = $file_info;
							}
						}

					// create new dato
						$dato_item = (object)[
							'files_info'	=> $files_info,
							'lib_data'		=> $lib_data
						];

					// fix final dato with new format as array
						$new_dato = [$dato_item];
						debug_log(__METHOD__." update_version new_dato ".to_string($new_dato), logger::DEBUG);

					$response = new stdClass();
						$response->result	= 1;
						$response->new_dato	= $new_dato;
						$response->msg		= "[$reference_id] Dato is changed from ".to_string($dato_unchanged)." to ".to_string($new_dato).".<br />";

					// clean vars
						unset($source_file_upload_date);
						unset($files_info);
						unset($lib_data);
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
		}//end switch ($update_version)


		return $response;
	}//end update_dato_version



}//end class component_svg
