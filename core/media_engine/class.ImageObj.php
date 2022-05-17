<?php
require_once( DEDALO_CONFIG_PATH .'/config.php');
require_once( DEDALO_CORE_PATH . '/media_engine/class.MediaObj.php');
require_once( DEDALO_CORE_PATH . '/media_engine/class.Thumb.php');
require_once( DEDALO_CORE_PATH . '/media_engine/class.ImageMagick.php');


class ImageObj extends MediaObj {



	protected $image_id; # image_id
	protected $quality; # like 'A2,A4,A0..'



	function __construct($image_id, $quality=false, $aditional_path=false, string $initial_media_path='', bool $external_source=false) {

		# SPECIFIC VARS
		$this->set_image_id($image_id);
		$this->set_name($image_id);
		$this->set_quality($quality);

		/*
		if( is_null($aditional_path) ) {
			dump($aditional_path,'$aditional_path');
			throw new Exception("Error Processing Request. Mandatory aditional_path", 1);
		}
		*/
		$this->initial_media_path = $initial_media_path;
		$this->aditional_path 	  = $aditional_path;
		$this->external_source 	  = $external_source;

		parent::__construct($image_id);
	}//end __construct



	# MANDATORY DEFINITIONS
	protected function define_name(){
		return $this->image_id;
	}
	protected function define_type() {
		return DEDALO_IMAGE_TYPE;
	}
	protected function define_extension() {
		return DEDALO_IMAGE_EXTENSION;
	}
	protected function define_media_path() {
		$this->get_media_path();
	}
	protected function define_media_path_abs() {
		return $this->get_media_path_abs();
	}
	protected function define_media_path_server() {
		return $this->get_media_path_server();
	}
	protected function define_mime_type() {
		return DEDALO_IMAGE_MIME_TYPE;
	}
	protected function define_external_source() {
		return $this->external_source;
	}

	public function get_media_path() : string {
		if($this->external_source){
			$external_parts = pathinfo($this->external_source);
			$media_path = $external_parts['dirname'];
			return $media_path;
		}else{
			return DEDALO_MEDIA_URL . DEDALO_IMAGE_FOLDER . $this->initial_media_path . '/' . $this->quality . $this->aditional_path;
		}
	}
	public function get_media_path_abs() : string {
		if($this->external_source){
			$external_parts = pathinfo($this->external_source);
			$media_path = $external_parts['dirname'];
			return $media_path;
		}else{
			return DEDALO_MEDIA_PATH . DEDALO_IMAGE_FOLDER. $this->initial_media_path . '/' . $this->quality . $this->aditional_path;
		}
	}

	public function get_media_path_server() : string {
			return DEDALO_MEDIA_PATH . DEDALO_IMAGE_FOLDER. $this->initial_media_path . '/' . DEDALO_IMAGE_QUALITY_ORIGINAL . $this->aditional_path;
	}

	# GET DEFAULT QUALITY
	public static function get_quality_default() : string {
		return DEDALO_IMAGE_QUALITY_DEFAULT;
	}

	# GET ARRAY QUALITY OPTIONS
	public static function get_ar_quality() : array {
		return DEDALO_IMAGE_AR_QUALITY;
	}

	public function get_target_filename() : string {
		if($this->external_source){
			$external_parts = pathinfo($this->external_source);
			return $external_parts['basename'];
		}else{
			return $this->image_id .'.'. $this->extension ;
		}
	}//end get_target_filename



	/**
	* SET QUALITY
	* Assign the quality received verifying that it exists in the array of qualities defined in config
	* If it does not exist, assign the default quality
	*
	* Asigna la calidad recibida verificando que existe en el array de calidades definido en config
	* Si no existe, asigna la calidad por defecto
	* @return string $this->quality
	*/
	protected function set_quality($quality) : string {

		$default	= $this->get_quality_default();
		$ar_valid 	= $this->get_ar_quality();

		if(empty($quality)) {
			$this->quality = $default;
			return $this->quality;
		}

		if(!is_array($ar_valid)) {
			throw new Exception("config ar_valid is not defined!", 1);
		}

		if(!in_array($quality,$ar_valid)) {
			#$quality = $default ;		#dump($ar_valid, "$quality NO IS IN ARRAY !!!!!");
			throw new Exception("$quality is not acepted value as quality. Please configure 'DEDALO_IMAGE_AR_QUALITY' ", 1);
		}

		$this->quality = $quality;

		return $this->quality;
	}//end set_quality



	# QUALITY FOLDERS WITH EXISTING FILES
	# Return array whith existing quality files
	public function get_ar_quality_with_file() : array {

		$ar_quality 			= self::get_ar_quality();
		$ar_quality_with_file	= array();

		if(is_array($ar_quality)) foreach($ar_quality as $quality) {

			$obj = new ImageObj($this->image_id, $quality, $this->aditional_path, $this->initial_media_path);

			if($obj->get_file_exists()) {

				 $ar_quality_with_file[] = $quality ;
			}
		}
		return $ar_quality_with_file ;
	}//end get_ar_quality_with_file


