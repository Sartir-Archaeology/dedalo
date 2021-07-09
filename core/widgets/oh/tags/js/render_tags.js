/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../../../common/js/ui.js'
	import {event_manager} from '../../../../common/js/event_manager.js'



/**
* RENDER_GET_ARCHIVE_WEIGHTS
* Manages the component's logic and apperance in client side
*/
export const render_tags= function() {

	return true
}//end render_tags



/**
* EDIT
* Render node for use in modes: edit, edit_in_list
* @return DOM node wrapper
*/
render_tags.prototype.edit = async function(options) {

	const self = this

	const render_level = options.render_level

	// content_data
		const content_data = await get_content_data_edit(self)
		if (render_level==='content') {
			return content_data
		}

	// wrapper. ui build_edit returns widget wrapper
		const wrapper = ui.widget.build_wrapper_edit(self, {
			content_data : content_data
		})


	return wrapper
}//end edit



/**
* GET_CONTENT_DATA_EDIT
* @return DOM node content_data
*/
const get_content_data_edit = async function(self) {

	if (!self.value || self.value.lenght<1) {
		console.warn("tags get_content_data_edit. Value is empty!", self);
	}

	const fragment = new DocumentFragment()

	// values container
		const values_container = ui.create_dom_element({
			element_type	: 'ul',
			class_name		: 'values_container',
			parent			: fragment
		})

	// values
		const ipo			= self.ipo
		const ipo_length	= ipo.length

		for (let i = 0; i < ipo_length; i++) {
			const data = self.value.filter(item => item.key === i)
			get_value_element(i, data , values_container, self)
		}

	// content_data
		const content_data = ui.create_dom_element({
			element_type : 'div'
		})
		content_data.appendChild(fragment)


	return content_data
}//end get_content_data_edit



/**
* GET_VALUE_ELEMENT
* @return DOM node li
*/
const get_value_element = (i, data, values_container, self) => {

	// li, for every ipo will create a li node
		const li = ui.create_dom_element({
			element_type	: 'li',
			class			: 'get_archive_weights',
			parent			: values_container
		})

	// reactive (Will be updated on every called event)
		const reactive_items = []

	// total_tc		
		const total_tc = item_value_factory('total_tc', 'TC', data)
		li.appendChild(total_tc)
		reactive_items.push(total_tc)

	// ar_tc_wrong		
		const ar_tc_wrong = item_value_factory('ar_tc_wrong', get_label.etiqueta_revisar, data)
		li.appendChild(ar_tc_wrong)
		reactive_items.push(ar_tc_wrong)

	// total_index		
		const total_index = item_value_factory('total_index', 'INDEX', data)
		li.appendChild(total_index)
		reactive_items.push(total_index)

	// total_missing_tags		
		const total_missing_tags = item_value_factory('total_missing_tags', (get_label.etiquetas_borradas || 'Removed tags'), data)
		li.appendChild(total_missing_tags)
		reactive_items.push(total_missing_tags)

	// total_to_review_tags		
		const total_to_review_tags = item_value_factory('total_to_review_tags', get_label.etiqueta_revisar, data)
		li.appendChild(total_to_review_tags)
		reactive_items.push(total_to_review_tags)

	// total_private_notes		
		const total_private_notes = item_value_factory('total_private_notes', 'Work NOTES', data)
		li.appendChild(total_private_notes)
		reactive_items.push(total_private_notes)

	// total_public_notes		
		const total_public_notes = item_value_factory('total_public_notes', 'Public NOTES', data)
		li.appendChild(total_public_notes)
		reactive_items.push(total_public_notes)

	// total_chars		
		const total_chars = item_value_factory('total_chars', 'CHARS', data)
		li.appendChild(total_chars)
		reactive_items.push(total_chars)

	// total_chars_no_spaces		
		const total_chars_no_spaces = item_value_factory('total_chars_no_spaces', 'NO SPACES', data)
		li.appendChild(total_chars_no_spaces)
		reactive_items.push(total_chars_no_spaces)

	// total_real_chars		
		const total_real_chars = item_value_factory('total_real_chars', 'CHARS REAL', data)
		li.appendChild(total_real_chars)
		reactive_items.push(total_real_chars)


	// update the values when the observable was changed
		self.events_tokens.push(
			event_manager.subscribe('update_widget_value_'+i+'_'+self.id, (changed_data) =>{

				function get_value_from_data(id) {				
					const found = changed_data.find(el => el.id===id)
					const value = found
						? found.value
						: ''
					return value;
				}

				// update reactive items value
				for (let i = 0; i < reactive_items.length; i++) {
					reactive_items[i].value.innerHTML = get_value_from_data(reactive_items[i].id)
				}
			})
		)

	return li
}//end get_value_element



/**
* ITEM_VALUE_FACTORY
* Build a DOM structure with wraper, label and value
* @param string id (like 'total_real_chars')
* @param string label (like 'CHARS REAL')
* @param array data
* @return DOM node wrapper
* 	ready to update using wrapper.value.innerHTML = 'my value'
*/
const item_value_factory = function(id, label, data) {

	const wrapper = ui.create_dom_element({
		element_type	: 'div',
		class_name		: id
	})
	// label
		ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'label',
			inner_html		: label + ' :',
			parent			: wrapper
		})
	// value
		const found			= data.find(item => item.id===id)
		const current_value	= found
			? found.value
			: ''
		
		const value_node = ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'value',
			inner_html		: current_value,
			parent			: wrapper
		})

	wrapper.value	= value_node
	wrapper.id		= id

	return wrapper
}//end item_value_factory


