<?php
// Turn off output buffering
	ini_set('output_buffering', 'off');

# set some time Important!
$myDateTimeZone = 'Europe/Madrid';
date_default_timezone_set($myDateTimeZone);



// text default
	$text = $_GET['id'] ?? false;
	if (empty($text)) {
		die("text var is manadatory!");
	}


/**
* tag_SAFE_XSS
* @return mixed $value
*/
function tag_safe_xss($value) {

	if (is_string($value)) {

		if ($decode_json=json_decode($value)) {
			// If var is a stringify json, not verify string now
		}else{
			$value = strip_tags($value,'<br><strong><em>');
			$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		}
	}
	#error_log("value: ".to_string($value));

	return $value;
}//end tag_safe_xss



// clean variable
$text = tag_safe_xss($text);



# Text to show
$text = trim(stripslashes(urldecode($text)));
$text = strip_tags($text, '');



#
# TAG TYPE
	$tag_image_dir = dirname(dirname(dirname(__FILE__))) . '/themes/default/tag_base';
	$type = false;
	switch (true) {
		case (strpos($text,'[TC_')!==false):
			$type 			= 'tc';
			$pattern 		= "/\[TC_([0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}(\.[0-9]{1,3})?)_TC\]/";
			$text_original 	= $text;
			preg_match_all($pattern, $text, $matches);
			#print_r($text.'<hr>'); print_r($pattern.'<hr>'); print_r($matches); die();
			$text			= $matches[1][0];
			$imgBase 		= $tag_image_dir."/tc_ms-x2.png";
			break;
		case (strpos($text,'[index-')!==false || strpos($text,'[/index-')!==false):
			$type 			= 'index';
			$pattern 		= "/\[\/{0,1}(index)-([a-z])-([0-9]{1,6})(-(.{0,22}))?(-data:(.*?):data)?\]/";
			$text_original 	= $text;
			preg_match_all($pattern, $text, $matches);
			#print_r($text.'<hr>'); print_r($pattern.'<hr>'); print_r($matches); die();
			$n 		= $matches[3][0];
			$state 	= $matches[2][0];

			if(strpos($text_original,'/')!==false) {
				# mode [/index-u-6]
				$text 		= " $n";
				$imgBase 	= $tag_image_dir."/indexOut-{$state}-x2.png";
			}else{
				# mode [index-u-1]
				$text 		= $n;
				$imgBase 	= $tag_image_dir."/indexIn-{$state}-x2.png";
			}
			break;
		case (strpos($text,'[draw-')!==false):
			$type = 'draw' ;
			# mode [draw-n-1-data:***]
			$state 		= substr($text,6,1);

			// echo "state-------------------".$state;
			$last_minus = strrpos($text, '-');
			$ar_parts 	= explode('-', $text);
			$text 		= $ar_parts[2];
			$imgBase 	= $tag_image_dir."/draw-{$state}-x2.png";
			break;
		case (strpos($text,'[geo-')!==false):
			$type = 'geo';
			# mode [geo-n-1-data:***]
			$state 		= substr($text,5,1);
			$last_minus = strrpos($text, '-');
			$ar_parts 	= explode('-', $text);
			$text 		= $ar_parts[2];
			$imgBase 	= $tag_image_dir."/geo-{$state}-x2.png";
			break;
		case (strpos($text,'[page-')!==false):
			$type = 'page';
			# mode [page-n-1-77]
			$pattern 		= "/\[(page)-([a-z])-([0-9]{1,6})-(.{0,22})?\]/";
			$text_original 	= $text;
			preg_match_all($pattern, $text, $matches);
			$text			= $matches[4][0]; //$matches[3][0]
			$state 			= $matches[2][0];
			$imgBase 		= $tag_image_dir."/page-{$state}-x2.png";
			break;
		case (strpos($text,'[person-')!==false):
			$type = 'person';
			# mode [person-0-name-data:locator_flat:data]
			$pattern 	= "/\[(person)-([a-z])-([0-9]{1,6})-(\S{0,22})\]/";
			$text_original 	= $text;
			preg_match_all($pattern, $text, $matches);
			#print_r($text.'<hr>'); print_r($pattern.'<hr>'); print_r($matches); die();
			$text			= urldecode($matches[4][0]);
			$state 			= $matches[2][0];
			if($state!=='a' && $state!=='b') {
				$state = 'a';
			}
			$imgBase 		= $tag_image_dir."/person-{$state}-x2.png";
			break;
		case (strpos($text,'[note-')!==false):
			$type = 'note';
			# mode [note-0-name-data:locator_flat:data]
			$ar_parts 	= explode('-', $text);
			$state 		= $ar_parts[1];
			$text 		= urldecode($ar_parts[2]);
			$imgBase 	= $tag_image_dir."/note-{$state}-x2.png";
			break;
		// locator case, used by svg or image or vídeo, etc...
		case (strpos($text,'{')===0):

			$changed_text = str_replace(['&#039;','\''],'"', $text);
			$locator = json_decode($changed_text);

			if(!$locator) return;
			include(dirname(dirname(dirname(dirname(__FILE__)))).'/config/config.php');

			$section_tipo 	= $locator->section_tipo;
			$section_id 	= $locator->section_id;
			$component_tipo = $locator->component_tipo;

			$model = RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);

			$component 		= component_common::get_instance($model,
															 $component_tipo,
															 $section_id,
															 'list',
															 DEDALO_DATA_NOLAN,
															 $section_tipo);
			$file_content = $component->get_file_content();




			header("Cache-Control: private, max-age=10800, pre-check=10800");
			header("Pragma: private");
			header("Expires: " . date(DATE_RFC822,strtotime(" 200 day")));

			# No cache header
			#header("Cache-Control: no-cache, must-revalidate");

			# Output to browser
			// header('Content-Length: '.strlen($file_content));
			header('Content-Type: image/svg+xml');
			// header('Content-Length: '.filesize($file_path));
			// header('Accept-Ranges: bytes');
			header('Vary: Accept-Encoding');
			// fpassthru( $file_path );
			header('Connection: close');
			echo $file_content;
			exit;
			break;

		default:
			die("Need type ..! <br>$text");
			break;
	}