	/**
	* GET_THUMB_URL
	* Build onthefly image at request size
	*/
	public function get_thumb_url($maxWidht, $maxHeight, $fx=null, $p=null, $prop=null) {

		$m 					= 'image';
		$quality 			= $this->quality;
		$initial_media_path = $this->initial_media_path;
		$aditional_path 	= $this->aditional_path;
		$SID 				= $this->image_id;
		$external_source 	= $this->external_source;
		$w 					= $maxWidht;
		$h 					= $maxHeight;
		# 'm','quality','SID','w','h','fx','p','prop'
		$thumb_url = DEDALO_CORE_URL . '/media_engine/img.php?m=' .$m. '&quality=' .$quality. '&initial_media_path=' .$initial_media_path. '&aditional_path=' .$aditional_path. '&SID=' .$SID. '&external_source='.$external_source. '&w=' .$w. '&h=' .$h. '&fx=' .$fx. '&p=' .$p. '&prop=' .$prop  ;
			#dump($thumb_url,'thumb_url');

		return $thumb_url;
	}//end get_thumb_url



	/**
	* GET_IMAGE_DIMENSIONS
	*/
	public function get_image_dimensions() {

		if($this->external_source){

			$filename 		= $this->external_source;

		}else{

			$image_id 		= $this->image_id;
			$quality 		= $this->quality;
			$media_path_abs = $this->media_path_abs;

			$filename 	= $media_path_abs .'/'. $image_id .'.'. DEDALO_IMAGE_EXTENSION;
		}

		if ( !file_exists( $filename )) {
			return false ;
		}

		try {
			$ar_info = @getimagesize($filename);
			if(!$ar_info)	throw new Exception('Unknow image width!') ;

			$width	= $ar_info[0];
			$height = $ar_info[1];
			$type	= $ar_info[2];

			return $ar_info;

		} catch (Exception $e) {
			return false;
		}
	}//end get_image_dimensions



	/**
	* GET_IMAGE_WIDTH
	*/
	public function get_image_width() {

		$ar_info = $this->get_image_dimensions();
		if(isset($ar_info[0])) return $ar_info[0];

		return false;
	}//end get_image_width



	/**
	* GET_IMAGE_HEIGHT
	*/
	public function get_image_height() {

		$ar_info = $this->get_image_dimensions();
		if(isset($ar_info[1])) return $ar_info[1];

		return false;
	}//end get_image_height



	/**
	* PIXEL_TO_CENTIMETRES
	* @param $quality - dir source of image
	* @param $dpi - resolution to convert E.g.: 72dpi or 300dpi
	* Use:
	*	$image = "/User/Dedalo/images/0.jpg";
	*	$dpi = 300;
	*	$result = px2cm($image, $dpi);
	*/
	public function pixel_to_centimetres($quality, $dpi=DEDALO_IMAGE_PRINT_DPI) {

		$image_path = $this->get_local_full_path();
			#dump($image,'image');

	    $size = getimagesize($image_path);
	    $x = $size[0];
	    $y = $size[1];

	    #Convert to centimeter
	    $h = $x * 2.54 / $dpi;
	    $l = $y * 2.54 / $dpi;

	    #Format a number with grouped thousands
	    $h = number_format($h, 2, ',', ' ');
	    $l = number_format($l, 2, ',', ' ');

	    #add size unit
	    $px2cm[] = $h."cm";
	    $px2cm[] = $l."cm";

	    #return array w values
	    #$px2cm[0] = X
	    #$px2cm[1] = Y
	    return $px2cm;
	}//end pixel_to_centimetres



	/**
	* GET_TARGET_PIXELS_TO_QUALITY_CONVERSION
	* @return array $result
	*/
	public static function get_target_pixels_to_quality_conversion($source_pixels_width, $source_pixels_height, $target_quality) {

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
	* GET_TARGET_PIXELS_TO_QUALITY_CONVERSION
	*/
	public static function get_megabytes_from_pixels(int $pixels) {
		$const = 350000;
		$total = ($pixels / $const);
		return number_format($total, 2, '.', '');
	}//end get_megabytes_from_pixels



	/*
	* FILE EXISTS
	*/
	public function get_file_exists() : bool {

		$source	= $this->get_local_full_path();

		if (file_exists($source)) {
			return true;
		}else{

			$ch = curl_init($source);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_exec($ch);
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if($code == 200){
				$status = true;
			}else{
				$status = false;
			}
			curl_close($ch);
			return $status;
		}

		return false;
	}//end get_file_exists



}//end class ImageObj
