/* global get_label, SHOW_DEBUG, SHOW_DEVELOPER */
/* eslint no-undef: "error" */



// imports
	import {clone, dd_console} from '../../common/js/utils/index.js'
	import {event_manager} from '../../common/js/event_manager.js'
	// import * as instances from '../../common/js/instances.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {common, set_context_vars, get_columns_map} from '../../common/js/common.js'
	import {component_common, init_events_subscription} from '../../component_common/js/component_common.js'
	import {paginator} from '../../paginator/js/paginator.js'
	// import {render_component_portal} from '../../component_portal/js/render_component_portal.js'
	import {render_edit_component_portal} from '../../component_portal/js/render_edit_component_portal.js'
	import {render_list_component_portal} from '../../component_portal/js/render_list_component_portal.js'
	import {render_search_component_portal} from '../../component_portal/js/render_search_component_portal.js'



/**
* COMPONENT_PORTAL
*/
export const component_portal = function() {

	this.id = null

	// element properties declare
	this.model					= null
	this.tipo					= null
	this.section_tipo			= null
	this.section_id				= null
	this.mode					= null
	this.lang					= null
	this.section_lang			= null
	this.column_id				= null
	this.parent					= null
	this.node					= null
	this.modal					= null
	this.caller					= null

	self.standalone 			= null

	// context - data
	this.datum					= null
	this.context				= null
	this.data					= null

	// pagination
	this.total					= null
	this.paginator				= null

	// autocomplete service
	this.autocomplete			= null
	this.autocomplete_active	= null

	// rqo
	this.request_config_object	= null
	this.rqo					= null


	return true
}//end  component_portal



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// life-cycle
	// component_portal.prototype.init				= component_common.prototype.init
	// component_portal.prototype.build				= component_common.prototype.build
	component_portal.prototype.render				= common.prototype.render
	component_portal.prototype.refresh				= common.prototype.refresh
	component_portal.prototype.destroy				= common.prototype.destroy

	// change data
	component_portal.prototype.save					= component_common.prototype.save
	component_portal.prototype.update_data_value	= component_common.prototype.update_data_value
	component_portal.prototype.update_datum			= component_common.prototype.update_datum
	component_portal.prototype.change_value			= component_common.prototype.change_value
	component_portal.prototype.set_changed_data		= component_common.prototype.set_changed_data
	component_portal.prototype.build_rqo_show		= common.prototype.build_rqo_show
	component_portal.prototype.build_rqo_search		= common.prototype.build_rqo_search
	component_portal.prototype.build_rqo_choose		= common.prototype.build_rqo_choose

	// render
	component_portal.prototype.list					= render_list_component_portal.prototype.list
	component_portal.prototype.tm					= render_list_component_portal.prototype.list
	component_portal.prototype.edit					= render_edit_component_portal.prototype.edit
	component_portal.prototype.search				= render_search_component_portal.prototype.search

	component_portal.prototype.change_mode			= component_common.prototype.change_mode



