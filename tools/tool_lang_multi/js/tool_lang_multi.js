/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// import
	import {clone, dd_console} from '../../../core/common/js/utils/index.js'
	import {data_manager} from '../../../core/common/js/data_manager.js'
	import {get_instance, delete_instance} from '../../../core/common/js/instances.js'
	import {common} from '../../../core/common/js/common.js'
	import {tool_common} from '../../tool_common/js/tool_common.js'
	import {render_tool_lang_multi} from './render_tool_lang_multi.js'



/**
* TOOL_LANG_MULTI
* Tool to translate contents from one language to the rest of the configured languages in any text component
*/
export const tool_lang_multi = function () {
	
	this.id				= null
	this.model			= null
	this.mode			= null
	this.node			= null
	this.ar_instances	= null
	this.status			= null
	this.events_tokens	= null
	this.type			= null
	this.source_lang	= null
	this.target_lang	= null
	this.langs			= null
	this.caller			= null


	return true
};//end page



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	tool_lang_multi.prototype.render 	= common.prototype.render
	tool_lang_multi.prototype.destroy 	= common.prototype.destroy
	tool_lang_multi.prototype.edit 		= render_tool_lang_multi.prototype.edit



/**
* INIT
*/
tool_lang_multi.prototype.init = async function(options) {

	const self = this

	// set the self specific vars not defined by the generic init (in tool_common)
		self.trigger_url 	= DEDALO_TOOLS_URL + "/tool_lang_multi/trigger.tool_lang_multi.php"
		self.lang 			= options.lang // page_globals.dedalo_data_lang
		self.langs 			= page_globals.dedalo_projects_default_langs
		self.source_lang 	= options.caller.lang
		self.target_lang 	= null


	// call the generic commom tool init
		const common_init = tool_common.prototype.init.call(this, options);


	return common_init
};//end init



/**
* BUILD_CUSTOM
*/
tool_lang_multi.prototype.build = async function(autoload=false) {

	const self = this

	// call generic commom tool build
		const common_build = tool_common.prototype.build.call(this, autoload);

	// specific actions..


	return common_build
};//end build_custom



/**
* LOAD_COMPONENT
*/
tool_lang_multi.prototype.load_component = async function(lang) {

	const self = this

	const component = self.caller

	const context = JSON.parse(JSON.stringify(component.context))
		  context.lang = lang

	const component_instance = await get_instance({
		model 			: component.model,
		tipo 			: component.tipo,
		section_tipo 	: component.section_tipo,
		section_id 		: component.section_id,
		mode 			: component.mode==='edit_in_list' ? 'edit' : component.mode,
		lang 			: lang,
		section_lang 	: component.lang,
		//parent 			: component.parent,
		type 			: component.type,
		context 		: context,
		data 			: {value:[]},
		datum 			: component.datum,
	})

	// set current tool as component caller (to check if component is inside tool or not)
		component_instance.caller = this

	await component_instance.build(true)

	// add
		const instance_found = self.ar_instances.find( el => el===component_instance )
		if (component_instance!==self.caller && typeof instance_found==="undefined") {
			self.ar_instances.push(component_instance)
		}


	return component_instance
};//end load_component



/**
* AUTOMATIC_TRANSLATION
*/
tool_lang_multi.prototype.automatic_translation = async function(translator, source_lang, target_lang, buttons_container) {

	const self = this

	const body = {
		url 			: self.trigger_url,
		mode 			: 'automatic_translation',
		source_lang 	: source_lang,
		target_lang 	: target_lang,
		component_tipo	: self.caller.tipo,
		section_id  	: self.caller.section_id,
		section_tipo  	: self.caller.section_tipo,
		translator 		: JSON.parse(translator)
	}

	const handle_errors = function(response) {
		if (!response.ok) {
			throw Error(response.statusText);
		}
		return response;
	}

	const trigger_response = await fetch(
 		self.trigger_url,
 		{
			method		: 'POST',
			mode		: 'cors',
			cache		: 'no-cache',
			credentials	: 'same-origin',
			headers		: {'Content-Type': 'application/json'},
			redirect	: 'follow',
			referrer	: 'no-referrer',
			body		: JSON.stringify(body)
		})
		.then(handle_errors)
		.then(response => response.json()) // parses JSON response into native Javascript objects
		.catch(error => {
			console.error("!!!!! REQUEST ERROR: ",error)
			return {
				result 	: false,
				msg 	: error.message,
				error 	: error
			}
		});

		//trigger_fetch.then((trigger_response)=>{

			// user messages
				const msg_type = (trigger_response.result===false) ? 'error' : 'ok'
				//if (trigger_response.result===false) {
					ui.show_message(buttons_container, trigger_response.msg, msg_type)
				//}

			// reload target lang
				const target_component_container = self.node[0].querySelector('.target_component_container')
				add_component(self, target_component_container, target_lang)

			// debug
				if(SHOW_DEBUG===true) {
					console.log("trigger_response:",trigger_response);
				}
		//})


	return trigger_response
};//end automatic_translation
