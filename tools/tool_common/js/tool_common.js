/*global Promise, page_globals, SHOW_DEBUG, DEDALO_TOOLS_URL, DEDALO_CORE_URL, lzstring */
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../../core/common/js/event_manager.js'
	import {data_manager} from '../../../core/common/js/data_manager.js'
	import {get_instance} from '../../../core/common/js/instances.js'
	import {common} from '../../../core/common/js/common.js'
	import {LZString as lzstring} from '../../../core/common/js/utils/lzstring.js'
	import {ui} from '../../../core/common/js/ui.js'
	import {
		clone,
		dd_console,
		printf,
		// object_to_url_vars,
		// url_vars_to_object,
		// JSON_parse_safely,
		// open_window_with_post
		} from '../../../core/common/js/utils/index.js'
	import {render_error} from './render_tool_common.js'



export const tool_common = function(){

	return true
}//end tool_common



/**
* INIT
* Generic tool init function.
* @param object options
* Sample:
* {
* 	caller: component_text_area {id: "component_text_area_rsc36_rsc167_1_edit_lg-eng_rsc167", …}
*	lang: "lg-eng"
*	mode: "edit"
*	model: "tool_indexation"
*	section_id: "1"
*	section_tipo: "rsc167"
*	tipo: "rsc36"
*	tool_config: {ddo_map:[], ...}
* }
*/
tool_common.prototype.init = async function(options) {
	if(SHOW_DEVELOPER===true) {
		dd_console(`init tool options`, 'DEBUG', options)
	}

	const self = this

	// options
		self.model			= options.model
		self.section_tipo	= options.section_tipo //
		self.section_id		= options.section_id
		self.lang			= options.lang
		self.mode			= options.mode || 'edit'
		self.config			= options.config // specific configuration that define, in current installation, things like machine translation will be used.
		self.tool_config	= options.tool_config
		self.caller			= options.caller

		// caller. Could be direct assigned (modal) or by URL caller_id (new window)
			// notify caller is already calculated (new window case)
			self.caller_is_calculated = !self.caller
			// caller fallback to window.opener.callers variable or local data base
			if (!self.caller) {

				// re-build from caller_ddo
					// searchParams
					const searchParams = new URLSearchParams(window.location.href)
					// raw_data
					const raw_data = searchParams.has('raw_data')
						? searchParams.get('raw_data') // string from url
						: null

					if (raw_data) {

						// Note that url param 'url_data' is an object stringify-ed and compressed-encoded
						// Expected raw_data decoded is an object as
						// {
						//	 caller_ddo : object {...},
						//	 tool_config : object {...}
						// }
						const url_data_string	= lzstring.decompressFromEncodedURIComponent(raw_data)
						const url_data_object	= JSON.parse(url_data_string)
						const caller_ddo		= url_data_object.caller_ddo
						const tool_config		= url_data_object.tool_config

						// set and build caller
							self.caller = await get_instance( caller_ddo )

						// dataframe
							self.caller_dataframe = caller_ddo.caller_dataframe ?? null

						// set current tool as caller
							self.caller.caller = self

						if(caller_ddo.model!=='section'){
							// build
								await self.caller.build(true)
						}

						// set tool_config
							self.tool_config = tool_config

					}else{
						console.error('Error. Unable to get caller_ddo from URL:', window.location.href);
					}
			}
			if (!self.caller) {
				self.error = `Warning. Empty caller !`
				console.warn(self.error, self)
				// return false
			}
			// console.log("self.caller:",self.caller);

		// tool_config. Contains the needed ddo_map
			if (!self.tool_config && self.caller) {

				if (self.caller.config && self.caller.config.tool_context) {

					// section_tool case

					// from caller config (transcription case for example)
						self.tool_config = clone(self.caller.config.tool_context.tool_config)
						if(SHOW_DEBUG===true) {
							// console.log("/// -> section_tool case self.caller.config -> self.tool_config:", self.tool_config);
						}

				}else if (self.caller.tools) {

					// component case

					const tool_found = self.caller.tools.find(el => el.model===self.model)

					if(SHOW_DEBUG===true) {
						// console.log("component case tool_found:", tool_found);
						// console.log("component case self.caller.tools:",self.caller.tools);
						// console.log("component case tool_found:",tool_found);
					}

					self.tool_config = tool_found.tool_config
				}

				// final fallback
					if (!self.tool_config) {

						// fallback
							self.tool_config = {
								ddo_map : [{
									tipo			: self.caller.tipo,
									section_tipo	: self.caller.section_tipo,
									section_id		: self.caller.section_id,
									model			: self.caller.model,
									mode			: self.caller.mode, //'edit',
									lang			: self.caller.lang,
									role			: 'main_element'
								}]
							}
							if(SHOW_DEBUG===true) {
								console.log("-> tool_common init final fallback case self.tool_config:", self.tool_config);
							}
					}
			}

		// parse ddo_map section_id
			if (self.tool_config && self.tool_config.ddo_map) {
				self.tool_config.ddo_map.map(el => {
					if (el.section_id==='self' && el.section_tipo===self.caller.section_tipo) {
						el.section_id = self.caller.section_id || self.caller.section_id_selected
					}
				})
			}

	// set some common vars
		self.node			= null
		self.type			= 'tool'
		self.ar_instances	= []
		self.events_tokens	= []
		self.get_tool_label	= get_tool_label // function get_label called by the different tools to obtain the own label in the current lang. The scope is for every tool.

	// set status
		self.status = 'initied'


	return true
}//end init



