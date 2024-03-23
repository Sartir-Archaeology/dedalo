<?php
/**
* DD_TOOLS_API
* Manage API REST data with Dédalo
*
*/
final class dd_tools_api {



	/**
	* USER_TOOLS
	* Get user authorized tools filtered by custom list (optional)
	* @param object $rqo
	* {
	*	dd_api	: 'dd_tools_api',
	*	action	: 'user_tools',
	*	source	: source,
	*	options	: {
	*		ar_requested_tools : ar_requested_tools
	*	}
	* }
	* @return object $response
	*/
	public static function user_tools(object $rqo) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__METHOD__.']. ';
			$response->error	= null;

		// options
			$options = $rqo->options;
			// list of requested tools
			$ar_requested_tools	= $options->ar_requested_tools ?? null;

		// all user authorized tools
			$user_id	= logged_user_id();
			$user_tools	= tool_common::get_user_tools($user_id);

		$result = [];
		foreach ($user_tools as $tool) {

			if(!empty($ar_requested_tools) && !in_array($tool->name, $ar_requested_tools)) {
				continue;
			}

			$simple_tool_object = tool_common::create_tool_simple_context($tool);

			$result[] = $simple_tool_object;
		}

		// response
			$response->result	= $result;
			$response->msg		= 'Ok. Request done: '.__METHOD__;


		return $response;
	}//end user_tools



	/**
	* TOOL_REQUEST
	* Call to tool method given and return and object with the response
	*
	* Class file of current tool must be exists in path: DEDALO_TOOLS_PATH / my_tool_name / class.my_tool_name.php
	* Method must be static and accept a only one object argument
	* Method must return an object like { result: mixed, msg: string }
	*
	* @param object $rqo
	* sample:
	* {
	* 	action: "tool_request"
	* 	dd_api: "dd_utils_api"
	* 	source: {
	* 		typo: "source",
	* 		action: "delete_tag",
	* 		model: "tool_indexation",
	* 		arguments: {
	*   		indexing_component_tipo: "rsc860"
	*			main_component_lang: "lg-eng"
	*			main_component_tipo: "rsc36"
	*			section_id: "1"
	*			section_tipo: "rsc167"
	*			tag_id: "5"
	*   	}
	* 	}
	* }
	* @return object response { result: mixed, msg: string }
	*/
	public static function tool_request(object $rqo) : object {

		// options
			$options		= $rqo->options ?? [];
			$fn_arguments	= $options;

		// source
			$source			= $rqo->source;
			$tool_name		= $source->model;
			$tool_method	= $source->action;

		// response
			$response = new stdClass();
				$response->result	= false;
				$response->msg		= 'Error. Request failed ['.__METHOD__.']. ';
				$response->error	= null;

		// load tool class file
			$class_file = DEDALO_TOOLS_PATH . '/' .$tool_name. '/class.' . $tool_name .'.php';
			if (!file_exists($class_file)) {
				$response->msg = 'Error. tool class_file do not exists. Create a new one in format class.my_tool_name.php ';
				if(SHOW_DEBUG===true) {
					$response->msg .= '. file: '.$class_file;
				}
				return $response;
			}
			require $class_file;

		// method (static)
			if (!method_exists($tool_name, $tool_method)) {
				$response->msg = 'Error. tool method \''.$tool_method.'\' do not exists ';
				return $response;
			}
			try {

				$fn_result = call_user_func(array($tool_name, $tool_method), $fn_arguments);

			} catch (Exception $e) { // For PHP 5

				debug_log(__METHOD__
					." Exception caught [tool_request] : ". $e->getMessage()
					, logger::ERROR
				);
				trigger_error($e->getMessage());


				$fn_result = new stdClass();
					$fn_result->result	= false;
					$fn_result->msg		= 'Error. Request failed on call_user_func tool_method: '.$tool_method;

			}

			$response = $fn_result;


		return $response;
	}//end tool_request



}//end dd_utils_api
