<?php
/**
* MediaObj
*/
abstract class MediaObj extends stdClass {



	# GENERAL VARS
	protected $name ;				# reelID
	protected $type ;				# (audio/video/image)
	protected $extension ;			# like 'mp4'
	protected $media_path ;			# relative path like '../media/av/mid/'
	protected $media_path_abs ;		# absolute path like '/Users/dedalo/Sites/site_dedalo/media/av/mid/'
	protected $additional_path;
	protected $initial_media_path;
	protected $mime_type ;			# like 'video/mp4'
	protected $file_size ;			# like '630MB'
	protected $file_exists ;		# verify file exists



	# MANDATORY DEFINITIONS METHODS
	abstract protected function define_name();
	abstract protected function define_type();
	abstract protected function define_extension();
	abstract protected function define_media_path();
	abstract protected function define_media_path_abs();
	abstract protected function define_media_path_server();
	abstract protected function define_mime_type();



	/**
	* __CONSTRUCT
	*/
	function __construct(?string $name) {

		# MEDIA OBJ SETUP
		$this->name					= $this->define_name();
		$this->type					= $this->define_type();
		$this->extension			= $this->define_extension();
		$this->media_path			= $this->define_media_path();
		$this->media_path_abs		= $this->define_media_path_abs();
		$this->media_path_server	= $this->define_media_path_server();
		$this->mime_type			= $this->define_mime_type();
	}//end __construct



	/**
	* GET_URL
	*/
	public function get_url() : string {

		$url = $this->get_media_path() .'/'. $this->get_name() . '.' . $this->get_extension();
		$url .= '?t='.time();

		return $url;
	}//end get_url



	/**
	* LOCAL PATH
	* @return complete absolute file path like '/Users/myuser/works/Dedalo/images/dd152-1.jpg'
	*/
	public function get_local_full_path() : string {

		$path = $this->get_media_path_abs() .'/'. $this->get_name() . '.' . $this->get_extension();

		return	$path;
	}//end get_local_full_path



	/**
	* GET_FILE_EXISTS
	*/
	public function get_file_exists() : bool {

		$this->media_file_exists = file_exists($this->get_local_full_path()); #dump( file_exists(self::get_local_full_path()) , self::get_local_full_path() );

		return $this->media_file_exists;
	}//end get_file_exists



	# GET UPLOAD PATH FOR FILE
	public function get_upload_path_for_file($file_from_form) : ?string {

		if(!isset($this->name)) return false;

		$destination_path	= $this->get_media_path_abs();
		$file_name			= $this->get_name();
		$extension			= $this->get_extension();

		# extension verify
		$file_from_form_ext	= pathinfo($file_from_form, PATHINFO_EXTENSION);
		if($file_from_form != $extension) {
			return null;
		}

		return $destination_path . $file_name . '.' . $extension ;
	}//end get_upload_path_for_file



	/**
	* FILE SIZE
	* Get file physical size in bytes (or KB/MB)
	* @return string|null $size
	* 	(round to KB or MB with label like '256 KB')
	*/
	public function get_size() : ?string {

		$filename = $this->get_media_path_abs() . $this->get_name() . '.' . $this->get_extension() ;

		try {

			if(!file_exists($filename)) {
				return null;
			}

			$size		= @filesize($filename);
			if(!$size)	throw new Exception('Unknow size!');
		} catch (Exception $e) {
			#echo '',  $e->getMessage(), "\n";
			#trigger_error( __METHOD__ . " " . $e->getMessage() , E_USER_NOTICE) ;
			return null;
		}

		$size_kb = round($size / 1024);

		if($size_kb <= 1024) {
			return $size_kb . ' KB';
		}

		return round($size_kb / 1024) . ' MB';
	}//end get_size



	# ACCESSORS
	final public function __call(string $strFunction, array $arArguments) {

		$strMethodType		= substr($strFunction, 0, 4); # like set or get_
		$strMethodMember	= substr($strFunction, 4);
		switch($strMethodType) {
			case 'set_' :
				if(!isset($arArguments[0])) return(false);	#throw new Exception("Error Processing Request: called $strFunction without arguments", 1);
				return($this->SetAccessor($strMethodMember, $arArguments[0]));
				break;
			case 'get_' :
				return($this->GetAccessor($strMethodMember));
				break;
		}
		return(false);
	}
	# SET
	final protected function SetAccessor(string $strMember, $strNewValue) : bool {

		if(property_exists($this, $strMember)) {

			// fix value
			$this->$strMember = $strNewValue;

			return true;
		}else{
			return false;
		}
	}
	# GET
	final protected function GetAccessor(string $strMember) {

		return property_exists($this, $strMember)
			? $this->$strMember
			: false;
	}//end GetAccessor



}//end class MediaObj
