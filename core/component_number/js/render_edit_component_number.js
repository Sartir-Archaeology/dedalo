 /*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'



/**
* render_edit_component_number
* Manage the components logic and appearance in client side
*/
export const render_edit_component_number = function() {

	return true
};//end render_edit_component_number



/**
* EDIT
* Render node for use in modes: edit, edit_in_list
* @return DOM node wrapper
*/
render_edit_component_number.prototype.edit = async function(options) {

	const self 	= this

	// render_level
		const render_level = options.render_level || 'full'

	// content_data
		const content_data = get_content_data_edit(self)
		if (render_level==='content') {
			return content_data
		}

	// buttons
		const buttons = get_buttons(self)

	// wrapper. ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_edit(self, {
			content_data : content_data,
			buttons 	 : buttons
		})
	// add events
		add_events(self, wrapper)

	return wrapper
};//end edit



/**
* ADD_EVENTS
*/
const add_events = function(self, wrapper) {

	// update value, subscription to the changes: if the dom input value was changed, observers dom elements will be changed own value with the observable value
		self.events_tokens.push(
			event_manager.subscribe('update_value_'+self.id, fn_update_value)
		)
		function fn_update_value (changed_data) {
			// change the value of the current dom element
			const changed_node = wrapper.querySelector('input[data-key="'+changed_data.key+'"]')
			changed_node.value = changed_data.value
		}

	// add element, subscription to the events
		self.events_tokens.push(
			event_manager.subscribe('add_element_'+self.id, fn_add_element)
		)
		function fn_add_element(changed_data) {
			const inputs_container = wrapper.querySelector('.inputs_container')
			// add new dom input element
			get_input_element_edit(changed_data.key, changed_data.value, inputs_container, self)
		}

	// remove element, subscription to the events
		//self.events_tokens.push(
		//	event_manager.subscribe('remove_element_'+self.id, remove_element)
		//)
		//async function remove_element(component) {
		//	// change all elements inside of content_data
		//	const new_content_data = await render_content_data(component)
		//	// replace the content_data with the refresh dom elements (imputs, delete buttons, etc)
		//	wrapper.childNodes[2].replaceWith(new_content_data)
		//}

	// change event, for every change the value in the imputs of the component
		wrapper.addEventListener('change', (e) => {
			// e.stopPropagation()

			// input_value. The standard input for the value of the component
			if (e.target.matches('input[type="number"].input_value')) {

				const changed_data = Object.freeze({
					action	: 'update',
					key		: JSON.parse(e.target.dataset.key),
					value	: (e.target.value.length>0) ? self.fix_number_format(e.target.value) : null,
				})
				self.change_value({
					changed_data : changed_data,
					refresh 	 : false
				})
				.then((save_response)=>{
					// event to update the dom elements of the instance
					event_manager.publish('update_value_'+self.id, changed_data)
				})

				return true
			}
		})

	// click event [mousedown]
		wrapper.addEventListener("click", e => {
			// insert
			if (e.target.matches('.button.add')) {

				const changed_data = Object.freeze({
					action	: 'insert',
					key		: self.data.value.length,//self.data.value.length>0 ? self.data.value.length : 1,
					value	: null
				})
				self.change_value({
					changed_data : changed_data,
					refresh 	 : false
				})
				.then((save_response)=>{
					// event to update the dom elements of the instance
					event_manager.publish('add_element_'+self.id, changed_data)
				})

				return true
			}

			// remove
			if (e.target.matches('.button.remove')) {

				// force possible input change before remove
				document.activeElement.blur()

				const changed_data = Object.freeze({
					action	: 'remove',
					key		: e.target.dataset.key,
					value	: null,
					refresh : true
				})
				self.change_value({
					changed_data : changed_data,
					label 		 : e.target.previousElementSibling.value,
					refresh 	 : true
				})
				.then(()=>{
				})

				return true
			}

		})

	// keyup event
		wrapper.addEventListener("keyup", async (e) => {

			// page unload event
				if (e.key!=='Enter') {
					const key				= e.target.dataset.key
					const original_value	= self.db_data.value[key]
					const new_value			= e.target.value
					if (new_value!=original_value) {
						// set_before_unload (bool) add
						event_manager.set_before_unload(true)
					}else{
						// set_before_unload (bool) remove
						event_manager.set_before_unload(false)
					}
				}
		})//end keyup


	return true
};//end add_events



/**
* GET_CONTENT_DATA_EDIT
* @return DOM node content_data
*/
const get_content_data_edit = function(self) {

	const value	= (self.data.value.length<1) ? [null] : self.data.value

	const fragment = new DocumentFragment()

	// inputs
		const inputs_container = ui.create_dom_element({
			element_type	: 'ul',
			class_name		: 'inputs_container',
			parent			: fragment
		})

	// build values
		const inputs_value	= value//(value.length<1) ? [''] : value
		const value_length	= inputs_value.length
		for (let i = 0; i < value_length; i++) {
			const input_element = get_input_element_edit(i, inputs_value[i], self)
			inputs_container.appendChild(input_element)
		}

	// content_data
		const content_data = ui.component.build_content_data(self)
			  content_data.appendChild(fragment)


	return content_data
};//end get_content_data_edit



/**
* GET_BUTTONS
* @param object instance
* @return DOM node buttons_container
*/
const get_buttons = (self) => {

	const is_inside_tool= self.is_inside_tool
	const mode 			= self.mode

	const fragment = new DocumentFragment()

	// button add input
		if(mode==='edit' || mode==='edit_in_list'){ // && !is_inside_tool
			const button_add_input = ui.create_dom_element({
				element_type	: 'span',
				class_name 		: 'button add',
				parent 			: fragment
			})
		}

	// buttons tools
		if (!is_inside_tool) {
			ui.add_tools(self, fragment)
		}

	// buttons container
		const buttons_container = ui.component.build_buttons_container(self)
			// buttons_container.appendChild(fragment)

	// buttons_fold (allow sticky position on large components)
		const buttons_fold = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'buttons_fold',
			parent			: buttons_container
		})
		buttons_fold.appendChild(fragment)


	return buttons_container
};//end get_buttons



/**
* GET_INPUT_ELEMENT_EDIT
* @return DOM element li
*/
const get_input_element_edit = (i, current_value, self) => {

	const mode 				= self.mode
	const is_inside_tool	= self.is_inside_tool

	// li
		const li = ui.create_dom_element({
			element_type	: 'li'
		})

	// input field
		const input = ui.create_dom_element({
			element_type	: 'input',
			type			: 'number',
			class_name		: 'input_value',
			dataset			: { key : i },
			value			: current_value,
			parent			: li
		})

	// button remove
		if((mode==='edit' || 'edit_in_list') && !is_inside_tool){
			const button_remove = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'button remove hidden_button',
				dataset			: { key : i },
				parent			: li
			})
		}

	return li
};//end input_element