# Text formatting in 1 or 2 lines depending on the number of characters
	# $maxchar 	= 16 ;
	$width 		= 66 ; 	# 88
	$angle 		= 0;	# 0
	$x 			= 0 ;	# 0
	$y 			= 0 ;	# 0


// Attempt to open. We create an image from the base image ($imgBase)
	$im = @imagecreatefrompng($imgBase);

// See if it failed
	if(!$im) {
		error_log("Error. ivalid im. type:". gettype($im) .' - REQUEST_URI: '.json_encode($_SERVER["REQUEST_URI"], JSON_PRETTY_PRINT));

		// Create a blank image
		$im  = imagecreatetruecolor(150, 30);
		$bgc = imagecolorallocate($im, 255, 255, 255);
		$tc  = imagecolorallocate($im, 0, 0, 0);

		imagefilledrectangle($im, 0, 0, 150, 30, $bgc);

		/* Output an error message */
		imagestring($im, 1, 5, 5, 'Error loading bogus.image', $tc);

		header('Content-Type: image/png');

		imagepng($im);
		imagedestroy($im);

		die();
	}


# Define colors
	$black	= imagecolorallocate($im, 0, 0, 0);
	$white	= imagecolorallocate($im, 255, 255, 255);
	$grey	= imagecolorallocate($im, 188, 188, 188);
	$colorH	= imagecolorallocate($im, 141, 198, 63);
	$colorH	= imagecolorallocate($im, 0, 232, 0);
	$colorP	= imagecolorallocate($im, 0, 167, 157);


# Font config defaults
	#$font_name 	= '/liberation/LiberationSans-Regular.ttf';
	#$font_name 	= '/oxigen-webfont/oxygen-bold-webfont.ttf';
	$font_name 	= '/san_francisco/System_San_Francisco_Display_Regular.ttf';
	$font_size 	= 8;


	switch($type) {

		case 'tc'	:	$colorText	= $colorH ;
						$colorBG 	= $black ;
						#$font_name	= $font; // --
						#$font_size	= 8  ; # 11 o 10.88
						$font_size 	= ($font_size *2)+2; // as 18
						break;

		case 'index':	$colorText	= $black ;
						$colorBG 	= $black ;
						#$font_name	= $font; // --
						#$font_size	= 7.9  ; # 11 o 10.88
						$font_size 	= ($font_size *2)+2; // as 18

						if($state=='n') $colorText	= $white ;
						break;

		case 'draw':	$colorText	= $white ;
						$colorBG 	= $black ;
						#$font_name	= $font; // --
						#$font_size	= 7.9  ; # 11 o 10.88
						$font_size 	= ($font_size *2)+2;
						break;

		case 'geo':		$colorText	= $white ;
						$colorBG 	= $black ;
						#$font_name	= $font; // --
						#$font_size	= 7.9  ; # 11 o 10.88
						$font_size 	= ($font_size *2)+2;

						if($state=='n') $colorText	= $white ;
						break;

		case 'page':	$colorText	= $black ;
						$colorBG 	= $black ;
						$font_size 	= ($font_size *2)+2;
						break;

		case 'person':	$colorText	= $black ;
						$colorBG 	= $black ;
						#$maxchar 	= 160 ;
						#$width 		= 400 ; 	# 88
						$font_size 	= ($font_size *2)+2; // as 18
						#$font_name 	= '/oxigen-webfont/oxygen-bold-webfont.ttf';
						#$font_name 	= '/san_francisco/System_San_Francisco_Display_Regular.ttf';
						#$font_name 	= '/san_francisco/SanFranciscoDisplay-Regular.otf';
						break;
		case 'note':	$colorText	= $black ;
						$colorBG 	= $white ;
						#$maxchar 	= 160 ;
						#$width 		= 400 ; 	# 88
						$font_size 	= ($font_size *2)+2; // as 18
						#$font_name 	= '/oxigen-webfont/oxygen-bold-webfont.ttf';
						#$font_name 	= '/san_francisco/System_San_Francisco_Display_Regular.ttf';
						#$font_name 	= '/san_francisco/SanFranciscoDisplay-Regular.otf';
						break;
	}