/**
* BUILD
* Generic tool build function. Load basic tool config info (stored in component_json dd1353) and css files
* @param bool autoload = false
* @param object options = {}
* 	callback function 'load_ddo_map'
* @return promise
* 	resolve: bool
*/
tool_common.prototype.build = async function(autoload=false, options={}) {
	// const t0 = performance.now()

	const self = this

	// options
		// load_ddo_map could be a callback or the default loader function
		const load_ddo_map = typeof options.load_ddo_map==='function'
			? options.load_ddo_map
			: async function() {
				// default loads all elements inside ddo_map
				const ar_promises		= []
				const ddo_map			= self.tool_config && self.tool_config.ddo_map
					? self.tool_config.ddo_map
					: []

				const ddo_map_length	= ddo_map.length
				for (let i = 0; i < ddo_map_length; i++) {

					// el. components / sections / areas used by the tool defined in tool_config.ddo_map
						const el = ddo_map[i]

					// skip caller ddo item when is section (case tool_diffusion very slow)
						if (self.caller && self.caller.model==='section' && self.caller.tipo===el.tipo && self.caller.section_tipo===el.section_tipo) {
							// self.ar_instances.push(self.caller)
							continue
						}

					//skip autoload false.
						if(el.autoload===false){
							continue
						}

					// lang. If is defined in properties, parse and use it, else use the tool lang
					// taking care to do not re-parse the value
						const current_el_lang = el.lang
							? el.lang // already exists
							: (typeof el.translatable!=='undefined' && el.translatable===false)
								? page_globals.dedalo_data_nolan // lg-nolan
								: page_globals.dedalo_data_lang // current data lang (DEDALO_DATA_LANG)

					ar_promises.push( new Promise(async (resolve) => {

						// new window cases. Caller is calculated, NOT from existing component, so we recycle the instance
							if (self.caller_is_calculated && el.tipo===self.caller.tipo) {
								console.log('Used already resolved caller instance:', self.caller);
								resolve(self.caller)
								return
							}

						const element_options = {
							model			: el.model,
							mode			: el.mode,
							tipo			: el.tipo,
							section_tipo	: el.section_tipo,
							section_id		: el.section_id,
							lang			: current_el_lang,
							type			: el.type,
							properties 		: el.properties || null,
							id_variant		: self.model,  // id_variant prevents id conflicts
							caller			: self // set tool as caller of the component :-)
						}

						// init and build instance
							get_instance(element_options) // load and init
							.then(function(element_instance){
								const load_data = true // el.model.indexOf('component')!==-1 || el.model==='area_thesaurus'
								element_instance.build( load_data ) // build, loading data
								.then(function(){
									// console.log('--->>> element_instance', element_instance)
									resolve(element_instance)
								})
							})
					}))
				}//end for (let i = 0; i < ddo_map.length; i++)

				// set on finish
				await Promise.all(ar_promises).then((ar_instances) => {
					// dd_console(`ar_instances`, 'DEBUG', ar_instances)
					self.ar_instances = ar_instances
				})

				return true
			  }//end async function() load_ddo_map


	// previous status
		// const previous_status = self.status

	// status update
		self.status = 'building'

	// load self style
		const tool_css_url = DEDALO_TOOLS_URL + '/' + self.model + '/css/' + self.model + '.css'
		common.prototype.load_style(tool_css_url)

	// load_ddo_map. Exec load ddo_map elements
		await load_ddo_map()


	// load data if is not already received as option
		if (autoload===true && !self.context) {

			// rqo. Create the basic rqo to load tool config data stored in component_json tipo 'dd1353'
				const rqo = {
					action	: 'get_element_context',
					// tool source for component JSON that stores full tool config
					source : {
						model			: self.model,
						section_tipo	: self.section_tipo,
						section_id		: self.section_id,
						mode			: self.mode,
						lang			: self.lang
					},
					prevent_lock : true
				}

			// load data. Load section data from db of the current tool.
			// Tool data configuration is inside the tool_registered section 'dd1324' and parsed into component_json 'dd1353',
			// The tool info was generated when it was imported / registered by admin
				const api_response = await data_manager.request({
					body : rqo
				})
				self.context = api_response.result

			// debug
				if(SHOW_DEBUG===true) {
					// console.log("/// [tool_common.build] api_response:", api_response);
					dd_console(`[tool_common.build] TOOL: ${self.model} api_response:`, 'DEBUG', api_response)
				}
		}

	// debug
		if(SHOW_DEBUG===true) {
			// console.log("__Time to build", self.model, " ms:", Math.round(performance.now()-t0));
			// dd_console(`__Time to build ${self.model} ${Math.round(performance.now()-t0)} ms`, 'DEBUG')
			// dd_console(`tool common build. self.ar_instances`, 'DEBUG', self.ar_instances)
		}

	// status update
		self.status = 'built'


	return true
}//end build



