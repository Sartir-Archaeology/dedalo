// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, SHOW_DEBUG, SHOW_DEVELOPER, DEDALO_TOOLS_URL */
/*eslint no-undef: "error"*/



// imports
	import {clone, url_vars_to_object, object_to_url_vars} from '../../common/js/utils/index.js'
	import {event_manager} from '../../common/js/event_manager.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import * as instances from '../../common/js/instances.js'
	import {
		common,
		set_context_vars,
		create_source,
		load_data_debug,
		get_columns_map,
		push_browser_history,
		build_autoload
	} from '../../common/js/common.js'
	import {ui} from '../../common/js/ui.js'
	import {check_unsaved_data} from '../../component_common/js/component_common.js'
	import {paginator} from '../../paginator/js/paginator.js'
	import {search} from '../../search/js/search.js'
	import {toggle_search_panel} from '../../search/js/render_search.js'
	import {inspector} from '../../inspector/js/inspector.js'
	import {render_edit_section} from './render_edit_section.js'
	import {render_list_section} from './render_list_section.js'
	import {render_solved_section} from './render_solved_section.js'
	import {render_common_section} from './render_common_section.js'



/**
* SECTION
*/
export const section = function() {

	this.id						= null

	// element properties declare
	this.model					= null
	this.type					= null
	this.tipo					= null
	this.section_tipo			= null
	this.section_id				= null
	this.section_id_selected	= null
	this.mode					= null
	this.lang					= null
	this.column_id				= null

	this.datum					= null
	this.context				= null
	this.data					= null
	this.total					= null

	this.ar_section_id			= null

	this.node					= null
	this.ar_instances			= null
	this.caller					= null

	this.status					= null

	this.filter					= null
	this.inspector				= null
	this.paginator				= null
	this.buttons				= null

	this.id_variant				= null

	this.request_config_object	= null
	this.rqo					= null

	this.config					= null
	this.fixed_columns_map		= null
}//end section



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// life cycle
	// section.prototype.render			= common.prototype.render
	section.prototype.destroy			= common.prototype.destroy
	section.prototype.refresh			= common.prototype.refresh
	section.prototype.build_rqo_show	= common.prototype.build_rqo_show
	section.prototype.build_rqo_search	= common.prototype.build_rqo_search

	// render
	section.prototype.edit				= render_edit_section.prototype.edit
	section.prototype.list				= render_list_section.prototype.list
	section.prototype.list_portal		= render_list_section.prototype.list
	section.prototype.tm				= render_list_section.prototype.list
	section.prototype.list_header		= render_list_section.prototype.list_header
	section.prototype.solved			= render_solved_section.prototype.solved


	section.prototype.delete_record		= render_common_section.prototype.delete_record



