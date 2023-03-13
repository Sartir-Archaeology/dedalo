/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../common/js/ui.js'
	import {event_manager} from '../../common/js/event_manager.js'
	import {view_default_edit_publication} from './view_default_edit_publication.js'
	import {view_line_edit_publication} from './view_line_edit_publication.js'



/**
* RENDER_EDIT_COMPONENT_PUBLICATION
* Manage the components logic and appearance in client side
*/
export const render_edit_component_publication = function() {

	return true
}//end render_edit_component_publication



/**
* EDIT
* Render node for use in edit mode
* @param object options
* @return HTMLElement|null
*/
render_edit_component_publication.prototype.edit = async function(options) {

	const self = this

	// view
		const view = self.context.view || 'default'

	switch(view) {

		case 'line':
			return view_line_edit_publication.render(self, options)

		case 'print':
			// view print use the same view as default, except it will use read only to render content_value
			// as different view as default it will set in the class of the wrapper
			// sample: <div class="wrapper_component component_publication oh32 oh1_oh32 edit view_print disabled_component">...</div>
			// take account that to change the css when the component will render in print context
			// for print we need to use read of the content_value and it's necessary force permissions to use read only element render
			self.permissions = 1

		case 'default':
		default:
			return view_default_edit_publication.render(self, options)
	}

	return null
}//end edit



/**
* GET_CONTENT_DATA
* @param object self
* @return HTMLElement content_data
*/
export const get_content_data = function(self) {

	// short vars
		const value = self.data.value || []

	// content_data
		const content_data = ui.component.build_content_data(self, {
			button_close : null // set to null to prevent it from being created
		})
		content_data.classList.add('nowrap')

	// build values
		const inputs_value	= (value.length<1) ? [''] : value
		const value_length	= inputs_value.length
		for (let i = 0; i < value_length; i++) {
			// get the content_value
			const content_value = (self.permissions===1)
				? get_content_value_read(i, inputs_value[i], self)
				: get_content_value(i, inputs_value[i], self)
			// add node to content_data
			content_data.appendChild(content_value)
			// set the pointer
			content_data[i] = content_value
		}


	return content_data
}//end get_content_data



/**
* GET_CONTENT_VALUE
* Render the current value HTMLElements
* @param int i
* 	Value key
* @param object current_value
* 	Current locator value as:
* 	{type: 'dd151', section_id: '1', section_tipo: 'dd64', from_component_tipo: 'rsc20'}
* @param object self
* @return HTMLElement content_value
*/
const get_content_value = (i, current_value, self) => {

	// content_value
		const content_value = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_value'
		})

	// div_switcher
		const div_switcher = ui.create_dom_element({
			element_type	: 'label',
			class_name		: 'switcher_publication text_unselectable',
			parent			: content_value
		})

	// input checkbox
		const input = ui.create_dom_element({
			element_type	: 'input',
			type			: 'checkbox',
			value			: JSON.stringify(current_value),
			parent			: div_switcher
		})
		input.addEventListener('change', function() {

			const checked		= input.checked
			const changed_value	= (checked===true)
				? self.data.datalist.filter(item => item.section_id==1)[0].value
				: self.data.datalist.filter(item => item.section_id==2)[0].value

			const changed_data = [Object.freeze({
				action	: 'update',
				key		: i,
				value	: changed_value
			})]
			self.change_value({
				changed_data	: changed_data,
				refresh			: false
			})
			.then(()=>{
			// publish the publication locator value. (ex: used to change state of notes tag)
				event_manager.publish('change_publication_value_'+self.id_base, changed_value)
			})
		})
		// set checked from current value
		if (current_value.section_id==1) {
			input.setAttribute('checked', true)
		}

	// switch_label
		ui.create_dom_element({
			element_type	: 'i',
			parent			: div_switcher
		})


	return content_value
}//end get_content_value



/**
* GET_CONTENT_VALUE_READ
* Render the current value HTMLElements
* @param int i
* 	Value key
* @param object current_value
* 	Current locator value as:
* 	{type: 'dd151', section_id: '1', section_tipo: 'dd64', from_component_tipo: 'rsc20'}
* @param object self
*
* @return HTMLElement content_value
*/
const get_content_value_read = (i, current_value, self) => {

	// get current datalist item that match with current_value to get the label to show it
		const datalist_item = self.data.datalist.find(item => item.section_id==current_value.section_id)

	// content_value
		const content_value = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_value read_only',
			inner_html 		: datalist_item.label || ''
		})

	return content_value
}//end get_content_value_read



/**
* GET_BUTTONS
* @param object instance
* @return HTMLElement buttons_container
*/
export const get_buttons = (self) => {

	// const is_inside_tool	= self.is_inside_tool
	const is_inside_tool	= self.caller && self.caller.type==='tool'
	const mode				= self.mode

	const fragment = new DocumentFragment()

	// buttons tools
		if( self.show_interface.tools === true){
			if (!is_inside_tool && mode==='edit') {
				ui.add_tools(self, fragment)
			}
		}

	// buttons container
		const buttons_container = ui.component.build_buttons_container(self)
		buttons_container.appendChild(fragment)


	return buttons_container
}//end get_buttons
