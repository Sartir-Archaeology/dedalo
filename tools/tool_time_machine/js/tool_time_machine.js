// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// import
	import {event_manager} from '../../../core/common/js/event_manager.js'
	import {get_instance} from '../../../core/common/js/instances.js'
	import {clone, dd_console} from '../../../core/common/js/utils/index.js'
	import {data_manager} from '../../../core/common/js/data_manager.js'
	import {common, create_source} from '../../../core/common/js/common.js'
	import {tool_common, load_component} from '../../tool_common/js/tool_common.js'
	import {render_tool_time_machine, add_component} from './render_tool_time_machine.js'
	// import {render_time_machine_view} from './render_time_machine_view.js'
	// import {paginator} from '../../../core/paginator/js/paginator.js'



/**
* TOOL_TIME_MACHINE
* Tool to translate contents from one language to other in any text component
*/
export const tool_time_machine = function () {

	this.id						= null
	this.model					= null
	this.mode					= null
	this.lang					= null
	this.node					= null
	this.ar_instances			= null
	this.status					= null
	this.events_tokens			= []
	this.type					= null

	this.caller					= null
	this.service_time_machine	= null
	// this.section				= null// custom section generated in tm mode on build
	this.button_apply			= null
	this.selected_matrix_id		= null
	this.modal_container		= null


	return true
}//end tool_time_machine



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	tool_time_machine.prototype.render	= tool_common.prototype.render
	tool_time_machine.prototype.refresh	= common.prototype.refresh
	tool_time_machine.prototype.destroy	= common.prototype.destroy
	tool_time_machine.prototype.edit	= render_tool_time_machine.prototype.edit



/**
* INIT
* @param object options
* @return bool
*/
tool_time_machine.prototype.init = async function(options) {

	const self = this

	// call the generic common tool init
		const common_init = await tool_common.prototype.init.call(this, options);

	// fix dedalo_projects_langs
		self.langs = page_globals.dedalo_projects_default_langs

	// fix lang from caller
		self.lang = self.caller && self.caller.lang
			? self.caller.lang
			: null

	// events subscribe. User click over list record eye icon (preview)
		self.events_tokens.push(
			event_manager.subscribe('tm_edit_record', fn_tm_edit_record)
		)
		async function fn_tm_edit_record(data) {
			const matrix_id	= data.matrix_id
			const date		= data.date
			// render. Create and add new component to preview container
			const load_mode = 'tm' // (!) Remember use tm mode to force component to load data from time machine table
			add_component(
				self,
				self.preview_component_container,
				self.lang,
				date,
				load_mode,
				matrix_id
			)
			// fix selected matrix_id
			self.selected_matrix_id = matrix_id
			// show Apply button
			self.button_apply.classList.remove('hide','lock')
		}//end fn_tm_edit_record


	return common_init
}//end init



