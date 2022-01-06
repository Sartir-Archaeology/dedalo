/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'
	import {data_manager} from '../../common/js/data_manager.js'



/**
* RENDER_EDIT_COMPONENT_SECURITY_ACCESS
* Manages the component's logic and apperance in client side
*/
export const render_edit_component_security_access = function() {

	return true
};//end render_edit_component_security_access



/**
* EDIT
* Render node for use in modes: edit, edit_in_list
* @return DOM node wrapper
*/
render_edit_component_security_access.prototype.edit = async function(options={render_level:'full'}) {

	const self = this

	// fix non value scenarios
		self.data.value = (self.data.value.length<1) ? [null] : self.data.value

	// render_level
		const render_level = options.render_level

	// content_data
		const content_data = await get_content_data_edit(self)
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
			event_manager.subscribe('update_value_'+self.id, update_value)
		)
		function update_value (changed_data) {
			//console.log("-------------- - event update_value changed_data:", changed_data);
			// change the value of the current dom element
			const changed_node = wrapper.querySelector('input[data-key="'+changed_data.key+'"]')
			changed_node.value = changed_data.value
		}

	// click event [mousedown]
		// wrapper.addEventListener("mousedown", e => {
		// 	e.stopPropagation()
		// 	// change_mode
		// 		if (e.target.matches('.button.close')) {

		// 			//change mode
		// 			self.change_mode('list', false)

		// 			return true
		// 		}
		// })


	return true
};//end add_events



/**
* get_CONTENT_DATA_EDIT
* @return DOM node content_data
*/
const get_content_data_edit = async function(self) {

	const value				= self.data.value
	const datalist			= self.data.datalist
	// const mode			= self.mode
	// const is_inside_tool	= self.is_inside_tool

	const fragment = new DocumentFragment()

	level_hierarchy({
		datalist 		: datalist,
		value 			: value,
		ul_container 	: fragment,
		parent_tipo		: 'dd1'
	})


	// content_data
		const content_data = ui.component.build_content_data(self)
			  content_data.classList.add("nowrap")
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
* INPUT_ELEMENT
* @return dom element li
*/
	// const input_element = (i, current_value, inputs_container, self, is_inside_tool) => {

	// 	const mode = self.mode

	// 	// li
	// 		const li = ui.create_dom_element({
	// 			element_type : 'li',
	// 			parent 		 : inputs_container
	// 		})

	// 	// q operator (search only)
	// 		if(mode==='search'){
	// 			const q_operator = self.data.q_operator
	// 			const input_q_operator = ui.create_dom_element({
	// 				element_type 	: 'input',
	// 				type 		 	: 'text',
	// 				value 		 	: q_operator,
	// 				class_name 		: 'q_operator',
	// 				parent 		 	: li
	// 			})
	// 		}

	// 	// input field
	// 		const input = ui.create_dom_element({
	// 			element_type 	: 'input',
	// 			type 		 	: 'text',
	// 			class_name 		: 'input_value',
	// 			dataset 	 	: { key : i },
	// 			value 		 	: current_value,
	// 			parent 		 	: li
	// 		})

	// 	// button remove
	// 		if((mode==='edit' || 'edit_in_list') && !is_inside_tool){
	// 			const button_remove = ui.create_dom_element({
	// 				element_type	: 'div',
	// 				class_name 		: 'button remove display_none',
	// 				dataset			: { key : i },
	// 				parent 			: li
	// 			})
	// 		}


	// 	return li
	// };//end input_element



/**
* LEVEL HIERARCHY
* @return bool
*/
const level_hierarchy = async (options) => {

	// options
		const datalist		= options.datalist
		const value			= options.value
		const ul_container	= options.ul_container
		const parent_tipo	= options.parent_tipo

	const root_areas = datalist.filter(item => item.parent === parent_tipo)

	// inputs container
		const inputs_container = ui.create_dom_element({
			element_type	: 'ul',
			class_name 		: 'inputs_container',
			parent 			: ul_container
		})

	// values (inputs)
		const root_areas_length = root_areas.length
		for (let i = 0; i < root_areas_length; i++) {
			item_hierarchy({
				datalist		: datalist,
				value			: value,
				ul_container	: inputs_container,
				item			: root_areas[i]
			})
		}

	return true
}//end level_hierarchy



/**
* ITEM_HIERARCHY
* @param object options
* @return dom element li
*/
const item_hierarchy = async (options) => {

	// options
		const datalist		= options.datalist
		const value			= options.value
		const ul_container	= options.ul_container
		const item			= options.item

	// children_item
	const children_item = datalist.find(el => el.parent === item.tipo)

	// get the item value
	const item_value = value.find(el => el.tipo === item.tipo)

	// const datalist_value ={
	// 	"tipo"		: item.tipo,
	// 	"type"		: "area",
	// 	"value"		: 2,
	// 	"parent"	: item.tipo
	// }

	// li
		const li = ui.create_dom_element({
			element_type	: 'li',
			parent			: ul_container
		})

	// input field
		const input = ui.create_dom_element({
			element_type	: 'input',
			type			: 'checkbox',
			class_name		: 'input_value',
			dataset			: { key : item.tipo },
			//value			: datalist_value,
			parent			: li
		})

		// checked option set on match
		if (typeof item_value !=='undefined') {
			item_value.value== 2 ? input.indeterminate = true :	input.checked = true
		}

		input.addEventListener("change", e => {
			e.stopPropagation()
			parents_node(li, input.checked)
		})


	// label
		const label = ui.create_dom_element({
			element_type	: 'label',
			class_name		: 'area_label',
			inner_html		: item.label,
			parent			: li
		})

	// button_add_input
		if (children_item) {
			const button_add_input = ui.create_dom_element({
				element_type	: 'span',
				class_name 		: 'button add',
				parent 			: li
			})
			button_add_input.addEventListener("mousedown", e => {
				e.stopPropagation()

				if(button_add_input.classList.contains('open')){

					button_add_input.classList.remove ('open')
					li.removeChild(li.querySelector('ul'))

				}else{
					button_add_input.classList.add ('open')
					level_hierarchy({
						datalist		: datalist,
						value			: value,
						ul_container	: li,
						parent_tipo		: item.tipo
					})
				}
			})
		}//end if (children_item)

	// button_section
		if(item.model==='section') {

			const button_section = ui.create_dom_element({
				element_type	: 'span',
				class_name 		: 'button close',
				parent 			: li
			})
			button_section.addEventListener("mouseup", async (e) => {
				e.stopPropagation()
				// data_manager
					const current_data_manager = new data_manager()

					const api_response = await current_data_manager.request({
						body : {
							action 		: 'ontology_get_children_recursive',
							target_tipo : item.tipo
						}
					})


				// render the new items
					const new_datalist = datalist.concat(api_response.result)
					level_hierarchy({
						datalist		: new_datalist,
						value			: value,
						ul_container	: li,
						parent_tipo		: item.tipo
					})
			})
		}//end if(item.model==='section')

	return li
};//end item_hierarchy



/**
* PARENTS_NODE
* @return bool
*/
const parents_node = async(child_node, checked) => {

	if(checked===false){
		return
	}

	const parent_node = child_node.parentNode.parentNode
	if(parent_node.classList.contains('content_data')) {

		return true

	}else{

		const input_node = parent_node.querySelector('.input_value')
		if(input_node) {
			input_node.checked = checked
		}

		parents_node(parent_node, checked)
	}

	return true
};//end parents_node