/**
* INIT
* Fix instance main properties
* @param object options
* @return bool
*/
section.prototype.init = async function(options) {

	const self = this

	// vars
		// instance key used vars
		self.model					= options.model
		self.tipo					= options.tipo
		self.section_tipo			= options.section_tipo
		self.section_id				= options.section_id
		self.section_id_selected	= options.section_id_selected
		self.mode					= options.mode
		self.lang					= options.lang

		// DOM
		self.node					= null

		self.section_lang			= options.section_lang
		self.parent					= options.parent

		self.events_tokens			= []
		self.ar_instances			= []

		self.caller					= options.caller	|| null

		self.datum					= options.datum		|| null
		self.context				= options.context	|| null
		self.data					= options.data		|| null

		self.type					= 'section'
		self.label					= null

		// filter. Allow false as value when no filter is required
		self.filter					= options.filter!==undefined ? options.filter : null

		// inspector. Allow false as value when no inspector is required (notes cases)
		self.inspector				= options.inspector!==undefined ? options.inspector : null

		// paginator. Allow false as value when no paginator is required
		self.paginator				= options.paginator!==undefined ? options.paginator : null

		self.permissions			= options.permissions || null

		// columns_map
		self.columns_map			= options.columns_map || []

		// config
		self.config					= options.config || null

		// request_config
		self.request_config			= options.request_config || null

		// add_show to rqo to configure specific show
		self.add_show 				= options.add_show || false

		// buttons. bool to show / hide the buttons in list
		self.buttons 				= options.buttons || true

		// session_key
		self.session_save			= options.session_save ?? true
		self.session_key			= options.session_key ?? 'section_' + self.tipo

		// view
		self.view					= options.view ?? null

	// event subscriptions
		// new_section_ event
			self.events_tokens.push(
				event_manager.subscribe('new_section_' + self.id, fn_create_new_section)
			)
			async function fn_create_new_section() {

				// data_manager. create
				const rqo = {
					action	: 'create',
					source	: {
						section_tipo : self.section_tipo
					}
				}
				const api_response = await data_manager.request({
					body : rqo
				})
				if (api_response.result && api_response.result>0) {

					const section_id = api_response.result

					const source = create_source(self, 'search')
						  source.section_id	= section_id
						  source.mode		= 'edit'

					const sqo = {
						mode				: self.mode,
						section_tipo		: [{tipo:self.section_tipo}],
						filter_by_locators	: [{
							section_tipo	: self.section_tipo,
							section_id		: section_id
						}],
						limit				: 1,
						offset				: 0
					}
					// launch event 'user_navigation' that page is watching
					event_manager.publish('user_navigation', {
						source	: source,
						sqo		: sqo
					})
				}
			}//end fn_create_new_section

		// duplicate_section_ event
			self.events_tokens.push(
				event_manager.subscribe('duplicate_section_' + self.id, fn_duplicate_section)
			)
			async function fn_duplicate_section( options ) {

				if (!confirm(get_label.sure || 'Sure?')) {
					return false
				}

				// data_manager. create
				const rqo = {
					action	: 'duplicate',
					source	: {
						section_tipo	: options.section_tipo,
						section_id		: options.section_id
					}
				}
				const api_response = await data_manager.request({
					body : rqo
				})
				if (api_response.result && api_response.result>0) {

					const section_id = api_response.result

					const source = create_source(self, 'search')
						  source.section_id	= section_id
						  source.mode		= 'edit'

					const sqo = {
						mode				: self.mode,
						section_tipo		: [{tipo:self.section_tipo}],
						filter_by_locators	: [{
							section_tipo	: self.section_tipo,
							section_id		: section_id
						}],
						limit				: 1,
						offset				: 0
					}
					// launch event 'user_navigation' that page is watching
					event_manager.publish('user_navigation', {
						source	: source,
						sqo		: sqo
					})
				}
			}//end fn_duplicate_section

		// delete_section_ event. (!) Moved to self button delete in render_section_list
			self.events_tokens.push(
				event_manager.subscribe('delete_section_' + self.id, fn_delete_section)
			)
			async function fn_delete_section(options) {

				// options
					const section_id	= options.section_id
					const section_tipo	= options.section_tipo
					const section		= options.caller
					const sqo			= options.sqo ||
						{
							section_tipo		: [section_tipo],
							filter_by_locators	: [{
								section_tipo	: section_tipo,
								section_id		: section_id
							}],
							limit				: 1
						}

				// delete_section
					self.delete_record({
						section			: section,
						section_id		: section_id,
						section_tipo	: section_tipo,
						sqo				: sqo
					})
			}//end fn_create_new_section

		// toggle_search_panel event. Triggered by button 'search' placed into section inspector buttons
			self.events_tokens.push(
				event_manager.subscribe('toggle_search_panel_'+self.id, fn_toggle_search_panel)
			)
			async function fn_toggle_search_panel() {

				if (!self.search_container || !self.filter) {
					console.log('stop event no filter 1:', this);
					return
				}
				if (self.search_container.children.length===0) {
					// await add_to_container(self.search_container, self.filter)
					await ui.load_item_with_spinner({
						container	: self.search_container,
						label		: 'filter',
						callback	: async () => {
							await self.filter.build()
							return self.filter.render()
						}
					})
				}
				toggle_search_panel(self.filter)
			}//end fn_toggle_search_panel

		// render_ event
			const render_token = event_manager.subscribe('render_'+self.id, fn_render)
			self.events_tokens.push(render_token)
			function fn_render() {

				// menu label control
					const update_menu = (menu) => {

						// menu instance check. Get from caller page
						if (!menu) {
							if(SHOW_DEBUG===true) {
								console.log('menu is not available from section.');
							}
							return
						}

						// update_section_label. Show icon Inspector and activate the link event
						menu.update_section_label({
							value					: self.label,
							mode					: self.mode,
							section_label_on_click	: section_label_on_click
						})
						async function section_label_on_click(e) {
							e.stopPropagation();

							if (self.mode!=='edit') return

							/* MODE USING PAGE user_navigation */
								// saved_sqo
								// Note that section build method store SQO in local DDBB to preserve user
								// navigation section filter and pagination. It's recovered here when exists,
								// to pass values to API server
								const saved_sqo	= await data_manager.get_local_db_data(
									self.session_key + '_list',
									'sqo',
									true
								);
								const sqo = saved_sqo
									? saved_sqo.value
									: {
										filter	: self.rqo.sqo.filter,
										order	: self.rqo.sqo.order || null
									  }
								// always use section request_config_object format instead parsed sqo format
								sqo.section_tipo = self.request_config_object.sqo.section_tipo

								// source
									const source = {
										action			: 'search',
										model			: self.model, // section
										tipo			: self.tipo,
										section_tipo	: self.section_tipo,
										mode			: 'list',
										lang			: self.lang
									 }

								// navigation
									const user_navigation_rqo = {
										caller_id			: self.id,
										source				: source,
										sqo					: sqo  // new sqo to use in list mode
										// event_in_history	: false // writes browser navigation step to allow back
									}
									event_manager.publish('user_navigation', user_navigation_rqo)

							/* MODE USING SECTION change_mode
								// change section mode. Creates a new instance and replace DOM node wrapper
									self.change_mode({
										mode : 'list'
									})
									.then(function(new_instance){

										// update_section_label value
											menu.update_section_label({
												value					: new_instance.label,
												mode					: new_instance.mode,
												section_label_on_click	: null
											})

										// update browser url and navigation history
											const source	= create_source(new_instance, null)
											const sqo		= new_instance.request_config_object.sqo
											const title		= new_instance.id
											// url search. Append section_id if exists
											const url_vars = url_vars_to_object({
												tipo : new_instance.tipo,
												mode : new_instance.mode
											})
											const url = '?' + object_to_url_vars(url_vars)
											// browser navigation update
											push_browser_history({
												source	: source,
												sqo		: sqo,
												title	: title,
												url		: url
											})
									})//end then
									*/
						}//end section_label_on_click
					}//end update_menu

				// call only for direct page created sections
					if (self.caller && self.caller.model==='page') {
						// ignore some section cases
						if (    self.tipo==='dd623' // search presets case
							|| (self.caller && self.caller.type==='tool') // inside tool (tool_user_admin case)
							) {
							// nothing to do
						}else{
							// menu. Get from caller page
							const menu_instance = self.caller && self.caller.ar_instances
								? self.caller.ar_instances.find(el => el.model==='menu')
								: null
							update_menu( menu_instance )
						}
					}

				// search control
					if (!self.search_container || !self.filter) {
						// console.log('stop event no filter 2:', this);
						return
					}
					// open_search_panel. local DDBB table status
					const status_id			= 'open_search_panel'
					const collapsed_table	= 'status'
					data_manager.get_local_db_data(status_id, collapsed_table, true)
					.then(async function(ui_status){
						// (!) Note that ui_status only exists when element is open
						const is_open = typeof ui_status==='undefined' || ui_status.value===false
							? false
							: true
						if (is_open===true && self.search_container && self.search_container.children.length===0) {
							// add_to_container(self.search_container, self.filter)
							await ui.load_item_with_spinner({
								container	: self.search_container,
								label		: 'filter',
								callback	: async () => {
									await self.filter.build()
									return self.filter.render()
								}
							})
							toggle_search_panel(self.filter)
						}
					})
			}//end fn_render

	// load additional files as css used by section_tool in self.config
		if(self.config && self.config.source_model==='section_tool') {
			self.load_section_tool_files()
		}

	// render_views
		// Definition of the rendering views that could de used.
		// Tools or another components could add specific views dynamically
		self.render_views = [
			{
				view	: 'default',
				mode	: 'edit',
				render	: 'view_default_edit_section'
			},
			{
				view	: 'default',
				mode	: 'list',
				render	: 'view_default_list_section'
			}
		]

	// status update
		self.status = 'initialized'


	return true
}//end init



