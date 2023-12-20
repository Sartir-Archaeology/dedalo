<?php
$global_start_time = hrtime(true);

// Turn off output buffering
	ini_set('output_buffering', 'off');
// Turn off PHP output compression
	// ini_set('zlib.output_compression', false);
// Flush (send) the output buffer and turn off output buffering
	// ob_end_flush();
	// while (@ob_end_flush());

	// Implicitly flush the buffer(s)
	// ini_set('implicit_flush', true);
	// ob_implicit_flush(true);

	// debug
		// $current = (hrtime(true) - $global_start_time) / 1000000;
		// error_log('--------------------------------------- current 0 ms: '.$current);



// header print as JSON data
	header('Content-Type: application/json');



// PUBLIC API HEADERS (!) TEMPORAL 16-11-2022
	// Allow CORS
	header('Access-Control-Allow-Origin: *');
	// header("Access-Control-Allow-Credentials: true");
	// header("Access-Control-Allow-Methods: GET,POST"); // GET,HEAD,OPTIONS,POST,PUT
	$allow_headers = [
		// 'Access-Control-Allow-Headers',
		// 'Origin,Accept',
		// 'X-Requested-With',
		'Content-Type',
		// 'Access-Control-Request-Method',
		// 'Access-Control-Request-Headers'
		'Content-Range'
	];
	header('Access-Control-Allow-Headers: '. implode(', ', $allow_headers));



	// CORS preflight OPTIONS requests
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']==='OPTIONS') {
			// error_log('Ignored '.print_r($_SERVER['REQUEST_METHOD'], true));
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Ignored call ' . $_SERVER['REQUEST_METHOD'];
			error_log('Error: '.$response->msg);
			echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit( 0 );
		}



// php version check
	$version = explode('.', phpversion());
	if ($version[0]<8 || ($version[0]==8 && $version[1]<1)) {
		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed. This PHP version is not supported ('.phpversion().'). You need: >=8.1';
		error_log('Error: '.$response->msg);
		echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		die();
	}



// includes
	// config dedalo
	include dirname(dirname(dirname(dirname(dirname(__FILE__))))) .'/config/config.php';



// get post vars. file_get_contents returns a string
	$str_json = file_get_contents('php://input');
	// error_log(print_r($str_json,true));
	// error_log(print_r($_REQUEST,true));
	if (!empty($str_json)) {
		$rqo = json_decode( $str_json );
	}

	// debug
		// $current = (hrtime(true) - $global_start_time) / 1000000;
		// error_log('--------------------------------------- current 1 (after file_get_contents) ms: '.$current);



// non php://input cases
	if (!empty($_FILES)) {

		// files case. Received files case. Uploading from tool_upload or text editor images upload
		if (!isset($rqo)) {
			$rqo = new stdClass();
				$rqo->action	= 'upload';
				$rqo->dd_api	= 'dd_utils_api';
				$rqo->options	= new stdClass();
		}
		foreach($_POST as $key => $value) {
				$rqo->options->{$key} = safe_xss($value);
		}
		foreach($_GET as $key => $value) {
				$rqo->options->{$key} = safe_xss($value);
		}
		foreach($_FILES as $key => $value) {
				$rqo->options->{$key} = $value;
		}

	}elseif (!empty($_REQUEST)) {

		// GET/POST case
		if (isset($_REQUEST['rqo'])) {
			$rqo = json_handler::decode($_REQUEST['rqo']);
		}else{
			$rqo = (object)[
				'source' => (object)[]
			];
			foreach($_REQUEST as $key => $value) {
				if (in_array($key, request_query_object::$direct_keys)) {
					$rqo->{$key} = safe_xss($value);
				}else{
					$rqo->source->{$key} = safe_xss($value);
				}
			}
		}
	}