# We activate the alpha chanel (24bit png)
	imageAlphaBlending($im, true);
	imageSaveAlpha($im, true);

# Making Image Transparent
#imagecolortransparent($im,$colorBG);

# FONT FILES . Path to our font file
	$path_fonts	= dirname(dirname(dirname(__FILE__))) . '/themes/default/fonts';
	$fontfile	= $path_fonts . $font_name;

# OFFSET
	$offsetX	= 0 ; # 0
	$offsetY	= 0 ; # 5

	switch ($type) {
		case 'tc':
			$offsetX = 0;
			$offsetY = 2;
			break;
		case 'index':
			$offsetX = 2;
			$offsetY = 2;
			break;
		case 'draw':
		case 'page':
			$offsetY = 2;
		case 'person':
			$offsetX = 8;
			break;
		case 'geo':
			$offsetY = 2;
			$offsetX = 7;
			break;
		case 'note':
			$offsetX = 0;
			$offsetY = 0;
			break;
	}

# CUSTOM OFFSET FOR MAC DEVELOPMENT
	if (PHP_OS==='Darwin') {

		$offsetX = -1 ; # 0

		switch ($type) {
			case 'tc':
			case 'index':
				break;
			case 'draw':
			case 'page':
			case 'person':
				$offsetX = 8;
				break;
			case 'geo':
				$offsetX = 7;
				break;
			case 'note':
				$offsetX = 0;
				break;
		}
	}//end if (DEDALO_ENTITY=='development')


# BACKGROUND. Set the background to be white
#$bg = imagefilledrectangle($im, 0, 0, $width, $width, $colorBG); //( resource $image , int $x1 , int $y1 , int $x2 , int $y2 , int $color )

// Get image Width and Height
	$image_width	= imagesx($im);
	$image_height	= imagesy($im);

	$centroXimg	= $image_width / 2;
	$centroYimg	= $image_height / 2;


if($text!==false) {

	# First we create our bounding box for the first text
	# Get Bounding Box Size
	$bbox = imagettfbbox($font_size, $angle, $fontfile, $text ); //( float $size , float $angle , string $fontfile , string $text )


	// Get your Text Width and Height
	$text_width		= abs($bbox[2])-abs($bbox[0]);
	$text_height	= abs($bbox[7])-abs($bbox[1]);

	// Calculate coordinates of the text
	$x = intval( ($image_width/2)  - ($text_width/2) 	+ $offsetX) ;
	$y = intval( ($image_height/2) - ($text_height/2) );	// + $offsetY ;

	//calculate y baseline
	$y = $baseline = abs($font_size/2 - ($image_height) ) + $offsetY ;

	# This is our cordinates for X and Y
	#$x = $bbox[0] + $centroXimg  - ($bbox[2] / 2)	+ $offsetX ;
	#$y = $bbox[1] + $centroYimg  - ($bbox[6] / 2)	+ $offsetY ;

	# Write it text1
	# Add the text
	$imgText  = imagettftext($im, $font_size , $angle, $x, $y, $colorText, $fontfile, $text );
	# Verify if it failed
	if ($imgText===false) {
		imagestring($im, 1, 5, 5, "Error $text1", 0);
	}
}//end if($text!==false) {


# Enable interlancing
	imageinterlace($im, true);


# HEADERS
	header("Cache-Control: private, max-age=10800, pre-check=10800");
	header("Pragma: private");
	header("Expires: " . date(DATE_RFC822,strtotime(" 200 day")));

# No cache header
	#header("Cache-Control: no-cache, must-revalidate");

# Output to browser
	header('Content-Type: image/png;');
	header('Connection: close');
	imagepng($im);

# On finish destroy
	imagedestroy($im);