/**
* BUILD
* Load and parse necessary data to create a full ready instance
* @param bool autoload = false
* @return bool
*/
section.prototype.build = async function(autoload=false) {
	// const t0 = performance.now()

	const self = this

	// previous status
		const previous_status = clone(self.status)

	// status update
		self.status = 'building'

	// self.datum. On building, if datum is not created, creation is needed
		self.datum = self.datum || {
			data	: [],
			context	: []
		}
		self.data	= self.data || {}

	// rqo
		const generate_rqo = async function(){

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

			// check request_config_object misconfigured issues (type = 'main' missed in request_config cases)
				if (self.request_config && !self.request_config_object) {
					console.warn('Warning: no request_config was found into the request_config. Maybe the request_config type is not set to "main" ');
					console.warn('self.request_config:', self.request_config);
				}

			// rqo build
			const action	= 'search'
			const add_show	= (self.add_show)
				? self.add_show
				: (self.mode==='tm') ? true	: false
			self.rqo = self.rqo || await self.build_rqo_show(
				self.request_config_object, // object request_config_object
				action,  // string action like 'search'
				add_show // bool add_show
			)
		}
		await generate_rqo()

	// filter search
		if (self.filter===null && self.mode!=='tm') {
			self.filter = new search()
			self.filter.init({
				caller	: self,
				mode	: self.mode
			})
		}

	// load from DDBB
		if (autoload===true) {

			// update rqo with session values
				self.rqo.source.session_save	= self.session_save
				self.rqo.source.session_key		= self.session_key

			// view
				self.rqo.source.view = self.view

			// build_autoload
			// Use unified way to load context and data with
			// errors and not login situation managing
				const api_response = await build_autoload(self)
				if (!api_response) {
					return false
				}

			// reset errors
				self.running_with_errors = null

			// destroy dependencies
				await self.destroy(
					false, // bool delete_self
					true, // bool delete_dependencies
					false // bool remove_dom
				)

			// set the result to the datum
				self.datum = api_response.result

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
					const context	= self.datum.context.find(el => el.section_tipo===self.section_tipo) || {}
					if (!context) {
						console.error("context not found in api_response:", api_response);
					}else{
						self.context = context
					}
				}

			// set Data
				self.data		= self.datum.data.find(el => el.tipo===self.tipo && el.typo==='sections') || {}
				self.section_id	= self.mode!=='list' && self.data && self.data.value
					? (() =>{
						const found = self.data.value.find(el => el.section_tipo===self.section_tipo)
						if (found && found.section_id) {
							return found.section_id
						}
						console.warn('Empty value found in self.data.value: ', self.data.value)
						return null
					  })()
					: null

			// rqo regenerate
				await generate_rqo()
				// console.log('SECTION self.rqo after load:", clone(self.rqo) );

			// update rqo.sqo.limit. Note that it may have been updated from the API response
			// Paginator takes limit from: self.rqo.sqo.limit
				const request_config_item = self.context.request_config
					? self.context.request_config.find(el => el.api_engine==='dedalo' && el.type==='main')
					: null // permissions 0 case
				if (request_config_item) {
					// Updated self.rqo.sqo.limit. Try sqo and show.sqo_config
					if (request_config_item.sqo && request_config_item.sqo.limit) {
						self.rqo.sqo.limit = request_config_item.sqo.limit
					}else
					if(request_config_item.show && request_config_item.show.sqo_config && request_config_item.show.sqo_config.limit) {
						self.rqo.sqo.limit = request_config_item.show.sqo_config.limit
					}
					// Updated self.rqo.sqo.offset. Try sqo and show.sqo_config
					if (request_config_item.sqo && request_config_item.sqo.offset) {
						self.rqo.sqo.offset = request_config_item.sqo.offset
					}else
					if(request_config_item.show && request_config_item.show.sqo_config && request_config_item.show.sqo_config.offset) {
						self.rqo.sqo.offset = request_config_item.show.sqo_config.offset
					}
				}

			// count rows
				if (!self.total) {
					self.get_total()
				}

			// set_local_db_data updated rqo
				// const rqo = self.rqo
				// data_manager.set_local_db_data(
				// 	rqo,
				// 	'rqo'
				// )

			// view
				if (self.context.view) {
					self.view = self.context.view
				}

			// debug
				if(SHOW_DEBUG===true) {

					// fn_show_debug_info
						const fn_show_debug_info = function() {
							event_manager.unsubscribe(event_token)

							const debug = document.getElementById("debug")
							if (!debug) {
								console.log('Ignored debug');
								return
							}

							// clean
								while (debug.firstChild) {
									debug.removeChild(debug.firstChild)
								}

							// button_debug add
								const button_debug = ui.create_dom_element({
									element_type	: 'button',
									class_name		: 'info eye',
									inner_html		: get_label.debug || "Debug",
									parent			: debug
								})
								button_debug.addEventListener("click", function(){

									if (debug_container.hasChildNodes()) {
										debug_container.classList.toggle('hide')
										return
									}

									// clean
										// while (debug_container.firstChild) {
										// 	debug_container.removeChild(debug_container.firstChild)
										// }

									// collect debug data
									load_data_debug(self, api_response, self.rqo)
									.then(function(info_node){
										// debug.classList.add("hide")
										if (info_node) {
											debug_container.appendChild(info_node)
										}

										// scroll debug to top of page
											const bodyRect	= document.body.getBoundingClientRect()
											const elemRect	= debug.getBoundingClientRect()
											const offset	= elemRect.top - bodyRect.top
											window.scrollTo({
												top			: offset,
												left		: 0,
												behavior	: 'smooth'
											});
									})
								})

							// debug_container
								const debug_container = ui.create_dom_element({
									element_type	: 'div',
									class_name		: 'debug_container',
									parent			: debug
								})

							// show
								debug.classList.remove("hide")
						}
					const event_token = event_manager.subscribe('render_'+self.id, fn_show_debug_info)
					self.events_tokens.push(event_token)
				}
		}//end if (autoload===true)

	// Update section mode/label with context declarations
		// const section_context = self.context || {
		// 	mode		: 'edit',
		// 	label		: 'Section without permissions '+self.tipo,
		// 	permissions	: 0
		// }
		// self.mode 	= section_context.mode

	// update instance properties from context
		set_context_vars(self, self.context)

	// initiator . URL defined var or Caller of parent section
	// this is a param that defined who is calling to the section, sometimes it can be a tool or page or ...,
		const searchParams = new URLSearchParams(window.location.href);
		const initiator = searchParams.has("initiator")
			? searchParams.get("initiator")
			: self.caller!==null
				? self.caller.id
				: false
		// fix initiator
			self.initiator = initiator
				? initiator.split('#')[0]
				: initiator

	// paginator
		if (self.paginator===null) {

			self.paginator = new paginator()
			self.paginator.init({
				caller	: self,
				mode	: self.mode
			})

			// event paginator_goto_
				const fn_paginator_goto = async function(offset) {

					// fix new offset value
						self.request_config_object.sqo.offset	= offset
						self.rqo.sqo.offset						= offset

					// navigate section rows
						self.navigate({
							callback			: () => { // callback

								// (!) This code is unified in function 'navigate' ⬇︎

								// // fix new offset value
								// 	self.request_config_object.sqo.offset	= offset
								// 	self.rqo.sqo.offset						= offset
								// // set_local_db_data updated rqo
								// 	if (self.mode==='list') {
								// 		const rqo = self.rqo
								// 		data_manager.set_local_db_data(
								// 			rqo,
								// 			'rqo'
								// 		)
								// 	}
							},
							navigation_history	: true, // bool navigation_history save
							action				: 'paginate'
						})
				}
				self.events_tokens.push(
					event_manager.subscribe('paginator_goto_'+self.paginator.id, fn_paginator_goto)
				)

		}//end if (!self.paginator)

	// inspector
		if (self.inspector===null && self.mode==='edit' && self.permissions) {
			// if (initiator && initiator.model==='component_portal') {

			// 	self.inspector = null

			// }else{

				const current_inspector = new inspector()
				current_inspector.init({
					section_tipo	: self.section_tipo,
					section_id		: self.section_id,
					caller			: self
				})
				// fix section inspector
				self.inspector = current_inspector
			// }
		}

	// reset fixed_columns_map (prevents to apply rebuild_columns_map more than once)
		self.fixed_columns_map = false

	// columns_map. Get the columns_map to use into the list
		self.columns_map = get_columns_map(self.context, self.datum.context)

	// fix SQO to local DDBB. Used later to preserve section filter and pagination across pagination
		if (self.session_save===true) {
			if(SHOW_DEVELOPER===true) {
				console.warn('to local DDBB value :', self.session_key + '_' + self.mode, self.rqo.sqo);
			}
			data_manager.set_local_db_data(
				{
					id		: self.session_key + '_' + self.mode,
					value	: self.rqo.sqo
				},
				'sqo'
			)
		}

	// debug
		if(SHOW_DEBUG===true) {
			// console.log("self.context section_group:",self.datum.context.filter(el => el.model==='section_group'));
			// load_section_data_debug(self.section_tipo, self.request_config, load_section_data_promise)
			// console.log("__Time to build", self.model, "(ms):", performance.now()-t0);
			// dd_console(`__Time to build ${self.model} ${Math.round(performance.now()-t0)} ms`, 'DEBUG')

			// debug duplicates check
				const ar_used = []
				for(const element of self.datum.data) {

					if (element.matrix_id) { continue; } // skip verification in matrix data

					const index = ar_used.findIndex(item => item.tipo===element.tipo &&
													item.section_tipo===element.section_tipo &&
													item.section_id==element.section_id &&
													item.from_component_tipo===element.from_component_tipo &&
													item.parent_section_id==element.parent_section_id
													// && item.row_section_id==element.row_section_id
													// && (item.matrix_id && item.matrix_id==element.matrix_id)
													&& (item.tag_id && item.tag_id==element.tag_id)
													)
					if (index!==-1) {
						console.error("SECTION ERROR. self.datum.data contains duplicated elements:", ar_used[index]); // clone(self.datum.data)
					}else{
						ar_used.push(element)
					}
				}
		}

	// status update
		self.status = 'built'


	return true
}//end build