/**
* INIT
* Fix instance main properties
* @param object options
* @return bool
*/
component_portal.prototype.init = async function(options) {

	const self = this

	// call the generic common tool init
		const common_init = await component_common.prototype.init.call(self, options);

	// autocomplete. set default values of service autocomplete
		self.autocomplete			= null
		self.autocomplete_active	= false

	// columns
		self.columns_map		= options.columns_map
		self.add_component_info	= false

	// request_config
		self.request_config		= options.request_config || null

	// Standalone
	// Set the component to manage his data by itself, calling to the database and it doesn't share his data with other through datum
	// if the property is set to false, the component will use datum to get his data and is forced to update datum to share his data with others
	// false option is used to reduce the calls to API server and database, section use to load all data with 1 call and components load his data from datum
	// true options is used to call directly to API and manage his data, used by tools or services that need components standalone.
		self.standalone = true

	// events subscribe
		// initiator_link. Observes user click over list record_
			self.events_tokens.push(
				event_manager.subscribe('initiator_link_' + self.id, fn_initiator_link)
			)
			async function fn_initiator_link(locator) {
				// debug
					if(SHOW_DEBUG===true) {
						console.log('-> event fn_initiator_link locator:', locator);
					}
				// add locator selected
					const result = await self.add_value(locator)
					if (result===false) {
						alert(`Value already exists! ${JSON.stringify(locator)}`);
						return
					}
				// modal close
					if (self.modal) {
						self.modal.close()
					}
			}//end fn_initiator_link

		// link_term. Observes thesaurus tree link index button click
			self.events_tokens.push(
				event_manager.subscribe('link_term_' + self.id, fn_link_term)
			)
			function fn_link_term(locator) {

				// empty tag_id is allowed too
				// add tag_id. Note that 'self.active_tag' is an object with 3 properties (caller, text_editor and tag)
					const tag_id = self.active_tag && self.active_tag.tag
						? self.active_tag.tag.tag_id || null
						: null
					if (tag_id) {
						locator.tag_id	= tag_id
					}

				// top_locator add
					const top_locator = self.caller.top_locator // property from tool_indexation
					// check active tag is already set
					if (!top_locator) {
						alert("Error. No top_locator exists");
						return
					}
					Object.assign(locator, top_locator)

				// debug
					if(SHOW_DEBUG===true) {
						console.log("-->> fn_link_term. Set locator to add:", locator);
					}

				// add locator selected
					self.add_value(locator)
					.then(function(result){
						if (result===false) {
							alert("Value already exists! "+ JSON.stringify(locator));
							return
						}
					})
			}//end fn_initiator_link

		// deactivate_component. Observes current component deactivation event
			self.events_tokens.push(
				event_manager.subscribe('deactivate_component', fn_deactivate_component)
			)
			function fn_deactivate_component(component) {
				if (component.id===self.id) {
					console.log('self.autocomplete_active:', self.autocomplete_active);
					if(self.autocomplete_active===true){
						self.autocomplete.destroy(
							true, // bool delete_self
							true, // bool delete_dependencies
							true // bool remove_dom
						)
						self.autocomplete_active	= false
						self.autocomplete			= null
					}
				}
			}

	// render_views
		// Definition of the rendering views that could de used.
		// Tools or another components could add specific views dynamically
		// Sample:
		// {
		// 		view	: 'default',
		// 		mode	: 'edit',
		// 		render	: 'view_default_edit_portal'
		// 		path 	: './view_default_edit_portal.js'
		// }
		self.render_views = [
			{
				view	: 'text',
				mode	: 'edit',
				render	: 'view_text_list_portal'
			},
			{
				view	: 'line',
				mode	: 'edit',
				render	: 'view_line_edit_portal'
			},
			{
				view	: 'tree',
				mode	: 'edit',
				render	: 'view_tree_edit_portal'
			},
			{
				view	: 'mosaic',
				mode	: 'edit',
				render	: 'view_mosaic_edit_portal'
			},
			{
				view	: 'indexation',
				mode	: 'edit',
				render	: 'view_indexation_edit_portal'
			},
			{
				view	: 'content',
				mode	: 'edit',
				render	: 'view_content_edit_portal'
			},
			{
				view	: 'default',
				mode	: 'edit',
				render	: 'view_default_edit_portal',
				path 	: './view_default_edit_portal.js'
			},
			{
				view	: 'line',
				mode	: 'list',
				render	: 'view_line_list_portal'
			},
			{
				view	: 'mini',
				mode	: 'list',
				render	: 'view_mini_portal'
			},
			{
				view	: 'text',
				mode	: 'list',
				render	: 'view_text_list_portal'
			},
			{
				view	: 'default',
				mode	: 'list',
				render	: 'view_default_list_portal'
			}
		]

	return common_init
}//end init



