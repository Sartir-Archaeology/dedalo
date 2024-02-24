<?php
/**
* CLASS TOOL_TIME_MACHINE
*
*
*/
class tool_time_machine extends tool_common {



	/**
	* APPLY_VALUE
	* Set user selected value from time machine to current element data
	* @param object $request_options
	* @return object $response
	*/
	public static function apply_value(object $request_options) : object {
		$start_time = start_time();

		$response = new stdClass();
			$response->result	= false;
			$response->msg		= 'Error. Request failed ['.__FUNCTION__.']';

		// options get and set
			$options = new stdClass();
				$options->section_tipo		= $request_options->section_tipo ?? null;
				$options->section_id		= $request_options->section_id ?? null;
				$options->tipo				= $request_options->tipo ?? null;
				$options->lang				= $request_options->lang ?? null;
				$options->matrix_id			= $request_options->matrix_id ?? null;
				$options->caller_dataframe	= $request_options->caller_dataframe ?? null;
				$options->has_dataframe		= $request_options->has_dataframe ?? null;
				$options->ddo_map			= $request_options->ddo_map ?? null;
				$options->source_data		= $request_options->source_data ?? null;
				$options->dataframe_data	= $request_options->dataframe_data ?? null;

		// short vars
			$section_tipo		= $options->section_tipo;
			$section_id			= $options->section_id;
			$tipo				= $options->tipo;
			$lang				= $options->lang;
			$matrix_id			= $options->matrix_id;
			$model				= RecordObj_dd::get_modelo_name_by_tipo($tipo,true);
			$caller_dataframe	= $options->caller_dataframe;
			$has_dataframe		= $options->has_dataframe;
			$ddo_map			= $options->ddo_map;
			$source_data		= $options->source_data;
			$dataframe_data		= $options->dataframe_data;

		// data. extract data from matrix_time_machine table
			$RecordObj_time_machine	= new RecordObj_time_machine($matrix_id);

			// main component with dataframe
			// if main component has a dataframe, his data is calculated at API build_json_rows() action search
			// it was contaminated with his dataframe and his record in time_machine table should not has the data to be restored
			// in those cases uses the data sent by the client.
			$dato_time_machine		= ( $has_dataframe && $has_dataframe===true )
				? $source_data->value
				: $RecordObj_time_machine->get_dato();

		// apply time machine data to element and save
			switch (true) {

				case ($model==='section'):
					// recovering section case

					// section. Inject data
						$element = section::get_instance(
							$section_id,
							$tipo,
							'edit',
							false
						);

					// Set data overwrites the data of the current element
						$element->set_dato($dato_time_machine);

					// Save the component with a new updated data from time machine
						$result = $element->Save((object)[
							'forced_create_record' => $section_id
						]);

					// section->Save returns int $section_id on success or null on fail
						if ($result==$section_id) {

							// matrix_time_machine restore state from 'deleted' to 'recovered'

							// Set state 'recovered' at matrix_time_machine record (to avoid be showed for recover later)
								$RecordObj_time_machine	= new RecordObj_time_machine($matrix_id);
									$RecordObj_time_machine->set_state('recovered');

								$tm_result = $RecordObj_time_machine->Save();

							// reset section session sqo
								$sqo_id	= implode('_', ['section', $section_tipo]); // cache key sqo_id
								if (isset($_SESSION['dedalo']['config']['sqo'][$sqo_id])) {
									unset($_SESSION['dedalo']['config']['sqo'][$sqo_id]);
								}

							// section recover media files. Expected array, null on fails
								$restored_result = $element->restore_deleted_section_media_files();
								if (is_null($restored_result)) {
									debug_log(__METHOD__." Error on restore deleted media files ".to_string(), logger::ERROR);
								}
								// add to response
								$response->restore_deleted_section_media_files = $restored_result;

							// LOGGER ACTIVITY : QUE(action normalized like 'LOAD EDIT'), LOG LEVEL(default 'logger::INFO'), TIPO(like 'dd120'), DATOS(array of related info)
								$matrix_table = common::get_matrix_table_from_tipo($section_tipo);
								logger::$obj['activity']->log_message(
									'RECOVER SECTION',
									logger::INFO,
									$section_tipo,
									null,
									[
										'msg'			=> 'Recovered section record from time machine',
										'section_id'	=> $section_id,
										'section_tipo'	=> $section_tipo,
										'top_id'		=> $section_id,
										'top_tipo'		=> $section_tipo,
										'table'			=> $matrix_table,
										'tm_id'			=> $matrix_id
									]
								);
						}
					break;

				case (strpos($model, 'component_')===0):
					// recovering component case

					// component. Inject tm data to the component
						$element = component_common::get_instance(
							$model,
							$tipo,
							$section_id,
							'list', // the component always in list because the edit could fire a save with the dato_default
							$lang,
							$section_tipo,
							false
						);

					// dataframe caller
						if (!empty($caller_dataframe)) {
							$element->set_caller_dataframe($caller_dataframe);
						}

					// Set data overwrites the data of the current element
						$element->set_dato($dato_time_machine);

					// Save the component with a new updated data from time machine
						$result = $element->Save();

					// dataframe
						// check if the main component has a dataframe to save his data too
						if($has_dataframe === true){
							$dataframe_ddo = $ddo_map->dataframe_ddo;
							// delete all data of the dataframe
							// it will delete all section_id_key
							// create the dataframe component
								$dataframe_component_to_delete = component_common::get_instance(
									$dataframe_ddo->model,
									$dataframe_ddo->tipo,
									$section_id,
									'list',
									DEDALO_DATA_NOLAN,
									$dataframe_ddo->section_tipo
								);
								$dataframe_component_to_delete->empy_full_data_associated_to_main_component();
							foreach ($dataframe_data as $key => $current_dataframe_data) {

								if($current_dataframe_data === null || empty($current_dataframe_data->value) ){
									continue;
								}
								// create new caller_dataframe with the current data
								$caller_dataframe = new stdClass();
									$caller_dataframe->section_id_key	= $current_dataframe_data->section_id_key;
									$caller_dataframe->section_tipo		= $current_dataframe_data->section_tipo;
								// // create the dataframe component
								$dataframe_component = component_common::get_instance(
									$dataframe_ddo->model,
									$dataframe_ddo->tipo,
									$section_id,
									'list', // the component always in tm because the edit could fire a save with the dato_default
									$lang,
									$dataframe_ddo->section_tipo,
									true,
									$caller_dataframe
								);

								$dataframe_component->set_dato( $current_dataframe_data->value );

								$dataframe_result = $dataframe_component->Save();
							}// end foreach ($dataframe_data as $key => $current_dataframe_data)
						}// end if($has_dataframe === true)


					// LOGGER ACTIVITY
						$matrix_table = common::get_matrix_table_from_tipo($section_tipo);
						logger::$obj['activity']->log_message(
							'RECOVER COMPONENT',
							logger::INFO,
							$section_tipo,
							null,
							[
								'msg'			=> 'Recovered component data from time machine',
								'model'			=> $model,
								'section_id'	=> $section_id,
								'section_tipo'	=> $section_tipo,
								'table'			=> $matrix_table,
								'tm_id'			=> $matrix_id
							]
						);
					break;

				default:
					// invalid model

					// error response
						$msg = ' Error on set time machine data. Model is not valid: '.to_string($model);
						debug_log(__METHOD__. $msg, logger::ERROR);
						$response->msg		= $msg;
						$response->error	= $msg;

					return $response;
					break;
			}


		$response->result	= true;
		$response->msg		= 'OK. Request done ['.__FUNCTION__.']';

		// debug
			if(SHOW_DEBUG===true) {
				$debug = new stdClass();
					$debug->exec_time	= exec_time_unit($start_time,'ms').' ms';
				$response->debug = $debug;
			}


		return (object)$response;
	}//end apply_value



}//end class tool_time_machine
