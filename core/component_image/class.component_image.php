<?php
/**
* CLASS COMPONENT IMAGE
*
*/
class component_image extends component_media_common {



	/**
	* CLASS VARS
	*/
		// id . file name formatted as 'tipo'-'order_id' like dd732-1
		public $id;
		public $image_url;

		public $external_source;

		// Default image dimensions (as showed in section edit)
		public $width	= 539;
		public $height	= 404;



	/**
	* __CONSTRUCT
	*/
	protected function __construct(string $tipo, $section_id, string $mode='list', string $lang=DEDALO_DATA_NOLAN, string $section_tipo=null) {

		// common constructor. Creates the component as normally do with parent class
			parent::__construct($tipo, $section_id, $mode, $lang, $section_tipo);

		// fix component main properties
			if (!empty($this->section_id)) {

				// additional_path : set and fix current additional image path
					$this->external_source = $this->get_external_source();
			}
	}//end __construct



	/**
	* SAVE
	* @return int|null $result
	* 	section_id
	*/
	public function Save() : ?int {

		$dato = $this->dato;

		// create_svg_file from dato item temporal container
		if (!empty($dato)) {
			foreach ($dato as $dato_item) {
				if(isset($dato_item->svg_file_data)) {
					$this->create_svg_file($dato_item->svg_file_data);
					// remove property, its only temporal
					unset($dato_item->svg_file_data);
				}
			}
		}

		$this->dato = $dato;

		$result = parent::Save();

		return $result;
	}//end Save



	/**
	* GET_INITIAL_MEDIA_PATH
	* @return string $this->initial_media_path
	*/
	public function get_initial_media_path() : string {

		$component_tipo		= $this->tipo;
		$parent_section		= $this->get_my_section();
		$properties			= $parent_section->get_properties();

		if (isset($properties->initial_media_path->$component_tipo)) {
			$this->initial_media_path = $properties->initial_media_path->$component_tipo;
			// Add / at begin if not exits
			if ( substr($this->initial_media_path, 0, 1) != '/' ) {
				$this->initial_media_path = '/'.$this->initial_media_path;
			}
		}else{
			$this->initial_media_path = false;
		}

		return $this->initial_media_path;
	}//end get_initial_media_path



	/**
	* GET_IMAGE ID
	* By default it's built with the type of the current component_image and the order number, eg. 'dd20_rsc750_1'
	* It can be overwritten in properties with json ex. {"id": "dd851"} and will be read from the content of the referenced component
	* @return string|null $id
	*/
	public function get_id() : ?string {

		// already set
			if(isset($this->id) && !empty($this->id)) {
				return $this->id;
			}

		// case 1 external source
			$external_source = $this->get_external_source();
			$id = !empty($external_source)
				? pathinfo($external_source)['filename']
				: null;
			if(!empty($id)){
				$this->id = $id;
				return $id;
			}

		// case 2 referenced name : If is set properties "image_id" overwrite name with field ddx content
			$properties = $this->get_properties();
			if(isset($properties->image_id)){
				$component_tipo	= $properties->image_id;
				$model			= RecordObj_dd::get_modelo_name_by_tipo($component_tipo, true);
				$component		= component_common::get_instance(
					$model,
					$component_tipo,
					$this->section_id,
					'edit',
					DEDALO_DATA_NOLAN,
					$this->section_tipo
				);
				$valor	= trim($component->get_valor());
				$id		= (!empty($valor) && strlen($valor)>0)
					? $valor
					: null;
				if(!empty($id)){
					$this->id = $id;
					return $id;
				}
			}

		// fallback default
			if (empty($id)) {
				// $id = $this->tipo.'_'.$this->section_tipo.'_'.$this->parent;
				// flat locator as id
				$locator = new locator();
					$locator->set_section_tipo($this->get_section_tipo());
					$locator->set_section_id($this->get_section_id());
					$locator->set_component_tipo($this->get_tipo());

				if (empty($locator->section_id)) {
					debug_log(__METHOD__." Error. Invalid locator with empty section_id ".to_string(), logger::ERROR);
					$id = null;
				}else{
					$id = $locator->get_flat();
				}
			}

		// fix value
			$this->id = $id;


		return $id;
	}//end get_id



	/**
	* GET_ADDITIONAL_PATH
	* Calculate image additional path from 'properties' json config.
	* @return string $additional_path
	*/
	public function get_additional_path() : string {

		// already set case
			if(isset($this->additional_path)) {
				return $this->additional_path;
			}

		// default value
			$additional_path = false;

		$properties				= $this->get_properties();
		$additional_path_tipo	= $properties->additional_path ?? null;
		$section_id				= $this->get_section_id();
		if ( !is_null($additional_path_tipo) && !empty($section_id) ) {

			switch (true) {

				case (is_string($additional_path_tipo)):

					$component_tipo	= $additional_path_tipo;
					$model			= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);
					$component		= component_common::get_instance(
						$model,
						$component_tipo,
						$section_id,
						'edit',
						DEDALO_DATA_NOLAN,
						$this->get_section_tipo()
					);

					// valor
						$valor = trim($component->get_valor());

					// Add a slash at the beginning if it doesn't already exist
						if ( substr($valor, 0, 1)!=='/' ) {
							$valor = '/'.$valor;
						}

					// Remove the trailing slash if it exists
						if ( substr($valor, -1)==='/' ) {
							$valor = substr($valor, 0, -1);
						}

					if(empty($valor) && isset($properties->max_items_folder)) {

						// max_items_folder defined case
							$max_items_folder	= $properties->max_items_folder;
							$int_section_id		= (int)$section_id;

						// add
							$additional_path = '/'.$max_items_folder*(floor($int_section_id / $max_items_folder));

						// update component dato. Final dato must be an array to saved into component_input_text
							$final_dato = array( $additional_path );
							$component->set_dato( $final_dato );

						// save if mode is edit
							if ($this->mode==='edit') {
								$component->Save();
							}
					}else{
						// add
							$additional_path = $valor;
					}
					break;

				/*
				case (is_object($additional_path) ):
					//dump(gettype($additional_path),'$additional_path');
					if(isset($additional_path->max_items_folder)){
						$max_items_folder = $additional_path->max_items_folder;
						$section_id = $this->parent;

						$ar_additional_path[$this->id] = '/'.$max_items_folder*(floor($section_id_section_id / $max_items_folder));
					}

					break;
				*/
			}//end switch (true)
		}

		// fix value
			$this->additional_path = $additional_path;