/**
* BUILD
* Load and parse necessary data to create a full ready instance
* @param bool autoload = false
* @return bool
*/
component_portal.prototype.build = async function(autoload=false) {
	// const t0 = performance.now()

	const self = this

	// status update
		self.status = 'building'

	// self.datum. On building, if datum is not created, creation is needed
		self.datum = self.datum || {
			data	: [],
			context	: []
		}
		self.data = self.data || {}
		// changed_data. Set as empty array always
		self.data.changed_data = []

	// rqo
		const generate_rqo = async function() {

			if (!self.context) {
				// request_config_object. get the request_config_object from request_config
				self.request_config_object = self.request_config
					? self.request_config.find(el => el.api_engine==='dedalo' && el.type==='main')
					: {}
			}else{
				// request_config_object. get the request_config_object from context
				self.request_config_object	= self.context && self.context.request_config
					? self.context.request_config.find(el => el.api_engine==='dedalo' && el.type==='main')
					: {}
			}

			// rqo build
			const action	= (self.mode==='search') ? 'resolve_data' : 'get_data'
			const add_show	= false
			self.rqo = self.rqo || await self.build_rqo_show(
				self.request_config_object, // object request_config_object
				action,  // string action like 'get_data' or 'resolve_data'
				add_show // bool add_show
			)
			if(self.mode==='search') {
				self.rqo.source.value = self.data.value || []
			}
		}
		await generate_rqo()

	// debug check
		// if(SHOW_DEBUG===true) {
		// 	// console.log("portal generate_rqo 1 self.request_config_object:", clone(self.request_config_object) );
		// 	// console.log("portal generate_rqo 1 self.rqo:", clone(self.rqo) );
		// 	const ar_used = []
		// 	for(const element of self.datum.data) {

		// 		if (element.matrix_id) { continue; } // skip verification in matrix data

		// 		const index = ar_used.findIndex(item => item.tipo===element.tipo &&
		// 												item.section_tipo===element.section_tipo &&
		// 												item.section_id==element.section_id &&
		// 												item.from_component_tipo===element.from_component_tipo &&
		// 												item.parent_section_id==element.parent_section_id &&
		// 												item.row_section_id==element.row_section_id
		// 												// && (item.matrix_id && item.matrix_id==element.matrix_id)
		// 												// && (item.tag_id && item.tag_id==element.tag_id)
		// 												)
		// 		if (index!==-1) {
		// 			console.error("PORTAL ERROR. self.datum.data contains duplicated elements:", ar_used[index]);
		// 		}else{
		// 			ar_used.push(element)
		// 		}
		// 	}
		// }

	// load data if not yet received as an option
		if (autoload===true) {

			// get context and data
				const api_response = await data_manager.request({
					body : self.rqo
				})
				// console.log("COMPONENT PORTAL api_response:",self.id, api_response);
				if(SHOW_DEVELOPER===true) {
					dd_console(`api_response [component_portal.build] COMPONENT ${self.model} build autoload:`, 'DEBUG', [api_response.debug.real_execution_time, api_response])
				}
			// set Context
				// context is only set when it's empty the origin context,
				// if the instance has previous context, it will need to preserve.
				// because the context could be modified by ddo configuration and it can no be changed
				// ddo_map -----> context
				// ex: oh27 define the specific ddo_map for rsc368
				// 		{ mode: list, view: line, children_view: text ... }
				// if you call to API to get the context of the rsc368 the context will be the default config
				// 		{ mode: edit, view: default }
				// but it's necessary preserve the specific ddo_map configuration in the new context.
				// Context is set and changed in section_record.js to get the ddo_map configuration
				if(!self.context){
					const context = api_response.result.context.find(el => el.tipo===self.tipo && el.section_tipo===self.section_tipo)
					if (!context) {
						console.error("context not found in api_response:", api_response);
					}else{
						self.context = context
					}
				}

			// set Data
				const data = api_response.result.data.find(el => el.tipo===self.tipo && el.section_tipo===self.section_tipo && el.section_id==self.section_id)
				if(!data){
					console.warn("data not found in api_response:",api_response);
				}
				self.data = data || {}

			// Update datum when the component is not standalone, it's dependent of section or others with common datum
				if(!self.standalone){
					await self.update_datum(api_response.result.data)
				}else{
					self.datum.context	= api_response.result.context
					self.datum.data		= api_response.result.data
				}

			// // context. update instance properties from context (type, label, tools, fields_separator, permissions)
			// 	self.context		= api_response.result.context.find(el => el.tipo===self.tipo && el.section_tipo===self.section_tipo)
			// 	self.datum.context	= api_response.result.context

			// force re-assign self.total
				self.total = null

			// rqo regenerate
				await generate_rqo()
				// console.log("portal generate_rqo 2 self.rqo:",self.rqo);

			// update rqo.sqo.limit. Note that it may have been updated from the API response
			// Paginator takes limit from: self.rqo.sqo.limit
				const request_config_item = self.context.request_config.find(el => el.api_engine==='dedalo' && el.type==='main')
				if (request_config_item) {
					// Updated self.rqo.sqo.limit. Try sqo and show.sqo_config
					if (request_config_item.sqo && request_config_item.sqo.limit) {
						self.rqo.sqo.limit = request_config_item.sqo.limit
					}else
					if(request_config_item.show && request_config_item.show.sqo_config && request_config_item.show.sqo_config.limit) {
						self.rqo.sqo.limit = request_config_item.show.sqo_config.limit
					}
				}
		}//end if (autoload===true)


	// update instance properties from context
		set_context_vars(self, self.context)

	// subscribe to the observer events (important: only once)
		init_events_subscription(self)

	// mode cases
		if (self.mode==='edit' || self.mode==='tm') {
			// pagination vars only in edit mode

			// pagination. update element pagination vars when are used
				if (self.data.pagination && !self.total) {
					self.total			= self.data.pagination.total
					self.rqo.sqo.offset	= self.data.pagination.offset

					// set_local_db_data updated rqo
						// const rqo = self.rqo
						// data_manager.set_local_db_data(
						// 	rqo,
						// 	'rqo'
						// )
				}

			// paginator
				if (!self.paginator) {

					// create new one
					self.paginator = new paginator()
					self.paginator.init({
						caller	: self,
						mode	: 'micro'
					})
					await self.paginator.build()

					// paginator_goto_ event
						self.events_tokens.push(
							event_manager.subscribe('paginator_goto_'+self.paginator.id, fn_paginator_goto)
						)//end events push
						function fn_paginator_goto(offset) {
							// navigate
							self.navigate({
								callback : () => {
									self.rqo.sqo.offset = offset
								}
							})
						}//end fn_paginator_goto

					// paginator_show_all_
						self.events_tokens.push(
							event_manager.subscribe('paginator_show_all_'+self.paginator.id, fn_paginator_show_all)
						)//end events push
						function fn_paginator_show_all(limit) {
							// navigate
							self.navigate({
								callback : async () => {
									// rqo and request_config_object set offset and limit
									self.rqo.sqo.offset	= self.request_config_object.sqo.offset = 0
									self.rqo.sqo.limit	= self.request_config_object.sqo.limit 	= limit
								}
							})
						}//end fn_paginator_goto

				}else{
					// refresh existing
					self.paginator.offset = self.rqo.sqo.offset
					self.paginator.total  = self.total
					// self.paginator.refresh()
					// await self.paginator.build()
					// self.paginator.render()
				}

			// autocomplete destroy. change the autocomplete service to false and deactivates it
				// if(self.autocomplete && self.autocomplete_active===true){
				// 	self.autocomplete.destroy(
				// 		true, // bool delete_self
				// 		true, // bool delete_dependencies
				// 		true // bool remove_dom
				// 	)
				// 	self.autocomplete_active	= false
				// 	self.autocomplete			= null
				// }
		}else if(self.mode==='search') {

			// active / prepare the autocomplete in search mode

			// (!) Used ?
			// autocomplete destroy. change the autocomplete service to false and deactivate it.
				// if(self.autocomplete && self.autocomplete_active===true){
				// 	self.autocomplete.destroy()
				// 	self.autocomplete_active = false
				// 	self.autocomplete 		 = null
				// }
		}// end if(self.mode==="edit")

	// check self.context.request_config
		if (!self.context.request_config) {
			console.error('Error. context.request_config not found. self:', self);
			throw 'Error';
			return false
		}

	// target_section
		self.target_section = self.request_config_object && self.request_config_object.sqo
			? self.request_config_object.sqo.section_tipo
			: null
		// self.target_section = self.rqo.sqo.section_tipo

	// columns
		// if(self.mode!=='list'){
			self.columns_map = get_columns_map(self.context)
		// }

	// component_info add
		const rqo = self.context.request_config.find(el => el.api_engine==='dedalo' && el.type==='main')
		self.add_component_info = rqo
			? (rqo.show.ddo_map[0] ? rqo.show.ddo_map[0].value_with_parents : false)
			: false

	// debug
		if(SHOW_DEBUG===true) {
			// console.log("/// component_portal build self.datum.data:",self.datum.data);
			// console.log("__Time to build", self.model, " ms:", performance.now()-t0);
			// console.log("component_portal self +++++++++++ :",self);
			//console.log("========= build self.pagination.total:",self.pagination.total);
		}

	// set the server data to preserve the data that is saved in DDBB
		self.db_data = clone(self.data)

	// set fields_separator
		self.context.fields_separator = self.context?.fields_separator
									|| self.request_config_object?.show.fields_separator
									|| ' | '

	// set records_separator
		self.context.records_separator = self.context?.records_separator
									|| self.request_config_object?.show.records_separator
									|| ' | '

	// self.show_interface is defined in component_comom init()
	// Default source external buttons configuration,
	// if show.interface is defined in properties used the definition, else use this default
		const is_inside_tool = self.caller && self.caller.type==='tool'
		switch (true) {
			case (self.context.properties.source?.mode==='external'):
				self.show_interface.button_add		= false
				self.show_interface.button_link		= false
				self.show_interface.tools			= false
				self.show_interface.button_external	= true
				self.show_interface.button_tree		= false
				break;

			case (is_inside_tool===true):
				self.show_interface.button_add		= false
				self.show_interface.button_link		= false
				self.show_interface.tools			= false
				self.show_interface.button_external	= false
				self.show_interface.button_tree		= false
				break;

			default:
				break;
		}

	// status update
		self.status = 'built'


	return true
}//end component_portal.prototype.build