// rqo check. Some cases like preflight, do not generates a rqo
	if (empty($rqo)) {
		error_log('API JSON index. ! Ignored empty rqo');
		debug_log(__METHOD__
			." Error on API : Empty rqo (Some cases like preflight, do not generates a rqo) " . PHP_EOL
			.' $_REQUEST: '. to_string($_REQUEST)
			, logger::ERROR
		);
		exit( 0 );
	}



// prevent_lock from session
	$session_closed = false;
	if (isset($rqo->prevent_lock) && $rqo->prevent_lock===true) {
		// close current session and set as only read
		session_write_close();
		$session_closed = true;
	}



// dd_dd_manager
	// try {

		$dd_manager	= new dd_manager();
		$response	= $dd_manager->manage_request( $rqo );

		// debug
			// $current = (hrtime(true) - $global_start_time) / 1000000;
			// error_log('--------------------------------------- current 2 ms: '.$current);

		// close current session and set as read only
			if ($session_closed===false) {
				session_write_close();
			}

		// debug
			if(SHOW_DEBUG===true) {
				// real_execution_time add
				$response->debug						= $response->debug ?? new stdClass();
				$response->debug->real_execution_time	= exec_time_unit($global_start_time,'ms').' ms';
			}

		// server_errors. bool true on debug_log write log with LOGGER_LEVEL as 'ERROR' or 'CRITICAL'
			$response->dedalo_last_error	= $_ENV['DEDALO_LAST_ERROR'] ?? null;
			// $response->dedalo_last_error	= 'fake error!';

	// } catch (Throwable $e) { // For PHP 7

	// 	$result = new stdClass();
	// 		$result->result	= false;
	// 		$result->msg	= (SHOW_DEBUG===true)
	// 			? 'Throwable Exception when calling Dédalo API: '.PHP_EOL.'  '. $e->getMessage()
	// 			: 'Throwable Exception when calling Dédalo API. Contact with your admin';
	// 		$result->debug	= (object)[
	// 			'rqo' => $rqo
	// 		];

	// 	trigger_error($e->getMessage());

	// } catch (Exception $e) { // For PHP 5

	// 	$result = new stdClass();
	// 		$result->result	= false;
	// 		$result->msg	= (SHOW_DEBUG===true)
	// 			? 'Exception when calling Dédalo API: '.PHP_EOL.'  '. $e->getMessage()
	// 			: 'Exception when calling Dédalo API. Contact with your admin';
	// 		$result->debug	= (object)[
	// 			'rqo' => $rqo
	// 		];

	// 	trigger_error($e->getMessage());
	// }



// output the response JSON string
	// $output_string = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$output_string = isset($rqo->pretty_print)
		? json_handler::encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
		: json_handler::encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	// debug (browser Server-Timing)
		// header('Server-Timing: miss, db;dur=53, app;dur=47.2');
		// $current = (hrtime(true) - $global_start_time) / 1000000;
		// header('Server-Timing: API;dur='.$current);



// output_string_and_close_connection
	// function output_string_and_close_connection($string_to_output) {
	// 	// set_time_limit(0);
	// 	ignore_user_abort(true);
	// 	// buffer all upcoming output - make sure we care about compression:
	// 	if(!ob_start("ob_gzhandler"))
	// 	    ob_start();
	// 	echo $string_to_output;
	// 	// get the size of the output
	// 	$size = ob_get_length();
	// 	// send headers to tell the browser to close the connection
	// 	header("Content-Length: $size");
	// 	header('Connection: close');
	// 	// flush all output
	// 	ob_end_flush();
	// 	// ob_flush();
	// 	flush();
	// 	// close current session
	// 	// if (session_id()) session_write_close();
	// }

	// debug
		// $current = (hrtime(true) - $global_start_time) / 1000000;
		// error_log('--------------------------------------- current 3 (before echo) ms: '.$current);
		// dump($_SESSION, ' _SESSION ++ '.to_string());



// output_string_and_close_connection($output_string);
	echo $output_string;

	// debug
		// $current = (hrtime(true) - $global_start_time) / 1000000;
		// error_log('--------------------------------------- current FINAL (after echo) ms: '.$current);