/**
* RENDER
* @param object options
*	render_level : level of deep that is rendered (full | content)
* @return promise
*	node first DOM node stored in instance 'node' array
*/
section.prototype.render = async function(options={}) {

	const self = this

	// call generic common render
		const result_node = await common.prototype.render.call(this, options)

	// event publish
		event_manager.publish('render_instance', self)

	// add node to instance
		self.node = result_node

	return result_node
}//end render



/**
* GET_SECTION_RECORDS
* Generate a section_record instance for each data value
* Create (init and build) a section_record for each component value
* Used by portals to get all rows for render
* @param object options
* @return array section_records
*/
export const get_section_records = async function(options) {

	// options
		const self				= options.caller
		const tipo				= options.tipo || self.tipo || {}
		const mode				= options.mode || self.mode || 'list'
		const columns_map		= options.columns_map || self.columns_map
		const id_variant		= options.id_variant || self.id_variant || null
		const view				= options.view || 'default'
		const column_id			= options.column_id || self.column_id || null
		const datum				= options.datum || self.datum || {}
		const context			= self.context || {}
		const request_config	= (options.request_config)
			? clone(options.request_config)
			: clone(context.request_config)
		const fields_separator	= options.fields_separator || context.fields_separator || {}
		const lang				= options.lang || self.section_lang || self.lang
		const value				= options.value || ((self.data && self.data.value)
			? self.data.value
			: [])
		const section_record_mode = mode==='tm'
			? 'list'
			: mode

	// iterate records
		const ar_promises	= []
		const value_length	= value.length
		for (let i = 0; i < value_length; i++) {

			const locator				= value[i];
			const current_section_id	= locator.section_id
			const current_section_tipo	= locator.section_tipo

			const instance_options = {
				model			: 'section_record',
				tipo			: tipo,
				section_tipo	: current_section_tipo,
				section_id		: current_section_id,
				mode			: section_record_mode,
				lang			: lang,
				context			: {
					view				: view,
					request_config		: request_config,
					fields_separator	: fields_separator
				},
				// data			: current_data,
				datum			: datum,
				row_key 		: i,
				caller			: self,
				paginated_key	: locator.paginated_key,
				columns_map		: columns_map,
				column_id		: column_id,
				locator			: locator,
				id_variant		: id_variant
			}

			// id_variant . Propagate a custom instance id to children
				if (id_variant) {
					instance_options.id_variant = id_variant
				}

			// locator tag_id modifies id_variant when is present
				if (locator.tag_id) {
					const tag_id_add = '_l' + locator.tag_id
					instance_options.id_variant = (instance_options.id_variant)
						? instance_options.id_variant + tag_id_add
						: tag_id_add
				}

		// matrix_id. time machine matrix_id
			// time machine options
				if (self.model==='service_time_machine' || self.matrix_id) {
					instance_options.matrix_id = locator.matrix_id || self.matrix_id
					// // instance_options.matrix_id = (self.model==='section')
					// instance_options.matrix_id = (self.model==='service_time_machine')
					// 	? locator.matrix_id
					// 	: self.matrix_id
					instance_options.modification_date	= locator.timestamp || null
					instance_options.id_variant = instance_options.id_variant + '_' + instance_options.matrix_id
				}

			// promise add and continue init and build
				ar_promises.push(new Promise(function(resolve){
					instances.get_instance(instance_options)
					.then(function(current_section_record){
						current_section_record.build()
						.then(function(){
							resolve(current_section_record)
						})
					})
				}))
		}//end for (let i = 0; i < value_length; i++)

	// ar_instances. When all section_record instances are built, set them
		const section_records = await Promise.all(ar_promises).then((ready_instances) => {
			return ready_instances
		});


	return section_records
}//end get_section_records