/**
* RENDER
* This method is an alias of common.render to allow catch and manage start tool errors
* Note that if is defined self.error (because a error was written in init or build phases)
* the tool common error will be used instead tool render
* @param object options
*	render_level : level of deep that is rendered (full | content)
* @return promise
*	node first DOM node stored in instance 'node' array
*/
tool_common.prototype.render = async function(options={}) {

	const self = this

	// call the generic common render or render tool generic error
		const result = typeof self.error!=='undefined'
			? render_error(this, options)
			: await common.prototype.render.call(this, options);


	return result
}//end render



/**
* LOAD_COMPONENT
* Loads component to place it in respective containers: current preview and preview version
* @param object options
* 	self				: instance of the caller
* 	model				: model of the component to load
* 	mode				: mode of the component to load
* 	tipo				: tipo of the component to load
* 	section_tipo		: section_tipo of the component to load
* 	section_lang		: section_lang of the component to load
* 	lang				: lang of the component to load
* 	type				: type of the component to load
* 	section_id			: section_id of the component to load
* 	data_source			: data_source of the component to load
* 	id_variant			: id_variant of the component to load, if not set use the model of the tool
* 	to_delete_instances	: array of instance object
* @return promise: object component_instance
*/
export const load_component = async function(options) {

	// options
		const self 					= options.self
		const model					= options.model
		const mode					= options.mode
		const tipo					= options.tipo
		const section_tipo			= options.section_tipo
		const section_lang			= options.section_lang
		const lang					= options.lang
		const type					= options.type
		const section_id			= options.section_id || null
		const matrix_id				= options.matrix_id || null
		const data_source			= options.data_source || null
		const id_variant			= options.id_variant || self.model
		const to_delete_instances	= options.to_delete_instances

	// component instance_options
		const instance_options = {
			model			: model,
			mode			: mode,
			tipo			: tipo,
			section_tipo	: section_tipo,
			section_id		: section_id,
			lang			: lang,
			section_lang	: section_lang,
			type			: type,
			id_variant		: id_variant, // id_variant prevents id conflicts
			caller			: self // set current tool as component caller (to check if component is inside tool or not)
		}

		if (matrix_id) {
			instance_options.matrix_id = matrix_id
		}

		if (data_source) {
			instance_options.data_source = data_source
		}

	// get instance and init
		const component_instance = await get_instance(instance_options)

	// clean instances
		if (to_delete_instances && to_delete_instances.length>0) {
			for (let i = self.ar_instances.length - 1; i >= 0; i--) {
				const current_instance = self.ar_instances[i]
				if (to_delete_instances.indexOf(current_instance)!==-1) {
					// destroy previous preview component instances
					const instance_index = self.ar_instances.findIndex( el => el.id===current_instance.id)
					// dd_console(`To delete instance index:`, 'DEBUG', instance_index)
					// remove from array of instances
					if (instance_index!==-1) {
						self.ar_instances.splice(instance_index, 1)
						// destroy instance
						await current_instance.destroy()
					}else{
						console.error("Error on delete previous component instance")
					}
				}
			}
		}

	// add component instance to current ar_instances if not already done
		const instance_found = self.ar_instances.find( el => el===component_instance )
		if (!instance_found) {
			self.ar_instances.push(component_instance)
		}

	// build
		await component_instance.build(true)


	return component_instance
}//end load_component



