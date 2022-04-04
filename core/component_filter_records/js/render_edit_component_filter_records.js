/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'



/**
* render_edit_component_filter_records
* Manage the components logic and appearance in client side
*/
export const render_edit_component_filter_records = function() {

	return true
};//end render_edit_component_filter_records



/**
* EDIT
* Render node for use in edit
* @return DOM node
*/
render_edit_component_filter_records.prototype.edit = async function(options) {

	const self = this

	// render_level
		const render_level = options.render_level || 'full'

	// content_data
		const content_data = get_content_data(self)
		if (render_level==='content') {
			return content_data
		}

	// buttons
		const buttons = get_buttons(self)

	// ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_edit(self, {
			content_data	: content_data,
			buttons			: buttons
		})
		wrapper.classList.add("with_100")

	// events (delegated)
		add_events(self, wrapper)

	return wrapper
};//end edit



/**
* ADD_EVENTS
*/
const add_events = function(self, wrapper) {

	// update value, subscription to the changes: if the dom input value was changed, observers dom elements will be changed own value with the observable value
		// self.events_tokens.push(
		// 	event_manager.subscribe('update_value_'+self.id, update_value)
		// )
		// function update_value (changed_data) {
		// 	//console.log("-------------- - event update_value changed_data:", changed_data);
		// 	// change the value of the current dom element
		// 	// const changed_node = wrapper.querySelector('input[data-key="'+changed_data.key+'"]')
		// 	//changed_node.value = changed_data.value.join(',')
		// }

	// change event, for every change the value in the imputs of the component
		wrapper.addEventListener('change', async (e) => {
			// e.stopPropagation()

			// update
			if (e.target.matches('input[type="text"].input_value')) {

				const section_tipo 	= e.target.dataset.tipo
				const key   		= JSON.parse(e.target.dataset.key)
				const value 		= (e.target.value.length>0)
					? {
						tipo 	: e.target.dataset.tipo,
						value 	: self.validate_value(e.target.value.split(','))
					  }
					: null;

				// key_found. search section tipo key if exists. Remember: data array keys are differents that inputs keys
					const current_values = self.data.value || []
					const values_length	 = current_values.length
					let key_found 		 = values_length // default is last (length of arary)
					for (let i = 0; i < values_length; i++) {
						if(current_values[i].tipo===section_tipo) {
							key_found = i;
							break;
						}
					}

				const changed_data = Object.freeze({
					action	: (value===null) ? 'remove' : 'update',
					key		: key_found,
					value	: value
				})
				self.change_value({
					changed_data : changed_data,
					refresh 	 : false
				})
				.then((save_response)=>{
					// update safe value in input text
					if (value) {
						e.target.value = value.value.join(",")
					}
					// event to update the dom elements of the instance
					//event_manager.publish('update_value_'+self.id, changed_data)
				})

				return true
			}
		})

	// click event [click]
		wrapper.addEventListener("click", e => {
			// e.stopPropagation()

			// change_mode
				if (e.target.matches('.button.close')) {

					//change mode
					self.change_mode('list', false)

					return true
				}
		})

	// keyup event
		wrapper.addEventListener("keyup", async (e) => {
			// e.stopPropagation()

			return true
		})


	return true
};//end add_events



/**
* GET_CONTENT_DATA
* @return DOM node content_data
*/
const get_content_data = function(self) {

	// const value			= self.data.value
	const datalist			= self.data.datalist
	const datalist_length	= datalist.length
	// const mode			= self.mode
	// const is_inside_tool	= self.is_inside_tool

	const fragment = new DocumentFragment()

	// inputs
		const inputs_container = ui.create_dom_element({
			element_type	: 'ul',
			class_name		: 'inputs_container',
			parent			: fragment
		})

		// header
			const header_li = ui.create_dom_element({
				element_type	: 'li',
				class_name		: 'header_row',
				parent			: inputs_container
			})
			const header_tipo = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'tipo',
				inner_html		: get_label.tipo || 'Tipo',
				parent			: header_li
			})
			const header_label = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'label',
				inner_html		: get_label.seccion || 'Section',
				parent			: header_li
			})
			const header_value = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'value',
				inner_html		: get_label.valor || 'Value',
				parent			: header_li
			})

		// render all items sequentially
			for (let i = 0; i < datalist_length; i++) {

				const datalist_item = datalist[i];

				// input
					const input_element = get_input_element(i, datalist_item, self)
					inputs_container.appendChild(input_element)
			}

		// realocate rendered dom items
			const nodes_lenght = inputs_container.childNodes.length
			// iterate in reverse order to avoid problems on move nodes
			for (let i = nodes_lenght - 1; i >= 0; i--) {

				const item = inputs_container.childNodes[i]
				if (item.dataset.parent) {
					//const parent_id = datalist_item.parent.section_tipo +'_'+ datalist_item.parent.section_id
					const current_parent = inputs_container.querySelector("[data-id='"+item.dataset.parent+"']")
					if (current_parent) {
						current_parent.appendChild(item)
					}
				}
			}

	// content_data
		const content_data = ui.component.build_content_data(self)
			  content_data.classList.add("nowrap")
			  content_data.appendChild(fragment)


	return content_data
};//end get_content_data



/**
* GET_BUTTONS
* @param object instance
* @return DOM node buttons_container
*/
const get_buttons = (self) => {

	const is_inside_tool= self.is_inside_tool
	const mode 			= self.mode

	const fragment = new DocumentFragment()

	// buttons tools
		if (!is_inside_tool) {
			ui.add_tools(self, fragment)
		}

	// buttons container
		const buttons_container = ui.component.build_buttons_container(self)
		buttons_container.appendChild(fragment)


	return buttons_container
};//end get_buttons



/**
* GET_INPUT_ELEMENT
* @param int i
* 	Value array current key
* @param object datalist_item
* {"label":"label","tipo":"rsc23","permissions":2}
* @return DOM node li
*/
const get_input_element = (i, datalist_item) => {

	// create li
		const li = ui.create_dom_element({
			element_type	: 'li',
			class_name		: 'body_row'
		})

	// tipo
		const tipo	= datalist_item.tipo
		ui.create_dom_element({
			element_type	: 'span',
			inner_html		: tipo,
			parent			: li
		})

	// label
		const label	= datalist_item.label
		ui.create_dom_element({
			element_type	: 'span',
			inner_html		: label,
			parent			: li
		})

	// input field
		const data					= self.data || {}
		const value					= data.value || []
		const item					= value.find(item => item.tipo===tipo)
		const input_value_string	= typeof item!=="undefined" ? item.value.join(',') : ''
		ui.create_dom_element({
			element_type	: 'input',
			type			: 'text',
			class_name		: 'input_value',
			dataset			: { key : i, tipo : tipo },
			value			: input_value_string,
			placeholder		: "Comma separated id like 1,2,3",
			parent			: li
		})
		//input.pattern = "[0-9]"
		//input.setAttribute("pattern", "[0-9,]{1,1000}")


	return li
};//end get_input_element