/**
* LOAD_SECTION_TOOL_FILES
* Used by section_tool to set the tool icon from tool css definition
* Normally mask-image: url('../img/icon.svg');
* @return void
*/
section.prototype.load_section_tool_files = function() {

	const self = this

	// css file load
		const model	= self.config.tool_context.model
		const url	= DEDALO_TOOLS_URL + '/' + model + '/css/' + model + '.css'
		common.prototype.load_style(url)

	// debug
		if(SHOW_DEBUG===true) {
			console.log('loaded section_tool files:', url);
		}
}//end load_section_tool_files



/**
* DELETE_SECTION
* @param object options
* {
* 	sqo : object,
* 	delete_mode : string
* }
* @return bool
*/
section.prototype.delete_section = async function (options) {

	const self = this

	// options
		const sqo						= clone(options.sqo)
		const delete_mode				= options.delete_mode
		const caller_dataframe			= options.caller_dataframe || null
		const delete_diffusion_records	= options.delete_diffusion_records ?? true

	// sqo
		// sqo.limit = null

	// source
		const source			= create_source(self, 'delete')
		source.section_id		= self.section_id
		source.delete_mode		= delete_mode
		source.caller_dataframe	= caller_dataframe

	// data_manager. delete
		const rqo = {
			action	: 'delete',
			source	: source,
			sqo		: sqo,
			options : {
				delete_diffusion_records : delete_diffusion_records
			}
		}
		const api_response = await data_manager.request({
			body : rqo
		})
		if (api_response.result && api_response.result.length>0) {

			// force to recalculate total records
			self.total = null
			// refresh self section
			self.refresh()
		}else{
			console.error( api_response.msg || 'Error on delete records!');
		}


	return true
}//end delete_section