/**
* OPEN_TOOL
* Init, build and render the tool requested.
* Called by page observe event (init)
* To load tool, don't call directly, publish a event as
	*	event_manager.publish('open_tool', {*
	* 		caller 		 : self,
	* 		tool_context : {
	* 			css: "/v6/tools/tool_lang/css/tool_lang.css"
	*			icon: "/v6/tools/tool_lang/img/icon.svg"
	*			label: "Translation"
	*			mode: "edit"
	*			model: "tool_lang"
	*			name: "tool_lang"
	*			properties: {open_as: 'modal', windowFeatures: null}
	*			section_id: 8
	*			section_tipo: "dd1324"
	*			show_in_component: true
	* 		}
	*	})
* The event is fired by the tool button created with method ui.build_tool_button.
* When the user triggers the click event, a publish 'open_tool' is made
* @param object options
* @return object|bool
* 	object is a tool instance
*/
export const open_tool = async (options) => {
	console.warn("------ open_tool call options:",options);

	// options
		const caller		= options.caller
		const tool_context	= clone(options.tool_context) // (!) full clone here to avoid circular references

	// open_as. Mode of tool visualization: modal, tab, popup
		const open_as = tool_context && tool_context.properties && tool_context.properties.open_as
			? tool_context.properties.open_as
			: 'modal' // default is 'modal'

	// windowFeatures. Features to pass to the tool visualizer
	// (normally standard JAVASCRIPT text features like: "left=100,top=100,width=320,height=320")
		const windowFeatures = tool_context && tool_context.properties && tool_context.properties.windowFeatures
			? tool_context.properties.windowFeatures
			: null

	// open tool visualization
		const js_promise = (open_as==='window')
			? view_window({
				tool_context	: tool_context, // object
				caller			: caller, // object like component_input_text instance
				open_as			: open_as, // string like 'tab' | 'popup'
				windowFeatures	: windowFeatures // string like 'left=100,top=100,width=320,height=320'
			  })
			: view_modal({
				tool_context	: tool_context, // object
				caller			: caller, // object like component_input_text instance
				open_as			: open_as, // string like 'tab' | 'popup'
				windowFeatures	: windowFeatures // string like 'left=100,top=100,width=320,height=320'
			  })


	return js_promise
}//end open_tool



/**
* VIEW_MODAL
* @param object options
* @return promise
* 	Resolve: object tool_instance
*/
const view_modal = async function(options) {

	// options
		const tool_context	= options.tool_context || {}
		const caller		= options.caller

	// (!) Moved yo tool_common init unified parse
	// tool_config. If is received, parse section_id. Else create a new one on the fly
		// to preserve the format of tool_context.tool_config ddo_map
		// if (!tool_context.tool_config) {
		// 	// create a new one on the fly
		// 	tool_context.tool_config = {
		// 		ddo_map : [{
		// 			tipo			: caller.tipo,
		// 			section_tipo	: caller.section_tipo,
		// 			section_id		: caller.section_id,
		// 			model			: caller.model,
		// 			mode			: 'edit',
		// 			role			: 'main_element'
		// 		}]
		// 	}
		// }else{
		// if (tool_context.tool_config) {

		// 	// parse ddo_map section_id
		// 	tool_context.tool_config.ddo_map.map(el => {
		// 		if (el.section_id==='self' && el.section_tipo===caller.section_tipo) {
		// 			el.section_id = caller.section_id
		// 		}
		// 	})
		// }

	// tool context additional properties
		tool_context.lang		= caller.lang
		tool_context.type		= 'tool'
		tool_context.id_variant	= caller.id_base // prevent instance id collisions

	// instance options
		const instance_options = Object.assign({
			caller : caller // add caller to tool_context (only to refresh it on close the tool)
		}, tool_context)

	// instance load / recover
		const tool_instance = await get_instance(instance_options)

	// stop if already loaded (toggle tool)
		if (tool_instance.status && tool_instance.status!=='initied') {
			return false
		}

	// build
		await tool_instance.build(true)

	// render tool (don't wait here)
		tool_instance.render()
		.then(function(wrapper){

			const header	= wrapper.tool_header // is created by ui.tool.build_wrapper_edit
			const modal		= ui.attach_to_modal({
				header	: header,
				body	: wrapper,
				footer	: null
			})
			modal.on_close	= () => {

				if (typeof tool_instance.on_close_actions==='function') {
					// custom actions
					tool_instance.on_close_actions('modal')
				}else{

					caller.refresh()
					tool_instance.destroy(true, true, true)
				}
			}

			// pointer from wrapper to modal
			wrapper.modal = modal
		})


	return tool_instance
}//end view_modal