/**
* ADD_VALUE
* Called from service autocomplete when the user selects a datalist option
* @param object value (locator)
* @return bool
*/
component_portal.prototype.add_value = async function(value) {

	const self = this

	// current_value. Get the current_value of the component
		const current_value	= self.data.value || []

	// data_limit. Check if the component has a data_limit (it could be defined in properties as data_limit with int value)
		const data_limit = self.context.properties.data_limit
		if(data_limit && current_value.length>=data_limit){
			console.log("[add_value] Data limit is exceeded!");
			// notify to user about the limit
			const data_limit_label = (get_label.exceeded_limit || 'The maximum number of values for this field has been exceeded. Limit =') + ' ' + data_limit
			window.alert(data_limit_label)
			// stop the process
			return false
		}

	// exists. Check if value already exists. (!) Note that only current loaded paginated values are available for compare, not the whole portal data
		const exists = current_value.find(item => item.section_tipo===value.section_tipo && item.section_id==value.section_id)
		if (typeof exists!=='undefined') {
			console.log('[add_value] Value already exists (1) !');
			return false
		}

	// add himself into the new locator as from_component_tipo
		value.from_component_tipo = self.tipo

	// changed_data
		const key			= self.total || 0
		const changed_data	= [Object.freeze({
			action	: 'insert',
			key		: key,
			value	: value
		})]

	// debug
		if(SHOW_DEBUG===true) {
			console.log("[component_portal.add_value] value:", value, " - changed_data:", changed_data);
		}

	// data pagination offset. Check and update self data to allow save request return the proper paginated data
		if (self.data.pagination && self.data.pagination.total>0 && key===self.data.pagination.total) {
			const next_offset = (self.data.pagination.offset + self.data.pagination.limit)
			if (self.data.pagination.total >= next_offset) {
				self.data.pagination.offset = next_offset // set before exec API request on Save
			}
		}

	// total_before
		const total_before = clone(self.total)

	// change_value (and save)
		const api_response = await self.change_value({
			changed_data	: changed_data,
			refresh			: false // not refresh here (!)
		})

	// total check (after save)
		const current_data = api_response.result.data.find(el => el.tipo===self.tipo)
		const total = current_data
			? current_data.pagination.total
			: 0
		if (total===0) {
			console.warn("// add_value api_response.result.data (unexpected total):",api_response.result.data);
		}

	// check if value already exist. (!) Note that here, the whole portal data has been compared in server
		if (parseInt(total) <= parseInt(total_before)) {
			// self.update_pagination_values('remove') // remove added pagination value
			console.log("[add_value] Value already exists (2) !");
			return false
		}

	// updates pagination values offset and total
		if (self.mode!=='search') {
			self.update_pagination_values('add')
		}

	// updates pagination values offset and total
		// self.update_pagination_values('add')

	// Update data from save API response (note that build_autoload will be passed as false later -when refresh- to avoid call to the API again)
		// set context and data to current instance
			await self.update_datum(api_response.result.data) // (!) Updated on save too (add/delete elements)

		// context. update instance properties from context (type, label, tools, fields_separator, permissions)
			self.context		= api_response.result.context.find(el => el.tipo===self.tipo && el.section_tipo===self.section_tipo)
			self.datum.context	= api_response.result.context

		// // data. update instance properties from data (locators)
			self.data		= api_response.result.data.find(el => el.tipo===self.tipo && el.section_tipo===self.section_tipo && el.section_id==self.section_id)
			self.datum.data	= api_response.result.data

		// force re-assign self.total and pagination values on build
			self.total = null

	// refresh self component
		await self.refresh({
			build_autoload	: false, //(self.mode==='search' ? true : false),
			render_level	: 'content'
		})

	// filter data. check if the caller has tag_id
		if(self.active_tag){
			// filter component data by tag_id and re-render content
			self.filter_data_by_tag_id(self.active_tag)
		}

	// mode specifics
		switch(self.mode) {

			case 'search' :
				// publish change. Event to update the DOM elements of the instance
				event_manager.publish('change_search_element', self)
				self.node.classList.remove('active')
				break;

			default:

				break;
		}


	return true
}//end add_value



