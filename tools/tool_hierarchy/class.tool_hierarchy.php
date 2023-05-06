<?php
/**
* CLASS TOOL_HIERARCHY
* Help to generate new custom Ontologies
*
*/
class tool_hierarchy extends tool_common {



	/**
	* GENERATE_VIRTUAL_SECTION
	* Exec a custom action called from client
	* Note that tool config is stored in the tool section data (tools_register)
	* @param object $options
	* @return object $response
	*/
	public static function generate_virtual_section(object $options) : object {

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';

		// options
			$section_id		= $options->section_id;
			$section_tipo	= $options->section_tipo;

		// create a new virtual section from real
			$hierarchy_response = hierarchy::generate_virtual_section((object)[
				'section_id'	=> $section_id,
				'section_tipo'	=> $section_tipo
			]);

		// response
			$response->result	= $hierarchy_response->result;
			$response->msg		= $hierarchy_response->msg ;


		return $response;
	}//end generate_virtual_section



}//end class tool_hierarchy
