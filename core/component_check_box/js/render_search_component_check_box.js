/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// import
	import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'



/**
* render_search_component_check_box
* Manage the components logic and appearance in client side
*/
export const render_search_component_check_box = function() {

	return true
}//end render_search_component_check_box



/**
* SEARCH
* Render node for use in search
* @return DOM node wrapper
*/
render_search_component_check_box.prototype.search = async function() {

	const self = this

	const content_data = get_content_data(self)

	// ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_search(self, {
			content_data : content_data
		})
		// set pointers
		wrapper.content_data = content_data


	return wrapper
}//end search



/**
* GET_CONTENT_DATA
* @param instance self
* @return DOM node content_data
*/
const get_content_data = function(self) {

	// short vars
		const datalist	= self.data.datalist || []

	// content_data
		const content_data = ui.component.build_content_data(self, {
			autoload : false
		})

	// q operator (search only)
		const q_operator = self.data.q_operator
		const input_q_operator = ui.create_dom_element({
			element_type	: 'input',
			type			: 'text',
			value			: q_operator,
			class_name		: 'q_operator',
			parent			: content_data
		})
		input_q_operator.addEventListener('change',function() {
			// value
				const value = (input_q_operator.value.length>0) ? input_q_operator.value : null
			// q_operator. Fix the data in the instance previous to save
				self.data.q_operator = value
			// publish search. Event to update the dom elements of the instance
				event_manager.publish('change_search_element', self)
		})

	// values (inputs)
		const datalist_length = datalist.length
		for (let i = 0; i < datalist_length; i++) {
			const input_element_node = get_input_element(i, datalist[i], self)
			content_data.appendChild(input_element_node)
			// set the pointer
			content_data[i] = input_element_node
		}


	return content_data
}//end get_content_data



/**
* GET_INPUT_ELEMENT
* Render a input element based on passed value
* @param int i
* 	data.value array key
* @param object current_value
* @param object self
*
* @return DOM node content_value
*/
const get_input_element = (i, current_value, self) => {

	// short vars
		const value				= self.data.value || []
		const value_length		= value.length
		const datalist_item		= current_value // is object as {label, section_id, value}
		const datalist_value	= datalist_item.value // is locator like {section_id:"1",section_tipo:"dd174"}
		const label				= datalist_item.label
		const section_id		= datalist_item.section_id

	// create content_value
		const content_value = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_value'
		})

	// label
		// const label_string = (SHOW_DEBUG===true) ? label + " [" + section_id + "]" : label
		const option_label = ui.create_dom_element({
			element_type	: 'label',
			inner_html		: label,
			parent			: content_value
		})

	// input checkbox
		const input_checkbox = ui.create_dom_element({
			element_type	: 'input',
			type			: 'checkbox'
		})
		option_label.prepend(input_checkbox)
		input_checkbox.addEventListener('change', function() {

			const action		= (input_checkbox.checked===true) ? 'insert' : 'remove'
			const changed_key	= self.get_changed_key(action, datalist_value) // find the data.value key (could be different of datalist key)
			const changed_value	= (action==='insert') ? datalist_value : null

			const changed_data_item = Object.freeze({
				action	: action,
				key		: changed_key,
				value	: changed_value
			})

			// update the instance data (previous to save)
				self.update_data_value(changed_data_item)
			// set data.changed_data. The change_data to the instance
				// self.data.changed_data = changed_data
			// publish search. Event to update the dom elements of the instance
				event_manager.publish('change_search_element', self)
		})//end change event

		// checked option set on match
			for (let j = 0; j < value_length; j++) {
				if (value[j] && datalist_value &&
					value[j].section_id===datalist_value.section_id &&
					value[j].section_tipo===datalist_value.section_tipo
					) {
						input_checkbox.checked = 'checked'
				}
			}


	return content_value
}//end get_input_element