/**
* UPDATE_PAGINATION_VALUES
* @param string action
* @return bool true
*/
component_portal.prototype.update_pagination_values = function(action) {

	const self = this

	// update self.data.pagination
		switch(action) {
			case 'remove' :
				// update pagination total
				if(self.data.pagination && self.data.pagination.total && self.data.pagination.total>0) {
					// self.data.pagination.total--
					self.total--
				}
				break;
			case 'add' :
				// update self.data.pagination
				if(self.data.pagination && self.data.pagination.total && self.data.pagination.total>=0) {
					// self.data.pagination.total++
					self.total++
				}
				break;
			default:
				// Nothing to add or remove
		}
		// self.total = self.data.pagination.total


	// last_offset
		const last_offset = (()=>{

			const total	= self.total
			const limit	= self.rqo.sqo.limit

			if (total>0 && limit>0) {

				const total_pages = Math.ceil(total / limit)

				return parseInt( limit * (total_pages -1) )
			}

			return 0
		})()

	// self pagination update
		self.rqo.sqo.offset	= last_offset

		if (!self.data.pagination) {
			self.data.pagination = {}
		}
		self.data.pagination.offset	= last_offset
		self.data.pagination.total	= self.total// sync pagination info
	// paginator object update
		self.paginator.offset	= self.rqo.sqo.offset
		self.paginator.total	= self.total

	// paginator content data update (after self update to avoid artifacts (!))
		self.events_tokens.push(
			event_manager.subscribe('render_'+self.id, fn_refresh_paginator)
		)
		function fn_refresh_paginator() {
			// remove the event to prevent multiple equal events
				event_manager.unsubscribe('render_'+self.id)
			// refresh paginator if already exists
				if (self.paginator) {
					self.paginator.refresh()
				}
		}

	// set_local_db_data updated rqo
		// const rqo = self.rqo
		// data_manager.set_local_db_data(
		// 	rqo,
		// 	'rqo'
		// )


	return true
}//end update_pagination_values



