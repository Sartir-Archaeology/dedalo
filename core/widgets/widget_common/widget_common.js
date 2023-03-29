/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL */
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {data_manager} from '../../common/js/data_manager.js'
	// import * as instances from '../../common/js/instances.js'
	import {common} from '../../common/js/common.js'
	// import {ui} from '../../common/js/ui.js'



export const widget_common = function(){

	return true
}//end widget_common



/**
* COMMON FUNCTIONS
* extend functions from common
*/
// prototypes assign
	// lifecycle
	widget_common.prototype.destroy	= common.prototype.destroy
	widget_common.prototype.refresh	= common.prototype.refresh
	widget_common.prototype.render	= common.prototype.render



/**
* INIT
* Common init prototype to use in components as default
* @return bool true
*/
widget_common.prototype.init = async function(options) {

	const self = this

	// set vars
		self.id				= options.id
		self.tipo			= null
		self.section_tipo	= options.section_tipo
		self.section_id		= options.section_id
		self.lang			= options.lang
		self.mode			= options.mode
		self.model			= 'widget'
		self.value			= options.value
		self.datalist		= options.datalist
		self.ipo			= options.ipo
		self.name			= options.name
		self.properties		= options.properties
		self.caller			= options.caller
		self.ar_instances	= [] // array of children instances of current instance (used for autocomplete, etc.)

	// status update
		self.status = 'initiated'


	return true
}//end init



/**
* BUILD
* Generic widget build function. Load css files
* @param bool autoload
* @return promise bool
*/
widget_common.prototype.build = async function(autoload=false) {

	const self = this

	// status update
		self.status = 'building'

	// load self style
		// const tool_css_url = DEDALO_CORE_URL + '/widgets' + self.properties.path + "/css/" + self.name + ".css"
		// common.prototype.load_style(tool_css_url) // returns promise

	// autoload
		if (autoload===true) {

			const rqo = {
				action	: "get_widget_dato",
				dd_api	: 'dd_component_info',
				source	: {
					tipo			: self.caller.tipo,
					section_tipo	: self.caller.section_tipo,
					section_id		: self.caller.section_id,
					mode			: self.mode,
					widget_name		: self.name
				}
			}
			const api_response = await data_manager.request({
				body: rqo
			});

			if(api_response.result){
				self.value = api_response.result
			}
		}

	// status update
		self.status = 'built'


	return true
}//end build
