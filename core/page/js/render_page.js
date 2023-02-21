/*global get_label, page_globals, SHOW_DEBUG, DEDALO_ROOT_WEB, DEDALO_CORE_URL */
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../common/js/ui.js'
	import {event_manager} from '../../common/js/event_manager.js'
	import {instantiate_page_element} from './page.js'
	// import {clone} from '../../common/js/utils/index.js'



/**
* RENDER_PAGE
* Manages the component's logic and appearance in client side
*/
export const render_page = function() {

	return true
}//end render_page



/**
* EDIT
* Render node for use in section
* @return DOM node
*/
render_page.prototype.edit = async function(options) {

	const self = this

	// options
		const render_level = options.render_level || 'full'

	// content data
		const content_data = await get_content_data(self) // result is a promise
		if (render_level==='content') {
			return content_data
		}

	// wrapper
		const wrapper = document.createElement('div')
		wrapper.classList.add('wrapper', self.type)
		wrapper.appendChild(content_data)
		// set pointers
		wrapper.content_data = content_data

	// events
		// page click
			// wrapper.addEventListener('click', fn_deactivate_components)
			window.addEventListener('click', fn_deactivate_components)
			function fn_deactivate_components() {
				if (page_globals.component_active) {
					ui.component.deactivate(page_globals.component_active)
				}
			}


 	return wrapper
}//end render_page.prototype.edit



/**
* GET_CONTENT_DATA
* @return DOM node content_data
*/
const get_content_data = async function(self) {

	// const fragment = new DocumentFragment()

	// content_data
		const content_data = document.createElement('div')
			  content_data.classList.add('content_data', self.type)

	// check page context is valid
		if (!self.context) {
			const response_error = render_page.render_server_response_error('Invalid context', false)
			return response_error
		}

	// add all instance rendered nodes
		// const ar_instances_length = self.ar_instances.length;

		// sequential mode
			// for (let i = 0; i < ar_instances_length; i++) {

			// 	const current_instance = self.ar_instances[i]

			// 	// exclude menu already added
			// 	if(current_instance.model==='menu') continue;

			// 	const child_item = current_instance.render({
			// 		render_level : 'full'
			// 	})

			// 	content_data.appendChild(await child_item)
			// }

		// parallel mode
			// const ar_promises = []
			// for (let i = 0; i < ar_instances_length; i++) {

			// 	const current_instance = self.ar_instances[i]

			// 	// exclude menu already added to wrapper
			// 	if(current_instance.model==='menu') continue;

			// 	const render_promise = current_instance.render()
			// 	ar_promises.push(render_promise)
			// }
			// await Promise.all(ar_promises).then(function(child_items) {
			//   for (let i = 0; i < child_items.length; i++) {
			//   	content_data.appendChild(child_items[i])
			//   }
			// });

		// async mode
			const context_length = self.context.length
			for (let i = 0; i < context_length; i++) {

				const current_context = self.context[i]

				// menu case. Prevent to render again on refresh page
					// const non_destroyable_instance = self.ar_instances.find(el => el.model===current_context.model)
					const non_destroyable_instance = self.ar_instances.find(el => el.model===current_context.model && el.destroyable===false)
					if (non_destroyable_instance) {
						content_data.appendChild(non_destroyable_instance.node)
						continue;
					}

				// load_item_with_spinner
					ui.load_item_with_spinner({
						container			: content_data,
						preserve_content	: true,
						label				: current_context.label || current_context.model,
						callback			: async () => {
							// instance
							const current_instance = await instantiate_page_element(
								self, // object page instance
								current_context // object is used as source
							)

							// set caller
							// current_instance.caller = self

							self.ar_instances.push(current_instance)

							// build (load data)
							const autoload = true // Note that it's necessary to load data here (in addition to context)
							await current_instance.build(autoload)
							return current_instance.render()
						}
					})
				/*
				// container placeholder until page element is built and rendered
					const label = current_context.label || current_context.model
					const container_placeholder = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'container container_placeholder ' + current_context.model,
						inner_html		: 'Loading '+ label +' ['+ current_context.tipo+']',
						parent			: content_data
					})
					// spinner
					ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'spinner',
						parent			: container_placeholder
					})

				// instance
					const current_instance = await instantiate_page_element(
						self, // object page instance
						current_context // object is used as source
					)

					self.ar_instances.push(current_instance)

					// build (load data)
					const autoload = true // Note that it's necessary to load data here (in addition to context)
					current_instance.build(autoload)
					.then(function(){
						// render instance
						current_instance.render()
						.then(function(node){
							if (node) {
								container_placeholder.replaceWith(node);
							}else{
								console.log('Error. page element render fails. Element:', current_instance);
							}
						})
					})
					*/
			}//end for (let i = 0; i < elements_length; i++)

	// event page rendered (used by menu..)
		event_manager.publish('render_page', self)

	// // content_data
	// 	const content_data = document.createElement("div")
	// 		  content_data.classList.add("content_data", self.type)
	// 	content_data.appendChild(fragment)


	return content_data
}//end get_content_data



/**
* RENDER_MENU
* @return DOM node render_menu
*/
	// const render_menu = async function(self) {

	// 	const menu_instance = self.ar_instances.find( instance => instance.model==='menu' )
	// 	if(menu_instance){

	// 		const menu_item = menu_instance.render({
	// 			render_level : 'full'
	// 		})
	// 		return menu_item
	// 	}

	// 	return null
	// }//end render_menu



/**
* RENDER_SERVER_RESPONSE_ERROR
* Render generic page error (Raspa background)
* @param string msg
* @return DOM node wrapper|error_container
*/
render_page.render_server_response_error = function(msg, add_wrapper=true) {

	// wrapper
		const wrapper = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'wrapper page'
		})

	// error_container
		const error_container = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'page_error_container',
			parent			: wrapper
		})

	// icon_dedalo
		ui.create_dom_element({
			element_type	: 'img',
			class_name		: 'icon_dedalo',
			src				: DEDALO_CORE_URL + '/themes/default/dedalo_logo.svg',
			parent			: error_container
		})

	// server_response_error h1
		ui.create_dom_element({
			element_type	: 'h1',
			class_name		: 'server_response_error',
			inner_html		: 'Server response error: <br>' + msg,
			parent			: error_container
		})

	// more_info
		ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'more_info',
			inner_html		: 'Received data format is not as expected. See your server log for details',
			parent			: error_container
		})

	// add_wrapper false  case
		if (add_wrapper===false) {
			return error_container
		}


	return wrapper
}//end render_server_response_error