/**
* NAVIGATE
* Refresh the section instance with new sqo params creating a
* history footprint. Used to paginate and sort records
* @param object options
* @return bool
*/
section.prototype.navigate = async function(options) {

	const self = this

	// options
		const action				= options.action || 'paginate'
		const callback				= options.callback
		const navigation_history	= options.navigation_history!==undefined
			? options.navigation_history
			: false

	// check_unsaved_data
		const result = await check_unsaved_data({
			confirm_msg : 'section: ' + (get_label.discard_changes || 'Discard unsaved changes?')
		})
		if (!result) {
			// user selects 'cancel' in dialog confirm. Stop navigation
			return false
		}

	// remove aux items
		if (window.page_globals.service_autocomplete) {
			window.page_globals.service_autocomplete.destroy(true, true, true)
		}

	// callback execute
		if (callback) {
			await callback()

			if(SHOW_DEBUG===true) {
				// console.log("-> Executed section navigate received callback:", callback);
			}
		}

	// loading
		if (self.node_body){
			self.node_body.classList.add('loading')
		}
		if (self.inspector && self.inspector.node) {
			self.inspector.node.classList.add('loading')
		}

	// refresh
		await self.refresh({
			destroy : false // avoid to destroy here to allow section to recover from loosed login scenarios
		})

	// loading
		if (self.node_body){
			self.node_body.classList.remove('loading')
		}
		if (self.inspector && self.inspector.node) {
			self.inspector.node.classList.remove('loading')
		}

	// navigation history. When user paginates, store navigation history to allow browser navigation too
		if (navigation_history===true) {

			const source	= create_source(self, null)
			const sqo		= self.request_config_object.sqo
			const title		= self.id

			// url search. Append section_id if exists
				const url_vars = url_vars_to_object(location.search)
				const url = '?' + object_to_url_vars(url_vars)

			// browser navigation update
				push_browser_history({
					source	: source,
					sqo		: sqo,
					title	: title,
					url		: url
				})
		}


	return true
}//end navigate



