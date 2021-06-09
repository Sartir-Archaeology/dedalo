/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// import
	// custom html elements
	// import '../../common/js/dd-modal.js'
	import '../../services/service_tinymce/js/dd-tiny.js'
	// others
	import {menu} from '../../menu/js/menu.js'
	import {event_manager} from '../../common/js/event_manager.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {get_instance, delete_instance} from '../../common/js/instances.js'
	import {common} from '../../common/js/common.js'
	import {load_tool} from '../../../tools/tool_common/js/tool_common.js'
	// import '../../common/js/components_list.js' // launch preload all components files in parallel
	// import '../../../lib/tinymce/js/tinymce/tinymce.min.js'
	import {render_page} from './render_page.js'



/**
* PAGE
*/
export const page = function () {

	this.id

	this.model
	this.mode
	this.node
	this.ar_instances
	this.context
	this.status
	this.events_tokens


	return true
};//end page



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	page.prototype.edit		= render_page.prototype.edit
	page.prototype.render	= common.prototype.render
	page.prototype.refresh	= common.prototype.refresh
	page.prototype.destroy	= common.prototype.destroy



/**
* INIT
* @param object options
*/
page.prototype.init = async function(options) {
	
	const self = this

	self.model			= 'page'
	self.type			= 'page'
	self.mode			= 'edit' // options.mode 	  // mode like 'section', 'tool', 'thesaurus'...
	self.node			= []
	self.ar_instances	= []
	self.context		= options.context // mixed items types like 'sections', 'tools'..
	// self.dd_request	= self.context ? self.context.dd_request : []
	self.status			= null
	self.events_tokens	= []
	self.menu_data		= options.menu_data

	// launch preload all components files in parallel
		//import('../../common/js/components_list.js')

	// update value, subscription to the changes: if the section or area was changed, observers dom elements will be changed own value with the observable value
		// user_navigation
			self.events_tokens.push(
				event_manager.subscribe('user_navigation', user_navigation)
			)
		// user_navigation fn
			async function user_navigation(user_navigation_options) {
				if(SHOW_DEBUG===true) {
					console.log("// page user_navigation received user_navigation_options", user_navigation_options);
				}

				// reset status to prevent errors lock 
					self.status = 'rendered'

				// loading css
					const node = self.node && self.node[0]
						? self.node[0].querySelector('.content_data.page')
						: null
					if (node) {
						node.classList.add('loading')
					}


				(async ()=>{

					// basic vars
						const source			= user_navigation_options.source
						const sqo				= user_navigation_options.sqo || null
						const request_config	= [{
							api_engine	: 'dedalo',
							sqo			: sqo,
						}]
						source.request_config = request_config

					// check response page element is valid for instantiate. Element instance loads the file
						const page_element_instance = await instantiate_page_element(self, source)
						if (!page_element_instance) {
							console.error("error on get page_element_instance:", page_element_instance);
							// loading
								if (node) {
									setTimeout(function(){
										node.classList.remove('loading')
									}, 150)
								}
							return false
						}

					// elements to stay
						const base_models = ['menu']
						const elements_to_stay 	= self.context.filter( item => base_models.includes(item.model))
						// add current source from options
							elements_to_stay.push(source)
						// fix new page context
							self.context = elements_to_stay
					
					// instances. Set property 'destroyable' as false for own instances to prevent remove. Refresh page
						const instances_to_stay = self.ar_instances.filter(item => base_models.includes(item.model))
						for (let i = instances_to_stay.length - 1; i >= 0; i--) {
							instances_to_stay[i].destroyable = false
						}
						const refresh_result = await self.refresh()

					// url history track
						if(refresh_result===true && user_navigation_options.event_in_history!==true)  {

							// options_url : clone options and remove optional 'event_in_history' property
							const options_url 	= Object.assign({}, user_navigation_options);
							delete options_url.event_in_history
							const var_uri		= Object.entries(options_url).map(([key, val]) => `${key}=${val}`).join('&');
							const state			= {options : user_navigation_options}
							const title			= ''
							const url			= '' // "?"+var_uri //window.location.href

							history.pushState(state, title, url)
						}
				})()

				return


				// options
					const caller_id	= options.caller_id
					const new_rqo	= JSON.parse(JSON.stringify(options.rqo))
								
				// new way
				// new_context_element
					// const new_context_element = {
					// 	tipo			: new_rqo.source.tipo,
					// 	section_tipo	: new_rqo.source.section_tipo || new_rqo.source.tipo,
					// 	mode			: new_rqo.source.mode,
					// 	model			: new_rqo.source.model,
					// 	lang			: new_rqo.source.lang,
					// 	request_config	: [{
					// 		api_engine	: 'dedalo',
					// 		sqo			: new_rqo.sqo
					// 	}]
					// }
					// console.log("new_context_element:",new_context_element);

				/*
				// rqo. request_config
					const new_rqo = JSON.parse(JSON.stringify(user_navigation_rqo))
				
					const config_section_tipo = new_rqo.sqo && new_rqo.sqo.section_tipo
						? new_rqo.sqo.section_tipo.map(item => ({tipo:item}))
						: [{tipo: user_navigation_rqo.section_tipo}]

					const config_sqo = new_rqo.sqo
						? new_rqo.sqo
						: {}

					config_sqo.section_tipo = config_section_tipo

					const request_config = [{
						api_engine	: 'dedalo',
						sqo			: config_sqo
					}]

						console.log("request_config---page:",request_config);

				// des
					// const current_data_manager 	= new data_manager()
					// const api_response 			= await current_data_manager.get_element_context(options)
					//
					// // element context from api server result
					// 	const page_element = api_response.result
			
				// check response page element is valid for instantiate. Element instance loads the file					
					const page_element_instance = await instantiate_page_element(self, new_rqo.source)
					page_element_instance.context.request_config = request_config
						console.log("page_element_instance:",page_element_instance);
					if (!page_element_instance) {
						console.error("error on get page_element_instance:", page_element_instance);
						// loading
							if (node) {
								setTimeout(function(){
									node.classList.remove('loading')
								}, 150)								
							}
						return false
					}
					
				// elements to stay
					// const base_models = ['section','tool','area']
					const base_models = ['menu']
					// const elements_to_stay 	= self.elements.filter(item => item.model!==page_element.model)
					const elements_to_stay 	= self.context.filter( item => base_models.includes(item.model))

					// add current source from options
						elements_to_stay.push(new_rqo.source)
						self.context = elements_to_stay

				// instances. Set property 'destroyable' as false for own instances to prevent remove. Refresh page					
					// const instances_to_destroy = self.ar_instances.filter(item => item.model!==page_element.model)
					const instances_to_stay = self.ar_instances.filter(item => base_models.includes(item.model))
					for (let i = instances_to_stay.length - 1; i >= 0; i--) {
						instances_to_stay[i].destroyable = false
					}

					const refresh_result = await self.refresh()
						console.log("self:",self);
					// loading
						if (node) {
							node.classList.remove('loading')
						}

				// url history track
					if(refresh_result===true && new_rqo.event_in_history!==true)  {

						// options_url : clone options and remove optional 'event_in_history' property
						const options_url 	= Object.assign({}, new_rqo);
						delete options_url.event_in_history

						// const var_uri	= Object.entries(options_url).map(([key, val]) => `${key}=${val}`).join('&');
						const new_instance 	= self.ar_instances.find(item => item.model === new_rqo.source.model && item.tipo === new_rqo.source.tipo && item.mode === new_rqo.source.mode)

						const var_uri		= 'id=' + new_instance.id
						const uri_options	= new_rqo
						const state			= {rqo : new_rqo}
						const title			= ''
						const url			= "?"+var_uri //window.location.href

						history.pushState(state, title, url)
					}

				return true
			};//end user_action


	// window onpopstate
		window.onpopstate = function(event) {
			if (event.state) {
				const new_rqo = event.state.rqo
				new_rqo.event_in_history = true
				event_manager.publish('user_action', new_rqo)
			}
		}


	// observe tool calls
		self.events_tokens.push(
			// load_tool from tool_common/js/tool_common.js
			// event_manager.subscribe('load_tool', load_tool)
			event_manager.subscribe('load_tool', function(e) {
				load_tool(e)
			})
		)


	// beforeunload (event)
		// window.addEventListener("beforeunload", function (event) {
		// 	event.preventDefault();

		// 	document.activeElement.blur()

		// 	const confirmationMessage = "Leaving tool transcription page.. ";
		// 	event.returnValue  	= confirmationMessage;	// Gecko, Trident, Chrome 34+
		// 	// return confirmationMessage;				// Gecko, WebKit, Chrome <34

		// 	return null
		// }, false)//end beforeunload


	// window messages
		// window.addEventListener("message", receiveMessage, false);
		// function receiveMessage(event) {
		// 	console.log("message event:",event);
		// 	alert("Mensaje recibido !");
		// }


	// status update
		self.status = 'initiated'
	

 	return true
};//end init



/**
* BUILD
*/
page.prototype.build = async function() {
	
	const self = this

	// instances (like section). Instances are returned init and builded
		await self.get_ar_instances()

	// status update
		self.status = 'builded'

 	return true
};//end build



/**
* GET_AR_INSTANCES
*/
page.prototype.get_ar_instances = async function(){

	const self = this

	// instances
		const ar_promises = []

		const context_length = self.context.length
		for (let i = 0; i < context_length; i++) {

			const current_ddo = self.context[i]
				console.log("current_ddo:",current_ddo); 
			ar_promises.push( new Promise(function(resolve){
			
				instantiate_page_element(self, current_ddo)
				.then(function(current_instance){
					// build (load data)
					const autoload = current_instance.status==="initiated" // avoid reload menu data
					current_instance.build(autoload)
					.then(function(response){
						resolve(current_instance)
					})
				})
			}))
		};//end for (let i = 0; i < elements_length; i++)

	// set on finish
		await Promise.all(ar_promises).then((ar_instances) => {
			self.ar_instances = ar_instances
		});

	return self.ar_instances
};//end get_ar_instances



/**
* INSTANTIATE_PAGE_ELEMENT
* @return promise current_instance_promise
*/
const instantiate_page_element = function(self, ddo) {

	const tipo			= ddo.tipo
	const section_tipo	= ddo.section_tipo || tipo
	const model			= ddo.model
	const section_id	= ddo.section_id || null
	const mode			= ddo.mode
	const lang			= ddo.lang
	const context		= ddo

	
	// instance options
		const instance_options = {
			model			: model,
			tipo			: tipo,
			section_tipo	: section_tipo,
			section_id		: section_id ,
			mode			: mode,
			lang			: lang,
			context			: context
		}

		// id_variant . Propagate a custom instance id to children
			if (self.id_variant) {
				instance_options.id_variant = self.id_variant
			}

	// page_element instance (load file)
		const instance_promise = get_instance(instance_options)


	return instance_promise
};//end instantiate_page_element



/**
* USER_ACTION
*/
	// const user_navigation = async function(self, options) {

	// 	const current_data_manager = new data_manager()
	// 	const api_response = await current_data_manager.request({
	// 		body : {
	// 			action 		: 'get_element',
	// 			options 	: options
	// 		}
	// 	})

	// 	// elements to stay
	// 		const api_element 		= api_response.result
	// 		const elements_to_stay 	= self.elements.filter(item => item.model!==api_element.model)
	// 			  elements_to_stay.push(api_element)
	// 		self.elements = elements_to_stay

	// 	// instances. remove all other instances but current an refresh page
	// 		const instances_to_destroy = self.ar_instances.filter(item => item.model!==api_element.model)
	// 		for (let i = instances_to_destroy.length - 1; i >= 0; i--) {
	// 			instances_to_destroy[i].destroyable = false
	// 		}
	// 		self.refresh()

	// 	// url history track
	// 		if(options.event_in_history===true) return;

	// 		const var_uri = Object.entries(options).map(([key, val]) => `${key}=${val}`).join('&');

	// 		const uri_options	= JSON.parse(JSON.stringify(options))
	// 		const state 		= {options : uri_options}
	// 		const title 		= ''
	// 		const url 			= "?"+var_uri //window.location.href

	// 		history.pushState(state, title, url)

	// 	return true
	// };//end user_navigation