/**
* FILTER_DATA_BY_TAG_ID
* Filtered data with the tag clicked by the user
* The portal will show only the locators for the tag selected
* @param DOM node tag
* @return promise self.render
*/
component_portal.prototype.filter_data_by_tag_id = function(options) {

	const self = this

	// options
		// const caller			= options.caller // not used
		// const text_editor	= options.text_editor // not used
		const tag				= options.tag // object

	// Fix received options from event as 'active_tag'
		self.active_tag = options

	// short vars
		const tag_id = tag.tag_id

	// get all data from datum because if the user select one tag the portal data is filtered by the tag_id,
	// in the next tag selection by user the data doesn't have all locators and is necessary get the original data
	// the full_data is clone to a new object because need to preserve the datum from these changes.
		const full_data	= self.datum.data.find(el =>
				el.tipo===self.tipo
			 && el.section_tipo===self.section_tipo
			 && el.section_id==self.section_id
		) || {}
		self.data = clone(full_data)

	// the portal will use the filtered data value to render it with the tag_id locators.
		self.data.value = self.data.value
			? self.data.value.filter(el => el.tag_id==tag_id)
			: []

	// reset status to enable re-render
		self.status = 'built'

	// re-render always the content
		return self.render({
			render_level : 'content'
		})
}// end filter_data_by_tag_id



