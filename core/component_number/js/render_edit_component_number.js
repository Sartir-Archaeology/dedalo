// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/* global get_label, page_globals, SHOW_DEBUG, flatpickr */
/*eslint no-undef: "error"*/



// imports
	import {set_before_unload} from '../../common/js/events.js'
	import {ui} from '../../common/js/ui.js'
	import {view_default_edit_number} from './view_default_edit_number.js'
	import {view_line_edit_number} from './view_line_edit_number.js'
	import {view_mini_number} from './view_mini_number.js'



/**
* RENDER_EDIT_COMPONENT_NUMBER
* Manage the components logic and appearance in client side
*/
export const render_edit_component_number = function() {

	return true
}//end render_edit_component_number



/**
* EDIT
* Render node for use in edit
* @param object options
* @return HTMLElement wrapper
*/
render_edit_component_number.prototype.edit = async function(options) {

	const self = this

	// view
		const view = self.context.view || 'default'

	switch(view) {

		case 'line':
			return view_line_edit_number.render(self, options)

		case 'mini':
			return view_mini_number.render(self, options)

		case 'print':
			// view print use the same view as default, except it will use read only to render content_value
			// as different view as default it will set in the class of the wrapper
			// sample: <div class="wrapper_component component_input_text oh14 oh1_oh14 edit view_print disabled_component">...</div>
			// take account that to change the css when the component will render in print context
			// for print we need to use read of the content_value and it's necessary force permissions to use read only element render
			self.permissions = 1

		case 'default':
		default:
			return view_default_edit_number.render(self, options)
	}
}//end edit



/**
* GET_CONTENT_DATA
* @param object self
* @return HTMLElement content_data
*/
export const get_content_data = function(self) {

	// short vars
		const data	= self.data || {}
		const value	= data.value || []

	// content_data
		const content_data = ui.component.build_content_data(self)

	// build values
		const value_length = value.length || 1
		for (let i = 0; i < value_length; i++) {
			const content_value_node = (self.permissions===1)
				? get_content_value_read(i, value[i], self)
				: get_content_value(i, value[i], self)
			content_data.appendChild(content_value_node)
			// set pointers
			content_data[i] = content_value_node
		}


	return content_data
}//end get_content_data



/**
* GET_CONTENT_VALUE
* @param int i
* @param int|null current_value
* @param object self
* @return HTMLElement content_value
*/
const get_content_value = (i, current_value, self) => {

	// content_value
		const content_value = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_value'
		})

	// input field
		const input = ui.create_dom_element({
			element_type	: 'input',
			type			: 'text',
			class_name		: 'input_value',
			value			: current_value,
			parent			: content_value
		})
		input.step = self.get_steps()
		// focus event
			input.addEventListener('focus', function() {
				// force activate on input focus (tabulating case)
				if (!self.active) {
					ui.component.activate(self, false)
				}
			})
		// keyup event
			input.addEventListener('keyup', function(e) {
				// page unload event
				keyup_handler(e, i, self)
			})//end keyup
		// blur event
			input.addEventListener('blur', function(e) {
				// saves changed data
				blur_handler(e, i, self)
			})//end blur
		// click event. Capture event propagation
			input.addEventListener('click', (e) => {
				e.stopPropagation()
			})
		// mousedown event. Capture event propagation
			input.addEventListener('mousedown', (e) => {
				e.stopPropagation()
			})

	// button remove
		const mode				= self.mode
		const is_inside_tool	= self.is_inside_tool
		if((mode==='edit') && !is_inside_tool) {

			const button_remove = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'button remove hidden_button',
				parent			: content_value
			})
			button_remove.addEventListener('mouseup', fn_remove_mouseup)
			function fn_remove_mouseup() {
				// force possible input change before remove
				document.activeElement.blur()

				const current_value = input.value ? input.value : null

				const changed_data = [Object.freeze({
					action	: 'remove',
					key		: i,
					value	: null
				})]
				self.change_value({
					changed_data	: changed_data,
					label			: current_value,
					refresh			: true
				})
			}//end fn_remove_mouseup
		}//end if((mode==='edit') && !is_inside_tool)


	return content_value
}//end get_content_value



/**
* GET_CONTENT_VALUE_READ
* @param int i
* @param int|null current_value
* @param object self
* @return HTMLElement content_value
*/
const get_content_value_read = (i, current_value, self) => {

	// content_value
		const content_value = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_value read_only',
			inner_html		: current_value
		})


	return content_value
}//end get_content_value_read



/**
* GET_BUTTONS
* @param object instance
* @return HTMLElement buttons_container
*/
export const get_buttons = (self) => {

	// short vars
		const is_inside_tool	= self.is_inside_tool
		const mode				= self.mode

	// DOM fragment
		const fragment = new DocumentFragment()

	// button add input
		if(!is_inside_tool) {

			const button_add_input = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'button add',
				parent			: fragment
			})
			button_add_input.addEventListener('mouseup', function() {

				const changed_data = [Object.freeze({
					action	: 'insert',
					key		: self.data.value.length,
					value	: null
				})]
				self.change_value({
					changed_data	: changed_data,
					refresh			: true
				})
				.then(()=>{
					const input_node = self.node.content_data[changed_data.key].querySelector('input')
					if (input_node) {
						input_node.focus()
					}
				})
			})
		}

	// buttons tools
		if( self.show_interface.tools === true){
			if (!is_inside_tool && mode==='edit') {
				ui.add_tools(self, fragment)
			}
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
}//end get_buttons



/**
* KEYUP_HANDLER
* Store current value in self.data.changed_data
* If key pressed is 'Enter', force save the value
* @param event e
* @param int key
* @param object self
* @return bool
*/
export const keyup_handler = function(e, key, self) {
	e.preventDefault()

	// when tab is pressed the node and self is the target component,
	// so the change data is not for the source component and user has enter (and don't changed nothing)
		if (e.key==='Tab' || e.key==='Shift') {
			return false
		}

	// Enter key force to save changes as the same way that blur
		if (e.key==='Enter') {
			blur_handler(e, key, self)
		}

	return true
}//end keyup_handler



/**
* BLUR_HANDLER
* Store current value in self.data.changed_data
* @param event e
* @param int key
* @param object self
* @return bool
*/
export const blur_handler = function(e, key, self) {
	e.preventDefault()

	// fix value to valid format as '5.21' from '5,21'
	const safe_value = (e.target.value.length>0)
		? self.fix_number_format(e.target.value)
		: null

	// if the safe_value is different than the value of user had enter set the safe_value
		if(safe_value!=e.target.value) {
			e.target.value = safe_value
		}

	// change data
		const changed_data_item = Object.freeze({
			action	: 'update',
			key		: key,
			value	: safe_value
		})

	// fix instance changed_data
		self.set_changed_data(changed_data_item)

	// force to save current input if changed
		const changed_data = self.data.changed_data || []
		// change_value (save data)
		self.change_value({
			changed_data	: changed_data,
			refresh			: false
		})

	return true
}//end blur_handler



/**
* REMOVE_HANDLER
* Handle button remove actions
* @param DOM  node input
* @param int key
* @param object self
* @return promise response
*/
export const remove_handler = function(input, key, self) {

	// force possible input change before remove
		document.activeElement.blur()

	// value
		const current_value = input.value ? input.value : null

	// changed_data
		const changed_data = [Object.freeze({
			action	: 'remove',
			key		: key,
			value	: null
		})]

	// change_value. Returns a promise that is resolved on api response is done
		const response = self.change_value({
			changed_data	: changed_data,
			label			: current_value,
			refresh			: true
		})


	return response
}//end remove_handler



// @license-end