/**
* CHANGE_MODE
* Destroy current instance and dependencies without remove HTML nodes (used to get target parent node placed in DOM)
* Create a new instance in the new mode (for example, from list to edit) and view (ex, from default to line )
* Render a fresh full element node in the new mode
* Replace every old placed DOM node with the new one
* @param object options
* @return object|null new_instance
*/
section.prototype.change_mode = async function(options) {

	const self = this

	// options vars
		// mode check. When mode is undefined, fallback to 'list'. From 'list', change to 'edit'
		const mode = (options.mode)
			? options.mode
			: self.mode==='list' ? 'edit' : 'list'
		const autoload = (typeof options.autoload!=='undefined')
			? options.autoload
			: true
		const view = options.view ?? null

	// short vars
		const current_context	= self.context
		const section_lang		= self.section_lang
		const id_variant		= self.id_variant
		const old_node			= self.node
		if (!old_node) {
			console.warn('Not old_node found!!');
			return null
		}

	// set the new view to context
		current_context.view = view
		current_context.mode = mode

	// instance
		const new_instance = await instances.get_instance({
			model			: current_context.model,
			tipo			: current_context.tipo,
			section_tipo	: current_context.section_tipo,
			mode			: mode,
			lang			: current_context.lang,
			section_lang	: section_lang,
			type			: current_context.type,
			id_variant		: id_variant,
			caller			: self.caller || null
		})

	// load_item_with_spinner
		ui.load_item_with_spinner({
			container			: old_node,
			preserve_content	: false,
			label				: current_context.label || current_context.model,
			replace_container	: true,
			callback			: async () => {

				// build (load data)
				await new_instance.build(autoload)

				// render node
				const node = await new_instance.render()

				// destroy self instance (delete_self=true, delete_dependencies=false, remove_dom=false)
				self.destroy(
					true, // delete_self
					true, // delete_dependencies
					true // remove_dom
				)

				return node || ui.create_dom_element({
					element_type	: 'div',
					class_name		: 'error',
					inner_html		: 'Error on render element ' + new_instance.model
				})
			}
		})


	return new_instance
}//end change_mode