/**
* VIEW_WINDOW
* @param object options
* @return promise
* 	Resolve: object tool_window
*/
const view_window = async function(options) {

	// options
		const tool_context		= options.tool_context
		const caller			= options.caller
		// const open_as		= options.open_as
		const windowFeatures	= options.windowFeatures || null
		// windowFeatures sample:
			// {
			// 	left	: 'return screen.width -760',
			// 	top		: 0,
			// 	width	: 760,
			// 	height	: 500
			// }

	// short vars
		const name			= tool_context.name
		const tool_config	= tool_context.tool_config || null

	// fix current instance as caller in global window to be accessible from new window
		// window.callers = window.callers || {}
		// window.callers[caller.id] = caller

	// caller_ddo. Minimum caller data to re-build it from tool
		const caller_ddo = {
			id_variant			: caller.id_variant || null,
			tipo				: caller.tipo,
			section_tipo		: caller.section_tipo,
			section_id			: caller.section_id,
			section_id_selected	: caller.section_id_selected,
			mode				: caller.mode,
			model				: caller.model,
			lang				: caller.lang
		}

	// caller_dataframe . Used for dataframe
		if (caller.context && caller.context.is_dataframe) {
			if(caller.caller && caller.caller.model==='section_record'){
				if(caller.caller.caller){
					caller_ddo.caller_dataframe = {
						section_tipo	: caller.caller.caller.section_tipo,
						section_id		: caller.caller.caller.section_id
					}
				}
			}
		}

	// URL
		// raw_data will be compressed and de-compressed from target window
		const raw_data	= lzstring.compressToEncodedURIComponent(
			JSON.stringify({
				// caller_id	: caller.id,
				caller_ddo		: caller_ddo,
				tool_config		: tool_config
			})
		)
		const url = DEDALO_CORE_URL + `/page/?tool=${name}&menu=false&raw_data=` + raw_data
		if (url.length>2000) {
			console.warn('Warning. The URL is too long:', url.length);
		}

	// window features
		const parsed_windowFeatures = typeof windowFeatures==='string'
			? windowFeatures // string case as 'left=100,top=100,width=320,height=320'
			: (()=>{ // object case as {"left":"return screen.width -760","top":0,"width":760,"height":500}

				const parsed_pairs = []
				for(const key in windowFeatures) {

					// value could be a Function as string like 'return screen.width -500'
					// @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function
					const value = typeof windowFeatures[key]==='string' && windowFeatures[key].indexOf('return')===0
						? Function(windowFeatures[key])() // parse and auto exec the created Function
						: windowFeatures[key]

					const pair = `${key}=${value}`

					parsed_pairs.push(pair)
				}

				const parsed_string = parsed_pairs.join(',')

				return parsed_string
			  })()

	// tool_window
		const window_name	= name +'_'+ (caller.id_base || '')
		const tool_window	= window.open(url, window_name, parsed_windowFeatures)
		tool_window.focus();

	// close tab tool_window actions (pagehide)
		// page lifecycle events: 'pageshow', 'focus', 'blur', 'visibilitychange', 'resume', 'pagehide'
		// @see https://developer.mozilla.org/en-US/docs/Web/API/Document/visibilitychange_event
		// tool_window.addEventListener('load', () => {
		// 	console.log("attaching event tool_window pagehide");

		// 	// tool_window.sessionStorage.setItem('reloaded', 'yes');
		// 	// console.log("tool_window.sessionStorage.getItem('reloaded') 1:",tool_window.sessionStorage.getItem('reloaded'));

		// 	tool_window.addEventListener(
		// 		'pagehide',
		// 		(event) => {
		// 			console.log("===== triggered pagehide event:",event);
		// 			// console.log("tool_window.visibilityState:",tool_window.visibilityState);
		// 			// console.log("tool_window.document:",tool_window.document);
		// 			// console.log("tool_window.document.visibilityState:",tool_window.document.visibilityState);
		// 			// console.log("event.persisted:",event.persisted);

		// 			// setTimeout(function(){
		// 			// 	console.log("tool_window.sessionStorage.getItem('reloaded') 2:",tool_window.sessionStorage.getItem('reloaded'));
		// 			// }, 1)


		// 			// if (event.persisted) {
		// 			// 	// If the event's persisted property is `true` the page is about
		// 			// 	// to enter the Back-Forward Cache, which is also in the frozen state.
		// 			// 	console.log("frozen");
		// 			// }else{
		// 			// 	// If the event's persisted property is not `true` the page is
		// 			// 	// about to be unloaded.
		// 			// 	// console.log("terminated");

		// 			// 	// On tool_window.document.visibilityState change to 'hidden', trigger actions
		// 			// 	if (tool_window.document.visibilityState==='hidden') {

		// 			// 	}
		// 			// }

		// 			/*
		// 			// remove window.callers pointer
		// 			delete window.callers[caller.id]
		// 			// refresh caller
		// 			caller.refresh()
		// 			*/

		// 		},{capture: true}
		// 	);//end pagehide event

		// },{capture: true})//end load event

	// focus event trigger on current window
		window.addEventListener('focus', fn_onfocus, true);
		function fn_onfocus() {
			// (!) remove listener after focus
			window.removeEventListener('focus', fn_onfocus, true);

			// remove window.callers pointer
			// delete window.callers[caller.id] /* (!) TEMPORAL DEACTIVATED ! */

			// refresh caller.
			// Note that in some situations, caller is not an instance like in grid_dd indexation button
			if (caller && typeof caller.refresh==='function') {
				caller.refresh()
			}

			// close opened window if is open
			// if (tool_window) {
				// tool_window.close() /* (!) TEMPORAL DEACTIVATED ! */
			// }
		}

	// close tool_window
		// tool_window.addEventListener('close', fn_onclose, true);
		// function fn_onclose() {
		// 	// refresh caller
		// 	if (caller) {
		// 		caller.refresh()
		// 	}
		// }


	return tool_window
}//end view_window



