<?php
declare(strict_types=1);
/**
* DD_MANAGER
* Manage API web
*
*/
final class dd_manager {



	static $version = "1.0.0"; // 05-06-2019



	/**
	* __CONSTRUCT
	*/
	public function __construct() {

	}//end __construct



	/**
	* MANAGE_REQUEST
	* @param object $rqo
	* @return object $response
	*/
	final public function manage_request( object $rqo ) : object {
		$api_manager_start_time = start_time();

		// debug
			// dump($rqo, ' MANAGE_REQUEST rqo ++++++++++++++++++++++++++++++ '.to_string());
			if(SHOW_DEBUG===true) {
				$text			= 'API REQUEST ' . $rqo->action;
				$text_length	= strlen($text) +1;
				$nchars			= 200;
				$line			= $text .' '. str_repeat(">", $nchars - $text_length).PHP_EOL.json_encode($rqo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL.str_repeat("<", $nchars).PHP_EOL;
				debug_log(__METHOD__ . PHP_EOL . $line, logger::DEBUG);
			}

		// logged check
			$no_login_needed_actions = [
				'start',
				'change_lang',
				'login',
				'get_login_context',
				'install',
				'get_install_context',
				'get_environment'
			];
			if (true===in_array($rqo->action, $no_login_needed_actions)) {
				// do not check login here
			}else{
				if (login::is_logged()!==true) {
					$response = new stdClass();
						$response->result	= false;
						$response->msg		= 'Error. user is not logged ! [action:'.$rqo->action.']';
						$response->error	= 'not_logged';
					return $response;
				}
			}

		// rqo check
			if (!is_object($rqo) || !property_exists($rqo,'action')) {

				$response = new stdClass();
					$response->result	= false;
					$response->msg		= "Invalid action var (not found in rqo)";
					$response->error	= 'Undefined method';

				debug_log(__METHOD__." $response->msg ".to_string(), logger::ERROR);

				return $response;
			}

		// actions
			$dd_api_type	= $rqo->dd_api ?? 'dd_core_api';
			$dd_api			= $dd_api_type; // new $dd_api_type(); // class selected
			if ( !method_exists($dd_api, $rqo->action) ) {
				// error
				$response = new stdClass();
					$response->result	= false;
					$response->msg		= "Error. Undefined $dd_api_type method (action) : ".$rqo->action;
					$response->error	= 'Undefined method';
					$response->action	= $rqo->action;
			}else{
				// success
				if($dd_api==='dd_area_maintenance_api'){
					// check access to maintenance area
					$permissions = common::get_permissions(DEDALO_AREA_MAINTENANCE_TIPO, DEDALO_AREA_MAINTENANCE_TIPO);
					if ($permissions<2) {
						$response = new stdClass();
							$response->result	= false;
							$response->msg		= 'Error. user has not permissions ! [action:'.$rqo->action.']';
							$response->error	= 'permissions error';
						return $response;
					}
				}
				$response			= $dd_api::{$rqo->action}( $rqo );
				$response->action	= $rqo->action;
			}

		// debug
			if(SHOW_DEBUG===true || SHOW_DEVELOPER===true) {
				$total_time_api_exec = exec_time_unit($api_manager_start_time,'ms').' ms';
				$api_debug = new stdClass();
					$api_debug->api_exec_time	= $total_time_api_exec;
					$api_debug->memory_usage	= dd_memory_usage();
					$api_debug->rqo				= $rqo;
					$api_debug->rqo_string		= is_object($rqo)
						? json_encode($rqo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
						: $rqo;

				if (isset($response->debug)) {
					// add to existing debug properties
					foreach ($api_debug as $key => $value) {
						$response->debug->{$key} = $value;
					}
				}else{
					// create new debug property
					$response->debug = $api_debug;
				}
				//dump($response->debug, ' $response->debug ++ '.to_string($rqo->action));
				// debug_log("API REQUEST $total_time ".str_repeat(">", 70).PHP_EOL.json_encode($rqo, JSON_PRETTY_PRINT).PHP_EOL.str_repeat("<", 171), logger::DEBUG);
				// debug_log(json_encode($rqo, JSON_PRETTY_PRINT) .PHP_EOL. "API REQUEST $total_time ".str_repeat(">", 70), logger::DEBUG);
				// $line = "API REQUEST total_time: $total_time ".str_repeat("<", 89); // 164
				// debug_log($line, logger::DEBUG);
				// if ($rqo->action==='read') {
				// 	dump($response, ' response ++ '.to_string());
				// }

				// end line info
					$id				= $rqo->id ?? $rqo->source->tipo ?? '';
					$text			= 'API REQUEST ' . $rqo->action . ' ' . $id . ' END IN ' . $total_time_api_exec;
					$text_length	= strlen($text) +1;
					$nchars			= 200;
					$repeat 		= ($nchars - $text_length) ?? 0;
					$line			= $text .' '. $repeat .PHP_EOL;
					debug_log(__METHOD__ . PHP_EOL . $line, logger::DEBUG);
			}


		return $response;
	}//end manage_request



}//end class dd_manager