/**
* GET_TOTAL
* Exec a async API call to count the current sqo records
* @return int total
*/
section.prototype.get_total = async function() {

	const self = this

	// debug
		if(SHOW_DEBUG===true) {
			// console.warn('section get_total self.total:', self.total);
		}

	// already calculated case
		if (self.total || self.total==0) {
			return self.total
		}

	// queue. Prevent double resolution calls to API
		if (self.loading_total_status==='resolving') {
			return new Promise(function(resolve){
				setTimeout(function(){
					resolve( self.get_total() )
				}, 600)
			})
		}

	// loading status update
		self.loading_total_status = 'resolving'

	// API request
		const count_sqo = clone(self.rqo.sqo )
		delete count_sqo.limit
		delete count_sqo.offset
		delete count_sqo.select
		delete count_sqo.order
		delete count_sqo.generated_time
		const source	= create_source(self, null)
		const rqo_count	= {
			action			: 'count',
			sqo				: count_sqo,
			prevent_lock	: true,
			source			: source
		}
		const api_count_response = await data_manager.request({
			body		: rqo_count,
			use_worker	: true
		})

	// API error case
		if (!api_count_response.result || api_count_response.error) {
			console.error('Error on count total : api_count_response:', api_count_response);
			return
		}

	// set result
		self.total = api_count_response.result.total


	// loading status update
		self.loading_total_status = 'resolved'


	return self.total
}//end get_total



// @license-end
