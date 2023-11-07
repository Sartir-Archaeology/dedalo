// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_TOOLS_URL */
/*eslint no-undef: "error"*/



// import
	// import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'
	import {strip_tags} from '../../common/js/utils/index.js'
	import {get_buttons} from './render_edit_component_check_box.js'


/**
* VIEW_TOOLS_EDIT_CHECK_BOX
* Manage the components logic and appearance in client side
*/
export const view_tools_edit_check_box = function() {

	return true
}//end view_tools_edit_check_box



/**
* RENDER
* Render node for use in edit
* @return HTMLElement
*/
view_tools_edit_check_box.render = async function(self, options) {

	// render_level
		const render_level = options.render_level || 'full'

	// content_data
		const content_data = get_content_data(self)
		if (render_level==='content') {
			return content_data
		}

	// buttons
		const buttons = (self.permissions > 1)
			? get_buttons(self)
			: null

	// ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_edit(self, {
			content_data	: content_data,
			buttons			: buttons
		})
		wrapper.classList.add('view_'+self.context.view)
		// set pointers
		wrapper.content_data = content_data


	return wrapper
}//end render



/**
* GET_CONTENT_DATA
* Render content_data node with all included contents
* @param instance object self
* @return HTMLElement content_data
*/
const get_content_data = function(self) {

	// short vars
		const datalist = self.data.datalist || []

	// content_data
		const content_data = ui.component.build_content_data(self, {
			autoload : true
		})
		content_data.classList.add('nowrap')

	// build options
		const datalist_length = datalist.length
		for (let i = 0; i < datalist_length; i++) {

			const datalist_item = datalist[i]

			// do not render tool always_active, they are for all users and profiles
			// if(datalist_item.always_active){
				// continue
			// }
			const input_element_node = get_input_element(i, datalist_item, self)
			content_data.appendChild(input_element_node)
			// set the pointer
			content_data[i] = input_element_node
		}


	return content_data
}//end get_content_data



/**
* GET_INPUT_ELEMENT
* @return HTMLElement content_value
*/
const get_input_element = (i, current_value, self) => {

	// short vars
		const data				= self.data || {}
		const value				= data.value || []
		const value_length		= value.length
		const datalist_item		= current_value
		const datalist_value	= datalist_item.value
		const section_id		= datalist_item.section_id
		const tool_name			= datalist_item.tool_name
		const label				= datalist_item.label

	// create content_value
		const content_value = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_value'
		})

	// label
		const option_label	= ui.create_dom_element({
			element_type	: 'label',
			class_name		: 'input_label',
			inner_html		: '<span>'+label+'</span>',
			parent			: content_value
		})

	// input checkbox
		const input_checkbox = ui.create_dom_element({
			element_type	: 'input',
			type			: 'checkbox',
			class_name		: 'input_checkbox',
		})
		option_label.prepend(input_checkbox)
		input_checkbox.addEventListener('focus', function() {
			// force activate on input focus (tabulating case)
			if (!self.active) {
				ui.component.activate(self)
			}
		})
		input_checkbox.addEventListener('change', function(e) {

			self.change_handler({
				self			: self,
				e				: e,
				i				: i,
				datalist_value	: datalist_value,
				input_checkbox	: input_checkbox
			})
		})//end change event
		// checked input_checkbox set on match
		for (let j = 0; j < value_length; j++) {
			if (value[j] && datalist_value &&
				value[j].section_id===datalist_value.section_id &&
				value[j].section_tipo===datalist_value.section_tipo
				) {
					input_checkbox.checked = 'checked'
			}
		}

	// do not render tool always_active, they are for all users and profiles
		if(datalist_item.always_active){
			// input_checkbox.checked = 'checked'
			input_checkbox.disabled = true
			option_label.innerHTML += ' (* always_active)'
		}

	// developer_info
		if(SHOW_DEBUG===true){
			ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'developer_info show_on_active',
				text_content	: `[${tool_name} - ${section_id}]`,
				parent			: content_value
			})
		}


	// tool_icon
		const icon_url	= DEDALO_TOOLS_URL + '/' + tool_name + '/img/icon.svg'
		const tool_icon	= ui.create_dom_element({
			element_type	: 'img',
			class_name		: 'tool_icon',
			src				: icon_url
		})
		content_value.prepend(tool_icon)


	return content_value
}//end get_input_element



// @license-end