/**
* TRIGGER_REQUEST
* This is a common tool API request way
*/
	// export const trigger_request = async function(trigger_url, body) {
	// 	const t0 = performance.now()

	// 	const handle_errors = function(response) {
	// 		if (!response.ok) {
	// 			throw Error(response.statusText);
	// 		}
	// 		return response;
	// 	}

	// 	const trigger_response = await fetch(
	//  		trigger_url,
	//  		{
	// 			method		: 'POST',
	// 			mode		: 'cors',
	// 			cache		: 'no-cache',
	// 			credentials	: 'same-origin',
	// 			headers		: {'Content-Type': 'application/json'},
	// 			redirect	: 'follow',
	// 			referrer	: 'no-referrer',
	// 			body		: JSON.stringify(body)
	// 		})
	// 		.then(handle_errors)
	// 		.then(response => response.json()) // parses JSON response into native Javascript objects
	// 		.catch(error => {
	// 			console.error("!!!!! REQUEST ERROR: ",error)
	// 			return {
	// 				result	: false,
	// 				msg		: error.message,
	// 				error	: error
	// 			}
	// 		});


	// 	// debug
	// 		if(SHOW_DEBUG===true) {
	// 			console.log("__Time to trigger_request", self.model, " ms:", performance.now()-t0);
	// 		}


	// 	return trigger_response
	// }//end trigger_request



/**
* GET_TOOL_LABEL
* Return the label in the current language.
* If the label is not defined, try with lang_default, not lang and received label_name if nothing is found
*
* @param string label_name like 'indexation_tool'
* @param mixed ...rest (accept an indefinite number of arguments as an array)
* @return string|null
* 	like 'Indexation Tool'
*/
const get_tool_label = function(label_name, ...rest) {

	const self = this

	const tool_labels = self.context.labels || []
	if (tool_labels.length>0) {

		// current lang try
			const found = tool_labels.find(el => el.name===label_name && el.lang===self.lang)
			if (found) {
				return printf(found.value, ...rest)
				// return found.value
			}

		// fallback to application lang default
			const lang_default 	= page_globals.dedalo_application_langs_default
			const found_default = tool_labels.find(el => el.name===label_name && el.lang===lang_default)
			if (found_default) {
				return printf(found_default.value, ...rest)
				// return found_default.value
			}

		// fallback to any lang available
			const found_any = tool_labels.find(el => el.name===label_name)
			if (found_any) {
				return printf(found_any.value, ...rest)
				// return found_any.value
			}
	}


	return null
}//end get_tool_label