		return $additional_path;
	}//end get_additional_path




	/**
	* GET_URL
	* Get image url for current quality
	*
	* @param string|bool $quality = null
	* @param bool $test_file = true
	*	Check if file exists. If not use 0.jpg as output
	* @param bool $absolute = false
	* @param bool $default_add = true
	*
	* @return string|null $url
	*	Return relative o absolute url
	*/
	public function get_url(?string $quality=null, bool $test_file=false, bool $absolute=false, bool $default_add=false) : ?string {

		// quality fallback to default
			if(empty($quality)) {
				$quality = $this->get_quality();
			}

		// external source (link to image outside Dédalo media)
			$external_source = $this->get_external_source();
			if(!empty($external_source)){
				$url = $external_source;
				return $url;
			}

		// image id
			$id = $this->get_id();

		// url
			$url = $this->get_media_url_dir($quality) .'/'. $id .'.'. $this->get_extension();

		// File exists test : If not, show '0' dedalo image logo
			if($test_file===true) {
				$file = $this->get_media_filepath($quality);
				if(!file_exists($file)) {
					if ($default_add===false) {
						return null;
					}
					$url = DEDALO_CORE_URL . '/themes/default/0.jpg';
				}
			}

		// Absolute (Default false)
			if ($absolute===true) {
				$url = DEDALO_PROTOCOL . DEDALO_HOST . $url;
			}


		return $url;
	}//end get_url



	/**
	* GET_EXTERNAL_SOURCE
	* @return string|null $external_source
	*/
	public function get_external_source() : ?string {

		$properties = $this->get_properties();
		if (isset($properties->external_source) && !empty($this->section_id)) {
			$component_tipo 	= $properties->external_source;
			$component_model 	= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);

			$component 	= component_common::get_instance(
				$component_model,
				$component_tipo,
				$this->section_id,
				'edit',
				DEDALO_DATA_NOLAN,
				$this->section_tipo
			);

			$dato = $component->get_dato();
			if(!empty($dato) && is_array($dato)){
				$dato = reset($dato);
			}
			// used to change the iri of the image, don't use it as dataframe section
			// only control if the uri is internal or external.
			if(!empty($dato->dataframe)){
				if(isset($dato->iri) && !empty($dato->iri)) {

					// external_source get from iri
					$external_source = $dato->iri;
				}
			}
		}//end if (isset($properties->external_source) && !empty($this->get_parent()) )


		return $external_source ?? null;
	}//end get_external_source



	/**
	* GET_DEFAULT_QUALITY
	*/
	public function get_default_quality() : string {

		return DEDALO_IMAGE_QUALITY_DEFAULT;
	}//end get_default_quality



	/**
	* GET_TARGET_FILENAME
	* Upload needed
	* @return string $target_filename
	*/
	public function get_target_filename() : string {

		if($this->external_source) {

			$external_parts		= pathinfo($this->external_source);
			$target_filename	= $external_parts['basename'];

		}else{

			$target_filename = $this->id .'.'. $this->get_extension();
		}


		return $target_filename;
	}//end get_target_filename



	/**
	* GET_TARGET_DIR
	* @param string|null $quality
	* @return string $target_dir
	*/
		// public function get_target_dir(?string $quality) : string {

		// 	if(empty($quality)) {
		// 		$quality = $this->get_quality();
		// 	}

		// 	$target_dir = $this->get_media_path_dir($quality);

		// 	return $target_dir;
		// }//end get_target_dir



	/**
	* GET_IMAGE_SIZE
	* Alias of $this->get_size()
	* Get file size in KB, MB, etc..
	* @param string|null $quality
	* @return string|null $size
	*/
	public function get_image_size(?string $quality=null) : ?string {

		if(empty($quality)) {
			$quality = $this->get_quality();
		}

		$size = $this->get_size($quality);

		return $size;
	}//end get_image_size



	/**
	* CONVERT_QUALITY
	* @param string $source_quality
	*
	* @return bool
	*/
	public function convert_quality(string $source_quality, string $target_quality) : bool {

		// invalid targets check
			// if ($target_quality===DEDALO_IMAGE_QUALITY_ORIGINAL || $target_quality===DEDALO_IMAGE_THUMB_DEFAULT) {
			if ($target_quality===DEDALO_IMAGE_QUALITY_ORIGINAL) {
				debug_log(__METHOD__." Ignored wrong target quality: ".to_string($target_quality), logger::ERROR);
				return false;
			}

		// vars
			$id					= $this->get_id();
			$additional_path	= $this->get_additional_path();
			$initial_media_path	= $this->get_initial_media_path();

		// original_file check (normalized Dédalo original viewable). If not exist, create it
			$original_file = $this->get_media_filepath($source_quality); //  $this->get_original_file_path('original');
			if ($original_file===false) {

				# source data (default quality is source)
				$original_image_path = $this->get_media_filepath(DEDALO_IMAGE_QUALITY_ORIGINAL);

				$path = pathinfo($original_image_path);
				$original_image_extension = $this->get_original_extension(
					true // bool exclude_converted
				);
				$original_image_path_real = $path['dirname'] . '/' .  $path['filename'] . '.' . $original_image_extension;
				$options = new stdClass();
					$options->source_file	= $original_image_path_real;
					$options->target_file	= $original_image_path;
					$options->quality		= 100;

				ImageMagick::convert($options);
			}

		// Image source
			$source_image			= $this->get_media_filepath($source_quality);
			$image_dimensions		= $this->get_image_dimensions($source_quality);

			$source_pixels_width	= $image_dimensions[0] ?? null;
			$source_pixels_height	= $image_dimensions[1] ?? null;

		// Image target
			$target_image			= $this->get_media_filepath($target_quality);
			$ar_target				= component_image::get_target_pixels_to_quality_conversion(
				$source_pixels_width,
				$source_pixels_height,
				$target_quality
			);
			$target_pixels_width	= $ar_target[0] ?? null;
			$target_pixels_height	= $ar_target[1] ?? null;

		# Target folder verify (EXISTS AND PERMISSIONS)
			$target_dir = $this->get_media_path_dir($target_quality) ;
			if( !is_dir($target_dir) ) {
				if(!mkdir($target_dir, 0775, true)) throw new Exception(" Error on read or create directory \"$target_quality\". Permission denied $target_dir (2)");
			}

		# Avoid enlarge images
			if ( ($source_pixels_width*$source_pixels_height)<($target_pixels_width*$target_pixels_height) ) {
				$target_pixels_width	= $source_pixels_width;
				$target_pixels_height	= $source_pixels_height;
			}

		// defaults when no value is available
			if($target_pixels_width<1)  $target_pixels_width  = 720;
			if($target_pixels_height<1) $target_pixels_height = 720;

		// convert options
			$options = new stdClass();
	 			$options->source_file = $source_image;
	 			$options->target_file = $target_image;

		// convert with ImageMagick command
			if ($target_quality===DEDALO_IMAGE_THUMB_DEFAULT) {
				$options->thumbnail = true;
			}else{
				$options->resize = $target_pixels_width.'x'.$target_pixels_height;
			}
			ImageMagick::convert($options);

			// $flags = '-thumbnail '.$target_pixels_width.'x'.$target_pixels_height;
			// ImageMagick::process($source_image, $target_image, $flags);


		return true;
	}//end convert_quality



	/**
	* GENERATE_DEFAULT_QUALITY_FILE
	* Generates the default quality image from retouched or original file
	* @param bool $overwrite = true
	* @return bool
	*/
	public function generate_default_quality_file(bool $overwrite=true) : bool {

		// short vars
			// $id					= $this->get_name();
			// $additional_path		= $this->get_additional_path();
			// $initial_media_path	= $this->get_initial_media_path();

		// quality retouched
			if (defined('DEDALO_IMAGE_QUALITY_RETOUCHED') && DEDALO_IMAGE_QUALITY_RETOUCHED!==false) {
				# source data (modified is source)
				$original_image_path	= $this->get_media_filepath(DEDALO_IMAGE_QUALITY_RETOUCHED);
				$real_orig_quality		= DEDALO_IMAGE_QUALITY_RETOUCHED;	// Modified
			}

		// quality original
			if (!isset($original_image_path) || !file_exists($original_image_path)) {
				# source data (default quality is source)
				$original_image_path	= $this->get_media_filepath(DEDALO_IMAGE_QUALITY_ORIGINAL);
				$real_orig_quality		= DEDALO_IMAGE_QUALITY_ORIGINAL; // Original
			}
			// check original file again
			if (!file_exists($original_image_path)) {
				debug_log(__METHOD__." Unable locate original_image. File does not exists in $original_image_path ".to_string(), logger::ERROR);
				return false;
			}

		// quality default
			$default_quality	= $this->get_default_quality();
			$image_default_path	= $this->get_media_filepath($default_quality);
			// overwrite or create default quality image version
			if ($overwrite===true || !file_exists($image_default_path)) {
				$this->convert_quality( $real_orig_quality, $default_quality );
			}


		return true;
	}//end generate_default_quality_file



	/**
	* GENERATE_DEFAULT_FROM_ORIGINAL_REAL
	* @return bool true
	*/
	public function generate_default_from_original_real(bool $overwrite=true) : bool {

		// common data
			// $id					= $this->get_id();
			// $additional_path		= $this->get_additional_path();
			// $initial_media_path	= $this->get_initial_media_path();

		// source data (default quality is source)
			$original_image_path		= $this->get_media_filepath(DEDALO_IMAGE_QUALITY_ORIGINAL);
			$path						= pathinfo($original_image_path);
			$original_image_extension	= $this->get_original_extension(
				true // bool exclude_converted
			);
			$original_image_path_real = $path['dirname'] . '/' .  $path['filename'] . '.' . $original_image_extension;
			if (!file_exists($original_image_path_real)) {
				debug_log(__METHOD__." Original image path does not exists ".to_string($original_image_path_real), logger::WARNING);
				return false;
			}

		// target data (target quality is thumb)
			$image_default_path = $this->get_media_filepath(DEDALO_IMAGE_QUALITY_DEFAULT);

		// conversion
			if ($overwrite===true) {

				// original quality create
					// Imagemagick::convert(
					// 	$original_image_path_real,
					// 	$original_image_path
					// );
				$options_original = new stdClass();
					$options_original->source_file	= $original_image_path_real;
					$options_original->target_file	= $original_image_path;
					$options_original->quality		= 100;

				ImageMagick::convert($options_original);

				// default quality create
				$options_default = new stdClass();
					$options_default->source_file	= $original_image_path;
					$options_default->target_file	= $image_default_path;

				ImageMagick::convert($options_default);
			}


		return true;
	}//end generate_default_from_original_real



	/**
	* GENERATE_THUMB
	* Called on save
	* @return object|null $result
	* 	url	path of thumb file path OR null if default quality file does not exists
	*/
	public function generate_thumb() : ?object {
		$start_time = start_time();

		// common data
			$id					= $this->get_id();
			$initial_media_path	= $this->get_initial_media_path();
			// $additional_path	= $this->get_additional_path();

		// source data (default quality is source)
			$default_image_path	= $this->get_media_filepath(DEDALO_IMAGE_QUALITY_DEFAULT);

		// check default quality image
			if (!file_exists($default_image_path)) {
				debug_log(__METHOD__." Default image quality does not exists. Skip to create thumb. ".to_string($id), logger::ERROR);
				return null;
			}

		// old thumb rename
			$image_thumb_path	= $this->get_media_filepath(DEDALO_IMAGE_THUMB_DEFAULT);
			$image_thumb_url	= $this->get_url(
				DEDALO_IMAGE_THUMB_DEFAULT,
				false,  // bool test_file
				false,  // bool absolute
				false // bool default_add
			);
			if(file_exists($image_thumb_path)) {
				// unlink($image_thumb_path);
				$image_thumb_path_des = $image_thumb_path.'_DES';
				shell_exec("mv $image_thumb_path $image_thumb_path_des");
			}

		// thumb generate
			ImageMagick::dd_thumb(
				'list',
				$default_image_path,
				$image_thumb_path,
				false, // bool dimensions
				$initial_media_path
			);

		// debug
			debug_log(__METHOD__." dd_thumb function called and executed in ".
				exec_time_unit($start_time,'ms').' ms'.
				". Created thumb file: ".to_string($image_thumb_path), logger::DEBUG);

		// result
			$result = (object)[
				'path'	=> $image_thumb_path,
				'url'	=> $image_thumb_url
			];


		return $result;
	}//end generate_thumb



	/**
	* GET_THUMB_URL
	* @return string $image_thumb_url
	*/
	public function get_thumb_url() : string {

		# target data (target quality is thumb)
		$image_thumb_url = $this->get_url(
			DEDALO_IMAGE_THUMB_DEFAULT,
			false,  // bool test_file
			false,  // bool absolute
			false // bool default_add
		);

		return $image_thumb_url;
	}//end get_thumb_url



	/**
	* GET_THUMB_PATH
	* @return string $image_thumb_path
	*/
	public function get_thumb_path() : string {

		# common data
		$id 			 = $this->get_id();
		$additional_path 	 = $this->get_additional_path();
		$initial_media_path  = $this->get_initial_media_path();

		# target data (target quality is thumb)
		$image_thumb_path 	 = $this->get_media_filepath(DEDALO_IMAGE_THUMB_DEFAULT);

		return $image_thumb_path;
	}//end get_thumb_path



	/**
	* GET_IMAGE_PRINT_DIMENSIONS
	* @return array $ar_info
	*/
	public function get_image_print_dimensions(string $quality) : array {

		$ar_info = $this->pixel_to_centimeters($quality, $dpi=DEDALO_IMAGE_PRINT_DPI);

		return $ar_info;
	}//end get_image_print_dimensions



	/**
	* CONVERT_QUALITY_TO_MEGABYTES
	*/
	public static function convert_quality_to_megabytes(string $quality) : float {

		// patern : '1MB' | '1.5MB' | <1MB | >100MB

		// We removed the megabytes ('MB') text in the quality name
		$string = substr($quality, 0,-2);

		switch (true) {

			case ( strpos($string, '>')===0 ):
				# Sample: >100 will be 100
				$number = intval(substr($string,1)) + 1;
				break;

			case ( strpos($string, '<')===0 ):
				# Sample: <1 will be 1
				$number = floatval( substr($string,1) - 0.1 );
				break;

			default:
				# Default 1.5 will be 1.5
				$number = $string;
				break;
		}

		# Float value
		$number = floatval($number);

		return $number;
	}//end convert_quality_to_megabytes



	/**
	* REMOVE_COMPONENT_MEDIA_FILES
	* "Remove" (rename and move files to deleted folder) all media file linked to current component (all quality versions)
	* Is triggered when section that contain media elements is deleted
	* @see section:remove_section_media_files
	*/
	public function remove_component_media_files(array $ar_quality=[]) : bool {

		$date=date("Y-m-d_Hi");

		// ar_quality
			if (empty($ar_quality)) {
				$ar_quality = $this->get_ar_quality();
			}

		// files remove
			foreach ($ar_quality as $current_quality) {

				// media_path is full path of file like '/www/dedalo/media_test/media_development/image/thumb/rsc29_rsc170_77.jpg'
					$media_path = $this->get_media_filepath($current_quality);
					if (!file_exists($media_path)) continue; # Skip

				// delete dir
					$folder_path_del = $this->get_target_dir($current_quality)  . '/deleted';
					if( !is_dir($folder_path_del) ) {
						if( !mkdir($folder_path_del, 0775, true) ) {
							trigger_error(" Error on read or create directory \"deleted\". Permission denied");
							return false;
						}
					}

				// move/rename file
					$image_name			= $this->get_name();
					$media_path_moved	= $folder_path_del . '/' . $image_name . '_deleted_' . $date . '.' . $this->get_extension();
					if( !rename($media_path, $media_path_moved) ) {
						trigger_error(" Error on move files to folder \"deleted\" [1]. Permission denied . The files are not deleted");
						return false;
					}

				debug_log(__METHOD__." >>> Moved file \n$media_path to \n$media_path_moved ".to_string(), logger::DEBUG);

				// Move original files too (PNG,TIF,Etc.)
				// NOTE : 'original files' are NOT 'original quality'. Are uploaded files with extension different to DEDALO_IMAGE_EXTENSION
					$original_extension	= $this->get_original_extension(
						true // bool exclude_converted
					);
					$path_parts			= pathinfo($media_path);
					$original_file		= $path_parts['dirname'].'/'.$path_parts['filename'].'.'.$original_extension;
					#$original_file_moved= $path_parts['dirname'].'/'.$path_parts['filename'].'_deleted_'.$date.'.'.$original_extension;
					$original_file_moved= $folder_path_del.'/'.$path_parts['filename'].'_deleted_'.$date.'.'.$original_extension;
					if (file_exists($original_file)) {
						if( !rename($original_file, $original_file_moved) ) {
							trigger_error(" Error on move files to folder \"deleted\" [2]. Permission denied . The files are not deleted");
							return false;
						}
					}
			}//end foreach

		#
		# Original image remove
		# remove additional source images like 'original_image.tif'
		# WORK IN PROGRESS !!

		return true;
	}//end remove_component_media_files



	/**
	* RESTORE_COMPONENT_MEDIA_FILES
	* "Restore" last version of deleted media files (renamed and stored in 'deleted' folder)
	* Is triggered when tool_time_machine recover a section
	* @see tool_time_machine::recover_section_from_time_machine
	*/
	public function restore_component_media_files() : bool {

		#
		# Image restore
		$ar_quality = DEDALO_IMAGE_AR_QUALITY;
		foreach ($ar_quality as $current_quality) {

			# media_path
			$media_path 	 = $this->get_media_filepath($current_quality);
			$folder_path_del = pathinfo($media_path,PATHINFO_DIRNAME).'/deleted';
			$id 		 = $this->get_id();
			if(SHOW_DEBUG===true) {
				#dump($folder_path_del, "folder_path_del current_quality:$current_quality - get_id:$id");
			}
			$file_pattern 	= $folder_path_del .'/'.$id .'_*.'. $this->get_extension();
			$ar_files 		= glob($file_pattern);
			if(SHOW_DEBUG===true) {
				#dump($ar_files, ' ar_files');#continue;
			}
			if (empty($ar_files)) {
				debug_log(__METHOD__."  No files to restore were found for id:$id in quality:$current_quality. Nothing was restored for this quality ".to_string(), logger::DEBUG);
				continue; // Skip
			}
			natsort($ar_files);	# sort the files from newest to oldest
			$last_file_path = end($ar_files);
			$new_file_path 	= $this->get_media_filepath($current_quality);
			if( !rename($last_file_path, $new_file_path) ) throw new Exception(" Error on move files to restore folder. Permission denied . Nothing was restored (2)");


			/* POR ACABAR
			// Move original files too (PNG,TIF,Etc.)
			// NOTE : 'original files' are NOT 'original quality'. Are uploaded files with extension different to DEDALO_IMAGE_EXTENSION
			$original_extension = $this->get_original_extension( $current_quality );
			$path_parts 		= pathinfo($media_path);
			$original_file  	= $path_parts['dirname'].'/'.$path_parts['filename'].'.'.$original_extension;
			#$original_file_moved= $path_parts['dirname'].'/'.$path_parts['filename'].'_deleted_'.$date.'.'.$original_extension;
			$original_file_moved= $folder_path_del.'/'.$path_parts['filename'].'_deleted_'.$date.'.'.$original_extension;
			if (file_exists($original_file)) {
				if( !rename($original_file, $original_file_moved) ) {
					#throw new Exception(" Error on move files to folder \"deleted\" . Permission denied . The files are not deleted");
					trigger_error(" Error on move files to folder \"deleted\" [2]. Permission denied . The files are not deleted");
				}
			}
			*/

			debug_log(__METHOD__." Successful Moved file \n$last_file_path to \n$new_file_path ".to_string(), logger::DEBUG);

		}//end foreach

		return true;
	}//end restore_component_media_files



	/**
	* IMAGE_VALUE_IN_TIME_MACHINE
	* @param string $image_value . Is valor_list of current image. We need replace path to enable view deleted image
	* @return
	*//*
	public static function image_value_in_time_machine( $image_value ) {

		# Example of url: /dedalo4/media_test/media_development/image/thumb/rsc29_rsc170_33.jpg

		preg_match("/src=\"(.+)\"/", $image_value, $output_array);
		if(!isset($output_array[1])) return $image_value;
		$image_url = $output_array[1];

		$id = pathinfo($image_url,PATHINFO_FILENAME);
			#dump($name, ' name ++ '.to_string());

		$image_deleted = self::get_deleted_image( $quality=DEDALO_IMAGE_THUMB_DEFAULT, $id );
			#dump($image_deleted, ' image_deleted ++ '.to_string());

		$ar_parts 		 = explode(DEDALO_MEDIA_PATH, $image_deleted);
		if(!isset($ar_parts[1])) return $image_value;
		$final_image_url = DEDALO_MEDIA_URL .$ar_parts[1];
			#dump($final_image_url, ' final_image_url ++ '.to_string());

		$final_image_value = str_replace($image_url, $final_image_url, $image_value);
			#dump($final_image_value, ' final_image_value ++ '.to_string());

		return (string)$final_image_value;
	}//end image_value_in_time_machine
	*/



	/**
	* GET_DELETED_IMAGE
	* @return string $last_file_path
	*/
	public function get_deleted_image( string $quality ) {

		# media_path
		$media_path			= $this->get_media_filepath($quality);
		$folder_path_del	= pathinfo($media_path,PATHINFO_DIRNAME).'/deleted';
		$id			= $this->get_id();

		#$media_path		= DEDALO_MEDIA_PATH . DEDALO_IMAGE_FOLDER .'/'.$quality.'/deleted';
		$file_pattern		= $folder_path_del .'/'. $id .'_*.'. $this->get_extension();
		$ar_files			= glob($file_pattern);
		if (empty($ar_files)) {
			debug_log(__METHOD__." No files were found for id:$id in quality:$quality. ".to_string(), logger::DEBUG);
			return false;
		}
		natsort($ar_files);	# sort the files from newest to oldest
		$last_file_path = end($ar_files);

		return $last_file_path;
	}//end get_deleted_image



	/**
	* BUILD_STANDARD_IMAGE_FORMAT
	* If uploaded file is not in Dedalo standard format (jpg), is converted, and original is conserved (like filename.tif)
	* Used in tool_upload post-processing file
	*/
	public static function build_standard_image_format(string $uploaded_file_path) : string {

		$f_extension = strtolower(pathinfo($uploaded_file_path, PATHINFO_EXTENSION));
		if ($f_extension!==DEDALO_IMAGE_EXTENSION) {

			# Create new file path
			$new_file_path = substr($uploaded_file_path, 0, -(strlen($f_extension)) ) . DEDALO_IMAGE_EXTENSION;
			# Convert
			$options = new stdClass();
				$options->source_file	= $uploaded_file_path;
				$options->target_file	= $new_file_path;
				$options->quality		= 100;

			ImageMagick::convert($options);
			
			$file_path = $new_file_path;

		}else{

			# Unchanged path
			$file_path = $uploaded_file_path;
		}

		return $file_path;
	}//end build_standard_image_format



	/**
	* GET_AR_QUALITY
	* Get the list of defined image qualities in Dédalo config
	* @return array $ar_image_quality
	*/
	public function get_ar_quality() : array {

		$ar_image_quality = DEDALO_IMAGE_AR_QUALITY;

		return $ar_image_quality;
	}//end get_ar_quality



	/**
	* GET_ALLOWED_EXTENSIONS
	* @return array $allowed_extensions
	*/
	public function get_allowed_extensions() : array {

		$allowed_extensions = DEDALO_IMAGE_EXTENSIONS_SUPPORTED;

		return $allowed_extensions;
	}//end get_allowed_extensions



	/**
	* GET_ORIGINAL_QUALITY
	* @return string $original_quality
	*/
	public function get_original_quality() : string {

		$original_quality = defined('DEDALO_IMAGE_QUALITY_ORIGINAL')
			? DEDALO_IMAGE_QUALITY_ORIGINAL
			: DEDALO_IMAGE_QUALITY_DEFAULT;

		return $original_quality;
	}//end get_original_quality



	/**
	* PROCESS_UPLOADED_FILE
	* @param object $file_data
	*	Data from trigger upload file
	* @return object $response
	*/
	public function process_uploaded_file(object $file_data) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__METHOD__.'] ';

		// vars
			$original_file_name	= $file_data->original_file_name; 	// kike "my photo785.jpg"
			// $full_file_name	= $file_data->full_file_name;		// like "test175_test65_1.jpg"
			$full_file_path		= $file_data->full_file_path;		// like "/mypath/media/image/1.5MB/test175_test65_1.jpg"

		// imagemagick. Normalize uploaded image format to Dédalo working format like jpg from tif
			try {

				// default_image_format : If uploaded file is not in Dedalo standard format (jpg), is converted,
				// and original file is conserved (like myfilename.tiff and myfilename.jpg)
					$standard_file_path = self::build_standard_image_format($full_file_path);

				// target_filename. Save original file name in a component_input_text if defined
					$properties = $this->get_properties();
					if (isset($properties->target_filename)) {

						$current_section_id			= $this->get_section_id();
						$target_section_tipo		= $this->get_section_tipo();
						$model_name_target_filename	= RecordObj_dd::get_modelo_name_by_tipo($properties->target_filename,true);
						$component_target_filename	= component_common::get_instance(
							$model_name_target_filename,
							$properties->target_filename,
							$current_section_id,
							'edit',
							DEDALO_DATA_NOLAN,
							$target_section_tipo
						);
						$component_target_filename->set_dato( $original_file_name );
						$component_target_filename->Save();
					}

				// custom_postprocessing_image. postprocessing_image_script
					if (defined('POSTPROCESSING_IMAGE_SCRIPT')) {
						sleep(1);
						require( POSTPROCESSING_IMAGE_SCRIPT );
						$result = custom_postprocessing_image($this);
					}

				// original and retouched cases rewrites default and thumb files
					$overwrite_default = ($this->quality===DEDALO_IMAGE_QUALITY_ORIGINAL || $this->quality===DEDALO_IMAGE_QUALITY_RETOUCHED);
					if ($overwrite_default===true) {
						// Generate default image quality from original if is needed
							$default = $this->generate_default_quality_file(true);

						// Generate thumb image quality from default always (if default exits)
							$thumb = $this->generate_thumb();

						// debug
							debug_log(__METHOD__." SAVING COMPONENT IMAGE: generate_default_quality_file response: ".to_string($default), logger::DEBUG);
							debug_log(__METHOD__." SAVING COMPONENT IMAGE: generate_thumb response: ".to_string($thumb), logger::DEBUG);
					}

				// generate the SVG file. Only when original, retouched or default quality files change
					if ( $this->quality===DEDALO_IMAGE_QUALITY_ORIGINAL ||
						 $this->quality===DEDALO_IMAGE_QUALITY_RETOUCHED ||
						 $this->quality===$this->get_default_quality()
						 ) {

						$svg_string_node		= $this->create_default_svg_string_node();
						$create_svg_file_result	= $this->create_svg_file($svg_string_node);
					}

				// add data with the file uploaded, only for original and retouched images, other quality images don't has relevant info.
					// if( $this->quality===DEDALO_IMAGE_QUALITY_ORIGINAL ||
					// 	$this->quality===DEDALO_IMAGE_QUALITY_RETOUCHED) {

					// 	$file_name_label	= $this->quality===DEDALO_IMAGE_QUALITY_ORIGINAL ? 'original_file_name'   : 'retouched_file_name';
					// 	$upload_date_label	= $this->quality===DEDALO_IMAGE_QUALITY_ORIGINAL ? 'original_upload_date' : 'retouched_upload_date';

					// 	$dato			= $this->get_dato();
					// 	$value			= empty($dato) ? new stdClass() : reset($dato);
					// 	$media_value	= $this->build_media_value((object)[
					// 		'value'				=> $value,
					// 		'file_name'			=> $original_file_name,
					// 		'file_name_label'	=> $file_name_label,
					// 		'upload_date'		=> component_date::get_date_now(),
					// 		'upload_date_label'	=> $upload_date_label
					// 	]);

					// 	$this->set_dato([$media_value]);
					// 	$this->Save();
					// }

				// get files info
					$files_info	= [];
					$ar_quality = DEDALO_IMAGE_AR_QUALITY;
					foreach ($ar_quality as $current_quality) {
						if ($current_quality==='thumb') continue;
						// read file if exists to get file_info
						$file_info = $this->get_quality_file_info($current_quality);
						// add non empty quality files data
						if (!empty($file_info)) {
							$files_info[] = $file_info;
						}
					}

				// save component dato
					$dato		= $this->get_dato();
					$save_dato	= false;
					if (isset($dato[0])) {
						if (!is_object($dato[0])) {
							// bad dato
							debug_log(__METHOD__." ERROR. BAD COMPONENT DATO ".to_string($dato), logger::ERROR);
						}else{
							// update property files_info
							$dato[0]->files_info = $files_info;
							$save_dato = true;
						}
					}else{
						// create a new dato from scratch
						$dato_item = (object)[
							'files_info' => $files_info
						];
						$dato = [$dato_item];
						$save_dato = true;
					}
					if ($save_dato===true) {
						$this->set_dato($dato);
						$this->Save();
					}

				// all is OK
					$response->result	= true;
					$response->msg		= 'OK. Request done ['.__METHOD__.'] ';

			} catch (Exception $e) {
				$msg = 'Exception[process_uploaded_file]: ' .  $e->getMessage() . "\n";
				debug_log(__METHOD__." $msg ".to_string(), logger::ERROR);
				$response->msg .= ' - '.$msg;
			}


		return $response;
	}//end process_uploaded_file



	/**
	* GET_PREVIEW_URL
	* @return string $preview_url
	*/
	public function get_preview_url(string $quality=DEDALO_IMAGE_QUALITY_DEFAULT) : string {

		$preview_url = $this->get_url(
			$quality,
			false,  // bool test_file
			false,  // bool  absolute
			false // bool default_add
		);

		return $preview_url;
	}//end get_preview_url



	/**
	* CREATE_DEFAULT_SVG_STRING_NODE
	* Generates the SVG code for default quality image and
	* If default quality image file does not exists, return null
	* (!) Note that svg file take the default quality file (the working file) as reference for dimensions
	* @return string|null $svg_string_node
	*/
	public function create_default_svg_string_node() : ?string {

		// short vars
			$id						= $this->get_id();
			$source_quality			= $this->get_default_quality(); // DEDALO_IMAGE_QUALITY_DEFAULT;
			// $additional_path		= $this->get_additional_path();
			// $initial_media_path	= $this->get_initial_media_path();


		// default quality check file
			$file_path = $this->get_media_filepath($source_quality);
			if (!file_exists($file_path)) {
				debug_log(
					__METHOD__." Unable to create create_default_svg_string_node. Default quality file does not exists: ". PHP_EOL . $file_path,
					logger::ERROR
				);
				return null;
			}

		// string_node
			$image_url			= $this->get_media_url_dir($source_quality) .'/'. $id .'.'. $this->get_extension(); // relative path
			$image_dimensions	= $this->get_image_dimensions($source_quality);
			$width				= $image_dimensions[0];
			$height				= $image_dimensions[1];

			$svg_string_node_pretty = '
				<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="'.$width.'" height="'.$height.'" viewBox="0,0,'.$width.','.$height.'">
					 <g id="raster">
						 <image width="'.$width.'" height="'.$height.'" xlink:href="'.$image_url.'"/>
					 </g>
				</svg>
			';
			$svg_string_node = trim(preg_replace('/\t+/', '', $svg_string_node_pretty));


		return $svg_string_node;
	}//end create_default_svg_string_node



	/**
	* GET_SVG_FILE_PATH
	* @return string $file_path
	*/
	public function get_svg_file_path() {

		$id					= $this->get_id();
		$additional_path	= $this->get_additional_path();
		$initial_media_path	= $this->get_initial_media_path();

		// media_path
		$media_path = DEDALO_MEDIA_PATH . DEDALO_IMAGE_FOLDER . $initial_media_path . '/svg' . $additional_path;

		// file_path
		$file_path = $media_path . '/' . $id . '.svg';

		return $file_path;
	}//end get_svg_file_path



	/**
	* CREATE_SVG_FILE
	* Writes the SVG code to disk as SVG file
	* @param string $svg_string_node
	* @return bool
	* 	On write fail return false, else true
	*/
	public function create_svg_file(string $svg_string_node) : bool {

		// paths
			$file_path	= $this->get_svg_file_path();
			$path_parts	= pathinfo($file_path);
			$media_path	= $path_parts['dirname'];

		// check target folder is accessible (EXISTS AND PERMISSIONS)
			if( !is_dir($media_path) ) {
				if( !mkdir($media_path, 0775,  true) ) {
					debug_log(
						__METHOD__." Failed to create directory for default SVG file in media_path: " .$media_path,
						logger::ERROR
					);
					return false;
				}
			}

		// write string_node to disk file
			if( !file_put_contents($file_path, $svg_string_node) ) {
				debug_log(
					__METHOD__." Failed to create file for default SVG file: " .$file_path,
					logger::ERROR
				);
				return false;
			}

		// debug
			debug_log(__METHOD__." Created svg file ".to_string($file_path), logger::DEBUG);


		return true;
	}//end create_svg_file



	/**
	* GET_BASE_SVG_URL
	* Get the url of the component SVG file
	* @param bool $test_file = false
	* @param bool $absolute = false
	* @param bool $add_default = false
	* @return string|null $image_url
	*/
	public function get_base_svg_url(bool $test_file=false, bool $absolute=false, bool $add_default=false) : ?string {

		// short vars
			$id			= $this->get_id();
			$additional_path	= $this->get_additional_path();
			$initial_media_path	= $this->get_initial_media_path();
			$base_path			= DEDALO_IMAGE_FOLDER . $initial_media_path . '/svg' . $additional_path;

		// image_url. Default url
			$image_url = DEDALO_MEDIA_URL . $base_path . '/' . $id . '.svg';

		// test_file
			if($test_file===true) {

				$file = DEDALO_MEDIA_PATH . $base_path . '/' . $id . '.svg';
				if( !file_exists($file) ) {
					if ($add_default===false) {
						return null;
					}
					$image_url = DEDALO_CORE_URL . '/themes/default/0.svg';
				}
			}

		// Absolute (Default false)
			if ($absolute===true) {
				$image_url = DEDALO_PROTOCOL . DEDALO_HOST . $image_url;
			}

		return $image_url;
	}//end get_base_svg_url



	/**
	* GET_FILE_CONTENT
	* Get the SVG data embedding the image data base64 encoded into
	* @param string $quality = DEDALO_IMAGE_QUALITY_DEFAULT
	* @return string|null $file_content
	*/
	public function get_file_content( string $quality=DEDALO_IMAGE_QUALITY_DEFAULT ) : ?string {

		// short vars
			$id			= $this->get_id();
			$additional_path	= $this->get_additional_path();
			$initial_media_path	= $this->get_initial_media_path();
			$additional_path	= $this->get_additional_path();

		// svg
			$svg_file_name	= $id .'.'. DEDALO_SVG_EXTENSION;
			$svg_file_path	= DEDALO_MEDIA_PATH . DEDALO_IMAGE_FOLDER . $initial_media_path . '/' .DEDALO_SVG_EXTENSION . $additional_path . '/' . $svg_file_name;
			// svg data
			$svg_data		= file_get_contents($svg_file_path); // returns the read data or false on failure.
			if (empty($svg_data)) {
				debug_log(__METHOD__." Unable to read svg_file_path: ".to_string($svg_file_path), logger::WARNING);
				return null;
			}

		// img
			$img_file_name	= $id .'.'. $this->get_extension();
			$img_file_path	= DEDALO_MEDIA_PATH . DEDALO_IMAGE_FOLDER . $initial_media_path . '/' . $quality . $additional_path . '/' . $img_file_name;
			// img data
			$img_data		= file_get_contents($img_file_path); // returns the read data or false on failure.
			if (empty($img_data)) {
				debug_log(__METHOD__." Unable to read img_file_path: ".to_string($img_file_path), logger::WARNING);
				return null;
			}
			// base64_encode image data
			$type	= pathinfo($img_file_path, PATHINFO_EXTENSION);
			$base64	= 'data:image/' . $type . ';base64,' . base64_encode($img_data);

		// file_content. Clean SVG code
			$file_content = preg_replace('/xlink:href=".*?.jpg"/', 'xlink:href="'.$base64.'"', $svg_data);


		return $file_content;
	}//end get_file_content



	/**
	* UPDATE_DATO_VERSION
	* @param object $options
	* @return object $response
	*	$response->result = 0; // the component don't have the function "update_dato_version"
	*	$response->result = 1; // the component do the update"
	*	$response->result = 2; // the component try the update but the dato don't need change"
	*/
	public static function update_dato_version(object $options) : object {

		// options
			$update_version	= $options->update_version ?? '';
			$dato_unchanged	= $options->dato_unchanged ?? null;
			$reference_id	= $options->reference_id ?? null;
			$tipo			= $options->tipo ?? null;
			$section_id		= $options->section_id ?? null;
			$section_tipo	= $options->section_tipo ?? null;
			$context		= $options->context ?? 'update_component_dato';


		$update_version	= implode('.', $update_version);
		switch ($update_version) {

			case '6.0.0':
				$is_old_dato = (
					empty($dato_unchanged) || // v5 early case
					isset($dato_unchanged->section_id) || // v5 modern case
					(isset($dato_unchanged[0]) && isset($dato_unchanged[0]->original_file_name)) // v6 alpha case
				);
				// $is_old_dato = true; // force here
				if ($is_old_dato===true) {

					// create the component image
						$model		= RecordObj_dd::get_modelo_name_by_tipo($options->tipo,true);
						$component	= component_common::get_instance(
							$model, // string 'component_image'
							$tipo,
							$section_id,
							'list',
							DEDALO_DATA_NOLAN,
							$section_tipo
						);

					// get existing files data
						$file_id			= $component->get_name();
						$source_quality		= $component->get_original_quality();
						$additional_path	= $component->get_additional_path();
						$initial_media_path	= $component->get_initial_media_path();
						$original_extension	= $component->get_original_extension(
							false // bool exclude_converted
						) ?? $component->get_extension(); // 'jpg' fallback is expected

						$base_path	= DEDALO_IMAGE_FOLDER . $initial_media_path . '/' . $source_quality . $additional_path;
						$file		= DEDALO_MEDIA_PATH   . $base_path . '/' . $file_id . '.' . $original_extension;

						// no original file found. Use default quality file
							if(!file_exists($file)) {
								// use default quality as original
								$source_quality	= $component->get_default_quality();
								$base_path		= DEDALO_IMAGE_FOLDER . $initial_media_path . '/' . $source_quality . $additional_path;
								$file			= DEDALO_MEDIA_PATH   . $base_path . '/' . $file_id . '.' . $component->get_extension();
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

					// create the svg_file if not already exists
						$svg_file_path = $component->get_svg_file_path();
						if (!file_exists($svg_file_path)) {
							$svg_string_node = $component->create_default_svg_string_node();
							if (!empty($svg_string_node)) {
								$create_svg_file_result	= $component->create_svg_file($svg_string_node);
								if ($create_svg_file_result===false) {
									debug_log(__METHOD__." Error creating svg file form svg_string_node ".PHP_EOL. to_string($svg_string_node), logger::ERROR);
								}
							}
						}

					// source_file_upload_date
						$dd_date							= new dd_date();
						$upload_date_timestamp				= date ("Y-m-d H:i:s", filemtime($file));
						$source_file_upload_date			= $dd_date->get_date_from_timestamp($upload_date_timestamp);
						$source_file_upload_date->time		= dd_date::convert_date_to_seconds($source_file_upload_date);
						$source_file_upload_date->timestamp	= $upload_date_timestamp;

					// get the source file name
						$source_file_name	= pathinfo($file)['basename'];
						// look for file name stored in another component data
						$properties			= $component->get_properties();
						if(isset($properties->target_filename)) {

							$original_name_tipo		= $properties->target_filename;
							$original_name_model	= RecordObj_dd::get_modelo_name_by_tipo($original_name_tipo,true);

							// create the component with the name of the original file
							$original_name_component = component_common::get_instance(
								$original_name_model,
								$original_name_tipo,
								$section_id,
								'list',
								DEDALO_DATA_NOLAN,
								$section_tipo
							);
							$name_component_dato	= $original_name_component->get_dato();
							$source_file_name		= isset($name_component_dato[0]) ? $name_component_dato[0] : $name_component_dato;
						}
						// if the original name is empty we can try to get the original name from Previous Code
						if(empty($source_file_name)) {
							$previous_code_tipo			= 'rsc22';
							$previous_code_model		=  RecordObj_dd::get_modelo_name_by_tipo($previous_code_tipo,true);
							// create the component_input_text where name was saved
							$previous_code_component	= component_common::get_instance(
								$previous_code_model, // expected 'component_input_text'
								$previous_code_tipo, // rsc22
								$section_id,
								'list',
								DEDALO_DATA_NOLAN,
								$section_tipo
							);
							$code_component_dato	= $previous_code_component->get_dato();
							$source_file_name		= isset($code_component_dato[0]) ? $code_component_dato[0] : $code_component_dato;
						}

					// lib_data
						$lib_data = null;

					// get files info
						$files_info	= [];
						$ar_quality = DEDALO_IMAGE_AR_QUALITY;
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
						$dato_item = new stdClass();
							$dato_item->files_info	= $files_info;
							$dato_item->lib_data	= $lib_data;

					// fix final dato with new format as array
						$new_dato = [$dato_item];
						debug_log(__METHOD__." update_version new_dato ".to_string($new_dato), logger::DEBUG);

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
		}//end switch ($update_version)


		return $response;
	}//end update_dato_version



	/**
	* ROTATE
	*	Rotates the given quality image file
	* @param string $degrees
	* 	0 to 360 (positive/negative)
	* @return string $result
	*
	*/
	public function rotate($degrees, ?string $quality=null) : ?string {

		$source = $this->get_media_filepath($quality);

		$result = ImageMagick::rotate($source, $degrees);

		return $result;
	}//end rotate



	/**
	* DELETE_FILE
	* Remove quality version moving the file to a deleted files dir
	* @see component_image->remove_component_media_files
	*
	* @return object $response
	*/
	public function delete_file(string $quality) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed';

		// remove_component_media_files returns bool value
		$result = $this->remove_component_media_files([$quality]);
		if ($result===true) {

			// save To update valor_list
				$this->Save();

			$response->result	= true;
			$response->msg		= 'File deleted successfully. ' . $quality;
		}


		return $response;
	}//end delete_file



	/**
	* GET_SOURCE_QUALITY_TO_BUILD
	* Iterate array DEDALO_IMAGE_AR_QUALITY (Order by quality big to small)
	*/
	public function get_source_quality_to_build(string $target_quality) {

		$ar_quality_source_valid	= [];
		$ar_quality					= DEDALO_IMAGE_AR_QUALITY;

		foreach($ar_quality as $current_quality) {

			if ($target_quality!==DEDALO_IMAGE_QUALITY_ORIGINAL && $target_quality!==$current_quality) {
				// check file
				$filename = $this->get_original_file_path($current_quality);
				if (!empty($filename) && file_exists($filename)) {
					return $current_quality;
				}
			}
		}//end foreach($ar_quality as $quality)


		return false;
	}//end get_source_quality_to_build



	/**
	* BUILD_VERSION
	* Creates a new version using FFMEPG conversion using settings based on target quality
	* @param string $quality
	* @return object $response
	*/
	public function build_version(string $quality) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed';

		// short vars
			$id		= $this->get_name();
			$source_quality	= $this->get_source_quality_to_build($quality);
			$target_quality = $quality;

		// convert. Returns boolean
			$result = $this->convert_quality($source_quality, $target_quality);

		// response
			$response->result			= $result;
			$response->msg				= 'Building av file in background';
			$response->command_response	= null;

		// logger activity : QUE(action normalized like 'LOAD EDIT'), LOG LEVEL(default 'logger::INFO'), TIPO(like 'dd120'), DATOS(array of related info)
			logger::$obj['activity']->log_message(
				'NEW VERSION',
				logger::INFO,
				$this->tipo,
				NULL,
				[
					'msg'				=> 'Generated av file',
					'tipo'				=> $this->tipo,
					'parent'			=> $this->section_id,
					'top_id'			=> TOP_ID ?? null,
					'top_tipo'			=> TOP_TIPO ?? null,
					'id'			=> $id,
					'quality'			=> $quality,
					'source_quality'	=> $source_quality
				]
			);

		return $response;
	}//end build_version



	/**
	* GET_EXTENSION
	* @return string DEDALO_IMAGE_EXTENSION from config
	*/
	public function get_extension() : string {

		return $this->extension ?? DEDALO_IMAGE_EXTENSION;
	}//end get_extension



	/**
	* GET_FOLDER
	* 	Get element dir from config
	* @return string
	*/
	public function get_folder() : string {

		return $this->folder ?? DEDALO_IMAGE_FOLDER;
	}//end get_folder



	/**
	* GET_MEDIA_ATTRIBUTES
	* Read file and get attributes using ffmpeg
	* @param string $file_path
	* @return array|null $media_attributes
	*/
	public function get_media_attributes(string $file_path) : ?array {

		$media_attributes = ImageMagick::get_media_attributes($file_path);

		return $media_attributes;
	}//end get_media_attributes



	/**
	* GET_MEDIA_PATH_DIR
	* Calculates the file base path based on the given quality
	* @param string $quality
	* @return string $media_path
	*/
	public function get_media_path_dir(string $quality) : string {

		if (empty($quality)) {
			debug_log(__METHOD__
				. " quality is empty !!! " .PHP_EOL
				. "tipo: $this->tipo, section_tipo: $this->section_tipo, section_id: $this->section_id"
				, logger::ERROR
			);
		}

		if($this->external_source) {

			$external_parts = pathinfo($this->external_source);
			$media_path		= $external_parts['dirname'];

		}else{

			$initial_media_path	= $this->initial_media_path ?? '';
			$additional_path	= $this->additional_path ?? '';

			$media_path = DEDALO_MEDIA_PATH . DEDALO_IMAGE_FOLDER . $initial_media_path . '/' . $quality . $additional_path;
		}


		return $media_path;
	}//end get_media_path_dir



	/**
	* GET_IMAGE_DIMENSIONS
	* @param string $quality
	* @return array|bool $ar_info
	*/
	public function get_image_dimensions(string $quality) : array|bool {

		if($this->external_source){

			$filename 		= $this->external_source;

		}else{

			$id				= $this->id;
			$media_path_abs	= $this->get_media_path_dir($quality);
			$filename		= rtrim($media_path_abs, '/') .'/'. $id .'.'. DEDALO_IMAGE_EXTENSION;
		}

		if ( !file_exists( $filename )) {
			debug_log(__METHOD__
				." Error. Image file not found ". PHP_EOL
				. 'filename: ' .$filename
				, logger::ERROR
			);
			return false ;
		}

		try {
			$ar_info = @getimagesize($filename);
			if(!$ar_info) {
				debug_log(__METHOD__
					." Error. Image getimagesize error 1 ". PHP_EOL
					. 'filename: ' .$filename
					, logger::ERROR
				);
				// throw new Exception('Unknown image width!');
				return false;
			}

			// data sample
				// $width	= $ar_info[0];
				// $height	= $ar_info[1];
				// $type	= $ar_info[2];

			return $ar_info;

		} catch (Exception $e) {
			debug_log(__METHOD__
				." Error. Image getimagesize error 2 " . PHP_EOL
				. 'filename: ' .$filename .PHP_EOL
				. 'Caught exception: '.  $e->getMessage()
				, logger::ERROR
			);
		}

		return false;
	}//end get_image_dimensions



	/**
	* GET_TARGET_PIXELS_TO_QUALITY_CONVERSION
	* @return array|null $result
	*/
	public static function get_target_pixels_to_quality_conversion($source_pixels_width, $source_pixels_height, $target_quality) : ?array {

		// check valid pixels
			if((int)$source_pixels_width===0 || (int)$source_pixels_height===0) {
				debug_log(__METHOD__." Invalid pixes received. source_pixels_width: '$source_pixels_width' , source_pixels_height: '$source_pixels_height' , target_quality: '$target_quality'  ".to_string(), logger::ERROR);
				return null;
			}

		// thumbs. To generate thumbs, the measurements are fixed
			if($target_quality===DEDALO_IMAGE_THUMB_DEFAULT) {
				# Default 102x57
				$result = [
					DEDALO_IMAGE_THUMB_WIDTH,
					DEDALO_IMAGE_THUMB_HEIGHT
				];

		// others. Calculated
			}else{

				// ratio
					$source_ratio = (int)$source_pixels_width / (int)$source_pixels_height;
				// target megabytes
					$target_megabytes = component_image::convert_quality_to_megabytes($target_quality) * 350000;
				// height
					$height = $target_megabytes / $source_ratio;
					$height = intval(sqrt($height));
				// width
					$width = round($height * $source_ratio);

				$result = [
					$width,
					$height
				];
			}


		return $result;
	}//end get_target_pixels_to_quality_conversion



	/**
	* PIXEL_TO_CENTIMETERS
	* @param string $quality
	* 	dir source of image
	* @param $dpi
	*	resolution to convert E.g.: 72dpi or 300dpi
	* Use:
	*	$image = "/User/Dedalo/images/0.jpg";
	*	$dpi = 300;
	*	$result = px2cm($image, $dpi);
	*/
	public function pixel_to_centimeters(string $quality, int $dpi=DEDALO_IMAGE_PRINT_DPI) : array {

		$image_path = $this->get_media_filepath($quality);

		$size = getimagesize($image_path);
		$x = $size[0];
		$y = $size[1];

		#Convert to centimeter
		$h = $x * 2.54 / $dpi;
		$l = $y * 2.54 / $dpi;

		#Format a number with grouped thousands
		$h = number_format($h, 2, ',', ' ');
		$l = number_format($l, 2, ',', ' ');

		$px2cm = [];

		#add size unit
		$px2cm[] = $h."cm";
		$px2cm[] = $l."cm";

		#return array w values
		#$px2cm[0] = X
		#$px2cm[1] = Y

		return $px2cm;
	}//end pixel_to_centimeters



	/**
	* REGENERATE_COMPONENT
	* Force the current component to re-build and save its data
	* @see class.tool_update_cache.php
	* @return bool
	*/
	public function regenerate_component() : bool {

		// files check
			// create default quality file if not exists
				$default_quality		= $this->get_default_quality();
				$image_default_filepath	= $this->get_media_filepath( $default_quality );
				if (!file_exists($image_default_filepath)) {
					$this->generate_default_quality_file();
				}

			// re-create thumb always
				$this->generate_thumb();

			// svg file. Create file if not exists
				$svg_file_path = $this->get_svg_file_path();
				if (!file_exists($svg_file_path)) {
					// If default quality file exists, svg_string_node will be generated, else null
					$svg_string_node = $this->create_default_svg_string_node();
					if (!empty($svg_string_node)) {
						// create the svg default file
						$this->create_svg_file($svg_string_node);
					}
				}

		// get files info
			$files_info	= $this->get_files_info(
				false // bool include_empty. Prevent to store empty quality files
			);

		// lib_data add
			$current_dato = $this->get_dato();
			if (empty($current_dato)) {

				// create a new dato from scratch
				$dato_item = (object)[
					'files_info' => $files_info
				];
				$dato_item->lib_data = null;
				$new_dato = [$dato_item];

			}else{

				$new_dato = [];
				foreach ($current_dato as $current_value) {
					// create a new dato from scratch
					$dato_item = (object)[
						'files_info' => $files_info
					];
					$dato_item->lib_data = $current_value->lib_data ?? null;
					$new_dato[] = $dato_item;
				}
			}

		// replace existing dato
			$this->set_dato($new_dato);

		// save
			$this->Save();


		return true;
	}//end regenerate_component



}//end class component_image
