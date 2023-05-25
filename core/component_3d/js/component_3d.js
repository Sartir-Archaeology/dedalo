// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL */
/*eslint no-undef: "error"*/



// imports
	import {dd_console} from '../../common/js/utils/index.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {common, create_source} from '../../common/js/common.js'
	import {component_common} from '../../component_common/js/component_common.js'
	import {render_edit_component_3d} from '../../component_3d/js/render_edit_component_3d.js'
	import {render_list_component_3d} from '../../component_3d/js/render_list_component_3d.js'

	import {upload} from '../../services/service_upload/js/service_upload.js'
	// import {render_mini_component_3d} from '../../component_3d/js/render_mini_component_3d.js'
	// import {render_player_component_3d} from '../../component_3d/js/render_player_component_3d.js'
	// import {render_viewer_component_3d} from '../../component_3d/js/render_viewer_component_3d.js'

	// Note about event_manager
	// the component_3d is configured by properties in the ontology,
	// it has subscribed to some events that comes defined in properties as: key_up_f2, key_up_esc, click_tag_tc
	// the events need to be linked to specific text_area and it's defined in ontology.



export const component_3d = function(){

	this.id

	// element properties declare
	this.model
	this.tipo
	this.section_tipo
	this.section_id
	this.mode
	this.lang

	this.section_lang
	this.context
	this.data
	this.parent
	this.node

	this.tools
	this.video
	this.quality

	this.fragment
}//end  component_3d



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// lifecycle
	component_3d.prototype.init					= component_common.prototype.init
	component_3d.prototype.build				= component_common.prototype.build
	component_3d.prototype.render				= common.prototype.render
	component_3d.prototype.refresh				= common.prototype.refresh
	component_3d.prototype.destroy				= common.prototype.destroy

	// change data
	component_3d.prototype.save					= component_common.prototype.save
	component_3d.prototype.update_data_value	= component_common.prototype.update_data_value
	component_3d.prototype.update_datum			= component_common.prototype.update_datum
	component_3d.prototype.change_value			= component_common.prototype.change_value
	component_3d.prototype.set_changed_data		= component_common.prototype.set_changed_data
	component_3d.prototype.build_rqo			= common.prototype.build_rqo

	// render
	component_3d.prototype.list					= render_list_component_3d.prototype.list
	component_3d.prototype.edit					= render_edit_component_3d.prototype.edit
	component_3d.prototype.tm					= render_edit_component_3d.prototype.edit
	component_3d.prototype.search				= render_edit_component_3d.prototype.search
	// component_3d.prototype.player			= render_player_component_3d.prototype.player
	// component_3d.prototype.viewer			= render_viewer_component_3d.prototype.viewer

	component_3d.prototype.change_mode			= component_common.prototype.change_mode



/**
* CREATE_POSTERFRAME
* 	Creates a new posterframe file from current_view overwriting old file if exists
* @param object viewer
* @return bool
*/
component_3d.prototype.create_posterframe = async function( viewer ) {

	const self = this

	// fallback to fixed self.viewer
		if (!viewer) {
			viewer = self.viewer
		}

	// image_blob
		const image_blob = await viewer.get_image({
			width	: 720,
			height	: 404
		})
		image_blob.name = self.tipo +'_'+ self.section_tipo +'_'+ self.section_id +'.jpg' // added name to the tmp file

	// debug
		if(SHOW_DEBUG===true) {
			console.log('3d create_posterframe image_blob:', image_blob);
		}

	// upload file (using service_upload)
		// upload file as another images to tmp directory
		const api_response = await upload({
			id					: self.id,
			file				: image_blob, // binary data as file
			resource_type		: '3d', // target dir
			allowed_extensions	: ['jpg'],
			key_dir				: '3d',
			max_size_bytes		: image_blob.size
		})
		if (!api_response.result) {
			console.error("Error on api_response:", api_response);
			return {
				result	: false,
				msg		: api_response.msg || 'Error on api_response'
			}
		}
		// file_data set
		const file_data = api_response.file_data
		// force to name as image_blob.name to prevent chunk mode issues
		file_data.name = image_blob.name


	// debug
		if(SHOW_DEBUG===true) {
			console.log('3d file_data (on upload finish):', file_data);
		}

	return new Promise(function(resolve){

		// move_file_to_dir
		const rqo = {
			dd_api	: 'dd_component_3d_api',
			action	: 'move_file_to_dir',
			source	: create_source(self),
			options	: {
				target_dir	: 'posterframe',
				file_data	: file_data
			}
		}
		// call to the API, fetch data and get response
		data_manager.request({
			body : rqo
		})
		.then(function(response){
			if(SHOW_DEVELOPER===true) {
				dd_console("-> upload_blob API response:",'DEBUG',response);
			}

			const result = response.result // array of objects

			resolve(result)
		})
	})
	// save file
	// const posterframe =  viewer.renderer.domElement.toDataURL("image/jpeg", 0.95);
	// const a = document.createElement('a');
	//	a.href = posterframe;
	//	a.download = 'a.jpg';
	//	document.body.appendChild(a);
	//	a.click();
}//end create_posterframe



// @license-end
