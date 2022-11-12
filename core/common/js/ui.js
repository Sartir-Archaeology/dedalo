/*global get_label, page_globals, SHOW_DEBUG, Promise, DEDALO_CORE_URL, DEDALO_ROOT_WEB */
/*eslint no-undef: "error"*/



// imports
	import {strip_tags, find_up_node} from '../../common/js/utils/index.js'
	import {event_manager} from '../../common/js/event_manager.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {open_tool} from '../../../tools/tool_common/js/tool_common.js'
	import {set_element_css} from '../../page/js/css.js'
	// import {get_instance, delete_instance} from '../../common/js/instances.js'
	// import {set_before_unload} from '../../common/js/events.js'
	import '../../common/js/dd-modal.js'



/**
* UI
*/
export const ui = {



	/**
	* SHOW_MESSAGE
	* @param DOM node wrapper
	*	component wrapper where message is placed
	* @param string message
	*	Text message to show inside message container
	* @param string msg_type = 'error'
	* @param string message_node = 'component_message'
	* @param bool clean = false
	*
	* @return DOM node message_wrap
	*/
	message_timeout : null,
	show_message : (wrapper, message, msg_type='error', message_node='component_message', clean=false) => {

		// message_wrap. always check if already exists, else, create a new one and recycle it
			const message_wrap = wrapper.querySelector('.'+message_node) || (()=>{

				const new_message_wrap = ui.create_dom_element({
					element_type	: 'div',
					class_name		: message_node, // + msg_type,
					parent			: wrapper
				})

				const close_button = ui.create_dom_element({
					element_type	: 'div',
					class_name		: 'close',
					text_content	: ' x ',
					parent			: new_message_wrap
				})
				close_button.addEventListener('click', (e) => {
					e.stopPropagation()
					message_wrap.remove()
				})

				return new_message_wrap
			})()

		// set style
			message_wrap.classList.remove('error','warning','ok')
			message_wrap.classList.add(msg_type)

		// clean messages
			if (clean===true) {
				// clean
				const items = message_wrap.querySelectorAll('.text')
				for (let i = items.length - 1; i >= 0; i--) {
					items[i].remove()
				}
			}

		// add message text
			ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'text',
				text_content	: message,
				parent			: message_wrap
			})

		// adjust height
			const computed_styles = getComputedStyle(message_wrap.parentNode);
			if (computed_styles.position!=='fixed') {
				message_wrap.style.top = '-' + message_wrap.offsetHeight + 'px'
			}

		// close button move to bottom when height is too much
			if (message_wrap.offsetHeight>120) {
				const close_button			= message_wrap.querySelector('.close')
				close_button.style.top		= 'unset';
				close_button.style.bottom	= '0px';
			}

		// remove message after time
			clearTimeout(ui.message_timeout);
			if (msg_type==='ok') {
				ui.message_timeout = setTimeout(()=>{
					message_wrap.remove()
				}, 10000)
			}


		return message_wrap
	},//end show_message



	component : {



		/**
		* BUILD_WRAPPER_EDIT
		* Component wrapper unified builder
		* @param object instance (self component instance)
		* @param object items = {}
		* 	Specific objects to place into the wrapper, like 'label', 'top', buttons, filter, paginator, content_data)
		* @return DOM node wrapper
		*/
		build_wrapper_edit : (instance, items={}) => {

			// short vars
				const model			= instance.model 	// like component_input-text
				const type			= instance.type 	// like 'component'
				const tipo			= instance.tipo 	// like 'rsc26'
				const section_tipo	= instance.section_tipo 	// like 'rsc26'
				const mode			= instance.mode 	// like 'edit'
				const view			= instance.view || instance.context.view || null
				const label			= instance.label // instance.context.label
				const element_css	= instance.context.css || {}

			// fragment
				const fragment = new DocumentFragment()

			// wrapper
				const wrapper = document.createElement('div')
				// css
					const wrapper_structure_css = typeof element_css.wrapper!=='undefined' ? element_css.wrapper : []
					const ar_css = [
						'wrapper_' + type,
						model,
						tipo,
						section_tipo +'_'+ tipo,
						mode,
						...wrapper_structure_css
					]
					if (view) {ar_css.push('view_'+view)}
					if (mode==='search') ar_css.push('tooltip_toggle')
					if (mode==='tm') ar_css.push('edit')
					wrapper.classList.add(...ar_css)

				// legacy CSS
					if (model!=='component_filter') {
						// const legacy_selector = '.wrap_component'
						// if (element_css[legacy_selector]) {
						// 	// mixin
						// 		if (element_css[legacy_selector].mixin){
						// 			// width from mixin
						// 			const found = element_css[legacy_selector].mixin.find(el=> el.substring(0,7)==='.width_') // like .width_33
						// 			if (found) { //  && found!=='.width_50'
						// 				// wrapper.style['flex-basis'] = found.substring(7) + '%'
						// 				// wrapper.style['--width'] = found.substring(7) + '%'
						// 				wrapper.style.setProperty('--component_width', found.substring(7) + '%');
						// 			}
						// 		}
						// 	// style
						// 		if (element_css[legacy_selector].style) {
						// 			// width from style
						// 			if (element_css[legacy_selector].style.width) {
						// 				// wrapper.style['flex-basis'] = element_css[legacy_selector].style.width;
						// 				// wrapper.style['--width'] = element_css[legacy_selector].style.width
						// 				wrapper.style.setProperty('--component_width', element_css[legacy_selector].style.width);
						// 			}
						// 			// display none from style
						// 			if (element_css[legacy_selector].style.display && element_css[legacy_selector].style.display==='none') {
						// 				wrapper.classList.add('display_none')
						// 			}
						// 		}
						// }
						// const legacy_selector_content_data = '.content_data'
						// if (element_css[legacy_selector_content_data] && items.content_data) {
						// 	// style
						// 		if (element_css[legacy_selector_content_data].style) {
						// 			// height from style
						// 			if (element_css[legacy_selector_content_data].style.height) {
						// 				items.content_data.style.setProperty('height', element_css[legacy_selector_content_data].style.height);
						// 			}
						// 		}
						// }
						if (instance.context.css) {
							const selector = `${section_tipo}_${tipo}.${tipo}.edit`
							set_element_css(selector, element_css)
						}
					}//end if (model!=='component_filter')

				// read only. Disable events on permissions <2
					if (instance.permissions<2) {
						wrapper.classList.add('disabled_component')
					}

				// event click . Activate component on event
					wrapper.addEventListener('click', (e) => {
						e.stopPropagation()
						ui.component.activate(instance)
					})

			// label. If node label received, it is placed at first. Else a new one will be built from scratch (default)
				if (label===null || items.label===null) {
					// no label add
				}else if(items.label) {
					// add custom label
					wrapper.appendChild(items.label)
					// set pointer
					wrapper.label = items.label
				}else{
					// default
					const component_label = ui.create_dom_element({
						element_type	: 'div',
						inner_html		: label
					})
					wrapper.appendChild(component_label)
					// set pointer
					wrapper.label = component_label
					// css
					const label_structure_css = typeof element_css.label!=='undefined' ? element_css.label : []
					const ar_css = ['label', ...label_structure_css]
					component_label.classList.add(...ar_css)
				}

			// top
				if (items.top) {
					wrapper.appendChild(items.top)
				}

			// buttons
				if (items.buttons && instance.permissions>1) {
					wrapper.appendChild(items.buttons)
				}

			// filter
				if (instance.filter) {
					const filter = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'filter',
						parent			: fragment
					})
					instance.filter.build().then(function(){
						instance.filter.render().then(filter_wrapper =>{
							filter.appendChild(filter_wrapper)
						})
					})
				}

			// paginator
				if (instance.paginator) {
					const paginator = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'paginator_container',
						parent			: wrapper
					})
					instance.paginator.render().then(paginator_wrapper => {
						paginator.appendChild(paginator_wrapper)
					})
				}

			// list_body
				if (items.list_body) {
					wrapper.appendChild(items.list_body)
				}

			// content_data
				if (items.content_data) {
					// const content_data = items.content_data
					// // css
					// 	const content_data_structure_css = typeof element_css.content_data!=='undefined' ? element_css.content_data : []
					// 	const ar_css = ['content_data', type, ...content_data_structure_css]
					// 	content_data.classList.add(...ar_css)
					wrapper.appendChild(items.content_data)
				}

			// tooltip
				// if (mode==='search' && instance.context.search_options_title) {
				// 	//fragment.classList.add('tooltip_toggle')
				// 	const tooltip = ui.create_dom_element({
				// 		element_type	: 'div',
				// 		class_name		: 'tooltip hidden_tooltip',
				// 		inner_html		: instance.context.search_options_title || '',
				// 		parent			: fragment
				// 	})
				// }

			// debug
				if(SHOW_DEBUG===true) {
					wrapper.addEventListener('click', function(e){
						if (e.altKey) {
							e.stopPropagation()
							e.preventDefault()
							// common.render_tree_data(instance, document.getElementById('debug'))
							console.log('/// selected instance:', instance);
						}
					})

					// test css
						// const my_css = {
						//    '.cssinjs-btn': {
						//       "color": "white",
						//       "background": "black"
						//     }
						// }
						// const toCssString = css => {
						//   let result = ''
						//   for (const selector in css) {
						//     result += selector + ' {' // .cssinjs-btn {
						//     for (const property in css[selector]) {
						//       // color: white;
						//       result += property + ': ' + css[selector][property] + ';'
						//     }
						//     result += '}'
						//   }
						//   return result
						// }
						// // Render styles.
						// let style = document.querySelector("#el_id_del_style")
						// if (!style) {
						// 	style = document.createElement('style')
						// 	style.id = 'el_id_del_style'
						// 	document.head.appendChild(style)
						// }
						// style.textContent += toCssString(my_css) + '\n'
				}


			return wrapper
		},//end build_wrapper_edit



		/**
		* BUILD_CONTENT_DATA
		* @param object instance
		* @param object options = {}
		* @return DOM node content_data
		*/
		build_content_data : (instance, options={}) => {

			// options
				const type			= instance.type
				const component_css	= instance.context.css || {}

			// div container
				const content_data = document.createElement('div')

			// css
				const content_data_structure_css = typeof component_css.content_data!=='undefined' ? component_css.content_data : []
				const ar_css = [
					'content_data',
					type,
					...content_data_structure_css
				]
				content_data.classList.add(...ar_css)

			return content_data
		},//end build_content_data



		/**
		* build_button_exit_edit
		* @param object options = {}
		* @return DOM node content_data
		*/
		build_button_exit_edit : (instance, options={}) => {

			const autoload		= options.autoload || true
			const target_mode	= options.target_mode || 'list'

			const button_close_node = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'button close button_exit_edit show_on_active'
			})
			button_close_node.addEventListener('click', async function(e){
				e.stopPropagation()

				await ui.component.deactivate(instance)

				const change_mode = instance.context.properties.with_value
				&& instance.context.properties.with_value.mode !== instance.mode
					? instance.context.properties.with_value.mode
					: instance.context.properties.mode

				const change_view = instance.context.properties.with_value
					&& instance.context.properties.with_value.view !== instance.context.view
						? instance.context.properties.with_value.view
						: instance.context.properties.view

				console.log('change_mode:', change_mode);
				console.log('change_view:', change_view);
				// change mode destroy current instance and render a fresh full element node in the new mode
				instance.change_mode({
					mode		: target_mode,
					autoload	: autoload
				})
			})

			return button_close_node;
		},// end build_button_exit_edit



		/**
		* BUILD_BUTTONS_CONTAINER
		* @param object instance
		* @return DOM node buttons_container
		*/
		build_buttons_container : (instance) => {

			const buttons_container = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'buttons_container'
			})

			return buttons_container
		},//end build_buttons_container



		/**
		* BUILD_WRAPPER_LIST
		* Render a unified version of component wrapper in list mode
		* @param object instance
		* @param object options = {}
		* @return DOM node wrapper
		*/
		build_wrapper_list : (instance, options={}) => {

			// options
				const value_string	= options.value_string

			// short vars
				const model			= instance.model 		// like component_input-text
				const type			= instance.type 		// like 'component'
				const tipo			= instance.tipo 		// like 'rsc26'
				const section_tipo	= instance.section_tipo // like 'oh1'
				const view			= instance.view || instance.context.view || null

			// wrapper
				const wrapper = document.createElement('div')
				// css
					const ar_css = [
						'wrapper_' + type,
						model,
						tipo,
						section_tipo +'_'+ tipo,
						'list'
					]
					if (view) {ar_css.push('view_'+view)}
					wrapper.classList.add(...ar_css)

			// value_string. span value. Add span if value_string is received
				if (value_string) {
					ui.create_dom_element({
						element_type	: 'span',
						inner_html		: value_string,
						parent			: wrapper
					})
				}

			// event dblclick change component mode
				// if(edit_in_list) {
				// 	wrapper.addEventListener('click', function(e){
				// 		// check level
				// 			const is_first_level = function(wrapper){
				// 				const parent_list_body = wrapper.parentNode.parentNode.parentNode.parentNode
				// 				return (parent_list_body && parent_list_body.classList.contains('list_body'))
				// 			}
				// 			if (!is_first_level(wrapper)) {
				// 				// ignore click and continue bubble event
				// 				return
				// 			}
				// 		e.stopPropagation()
				// 		// change mode (from 'list' to 'edit_in_list')
				// 			if (instance.change_mode) {
				// 				instance.change_mode('edit_in_list', autoload)
				// 			}else{
				// 				console.warn('WARNING: change_mode method its not available for instance: ', instance)
				// 			}
				// 	})
				// }

			// debug
				if(SHOW_DEBUG===true) {
					wrapper.addEventListener('click', function(e){
						if (e.altKey) {
							e.stopPropagation()
							e.preventDefault()
							// common.render_tree_data(instance, document.getElementById('debug'))
							console.log('/// selected instance:', instance);
						}
					})
					wrapper.classList.add('_'+instance.id)
				}


			return wrapper
		},//end build_wrapper_list



		/**
		* BUILD_WRAPPER_MINI
		* @param object instance
		* @param object options = {}
		* @return DOM node wrapper
		*/
		build_wrapper_mini : (instance, options={}) => {

			// options
				const value_string = options.value_string

			// wrapper
				const wrapper = document.createElement('span')
				// css
					const ar_css = [
						instance.model + '_mini' // add suffix '_mini'
					]
					wrapper.classList.add(...ar_css)

			// value_string
				if (value_string) {
					wrapper.insertAdjacentHTML('afterbegin', value_string)
				}

			return wrapper
		},//end build_wrapper_mini



		/**
		* BUILD_WRAPPER_search
		* Component wrapper unified builder
		* @param object instance (self component instance)
		* @param object items
		* 	Specific objects to place into the wrapper, like 'label', 'top', buttons, filter, paginator, content_data)
		* @return DOM node wrapper
		*/
		build_wrapper_search : (instance, items={}) => {

			// short vars
				const id			= instance.id || 'id is not set'
				const model			= instance.model 	// like component_input-text
				const type			= instance.type 	// like 'component'
				const tipo			= instance.tipo 	// like 'rsc26'
				const mode			= instance.mode 	// like 'edit'
				const view			= instance.view || null
				const label			= instance.label // instance.context.label
				const element_css	= instance.context.css || {}

			const fragment = new DocumentFragment()

			// label. If node label received, it is placed at first. Else a new one will be built from scratch (default)
				if (label===null || items.label===null) {
					// no label add
				}else if(items.label) {
					// add custom label
					fragment.appendChild(items.label)
				}else{
					// default
					const component_label = ui.create_dom_element({
						element_type	: 'div',
						//class_name	: 'label'  + tipo + (label_structure_css ? ' ' + label_structure_css : ''),
						inner_html		: label + ' [' + instance.lang.substring(3) + ']' + ' ' + tipo + ' ' + (model.substring(10)) + ' [' + instance.permissions + ']'
					})
					fragment.appendChild(component_label)
					// css
						const label_structure_css = typeof element_css.label!=="undefined" ? element_css.label : []
						const ar_css = ['label', ...label_structure_css]
						component_label.classList.add(...ar_css)
				}

			// top
				// if (items.top) {
				// 	fragment.appendChild(items.top)
				// }

			// buttons
				// if (items.buttons) {
				// 	fragment.appendChild(items.buttons)
				// }

			// filter
				// if (instance.filter) {
				// 	const filter = ui.create_dom_element({
				// 		element_type	: 'div',
				// 		class_name		: 'filter',
				// 		parent			: fragment
				// 	})
				// 	instance.filter.render().then(filter_wrapper =>{
				// 		filter.appendChild(filter_wrapper)
				// 	})
				// }

			// paginator
				// if (instance.paginator) {
				// 	const paginator = ui.create_dom_element({
				// 		element_type	: 'div',
				// 		class_name		: 'paginator',
				// 		parent			: fragment
				// 	})
				// 	instance.paginator.render().then(paginator_wrapper =>{
				// 		paginator.appendChild(paginator_wrapper)
				// 	})
				// }

			// content_data
				if (items.content_data) {
					fragment.appendChild(items.content_data)
				}

			// tooltip
				if (instance.context.search_options_title) {
					//fragment.classList.add("tooltip_toggle")
					const tooltip = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'tooltip hidden_tooltip',
						inner_html		: instance.context.search_options_title || '',
						parent			: items.content_data // fragment
					})
				}

			// wrapper
				const wrapper = document.createElement('div')
					  wrapper.id = id
				// css
					const wrapper_structure_css = typeof element_css.wrapper!=="undefined" ? element_css.wrapper : []
					const ar_css = [
						'wrapper_' + type,
						model,
						tipo,
						mode,
						...wrapper_structure_css
					]
					if (view) {ar_css.push('view_'+view)}
					if (mode==='search') ar_css.push('tooltip_toggle')
					wrapper.classList.add(...ar_css)

				// event click . Activate component on event
					wrapper.addEventListener('click', e => {
						e.stopPropagation()
						ui.component.activate(instance)
					})

				wrapper.appendChild(fragment)

			return wrapper
		},//end build_wrapper_search



		/**
		* ACTIVATE
		* Set component state as active/inactive and publish activation event
		*
		* @param object component
		*	Full component instance
		* @return bool
		* 	If component is undefined or already active return false, else true
		*/
		activate : async (component) => {

			// component mandatory check
				if (typeof component==='undefined') {
					console.warn('[ui.component.active]: WARNING. Received undefined component!');
					return false
				}

			// already active case
				if (component.active===true) {
					// console.log('Ignored already active component: ', component.id);
					return false
				}

			// deactivate others
				if (page_globals.component_active &&
					page_globals.component_active.id!==component.id
					) {
					await ui.component.deactivate(page_globals.component_active)
				}

			// inspector. fix nearby inspector overlapping
				const wrapper = component.node
				if (wrapper) {
					wrapper.classList.add('active')

					const el_rect	= wrapper.getBoundingClientRect();
					const inspector	= document.getElementById('inspector')
					if (inspector) {
						const inspector_rect = inspector.getBoundingClientRect();
						// console.log("/// inspector_rect:",inspector_rect);
						if (inspector_rect.left > 50 // prevent affects responsive mobile view
							&& el_rect.right > inspector_rect.left-20
							) {
							wrapper.classList.add('inside')
							// const buttons_container = wrapper.querySelector(':scope > .buttons_container')
							// if (buttons_container) {
							// 	buttons_container.classList.add('left')
							// }
						}
					}
				}

			// component active status
				component.active = true

			// fix component as active
				page_globals.component_active = component

			// publish activate_component event
				event_manager.publish('activate_component', component)

				// console.log('ui Activating component:', component.id);


			return true
		},//end activate



		/**
		* DEACTIVATE
		* Removes component active style and save it
		* if changed_data is different from undefined
		* (!) Note that component changed_data existence provoke the save call (change_value())
		* @param object component
		*	Full component instance
		* @return promise
		* 	Resolve bool false if component it's not active or
		* 	true when deactivation finish
		*/
		deactivate : async (component) => {

			// check already inactive
				if (component.active!==true) {
					// console.log('Ignored component. It\'s not active:', component.id);
					return false
				}

			// styles. Remove wrapper css active if exists
				if(component.node && component.node.classList.contains('active')) {
					component.node.classList.remove('active')
				}

			// changed_data check. This action saves changed_data
			// and reset component changed_data to empty array []
				if (component.data && component.data.changed_data && component.data.changed_data.length>0) {
					// console.log('>>>>>> UI component.data.changed_data:', component.model, component.data.changed_data);
					// set_before_unload(true)
					await component.change_value({
						changed_data	: component.data.changed_data,
						refresh			: false
					})
				}

			// component active status
				component.active = false

			// fix component_active as null
				if (page_globals.component_active && page_globals.component_active.id===component.id) {
					page_globals.component_active = null
				}

			// publish event deactivate_component
				event_manager.publish('deactivate_component', component)

				// console.log('ui Deactivating component:', component.id);


			return true
		},//end deactivate



		/**
		* ERROR
		* Set component state as valid or error
		*
		* @param boolean error
		*	Boolean value obtained from previous component validation functions
		* @param object component
		*	Component that has to be set as valid or with data errors
		* @return boolean
		*/
		error : (error, component) => {

			if (error) {
					console.error("ERRROR IN component:------////////-----------------",component);
				component.classList.add('error')

			}else{
				component.classList.remove('error')
			}

			return true
		},//end error



		/**
		* REGENERATE
		*/
		regenerate : (current_node, new_node) => {

			//// clean
			//	while (current_node.firstChild) {
			//		current_node.removeChild(current_node.firstChild)
			//	}
			//// set children nodes
			//	while (new_node.firstChild) {
			//		current_node.appendChild(new_node.firstChild)
			//	}

			current_node.parentNode.replaceChild(new_node, current_node);

			return current_node
		},//end regenerate



		/**
		* ADD_IMAGE_FALLBACK
		* Unified fallback image adds event listener error and changes the image src when event error is triggered
		* @return bool
		*/
		add_image_fallback : (img_node, callback) => {

			img_node.addEventListener('error', change_src, true)

			function change_src(item) {

				// remove onerror listener to avoid infinite loop (!)
				item.target.removeEventListener('error', change_src, true);

				// set fallback src to the image
				item.target.src = page_globals.fallback_image

				if(typeof callback==='function'){
					callback()
				}

				return true
			}


			return true
		},//end  add_image_fallback



		/**
		* EXEC_SAVE_SUCCESSFULLY_ANIMATION
		* Used on component save successfully
		* @param object self
		* 	Element instance
		* @return promise
		* 	Resolve bool
		*/
		exec_save_successfully_animation : (self) => {

			// disable_save_animation from self.view_properties
				if (self.view_properties && self.view_properties.disable_save_animation===true) {
					return Promise.resolve(false)
				}

			// no rendered node exists cases
				if (!self.node) {
					return Promise.resolve(false)
				}

			return new Promise(function(resolve){

				// remove previous save_success classes
					if (self.node.classList.contains('save_success')) {
						self.node.classList.remove('save_success')
					}

				setTimeout(()=>{

					// success. add save_success class to component wrappers (green line animation)
						if (self.node) {
							self.node.classList.add('save_success')
						}

					// remove save_success. after 2000ms, remove wrapper class to avoid issues on refresh
						setTimeout(()=>{

							if (self.node) {
								// item.classList.remove('save_success')
								// allow restart animation. Not set state pause before animation ends (2 secs)
								self.node.style.animationPlayState = 'paused';
								self.node.style.webkitAnimationPlayState = 'paused';

								// remove animation style
								if (self.node.classList.contains('save_success')) {
									self.node.classList.remove('save_success')
								}
							}

							resolve(true)
						}, 2000)
				}, 25)
			})
		}//end exec_save_successfully_animation



	},//end component



	section : {



		/**
		* BUILD_WRAPPER_EDIT
		*/
			// build_wrapper_edit : (instance, items={}) => {
			// 	if(SHOW_DEBUG===true) {
			// 		//console.log("[ui.build_wrapper_edit] instance:",instance)
			// 	}

			// 	const id 			= instance.id || 'id is not set'
			// 	const model 		= instance.model 	// like component_input-text
			// 	const type 			= instance.type 	// like 'component'
			// 	const tipo 			= instance.tipo 	// like 'rsc26'
			// 	const mode 			= instance.mode 	// like 'edit'
			// 	const label 		= mode === 'edit_in_list' ? null : instance.label // instance.context.label
			// 	const main_context 	= instance.context
			// 	const element_css 	= main_context.css || {}

			// const fragment = new DocumentFragment()

			// 	// label
			// 		if (label===null || items.label===null) {
			// 			// no label add
			// 		}else if(items.label) {
			// 			// add custom label
			// 			fragment.appendChild(items.label)
			// 		}else{
			// 			// default
			// 			// const component_label = ui.create_dom_element({
			// 			// 	element_type	: 'div',
			// 			// 	class_name		: 'label',
			// 			// 	inner_html		: label + ' [' + instance.lang.substring(3) + '] [' + instance.permissions +']',
			// 			// 	parent			: fragment
			// 			// })
			// 		}

			// 	// inspector
			// 		if (items.inspector_div) {
			// 			fragment.appendChild(items.inspector_div)
			// 		}

			// 	// buttons
			// 		if (items.buttons) {
			// 			const buttons = ui.create_dom_element({
			// 				element_type	: 'div',
			// 				class_name		: 'buttons',
			// 				parent			: fragment
			// 			})
			// 			const items_buttons_length = items.buttons.length
			// 			for (let i = 0; i < items_buttons_length; i++) {
			// 				buttons.appendChild(items.buttons[i])
			// 			}
			// 		}

			// 	// filter
			// 		// if (instance.filter) {
			// 		// 	const filter = ui.create_dom_element({
			// 		// 		element_type	: 'div',
			// 		// 		class_name		: 'filter',
			// 		// 		parent			: fragment
			// 		// 	})
			// 		// 	instance.filter.build().then(()=>{
			// 		// 		instance.filter.render().then(filter_wrapper =>{
			// 		// 			filter.appendChild(filter_wrapper)
			// 		// 		})
			// 		// 	})
			// 		// }

			// 	// paginator
			// 		if (items.paginator_div) {
			// 			// place paginator in inspector
			// 			ui.place_element({
			// 				source_node			: items.paginator_div,
			// 				source_instance		: instance,
			// 				target_instance		: instance.inspector,
			// 				container_selector	: ".paginator_container",
			// 				target_selector		: ".wrapper_paginator"
			// 			})
			// 		}

			// 	// content_data
			// 		if (items.content_data) {
			// 			const content_data = items.content_data
			// 			// css
			// 				const content_data_structure_css = typeof element_css.content_data!=="undefined" ? element_css.content_data : []
			// 				const ar_css = ["content_data", type, ...content_data_structure_css]
			// 				content_data.classList.add(...ar_css)
			// 			// add to fragment
			// 				fragment.appendChild(content_data)
			// 		}

			// 	// wrapper
			// 		const wrapper = ui.create_dom_element({
			// 			element_type	: 'div',
			// 			class_name		: 'wrapper_' + type + ' ' + model + ' ' + tipo + ' ' + mode
			// 			})
			// 			// css
			// 				const wrapper_structure_css = typeof element_css.wrapper!=="undefined" ? element_css.wrapper : []
			// 			const ar_css = ['wrapper_'+type, model, tipo, mode,	...wrapper_structure_css]
			// 			wrapper.classList.add(...ar_css)

			// 		// append fragment
			// 			wrapper.appendChild(fragment)


			// 	return wrapper
			// }//end  build_wrapper_edit



	},//end section



	area : {


		/**
		* BUILD_WRAPPER_EDIT
		* Common method to create element wrapper in current mode
		* @return DOM node wrapper
		*/
		build_wrapper_edit : (instance, items={}) => {

			// short vars
				const model			= instance.model 	// like component_input-text
				const type			= instance.type 	// like 'component'
				const tipo			= instance.tipo 	// like 'rsc26'
				const section_tipo	= instance.section_tipo 	// like 'rsc26'
				const mode			= instance.mode 	// like 'edit'
				const view			= instance.view || instance.context.view || null
				const label			= instance.label 	// instance.context.label
				const content_data	= items.content_data || null

			// fragment
				const fragment = new DocumentFragment()

			// label
				if (label===null || items.label===null) {
					// no label add
				}else if(items.label) {
					// add custom label
					fragment.appendChild(items.label)
				}else{
					// default
					const component_label = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'label',
						inner_html		: label + ' [' + instance.lang.substring(3) + ']',
						parent			: fragment
					})
				}

			// buttons
				// if (items.buttons) {
				// 	const buttons = ui.create_dom_element({
				// 		element_type	: 'div',
				// 		class_name		: 'buttons',
				// 		parent			: fragment
				// 	})
				// 	const items_buttons_length = items.buttons.length
				// 	for (let i = 0; i < items_buttons_length; i++) {
				// 		buttons.appendChild(items.buttons[i])
				// 	}
				// }

			// filter
				// if (instance.filter) {
				// 	const filter = ui.create_dom_element({
				// 		element_type	: 'div',
				// 		class_name		: 'filter',
				// 		parent			: fragment
				// 	})
				// 	instance.filter.render().then(filter_wrapper =>{
				// 		filter.appendChild(filter_wrapper)
				// 	})
				// }

			// content_data
				if (content_data) {
					// content_data.classList.add("content_data", type)
					fragment.appendChild(content_data)
				}

			// wrapper
				const wrapper = document.createElement('div')
				// css
					const ar_css = [
						'wrapper_' + type,
						model,
						tipo,
						section_tipo +'_'+ tipo,
						mode
					]
					if (view) {ar_css.push('view_'+view)}
					wrapper.classList.add(...ar_css)

				// context css new way v6
					if (instance.context.css) {
						const selector = `${section_tipo}_${tipo}.edit`
						set_element_css(selector, instance.context.css)
						// add_class
							// sample
							// "add_class": {
							// "wrapper": [
							// 	"bg_warning"
							// ]
							// }
							if (instance.context.css.add_class) {

								for(const selector in instance.context.css.add_class) {
									const values = instance.context.css.add_class[selector]
									const element = selector==='wrapper'
										? wrapper
										: selector==='content_data'
											? content_data
											: null

									if (element) {
										element.classList.add(values)
									}else{
										console.warn("Invalid css class selector was ignored:", selector);
									}
								}
							}
					}
				// append fragment
					wrapper.appendChild(fragment)


			return wrapper
		}//end build_wrapper_edit



	},//end area



	tool : {



		build_wrapper_edit : (instance, items={})=>{

			// short vars
				const model			= instance.model 	// like component_input_text
				const type			= instance.type || 'tool' 	// like 'component'
				// const tipo		= instance.tipo 	// like 'rsc26'
				const mode			= instance.mode 	// like 'edit'
				const view			= instance.view || instance.context.view || null
				const context		= instance.context || {}
				const label			= context.label || ''
				const description	= context.description || ''
				const name			= instance.constructor.name

			// wrapper
				const wrapper = document.createElement('div')
				// css
					const ar_css = [
						'wrapper_' + type,
						model,
						mode
					]
					if (view) {ar_css.push('view_'+view)}
					wrapper.classList.add(...ar_css)

			// fragment
				const fragment = new DocumentFragment()

			if (mode!=='mini') {
				// header
					const tool_header = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'tool_header ' + name,
						parent			: fragment
					})
					// pointer
					wrapper.tool_header = tool_header

				// tool_name_container
					const tool_name_container = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'tool_name_container',
						parent			: tool_header
					})

					// label
					if (label!==null) {
						// default
						const component_label = ui.create_dom_element({
							element_type	: 'div',
							class_name		: 'label',
							inner_html		: label,
							parent			: tool_name_container
						})

						// icon (optional)
						if (context.icon) {
							const icon = ui.create_dom_element({
								element_type	: 'span',
								class_name		: 'button white', // gear
								style : {
									'-webkit-mask'	: "url('" +context.icon +"')",
									'mask'			: "url('" +context.icon +"')"
								}
							})
							component_label.prepend(icon)
						}
					}

					// description
					if (description!==null) {
						// component_description
						ui.create_dom_element({
							element_type	: 'div',
							class_name		: 'description',
							inner_html		: description,
							parent			: tool_name_container
						})
					}

				// tool_buttons_container
					const tool_buttons_container = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'tool_buttons_container',
						parent			: tool_header
					})
					// pointer
					wrapper.tool_buttons_container = tool_buttons_container

				// activity_info_container
					const activity_info_container = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'activity_info_container',
						parent			: tool_header
					})
					// pointer
					wrapper.activity_info_container = activity_info_container

				// button_close (hidden inside modal)
					const button_close = ui.create_dom_element({
						element_type	: 'span',
						class_name		: 'button close white',
						parent			: tool_header
					})
					button_close.addEventListener('click', function(){
						window.close();
					})
			}//end if (mode!=='mini')

			// buttons (not used anymore)
				// if (items.buttons) {
				// 	const buttons = ui.create_dom_element({
				// 		element_type	: 'div',
				// 		class_name		: 'buttons',
				// 		parent			: fragment
				// 	})
				// 	const items_buttons_length = items.buttons.length
				// 	for (let i = 0; i < items_buttons_length; i++) {
				// 		buttons.appendChild(items.buttons[i])
				// 	}
				// }

			// content_data
				if (items.content_data) {
					fragment.appendChild(items.content_data)
					// set pointers
					wrapper.content_data = items.content_data
				}

			// wrapper
				wrapper.appendChild(fragment)


			return wrapper
		},//end build_wrapper_edit



		/**
		* BUILD_CONTENT_DATA
		* @param object tool instance
		* @return DOM node content_data
		*/
		build_content_data : (instance, options) => {

			// short vars
				const type = instance.type // expected tool
				const mode = instance.mode

			// node
				const content_data = document.createElement('div')

			// css
				content_data.classList.add('content_data', type, mode)


			return content_data
		},//end build_content_data



		/**
		* BUILD_SECTION_TOOL_BUTTON
		* Generate button element for open the target tool
		* @return DOM element tool_button
		*/
		build_section_tool_button : (tool_context, self) => {

			// button
				const tool_button = ui.create_dom_element({
					element_type	: 'button',
					class_name		: 'warning ' + tool_context.model,
					// text_content	: tool_context.label,
					dataset			: {
						tool : tool_context.name
					}
					// style			: {
					// 	"background-image"		: "url('" +tool_context.icon +"')"
					// }
				})
				// icon inside
				const tool_icon = ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'button white tool',
					style			: {
						'-webkit-mask'	: "url('" +tool_context.icon +"')",
						'mask'			: "url('" +tool_context.icon +"')"
					},
					parent : tool_button
				})
				tool_button.insertAdjacentHTML('beforeend', tool_context.label)


			// Events
				tool_button.addEventListener('mousedown', function(e){
					e.stopPropagation()

					// open_tool (tool_common)
						open_tool({
							tool_context	: tool_context,
							caller			: self
						})
				})


			return tool_button
		},//build_section_tool_button



		/**
		* BUILD_COMPONENT_TOOL_BUTTON
		* Generate button element for open the target tool
		* @return DOM element tool_button
		*/
		build_component_tool_button : (tool_context, self) => {

			if (tool_context.show_in_component===false) {
				return null
			}

			// button
				const tool_button = ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'button tool',
					title_label		: tool_context.label,
					style			: {
						'-webkit-mask'	: "url('" +tool_context.icon +"')",
						'mask'			: "url('" +tool_context.icon +"')"
					},
					dataset			: {
						tool : tool_context.name
					}
				})
				// const tool_button = ui.create_dom_element({
				// 	element_type	: 'img',
				// 	class_name		: 'button tool',
				// 	// style		: { "background-image": "url('" +tool_context.icon +"')" },
				// 	src				: tool_context.icon,
				// 	dataset			: { tool : tool_context.name },
				// 	title_label		: tool_context.label
				// })

			// Events
				tool_button.addEventListener('click', function(e){
					e.stopPropagation();

					// open_tool (tool_common)
						open_tool({
							tool_context	: tool_context,
							caller			: self
						})
				})


			return tool_button
		}//build_component_tool_button
	},//end tool



	widget : {



		build_wrapper_edit : (instance, items)=>{

			// short vars
				// const id	= instance.id || 'id is not set'
				const mode	= instance.mode 	// like 'edit'
				const type	= 'widget'
				const name	= instance.constructor.name

			// fragment
				const fragment = new DocumentFragment()

			// content_data
				if (items.content_data) {
					const content_data = items.content_data
					content_data.classList.add('content_data', type)
					fragment.appendChild(content_data)
				}

			// wrapper
				const wrapper = document.createElement('div')
				// css
					const ar_css = [
						'wrapper_' + type,
						name,
						mode
					]
					wrapper.classList.add(...ar_css)
				// append fragment
				wrapper.appendChild(fragment)


			return wrapper
		}//end build_wrapper_edit
	},//end widget



	// DES
		// button : {



		// 	/**
		// 	* BUILD_BUTTON
		// 	* Generate button element for open the target tool
		// 	* @return dom element tool_button
		// 	*/
		// 	build_button : (options) => {

		// 		const class_name = 'button' + (options.class_name ? (' ' + options.class_name) : '')
		// 		const label 	 = options.label || "undefined"

		// 		// button
		// 			const button = ui.create_dom_element({
		// 				element_type	: 'span',
		// 				class_name		: class_name,
		// 				text_content	: label
		// 				//style			: { "background-image": "url('" +tool_object.icon +"')" },
		// 			})

		// 		// Events
		// 			//button.addEventListener('mouseup', (e) => {
		// 			//	e.stopPropagation()
		// 			//	alert("Click here! "+label)
		// 			//})

		// 		return button
		// 	}//build_button



		// },//end button



	/**
	* CREATE_DOM_ELEMENT
	* Builds a DOM node based on received options
	*/
	create_dom_element : function(options){

		// options
			const element_type		= options.element_type
			const type				= options.type
			const id				= options.id
			const parent			= options.parent
			const class_name		= options.class_name
			const style				= options.style
			const data_set			= (typeof options.dataset!=="undefined") ? options.dataset : options.data_set
			const title_label		= options.title_label || options.title
			const text_node			= options.text_node
			const text_content		= options.text_content
			const inner_html		= options.inner_html
			const draggable			= options.draggable
			const value				= options.value
			const src				= options.src
			const contenteditable	= options.contenteditable
			const name				= options.name
			const placeholder		= options.placeholder
			const pattern			= options.pattern
			const href				= options.href

		// DOM node element
			const element = document.createElement(element_type)

		// id. Add id property to element
			if(id) {
				element.id = id
			}

		// element_type. A element. Add default href property to element
			if(element_type==='a') {
				element.href = href || 'javascript:;'
			}

		// type
			if (type && element_type!=='textarea') {
				element.type = type
			}

		// class_name. Add CSS classes property to element
			if(class_name) {
				element.className = class_name
			}

		// style. Add CSS style property to element
			if(style) {
				for(let key in style) {
					element.style[key] = style[key]
					//element.setAttribute("style", key +":"+ style[key]+";");
				}
			}

		// title . Add title attribute to element
			if(title_label) {
				element.title = title_label
			}

		// dataset Add dataset values to element
			if(data_set) {
				for (let key in data_set) {
					element.dataset[key] = data_set[key]
				}
			}

		// value
			if(value!==undefined) {
				element.value = value
			}

		// Text content: + span,
			if(text_node){
				//element.appendChild(document.createTextNode(TextNode));
				// Parse HTML text as object
				if (element_type==='span') {
					element.textContent = text_node
				}else{
					const el = document.createElement('span')
						  // Note that prepend a space to span to prevent Chrome bug on selection
						  // el.innerHTML = " "+text_node
						  el.insertAdjacentHTML('afterbegin', " "+text_node)
					element.appendChild(el)
				}
			}else if(text_content) {
				element.textContent = text_content
			}else if(inner_html) {
				// element.innerHTML = inner_html
				element.insertAdjacentHTML('afterbegin', inner_html)
			}


		// draggable
			if(draggable) {
				element.draggable = draggable
			}

		// src
			if(src) {
				element.src = src
			}

		// contenteditable
			if (contenteditable) {
				element.contentEditable = contenteditable
			}

		// name
			if(name) {
				element.name = name
			}

		// placeholder
			if(placeholder) {
				element.placeholder = placeholder
			}

		// pattern
			if(pattern) {
				element.pattern = pattern
			}

		// parent. Append created element to parent
			if (parent) {
				parent.appendChild(element)
			}


		return element;
	},//end create_dom_element



	/**
	* INSIDE_TOOL
	* Check if instance is inside tool
	* @return bool | string tool name
	*/
	inside_tool : function(self) {

		// already custom fixed case (bool is expected)
			if (self.is_inside_tool!==undefined && self.is_inside_tool!==null) {
				return self.is_inside_tool
			}

		// caller is a tool case
			if (self.caller && self.caller.type==='tool') {
				return self.caller.constructor.name
			}

		return false
	},//end inside_tool



	/**
	* ADD_TOOLS
	* Adds all the existent tools for the selected component
	* @param object self
	* @param DOM node buttons_container
	* @return array tools
	*/
	add_tools : function(self, buttons_container) {

		const tools			= self.tools || []
		const tools_length	= tools.length

		for (let i = 0; i < tools_length; i++) {

			const tool_node = (self.type==='component')
				? ui.tool.build_component_tool_button(tools[i], self)
				: ui.tool.build_section_tool_button(tools[i], self)

			if (tool_node) {
				buttons_container.appendChild(tool_node)
			}

			// if(self.type === 'component' && tools[i].show_in_component){
			// 	buttons_container.appendChild( ui.tool.build_component_tool_button(tools[i], self) )
			// }else if(self.type === 'section'){
			// 	buttons_container.appendChild( ui.tool.build_section_tool_button(tools[i], self) )
			// }
		}

		return tools
	},//end add_tools



	/**
	* PLACE_ELEMENT
	* Place DOM element inside target instance nodes
	* Used in section_record to send component_filter to inspector
	* @param object options
	* @return bool
	*/
	place_element : function(options) {

		// options
			const source_node			= options.source_node // like node of component_filter
			const source_instance		= options.source_instance // like section
			const target_instance		= options.target_instance // like inspector instance
			const container_selector	= options.container_selector // like .project_container
			const target_selector		= options.target_selector // like .wrapper_component.component_filter
			const place_mode			= options.place_mode || 'replace' // like 'add' | 'replace'

		if (!target_instance) {
			console.error("[ui.place_element] Error on get target instance:", options);
			return false
		}

		if (target_instance.status==='rendered') {

			if (target_instance.node===null) {
				console.error('Error. Instance node not found:', target_instance);
			}

			// instance node already exists case
			// const node_length = target_instance.node.length;
			// for (let i = 0; i < node_length; i++) {

				const target_container	= target_instance.node.querySelector(container_selector)
				const target_node		= target_container.querySelector(target_selector)
				if (!target_node) {
					// first set inside container. Append
					target_container.appendChild(source_node)
				}else{
					// already exist target node like 'wrapper_x'. Replace or add
					if (place_mode==='add') {
						target_container.appendChild(source_node)
					}else{
						target_node.parentNode.replaceChild(source_node, target_node)
					}
				}
			// }
		}else{

			// target_instance node not ready case
			source_instance.events_tokens.push(
				event_manager.subscribe('render_'+target_instance.id , fn_render_target)
			)
			function fn_render_target(instance_wrapper) {
				const target_container = instance_wrapper.querySelector(container_selector)
				if (target_container) {
					target_container.appendChild(source_node)
				}
			}
		}


		return true
	},//end place_element



	/**
	* TOGGLE_INSPECTOR
	* @return void
	*/
	toggle_inspector : () => {

		const inspector_wrapper = document.querySelector('.inspector')
		if (inspector_wrapper) {

			const wrapper_section = document.querySelector(".wrapper_section.edit")

			if (inspector_wrapper.classList.contains("hide")) {
				inspector_wrapper.classList.remove("hide")
				wrapper_section.classList.remove("full_width")
			}else{
				inspector_wrapper.classList.add("hide")
				wrapper_section.classList.add("full_width")
			}
		}
	},//end toggle_inspector



	/**
	* COLLAPSE_TOGGLE_TRACK
	* Used by inspector to collapse information blocks like 'Relations'
	* Manages a persistent view ob content (body) based on user selection
	* Uses local DB to track the state of current element
	* @param object options
	*/
	collapse_toggle_track : (options) => {

		// options
			const toggler			= options.toggler // DOM item (usually label)
			const container			= options.container // DOM item (usually the body)
			const collapsed_id		= options.collapsed_id // id to set DDBB record id
			const collapse_callback	= options.collapse_callback // function
			const expose_callback	= options.expose_callback // function
			const default_state		= options.default_state || 'opened' // opened | closed . default body is exposed (open)


		// local DDBB table
			const collapsed_table = 'status'

		// content data state
			data_manager.get_local_db_data(collapsed_id, collapsed_table, true)
			.then(function(ui_status){

				// (!) Note that ui_status only exists when element is collapsed
				const is_collapsed = typeof ui_status==='undefined' || ui_status.value===false
					? false
					: true

				// console.log(default_state, "ui_status:", ui_status, 'is_collapsed', is_collapsed);

				if (is_collapsed) {

					if (!container.classList.contains('hide')) {
						container.classList.add('hide')
					}

					// exec function
					if (typeof collapse_callback==='function') {
						collapse_callback()
					}

				}else{

					if (default_state==='closed' && !ui_status) {

						// Nothing to do. Is the first time access. Not is set the local_db_data yet
						// console.log("stopped open:",default_state, collapsed_id);

					}else{

						container.classList.remove('hide')
						// exec function
						if (typeof expose_callback==='function') {
							expose_callback()
						}
					}
				}
			})

		// event attach
			toggler.addEventListener('click', fn_toggle_collapse)

		// fn_toggle_collapse
			function fn_toggle_collapse(e) {
				e.stopPropagation()

				const collapsed	= container.classList.contains('hide')
				if (!collapsed) {

					// close

					// add record to local DB
						const data = {
							id		: collapsed_id,
							value	: true
						}
						data_manager.set_local_db_data(
							data,
							collapsed_table
						)

					container.classList.add('hide')

					// exec function
					if (typeof collapse_callback==='function') {
						collapse_callback()
					}
				}else{

					// open

					// remove record from local DB (or set value=false)
					if (default_state==='opened') {
						// default case for section_group, inspector_project, etc.
						data_manager.delete_local_db_data(
							collapsed_id,
							collapsed_table
						)
					}else{
						// when default is closed, we need to store the state as NOT collapsed
						// to prevent an infinite loop
						const data = {
							id		: collapsed_id,
							value	: false
						}
						data_manager.set_local_db_data(
							data,
							collapsed_table
						)
					}

					container.classList.remove('hide')

					// exec function
					if (typeof expose_callback==='function') {
						expose_callback()
					}
				}
			}


		return true
	},//end collapse_toggle_track



	/**
	* BUILD_SELECT_LANG
	* Render a lang selector with a given array of langs or the default
	* page_globals.dedalo_projects_default_langs list
	* @param object options
	* @return DOM node select_lang
	*/
	build_select_lang : (options) => {

		// options
			const id			= options.id || null
			const langs			= options.langs ||
								  page_globals.dedalo_projects_default_langs ||
								  [{
									label : 'English',
									value : 'lg-eng'
								  }]
			const selected		= options.selected || page_globals.dedalo_application_lang || 'lg-eng'
			const action		= options.action || null
			const class_name	= options.class_name || 'select_lang'

		const fragment = new DocumentFragment()

		// unify format from object to array
			const ar_langs = (!Array.isArray(langs))
				// object case (associative array)
				? (()=>{
					const ar_langs = []
					for (const lang in langs) {
						ar_langs.push({
							value : lang,
							label : langs[lang]
						})
					}
					return ar_langs
				})()
				// default array of objects case
				: langs

		// iterate array of langs and create option for each one
			const ar_langs_lenght = ar_langs.length
			for (let i = 0; i < ar_langs_lenght; i++) {

				const option = ui.create_dom_element({
					element_type	: 'option',
					value			: ar_langs[i].value,
					text_content	: ar_langs[i].label,
					parent			: fragment
				})
				// selected options set on match
				if (ar_langs[i].value===selected) {
					option.selected = true
				}
			}

		// des
			// for (const lang in langs) {
			// 	const option = ui.create_dom_element({
			// 		element_type	: 'option',
			// 		value			: lang,
			// 		text_content	: langs[lang],
			// 		parent			: fragment
			// 	})
			// 	// selected options set on match
			// 	if (lang===reference_lang) {
			// 		option.selected = true
			// 	}
			// }

		// select
			const select_lang = ui.create_dom_element({
				id				: id,
				element_type	: 'select',
				class_name		: class_name
			})
			if (action) {
				select_lang.addEventListener('change', action)
			}
			select_lang.appendChild(fragment)


		return select_lang
	},//end build_select_lang



	/**
	* GET_CONTENTEDITABLE_BUTTONS
	* @return DOM node contenteditable_buttons
	*/
	get_contenteditable_buttons : () => {

		const fragment = new DocumentFragment()

		// bold
			const button_bold = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'button bold',
				text_content	: "Bold",
				parent			: fragment
			})
			button_bold.addEventListener("click", (e)=>{
				e.stopPropagation()
				ui.do_command('bold', null)
			})
		// italic
			const button_italic = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'button italic',
				text_content	: "Italic",
				parent			: fragment
			})
			button_italic.addEventListener("click", (e)=>{
				e.stopPropagation()
				ui.do_command('italic', null)
			})
		// underline
			const button_underline = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'button underline',
				text_content	: "Underline",
				parent			: fragment
			})
			button_underline.addEventListener("click", (e)=>{
				e.stopPropagation()
				ui.do_command('underline', null)
			})
		// find and replace
			const button_replace = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'button replace',
				text_content	: "Replace",
				parent			: fragment
			})
			button_replace.addEventListener("click", (e)=>{
				e.stopPropagation()

				//replace_selected_text('nuevooooo')
				//const editor = document.activeElement.innerHTML
				//.textContent
				//.inner
				console.log("editor:",contenteditable_buttons.target);
					//console.log("editor:",editor);

				ui.do_search('palabras',contenteditable_buttons.target)

				ui.do_command('insertText', 'nuevoooooXXX')
			})

		// contenteditable_buttons
			const contenteditable_buttons = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'contenteditable_buttons'
			})
			contenteditable_buttons.addEventListener("mousedown", (e)=>{
				e.preventDefault()
			})
			contenteditable_buttons.appendChild(fragment)


		return contenteditable_buttons
	},//end get_contenteditable_buttons



	/**
	* ATTACH_TO_MODAL
	* Insert wrapper into a modal box
	* @param object options
	* {
	* 	header	: node|string,
	* 	body	: node|string,
	* 	footer	: node|string,
	* 	size	: string
	* 	remove_overlay : bool
	* }
	* @return DOM node modal_container
	*/
	attach_to_modal : (options) => {

		// options
			const header	= options.header
				? (typeof options.header==='string')
					? ui.create_dom_element({ // string case. auto-create the header node
						element_type	: 'div',
						class_name		: 'header content',
						inner_html		: options.header
					  })
					: options.header // DOM node
				: null
			const body	= options.body
				? (typeof options.body==='string')
					? ui.create_dom_element({ // string case. auto-create the body node
						element_type	: 'div',
						class_name		: 'body content',
						inner_html		: options.body
					  })
					: options.body // DOM node
				: null
			const footer	= options.footer
				? (typeof options.footer==='string')
					? ui.create_dom_element({ // string case. auto-create the footer node
						element_type	: 'div',
						class_name		: 'footer content',
						inner_html		: options.footer
					  })
					: options.footer // DOM node
				: null
			const size				= options.size || 'normal' // string size='normal'
			const modal_parent		= options.modal_parent || document.querySelector('.wrapper.page')
			const remove_overlay	= options.remove_overlay || false

		// page_y_offset. Current window scroll position (used to restore later)
			const page_y_offset = window.pageYOffset || 0

		// modal container select from DOM (created hidden when page is built)
			// const modal_container = document.querySelector('dd-modal')
		// modal container build new DOM on each call and remove on close
			// const previous_modal  	= document.querySelector('dd-modal')
			// if (previous_modal) {
			// 	previous_modal.remove()
			// }
			const modal_container	= document.createElement('dd-modal')
			// document.body.appendChild(modal_container)
			// const wrapper_page		= document.querySelector('.wrapper.page')
			modal_parent.appendChild(modal_container)

		// modal_node
			const modal_node = modal_container.get_modal_node()

		// remove_overlay
			if (remove_overlay===true) {
				modal_node.classList.add("remove_overlay")
			}

		// publish close event
			modal_container.publish_close = function(e) {
				event_manager.publish('modal_close', e)
				modal_container.remove()
			}

		// header . Add node header to modal header and insert it into slot
			if (header) {
				header.slot = 'header'
				if (!header.classList.contains('header')) {
					header.classList.add('header')
				}
				modal_container.appendChild(header)
			}else{
				const header_blank = ui.create_dom_element({
					element_type	: 'div',
					class_name		: 'hide'
				})
				header_blank.slot = 'header'
				modal_container.appendChild(header_blank)
			}

		// body . Add  wrapper to modal body and insert it into slot
			if (body) {
				body.slot = 'body'
				if (!body.classList.contains('body')) {
					body.classList.add('body')
				}
				modal_container.appendChild(body)
			}

		// footer . Add node footer to modal footer and insert it into slot
			if (footer) {
				footer.slot = 'footer'
				if (!footer.classList.contains('footer')) {
					footer.classList.add('footer')
				}
				modal_container.appendChild(footer)
			}

		// size. Modal special features based on property 'size'
			switch(size) {
				case 'big' :
					// hide contents to avoid double scrollbars
						const content_data_page = document.querySelector(".content_data.page")
							  // content_data_page.classList.add("hide")
						// const menu_wrapper = document.querySelector(".menu_wrapper")
							  // menu_wrapper.classList.add("hide")
						const debug_div = document.getElementById("debug")
							  // if(debug_div) debug_div.classList.add("hide")

					// show hidden elements again on close
						event_manager.subscribe('modal_close', () => {
							content_data_page.classList.remove("hide")
							// menu_wrapper.classList.remove("hide")
							if(debug_div) debug_div.classList.remove("hide")

							// scroll window to previous scroll position
								window.scrollTo({
									top			: page_y_offset,
									behavior	: "auto"
								})
						})

					modal_container._showModalBig();
					break;

				case 'small' :
					modal_container._showModalSmall();
					break;

				default :
					modal_container._showModal();
					break;
			}

		// navigation
			// const state	= {
			// 	event_in_history : false
			// }
			// const title	= 'modal'
			// const url	= null // 'Modal url'
			// 	console.log("history:",history, this);
			// history.pushState(state, title, url)

		// remove on close
			modal_container.on_close = () => {
				modal_container.remove()
			}


		return modal_container
	},//end attach_to_modal



	/**
	* DO_COMMAND
	* Exec document 'execCommand' https://developer.mozilla.org/en-US/docs/Web/API/Document/execCommand
	* Obsolete (!)
	*/
	do_command : (command, val) => {
		document.execCommand(command, false, (val || ""));
	},



	/**
	* DO_SEARCH
	* Unfinished function (!)
	*/
	do_search : (search_text, contenteditable) =>{

		// get the regex
		const regext_text	= search_text.replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
		const regext		= RegExp(regext_text, 'g')

		// const regext_text = search_text.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&').replace(/\s/g, '[^\\S\\r\\n]');
		// const regext_text = search_text.replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
		// const regex = new RegExp(regext_text)

		const text = getText(contenteditable)

		let match = regext.exec(text)

			const endIndex = match.index + match[0].length;
			const startIndex = match.index;
				console.log("endIndex:",endIndex);
				console.log("startIndex:",startIndex);

			const range = document.createRange();
			range.setStart(contenteditable, 0);
			range.setEnd(contenteditable, 3);
			// const sel = window.getSelection();

		// const regext = (text, full_word) => {
		// 	const regext_text = text.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&').replace(/\s/g, '[^\\S\\r\\n]');
		// 	return wholeWord ? '\\b' + escapedText + '\\b' : escapedText;
		// };

			function getText(node) {

				// if node === text_node (3), text inside an Element or Attr. don't has other nodes and return the full data
				if (node.nodeType === Node.TEXT_NODE) {
					return [node.data];
				}

				var txt = [''];
				var i = 0;

				if (node == node.firstChild) do {

					if (node.nodeType === Node.TEXT_NODE) {
						txt[i] += node.data;
						continue;
					}

					var innerText = getText(node);

					if (typeof innerText[0] === 'string') {
						// Bridge nested text-node data so that they're
						// not considered their own contexts:
						// I.e. ['some', ['thing']] -> ['something']
						txt[i] += innerText.shift();
					}
					if (innerText.length) {
						txt[++i] = innerText;
						txt[++i] = '';
					}

				} while (node == node.nextSibling);

				return txt;
			}
	},//end do_search



	/**
	* RENDER_LIST_HEADER
	* Creates the header nodes needed for portal and section in the same unified way
	* @param array columns_map
	* 	Parsed columns_map array as [{id: 'oh87', label: 'Information'}]
	* @param object self
	* 	Instance of section/component_portal
	* @return DOM node header_wrapper
	*/
	render_list_header : (columns_map, self) =>{

		// header_wrapper
			const header_wrapper = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'header_wrapper_list ' + self.model
			})

		const ar_nodes				= []
		const sort_nodes			= []
		const columns_map_length	= columns_map.length
		for (let i = 0; i < columns_map_length; i++) {

			// column
				const column = columns_map[i]
				if (!column) {
					console.warn("ignored empty component: [key, columns_map]", i, columns_map);
					continue;
				}

			// label
				const label = []
				const current_label = SHOW_DEBUG
					? column.label //+ " [" + component.tipo + "]"
					: column.label
				label.push(current_label)

			// node header_item
				const id			= column.id //component.tipo + "_" + component.section_tipo +  "_"+ component.parent
				const header_item	= ui.create_dom_element({
					element_type	: 'div',
					// id			: id,
					class_name		: 'head_column ' + id
				})
				// item_text
				ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'name',
					title			: label.join(' '),
					inner_html		: label.join(' '),
					parent			: header_item
				})

			// sub header items
				if(column.columns_map){

					header_item.classList.add('with_sub_header')
					// header_item.innerHTML = '<span>' + label.join(' ') + '</span>'
					if (!header_item.hasChildNodes()) {
						// item_text include once
						ui.create_dom_element({
							element_type	: 'span',
							class_name		: 'name',
							title			: label.join(' '),
							inner_html		: label.join(' '),
							parent			: header_item
						})
					}

					const sub_header = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'sub_header',
						parent			: header_item
					})

					// grid column calculate
						const items				= ui.flat_column_items(column.columns_map)
						const template_columns	= items.join(' ')
						const css_object = {
							'.sub_header' : {
								'grid-template-columns' : template_columns
							}
						}
						const selector = 'head_column.'+id
						set_element_css(selector, css_object)


					const current_column_map	= column.columns_map
					const columns_map_length	= current_column_map.length
					for (let j = 0; j < columns_map_length; j++) {
						const current_column  = current_column_map[j]
						// node header_item
						const id				= current_column.id //component.tipo + '_' + component.section_tipo +  '_'+ component.parent
						const sub_header_item	= ui.create_dom_element({
							element_type	: 'div',
							// id			: id,
							class_name		: 'head_column '+id,
							parent			: sub_header
						})
						// item_text
						ui.create_dom_element({
							element_type	: 'span',
							class_name		: 'name',
							title			: current_column.label,
							inner_html		: current_column.label,
							parent			: sub_header_item
						})

						// add sort column icons
							if (self.constructor.name==='section' && current_column.sortable===true) {
								const sort_node = ui.add_column_order_set(self, current_column, header_wrapper)
								sort_nodes.push(sort_node)
								sub_header_item.appendChild(sort_node)
							}
					}
				}else{
					// add sort column icons
						if (self.constructor.name==='section' && column.sortable===true) {
							const sort_node = ui.add_column_order_set(self, column, header_wrapper)
							sort_nodes.push(sort_node)
							header_item.appendChild(sort_node)
						}
				}

			ar_nodes.push(header_item)
		}//end for (let i = 0; i < columns_length; i++)

		// header_wrapper pointers add
			header_wrapper.sort_nodes = sort_nodes

		// header_wrapper
			const searchParams = new URLSearchParams(window.location.href);
			const initiator = searchParams.has("initiator")
				? searchParams.get("initiator")
				: false

			if (initiator!==false) {
				header_wrapper.classList.add('with_initiator')
			}else if (SHOW_DEBUG===true) {
				header_wrapper.classList.add('with_debug_info_bar')
			}

		// regular columns append
			const ar_nodes_length = ar_nodes.length
			for (let i = 0; i < ar_nodes_length; i++) {
				header_wrapper.appendChild(ar_nodes[i])
			}

		// css calculation
			// Object.assign(
			// 	header_wrapper.style,
			// 	{
			// 		//display: 'grid',
			// 		//"grid-template-columns": "1fr ".repeat(ar_nodes_length),
			// 		"grid-template-columns": self.id_column_width + " repeat("+(ar_nodes_length)+", 1fr)",
			// 	}
			// )

		return header_wrapper
	},//end render_list_header



	/**
	* ADD_COLUMN_ORDER_SET
	* Creates the arrows to sort list by column and
	* place it into the header_item node
	* @param object self
	* 	Instance of section/component_portal
	* @param DOM node header_item
	* 	Container where place the sort buttons
	* @param object column
	* @return DOM node sort_node
	*/
	add_column_order_set(self, column, header_wrapper) {

		// short vars
			const path				= column.path
			const title_asc			= (get_label.sort || 'Sort') + ' ' + (get_label.ascending || 'ascending')
			const title_desc		= (get_label.sort || 'Sort') + ' ' + (get_label.descending || 'descending')
			let default_direction	= 'DESC'
			let current_direction	= undefined

		// current_direction. current order current_direction check from sqo
		// default is undefined
			const sqo_order = self.rqo.sqo.order || null
			if (sqo_order) {

				const sqo_order_length = sqo_order.length
				for (let i = 0; i < sqo_order_length; i++) {

					const item = sqo_order[i]

					const last_path	= item.path[item.path.length-1]
					if (last_path.component_tipo===column.tipo) {
						current_direction = item.direction
						break;
					}
				}
			}
			// console.log("current_direction:", column.tipo, current_direction);

		// exec_order function
			const exec_order = (direction) => {

				// sample
					// [
					//    {
					//        "direction": "DESC",
					//        "path": [
					//            {
					//                "name": "Code",
					//                "modelo": "component_input_text",
					//                "section_tipo": "oh1",
					//                "component_tipo": "oh14"
					//            }
					//        ]
					//    }
					// ]

				// order sqo build
					const order = [{
						direction: direction, // ASC|DESC
						// path : [{
						// 	component_tipo	: column.tipo || column.id,
						// 	section_tipo	: column.section_tipo
						// }]
						path : path
					}]
					// console.log("order:",order);

				// update rqo (removed way. navigate from page directly wit a user_navigation event bellow)
				// note that navigate only refresh current instance content_data, not the whole page
					self.navigate(
						() => { // callback
							self.rqo_config.sqo.order	= order
							self.rqo.sqo.order			= order
						},
						true // bool navigation_history save
					)

				// update current_direction
					current_direction = direction

				// reset all other sort nodes styles
					const sort_nodes		= header_wrapper.sort_nodes // header_wrapper.querySelectorAll('.order')
					const sort_nodes_length	= sort_nodes.length
					for (let i = 0; i < sort_nodes_length; i++) {
						sort_nodes[i].classList.remove('asc','desc')
					}

				// set current class
					sort_node.classList.add( direction.toLowerCase() )

				// update title
					sort_node.title = direction==='DESC'
						? title_asc
						: title_desc
			}

		// title
			const title = current_direction && current_direction==='DESC'
				? title_asc
				: title_desc

		// sort_node
			const sort_node = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'order',
				title			: title
			})
			// set current style
			if (current_direction) {
				sort_node.classList.add( current_direction.toLowerCase() )
			}
			// mouseenter
			sort_node.addEventListener('mouseenter', function(){
				// selected is self. Nothing to do
				if (current_direction) {
					return
				}

				// check if any other sort item is used
				// if true, change default action from desc to asc
				const sort_nodes		= header_wrapper.sort_nodes // header_wrapper.querySelectorAll('.order')
				const sort_nodes_length	= sort_nodes.length
				for (let i = 0; i < sort_nodes_length; i++) {
					if (sort_nodes[i].classList.contains('asc') || sort_nodes[i].classList.contains('desc')) {
						// console.log("---------------------- sort_nodes[i]:", sort_nodes[i]);
						default_direction = 'ASC'
						sort_node.title = title_asc
						break;
					}
				}
			})
			// click
			sort_node.addEventListener('click', function(e){
				e.stopPropagation()

				const direction = current_direction
					? current_direction==='ASC' ? 'DESC' : 'ASC' // reverse current value
					: default_direction // defaults

				exec_order(direction)
			})


		return sort_node
	},//end add_column_order_set



	/**
	* FLAT_COLUMN_ITEMS
	* create the css grid columns to build list items
	* @param array list
	*	Array of column items
	* @return array ar_elements
	*/
	flat_column_items : (list, level_max=3, type='fr', level=1) => {

		if (level>level_max) {
			return []
		}

		// defaults definitions by model
		// if ddo width is not defined, use this defaults
			const width_defaults = {
				component_publication	: '5rem',
				component_info			: 'minmax(9rem, 1fr)',
				// component_image			: '102px',
				component_av			: '102px',
				component_svg			: '102px',
				component_pdf			: '102px'
			}

		let ar_elements = []
		const list_length = list.length
		for (let i = 0; i < list_length; i++) {

			const item = list[i]

			if (item.width) {
				// already defined width cases
				ar_elements.push(item.width)

			}else{
				// default defined by model
				if (width_defaults[item.model]) {
					ar_elements.push(width_defaults[item.model])
				}else{
					// non defined width cases, uses default grid measure like '1fr'
					const unit = (item.columns_map && item.columns_map.length>0)
						? ui.flat_column_items(item.columns_map, level_max, type, level++).length || 1
						: 1
					ar_elements.push(unit+type) // like '1fr'
				}
			}
		}
		return ar_elements
	},//end flat_column_items



	/**
	* SET_BACKGROUND_IMAGE
	* @param DOM node image
	* @param DOM node target_node
	* @return DOMnode image
	*/
	set_background_image : (image, target_node) => {

		const canvas	= document.createElement('canvas');
		canvas.width	= image.width;
		canvas.height	= image.height;

		try {
			canvas.getContext('2d').drawImage(image, 0, 0, image.width, image.height);
			const rgb = canvas.getContext('2d').getImageData(0, 0, 1, 1).data;

			// round rgb values
				function correction(value) {

					const factor = 1.016

					const result = (value>127)
						? Math.floor(value * factor)
						: Math.floor(value / factor)

					return result
				}

				const r = correction(rgb[0])
				const g = correction(rgb[1])
				const b = correction(rgb[2])

			// build backgroundColor style string
			const bg_color_rgb = 'rgb(' + r + ',' + g + ',' + b +')';

			// set background color style (both container and image)
			target_node.style.backgroundColor = bg_color_rgb

		}catch(error){
			console.warn("ui.set_background_image . Unable to get image canvas: ", image);
		}

		canvas.remove()
		image.classList.remove('loading')

		return image
	},//end set_background_image



	/**
	* MAKE_COLUMN_RESPONSIVE
	* Used in section_record to add responsive CSS
	* @param object options
	* @return bool
	*/
	make_column_responsive : function(options) {

		// options
			const selector	= options.selector // as '#column_id_rsc3652'
			const label		= options.label

		// strip label HTML tags
			const label_text = strip_tags(label);

		// const add_css_rule = function (selector, css) {

		// 	// css_style_sheet
		// 		// create new styleSheet if not already exists
		// 		// if (!window.css_style_sheet) {
		// 		// 	const style = document.createElement("style");
		// 		// 	style.type = 'text/css'
		// 		// 	document.head.appendChild(style);
		// 		// 	window.css_style_sheet = style.sheet;
		// 		// }
		// 		// const css_style_sheet	= window.css_style_sheet
		// 		const css_style_sheet		= get_elements_style_sheet()

		// 	const rules			= css_style_sheet.rules
		// 	const rules_length	= rules.length
		// 	for (let i = rules_length - 1; i >= 0; i--) {

		// 		const current_selector = rules[i].selectorText
		// 		if(current_selector===selector) {
		// 			// already exists
		// 			// console.warn("/// stop current_selector:",current_selector);
		// 			return false
		// 		}
		// 	}

		// 	const propText = typeof css==='string'
		// 		? css
		// 		: Object.keys(css).map(function (p) {
		// 			return p + ':' + (p==='content' ? "'" + css[p] + "'" : css[p]);
		// 		  }).join(';');
		// 	css_style_sheet.insertRule(selector + '{' + propText + '}', css_style_sheet.cssRules.length);

		// 	return true
		// };

		// const width  = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
		// if (width<960) {
			// return add_css_rule(`#column_id_${column_id}::before`, {
			// return add_css_rule(`${selector}::before`, {
			// 	content	: label_text
			// });

			// const css_object = {
			// 	[`${selector}::before`] : {
			// 		style : function() {
			// 			return {
			// 				selector : `${selector}::before`,
			// 				value : {
			// 					content : label_text
			// 				}
			// 			}
			// 		}
			// 	}
			// }
			const css_object = {
				[`${selector}::before`] : function() {
					return {
						selector : `${selector}::before`,
						value : {
							content : label_text
						}
					}
				}
			}
			set_element_css(selector.replace('#',''), css_object)
		// }
	},//end make_column_responsive



	/**
	* HILITE
	* Hilite/un-hilite and element (usually a component) in the DOM
	* @param object options
	* @return bool
	*/
	hilite : function(options) {

		// options
			const hilite	= options.hilite // bool
			const instance	= options.instance // object instance

		// check wrapper node
			if (!instance.node) {
				console.log('Skip hilite! Invalid instance node. instance :', instance);
				return
			}

		// add/remove wrapper class
			const wrapper_node = instance.node

			if (hilite===true) {
				if (!wrapper_node.classList.contains('hilite_element')) {
					wrapper_node.classList.add('hilite_element')
				}
			}else{
				if (wrapper_node.classList.contains('hilite_element')) {
					wrapper_node.classList.remove('hilite_element')
				}
			}


		return true
	},//end hilite



	/**
	* ENTER_FULLSCREEN
	* Set element as full screen size
	* To exit, press key 'Escape'
	* @param DOM none
	* 	Usually the component wrapper
	* @return bool
	*/
	enter_fullscreen : function(node) {

		// apply style fullscreen
		node.classList.toggle('fullscreen')

		// set exit event
		document.addEventListener('keyup', exit_fullscreen, {
			passive : true
		})
		function exit_fullscreen(e) {
			if (e.key==='Escape') {
				document.removeEventListener('keyup', exit_fullscreen)
				node.classList.remove('fullscreen')
			}
		}


		return true
	},//end enter_fullscreen



	/**
	* GET_ONTOLY_TERM_LINK
	* @return DOM node ontoly_link
	*/
	get_ontoly_term_link(tipo) {

		const url = DEDALO_CORE_URL + '/ontology/dd_edit.php?terminoID=' + tipo

		const ontoly_term_link = ui.create_dom_element({
			element_type	: 'a',
			// class_name		: 'button pen',
			href			: url,
			text_content	: tipo,
			title			: 'Local Ontology'
		})
		ontoly_term_link.target	= '_blank'
		ontoly_term_link.rel	= 'noopener'
		// ontoly_term_link.addEventListener('click', function(e){
		// 	e.stopPropagation()
		// 	const custom_url = DEDALO_CORE_URL + '/ontology/dd_edit.php?terminoID=' + section_tipo
		// 	open_ontology_window(section_tipo, custom_url)
		// })

		return ontoly_term_link
	},//end get_ontoly_term_link



	/**
	* LOAD_ITEM_WITH_SPINNER
	* Render a spinner item while callback function is calculating
	* When is finished, spinner will be replaced by callback result node
	* Usually, callback is a async function that builds and render a element
	* like filter
	* @param object options
	* 	{
	* 		container			: DOM node,
	* 		preserve_content	: bool false
	* 		label				: string,
	* 		callback			: function
	* 	}
	* @return promise
	* 	Resolve: DOM node result_node
	*/
	load_item_with_spinner : async function(options) {

		// options
			const container			= options.container
			const preserve_content	= options.preserve_content || false
			const label				= options.label
			const callback			= options.callback

		// clean container
			if (preserve_content===false) {
				while (container.firstChild) {
					container.removeChild(container.firstChild)
				}
			}

		// container_placeholder
			const container_placeholder = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'container container_placeholder ' + label,
				inner_html		: 'Loading ' + label,
				parent			: container
			})
			// spinner
			ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'spinner',
				parent			: container_placeholder
			})

		// callback wait (expect promise resolving DOM node)
			const result_node = await callback()
			if (!result_node) {
				console.warn('Unexpected result. no node returned from callback:', options);
				container_placeholder.remove()
				return null
			}

		// replace node
			await container_placeholder.replaceWith(result_node);

		return result_node
	}//end load_item_with_spinner



	/**
	* SET_PARENT_CHECKED_VALUE
	* Set input check value based on direct children checked values
	* Could be checked, unchecked or indeterminate
	* @return bool
	*/
		// set_parent_checked_value : (input_node, all_direct_children, callback) => {

		// 	// look children status until find checked value false
		// 		const all_children_checked = (()=>{

		// 			const all_direct_children_length = all_direct_children.length
		// 			for (let i = 0; i < all_direct_children_length; i++) {
		// 				if(all_direct_children[i].checked!==true) {
		// 					return false
		// 				}
		// 			}

		// 			return true
		// 		})()

		// 	// set checked value
		// 		if (all_children_checked===true) {
		// 			// full checked
		// 			input_node.indeterminate	= false
		// 			input_node.checked			= true
		// 		}else{
		// 			// intermediate
		// 			input_node.checked			= false
		// 			input_node.indeterminate	= true
		// 		}

		// 	// callback
		// 		if (callback) {
		// 			callback(input_node)
		// 		}

		// 	return true
		// },//end set_parent_checked_value



	/**
	* EXEC_SCRIPTS_INSIDE
	* @return js promise
	*/
		// exec_scripts_inside( element ) {
		// 	console.log("context:",context);

		// 	const scripts 		 = Array.prototype.slice.call(element.getElementsByTagName("script"))
		// 	const scripts_length = scripts.length
		// 	if (scripts_length<1) return false

		// 	const js_promise = new Promise((resolve, reject) => {

		// 		const start = new Date().getTime()

		// 		for (let i = 0; i < scripts_length; i++) {

		// 			if(SHOW_DEBUG===true) {
		// 				var partial_in = new Date().getTime()
		// 			}

		// 			if (scripts[i].src!=="") {
		// 				const tag 	  = document.createElement("script")
		// 					  tag.src = scripts[i].src
		// 				document.getElementsByTagName("head")[0].appendChild(tag)

		// 			}else{
		// 				//eval(scripts[i].innerHTML);
		// 				console.log(scripts[i].innerHTML); //continue;

		// 				// Encapsulate code in a function and execute as well
		// 				const my_func = new Function(scripts[i].innerHTML)
		// 					//console.log("my_func:",my_func); continue;
		// 					my_func() // Exec
		// 			}

		// 			if(SHOW_DEBUG===true) {
		// 				const end  	= new Date().getTime()
		// 				const time 	= end - start
		// 				const partial = end - partial_in
		// 				//console.log("->insertAndExecute: [done] "+" - script time: " +time+' ms' + ' (partial:'+ partial +')')
		// 			}
		// 		}

		// 	});//end js_promise


		// 	return js_promise;
		// }//end  exec_scripts_inside



}//end ui



/**
* EXECUTE_FUNCTION_BY_NAME
*
*//*
export const execute_function_by_name = function(functionName, context /*, args *\/) {

	const args 		 = Array.prototype.slice.call(arguments, 2);
	const namespaces = functionName.split(".");
	const func = namespaces.pop();
	for(let i = 0; i < namespaces.length; i++) {
		context = context[namespaces[i]];
	}

	return context[func].apply(context, args);
}//end execute_function_by_name
*/