/**
* RESET_FILTER_DATA
* reset filtered data to the original and full server data
* @return promise self.render
*/
component_portal.prototype.reset_filter_data = function() {

	const self = this

	// reset self.active_tag (important)
		self.active_tag = null

	// refresh the data with the full data from datum and render portal.
		self.data = self.datum.data.find(el => el.tipo===self.tipo && el.section_tipo===self.section_tipo && el.section_id==self.section_id) || {}

	// reset status to able re-render
		self.status = 'built'

	// reset instances status
		// self.ar_instances = null
		// for (let i = 0; i < self.ar_instances.length; i++) {
		// 	self.ar_instances[i].status = 'built'
		// }

	// re-render content
		return self.render({
			render_level : 'content'
		})
}// end reset_filter_data



/**
* GET_SEARCH_VALUE
* @return array new_value
*/
component_portal.prototype.get_search_value = function() {

	const self = this

	const data			= self.data || {}
	const current_value	= data.value || []

	const new_value = [];
	const value_len = current_value.length
	for (let i = 0; i < value_len; i++) {
		new_value.push({
			section_tipo		: current_value[i].section_tipo,
			section_id			: current_value[i].section_id,
			from_component_tipo	: current_value[i].from_component_tipo
		})
	}

	return new_value
}//end get_search_value



/**
* NAVIGATE
* Refresh the portal instance with new sqo params.
* Used to paginate and sort records
* @param object options
* @return promise
*/
component_portal.prototype.navigate = async function(options) {

	const self = this

	// options
		const callback = options.callback

	// unsaved_data check
		// if (window.unsaved_data===true) {
		// 	if (!confirm(get_label.discard_changes || 'Discard unsaved changes?')) {
		// 		return false
		// 	}else{
		// 		window.unsaved_data===false
		// 	}
		// }

	// callback execute
		if (callback) {
			await callback()
		}

	// container
		const container = self.node.list_body // view table
					   || self.node.content_data // view line

	// loading
		container.classList.add('loading')

	// refresh
		await self.refresh()

	// loading
		container.classList.remove('loading')


	return true
}//end navigate



/**
* DELETE_LOCATOR
* @param object locator
* 	Locator complete or partial to match as
* {
*	tag_id	: tag_id,
*	type	: DD_TIPOS.DEDALO_RELATION_TYPE_INDEX_TIPO // dd96
* }
* @param array ar_properties
* 	To compare locators as ['tag_id','type']
* @return promise
* 	resolve object response
*/
component_portal.prototype.delete_locator = function(locator, ar_properties) {

	const self = this

	return data_manager.request({
		body : {
			action	: 'delete_locator',
			dd_api	: 'dd_component_portal_api', // component_portal
			source	: {
				section_tipo	: self.section_tipo, // current component_text_area section_tipo
				section_id		: self.section_id, // component_text_area section_id
				tipo			: self.tipo, // component_text_area tipo
				lang			: self.lang, // component_text_area lang
				locator			: locator,
				ar_properties	: ar_properties
			}
		}
	})
}//end delete_locator



/**
* SORT_DATA
* Create ad saves new sorted values
* Used by on_drop method
* @see on_drop
*
* @param object options
* @return object
*  API request response
*/
component_portal.prototype.sort_data = async function(options) {

	const self = this

	// options
		const value			= options.value
		const source_key	= options.source_key
		const target_key	= options.target_key

	// sort_data
		const changed_data = [Object.freeze({
			action		: 'sort_data',
			source_key	: source_key,
			target_key	: target_key,
			value		: value
		})]

	// exec async change_value
		const result = await self.change_value({
			changed_data	: changed_data,
			refresh			: true
		})


	return result
}//end sort_data



/**
* GET_TOTAL
* this function is for compatibility with section and paginator
* total is resolved in server and comes in data, so it's not necessary call to server to get it
*
* @return int
*/
component_portal.prototype.get_total = async function() {

	const self = this

	return self.total
}//end get_total

