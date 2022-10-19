/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {when_in_viewport} from '../../common/js/events.js'
	// import {data_manager} from '../../common/js/data_manager.js'
	import {render_tree_data} from '../../common/js/common.js'
	import {ui} from '../../common/js/ui.js'



/**
* RENDER_AREA_DEVELOPMENT
* Manages the area appearance in client side
*/
export const render_area_development = function() {

	return true
}//end render_area_development



/**
* EDIT
* Render node for use in edit
* @param object options
* @return DOM node wrapper
*/
render_area_development.prototype.edit = async function(options) {

	const self = this

	const render_level = options.render_level || 'full'

	// content_data
		const content_data = get_content_data(self)
		if (render_level==='content') {
			return content_data
		}

	// buttons
		//const current_buttons = await buttons(self);

	// wrapper. ui build_edit returns component wrapper
		const wrapper =	ui.area.build_wrapper_edit(self, {
			content_data : content_data,
			// buttons 	 : current_buttons
		})
		// set pointers
		wrapper.content_data = content_data


	return wrapper
}//end edit



/**
* LIST
* Alias of edit
* @return DOM node
*/
render_area_development.prototype.list = async function(options) {

	return this.edit(options)
}//end list



/**
* CONTENT_DATA
* @return DOM node content_data
*/
const get_content_data = function(self) {

	const fragment = new DocumentFragment()

	// widgets
		const widgets_lenght = self.widgets.length
		for (let i = 0; i < widgets_lenght; i++) {

			const widget = self.widgets[i]

			const widget_dom = build_widget(widget, self);
			fragment.appendChild(widget_dom)

			// load external
				/*
				const load_promises = []
				if(widget.load_style) {

					for (let i = 0; i < widget.load_style.length; i++) {
						const src = widget.load_style[i]
						load_promises.push( common.prototype.load_style(src) )
					}
				}
				if(widget.load_script) {

					for (let i = 0; i < widget.load_script.length; i++) {
						const src = widget.load_script[i]
						load_promises.push( common.prototype.load_script(src) )
					}
				}
				*/
		}

	// content_data
		const content_data = document.createElement('div')
			  content_data.classList.add('content_data', self.type)
			  content_data.appendChild(fragment)


	return content_data
}//end content_data



/**
* BUILD_WIDGET
*
* @param object item
* @param object self
* 	Instance of current area
*/
const build_widget = (item, self) => {

	// container
		const container = ui.create_dom_element({
			id				: item.id,
			element_type	: 'div',
			dataset			: {},
			class_name		: 'widget_container ' + (item.class || '')
		})

	// label
		const label = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'widget_label icon_arrow',
			parent			: container,
			inner_html		: item.label || ''
		})

	// body
		const body = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'widget_body hide',
			parent			: container
		})

	// collapse_toggle_track
		when_in_viewport(label, ()=>{
			ui.collapse_toggle_track({
				header				: label,
				content_data		: body,
				collapsed_id		: 'collapsed_' + item.id,
				collapse_callback	: collapse,
				expose_callback		: expose
			})
		})
		function collapse() {
			label.classList.remove('up')
		}
		function expose() {
			label.classList.add('up')
		}


		// item info
			if (item.info) {
				const widget_info = ui.create_dom_element({
					element_type	: 'div',
					class_name		: 'link',
					inner_html		: item.info || '',
					parent			: body
				})

				// action
					widget_info.addEventListener('mouseup',  async function(e){
						e.stopPropagation()

						// confirm optional
							if (item.confirm && !confirm(item.confirm)) {
								return false
							}

						widget_info.classList.add('lock')

						// spinner
							const spinner = ui.create_dom_element({
								element_type	: 'div',
								class_name		: 'spinner'
							})
							body_response.prepend(spinner)

						// data_manager
							// const api_response = await data_manager.request({
							// 	body : {
							// 		dd_api	: item.trigger.dd_api,
							// 		action	: item.trigger.action,
							// 		options	: item.trigger.options
							// 	}
							// })
							// print_response(body_response, api_response)
							// widget_info.classList.remove("lock")
							// spinner.remove()

						// delegates get_children task to worker. When finish, create global radio for current area
							const current_worker = new Worker('../area_development/js/worker_area_development.js', {
								type : 'module'
							});
							current_worker.postMessage({
								url		: DEDALO_CORE_URL + '/api/v1/json/',
								dd_api	: item.trigger.dd_api,
								action	: item.trigger.action,
								options	: item.trigger.options
							});
							current_worker.onmessage = function(e) {

								const api_response = e.data.api_response

								print_response(body_response, api_response)
								widget_info.classList.remove("lock")
								spinner.remove()

								current_worker.terminate()
							}
					})
			}//end if (item.info) {

		// body info
			const body_info = ui.create_dom_element({
				element_type	: 'div',
				class_name		: "body_info",
				parent			: body,
				inner_html		: item.body || ''
			})

		// script (javascript code)
			// if (item.script) {
			// 	const script = ui.create_dom_element({
			// 		element_type	: 'script',
			// 		parent			: body,
			// 		inner_html		: item.script
			// 	})
			// }

		// body response
			const body_response = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'body_response',
				parent			: body
			})

	// run widget scripts
		if(item.run) {
			for (let i = 0; i < item.run.length; i++) {

				const func			= item.run[i].fn
				const func_options	= item.run[i].options

				// promise
				self[func].apply(self, [{
					...item,
					...func_options,
					body_info		: body_info,
					body_response	: body_response,
					print_response	: print_response
				}])
			}
		}


	return container
}//end build_widget



