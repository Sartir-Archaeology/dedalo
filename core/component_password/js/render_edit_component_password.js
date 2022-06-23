/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'



/**
* render_edit_component_password
* Manages the component's logic and apperance in client side
*/
export const render_edit_component_password = function() {

	return true
}//end render_edit_component_password



/**
* EDIT
* Render node for use in modes: edit, edit_in_list
* @return DOM node wrapper
*/
render_edit_component_password.prototype.edit = async function(options) {

	const self = this

	// render_level
		const render_level = options.render_level || 'full'

	// content_data
		const current_content_data = await get_content_data_edit(self)
		if (render_level==='content') {
			return current_content_data
		}

	// wrapper. ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_edit(self, {
			content_data : current_content_data
		})

	// add events
		add_events(self, wrapper)


	return wrapper
}//end edit



/**
* ADD_EVENTS
*/
const add_events = function(self, wrapper) {

	// change event, for every change the value in the imputs of the component
		wrapper.addEventListener('change', async (e) => {
			//e.stopPropagation()

			// update
			if (e.target.matches('input[type="password"].password_value')) {

				// Avoid Safari autofill save
				if (!confirm(get_label["seguro"] + " [edit password]")) {
					return false
				}

				// Test password is aceptable string
				const validated = self.validate_password_format(e.target.value)
				ui.component.error(!validated, e.target)

				if (validated) {

					const changed_data = Object.freeze({
						action	: 'update',
						key		: 0,
						value	: (e.target.value.length>0) ? e.target.value : null,
					})
					self.change_value({
						changed_data : changed_data,
						refresh 	 : false
					})
					.then((save_response)=>{
						// event to update the dom elements of the instance
						//event_manager.publish('update_value_'+self.id, changed_data)
					})
				}

				return true
			}
		})

	return true
}//end add_events



/**
* GET_CONTENT_DATA_EDIT
* @return DOM node content_data
*/
const get_content_data_edit = function(self) {

	// const value	= (self.data.value.length<1) ? [null] : self.data.value
	// const mode	= self.mode

	const fragment = new DocumentFragment()

	// inputs container
		const inputs_container = ui.create_dom_element({
			element_type	: 'ul',
			class_name		: 'inputs_container',
			parent			: fragment
		})

	// value (input)
		const input_element = get_input_element(self)
		inputs_container.appendChild(input_element)

	// content_data
		const content_data = ui.component.build_content_data(self)
			  content_data.classList.add("nowrap")
			  content_data.appendChild(fragment)


	return content_data
}//end get_content_data_edit



/**
* GET_INPUT_ELEMENT
* @return DOM node li
*/
const get_input_element = () => {

	// li
		const li = ui.create_dom_element({
			element_type : 'li'
		})

	// input field
		const input = ui.create_dom_element({
			element_type	: 'input',
			type			: 'password',
			class_name		: 'password_value',
			value			: 'XXXXXXXXX',
			parent			: li
		})

		input.autocomplete = 'new-password'

	return li
}//end get_input_element