/**
* BUILD (CUSTOM)
* @param bool autoload
* 	callback function 'load_ddo_map'
* @return promise bool
*/
tool_time_machine.prototype.build = async function(autoload=false) {

	const self = this

	// ddo_map. Update ddo_map elements lang before common build
		// Note that when user switch lang from tool lang selector, we need refresh whole tool
		// re-building ddo_map from tool_common. To prevent re-create the first ddo_imap items
		// it its necessary to update the items lang before (only for translatable elements)
		self.tool_config.ddo_map = self.tool_config.ddo_map || []
		const ddo_map_length = self.tool_config.ddo_map.length
		for (let i = 0; i < ddo_map_length; i++) {
			const item = self.tool_config.ddo_map[i]
			if (item.lang && item.lang!==page_globals.dedalo_data_nolan) {
				item.lang = self.lang
			}
		}

	// call generic common tool build
		const common_build = await tool_common.prototype.build.call(self, autoload);

	// service_time_machine
		try {

			// fix main_element for convenience
				const main_element_ddo	= self.tool_config.ddo_map.find(el => el.role==='main_element')

			// section case. (!) note that section is not loaded automatically from tool common build
				if (main_element_ddo.model==='section') {
					const element_options = {
						model			: main_element_ddo.model,
						mode			: main_element_ddo.mode,
						tipo			: main_element_ddo.tipo,
						section_tipo	: main_element_ddo.section_tipo,
						section_id		: main_element_ddo.section_id,
						lang			: main_element_ddo.lang,
						type			: main_element_ddo.type,
						properties 		: main_element_ddo.properties || null,
						id_variant		: self.model,  // id_variant prevents id conflicts
						caller			: self // set tool as caller of the element :-)
					}
					const instance = await get_instance(element_options) // load and init
					await instance.build(true)
					self.ar_instances.push(instance)
				}

			// fix main_element
				self.main_element = self.ar_instances.find(el => el.tipo===main_element_ddo.tipo)

			// ddo_map for service_time_machine. Section uses is request_config_object show
			// NOTE: The ddo_map will be changed in service_time_machine to mode = list
				const ddo_map = self.main_element.model==='section'
					? self.main_element.request_config_object.show.ddo_map
					: [{
							tipo			: self.main_element.tipo,
							type			: self.main_element.type,
							typo			: 'ddo',
							model			: self.main_element.model,
							section_tipo	: self.main_element.section_tipo,
							parent			: self.main_element.section_tipo,
							label			: self.main_element.label,
							mode			: 'list',
							view			: 'text'
					   }]

			// ignore_columns
				const ignore_columns = self.main_element.model==='section'
					? [
						'dd1573', // matrix_id
						'dd547', // when
						'dd543', // who
						'dd546' // where
						]
					: []

			 // template_columns
				const template_columns = self.main_element.model==='section'
					? null
					: '5rem 8rem 8rem 16rem 1fr 5fr'

			// time_machine. Create, build and assign the time machine service to the instance
				self.service_time_machine = await get_instance({
					model			: 'service_time_machine',
					section_tipo	: self.caller.section_tipo,
					section_id		: self.caller.section_id,
					view			: 'tool',
					id_variant		: self.main_element.tipo +'_'+ self.model,
					caller			: self,
					config			: {
						id					: 'tool_tm',
						model				: self.main_element.model,
						tipo				: self.main_element.tipo,
						lang				: self.main_element.lang,
						template_columns	: template_columns,
						ignore_columns		: ignore_columns,
						ddo_map				: ddo_map
					}
				})

			// build
				await self.service_time_machine.build(true)

			// add to self instances list
				self.ar_instances.push(self.service_time_machine)

			// (!) force self.caller_is_calculated as false to avoid to re-use calculated
			// component instances on lang change
				self.caller_is_calculated = false

		} catch (error) {
			self.error = error
			console.error(error)
		}


	return common_build
}//end build_custom



/**
* GET_COMPONENT
* Loads component to place in respective containers: current preview and preview version
* @param string lang
* @param string mode
* @param string|int|null matrix_id
* @return object component_instance
*/
tool_time_machine.prototype.get_component = async function(lang, mode, matrix_id=null) {
	// console.log("))))))))))) get_component:",lang, mode, matrix_id);

	const self = this

	// to_delete_instances. Select instances with same tipo and property matrix_id not empty
		const to_delete_instances = self.ar_instances.filter(el => el.tipo===self.main_element.tipo && el.matrix_id)

	// instance_options (clone context and edit)
		const options = Object.assign(clone(self.main_element.context), {
			self				: self,
			lang				: lang,
			mode				: 'edit', // mode,
			section_id			: self.main_element.section_id,
			matrix_id			: matrix_id,
			data_source			: 'tm',
			to_delete_instances	: to_delete_instances // array of instances to delete after create the new on
		})

	// call generic common tool build
		const component_instance = await load_component(options);


	return component_instance
}//end get_component



/**
* APPLY_VALUE
* Set selected version value to active component and close the tool
* @param object options
* @return promise
*/
tool_time_machine.prototype.apply_value = function(options) {

	const self = this

	// options
		const section_id	= options.section_id
		const section_tipo	= options.section_tipo
		const tipo			= options.tipo
		const lang			= options.lang
		const matrix_id		= options.matrix_id

	// source. Note that second argument is the name of the function to manage the tool request like 'apply_value'
	// this generates a call as my_tool_name::my_function_name(options)
		const source = create_source(self, 'apply_value')

	// rqo
		const rqo = {
			dd_api	: 'dd_tools_api',
			action	: 'tool_request',
			source	: source,
			options	: {
				section_id		: section_id,
				section_tipo	: section_tipo,
				tipo			: tipo,
				lang			: lang,
				matrix_id		: matrix_id
			}
		}

	// dataframe caller
		if (self.caller_dataframe) {
			rqo.options.caller_dataframe = self.caller_dataframe
		}

	// call to the API, fetch data and get response
		return new Promise(function(resolve){

			data_manager.request({
				body : rqo
			})
			.then(function(response){
				if(SHOW_DEVELOPER===true) {
					dd_console("-> apply_value API response:",'DEBUG',response);
				}
				// // reload source component on finish close
				// 	if (self.caller) {
				// 		self.caller.refresh()
				// 	}

				// // close tool modal (implies destroy current tool instance)
				// 	self.modal_container.close()

				resolve(response)
			})
		})
}//end apply_value


// @license-end