/**
* PRINT_RESPONSE
* Render API response result message and result
* Note that api_response is returned by the delegated worker
* @param DOM node container
* @param object api_response
* @return DON node container
*/
const print_response = (container, api_response) => {

	// clean container
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}

	// button_eraser
		const button_eraser = ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'button reset eraser',
			parent			: container
		})
		button_eraser.addEventListener('mouseup', function(e){
			e.stopPropagation();

			while (container.firstChild) {
				container.removeChild(container.firstChild);
			}
		})

	// msg
		const api_msg = api_response && api_response.msg
			? Array.isArray(api_response.msg)
				? api_response.msg.join('<br>')
				: api_response.msg.replace(/\\n/g, '<br>') // api_response.msg.replace(/\\n/g, '<br>')
			: 'Unknown API response error'
		ui.create_dom_element({
			element_type	: 'div',
			class_name		: '',
			parent			: container,
			inner_html		: api_msg
		})

	// JSON response result
		const result = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'pre',
			parent			: container
		})
		render_tree_data(api_response, result)


	return container
}//end print_response



/**
* BUTTONS
* @return DOM node buttons
*/
	// const buttons = async function(self) {

	// 	const buttons = []

	// 	return buttons
	// }//end buttons



/**
* BUILD_FORM
* Render a form for given widget_object
* @param object widget_object
* @return DOM node form_container
*/
export const build_form = function(widget_object) {

	const self = this

	// widget_object
		// const trigger		= widget_object.trigger
		const body_info			= widget_object.body_info
		const body_response		= widget_object.body_response
		const print_response	= widget_object.print_response
		const confirm_text		= widget_object.confirm_text
		const inputs			= widget_object.inputs || []

	// create the form
		const form_container = ui.create_dom_element({
			element_type	: 'form',
			class_name		: 'form_container',
			parent			: body_info
		})

		form_container.addEventListener('submit', async function(e){
			e.preventDefault()

			if ( confirm( (confirm_text || get_label.sure || 'Sure?') ) ) {

				// check mandatory values
					for (let i = 0; i < input_nodes.length; i++) {
						if(input_nodes[i].classList.contains('mandatory') && input_nodes[i].value.length<1) {
							input_nodes[i].focus()
							input_nodes[i].classList.add('empty')
							return
						}
					}

				// submit data
					form_container.classList.add('lock')

					// spinner
					const spinner = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'spinner'
					})
					body_response.prepend(spinner)

					// collect values from inputs
					const values = input_nodes.map((el)=>{
						return {
							name	: el.name,
							value	: el.value
						}
					})

					const options = (widget_object.trigger.options)
						? Object.assign(widget_object.trigger.options, values)
						: values

					// data_manager
						// const api_response = await data_manager.request({
						// 	body : {
						// 		dd_api	: widget_object.trigger.dd_api,
						// 		action	: widget_object.trigger.action,
						// 		options	: options
						// 	}
						// })
						// print_response(body_response, api_response)
						// form_container.classList.remove("lock")
						// spinner.remove()

					// delegates get_children task to worker. When finish, create global radio for current area
						const current_worker = new Worker('../area_development/js/worker_area_development.js', {
							type : 'module'
						});
						current_worker.postMessage({
							url		: DEDALO_CORE_URL + '/api/v1/json/',
							dd_api	: widget_object.trigger.dd_api,
							action	: widget_object.trigger.action,
							options	: options
						});
						current_worker.onmessage = function(e) {
							const api_response = e.data.api_response

							print_response(body_response, api_response)

							form_container.classList.remove('lock')
							spinner.remove()

							current_worker.terminate()
						}
			}
		})

	// form inputs
		const input_nodes = []
		for (let i = 0; i < inputs.length; i++) {

			const input = inputs[i]

			const class_name = input.mandatory
				? 'mandatory'
				: ''

			const input_node = ui.create_dom_element({
				element_type	: 'input',
				type			: input.type,
				name			: input.name,
				placeholder		: input.label,
				class_name		: class_name,
				parent			: form_container
			})
			if (input.value) {
				input_node.value = input.value
			}
			input_node.addEventListener('keyup', function(){
				if (this.value.length>0) {
					this.classList.remove('empty')
				}
			})

			input_nodes.push(input_node)
		}

	// button submit
		const button_submit = ui.create_dom_element({
			element_type	: 'button',
			class_name		: 'light',
			text_content	: 'OK',
			parent			: form_container
		})
		button_submit.addEventListener('click', function(){

			// if (confirm( (get_label["seguro"] || "Sure?") )) {

			// 	for (let i = 0; i < input_nodes.length; i++) {
			// 		if(input_nodes[i].classList.contains("mandatory") && input_nodes[i].value.length<1) {
			// 			input_nodes[i].focus()
			// 			input_nodes[i].classList.add("empty")
			// 			return
			// 		}
			// 	}
			// }
		})


	return form_container
}//end build_form
