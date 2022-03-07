/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'



/**
* RENDER_EDIT_COMPONENT_FILTER
* Manage the components logic and appearance in client side
*/
export const render_edit_component_filter = function() {

	return true
};//end render_edit_component_filter



/**
* EDIT
* Render node for use in edit
* @return DOM node
*/
render_edit_component_filter.prototype.edit = async function(options={render_level:'full'}) {

	const self = this

	// render_level
		const render_level 	= options.render_level

	const value		= self.data.value || []
	const datalist	= self.data.datalist || []

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

	// events (delegated)
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
		function update_value (component) {
			// change the value of the current dom element
			const changed_data = component.data.changed_data
			const changed_node = wrapper.querySelector('input[data-key="'+component.selected_key+'"]')
			changed_node.checked = (changed_data.value === null) ? false : true
		}

	// add button element, subscription to the events
		self.events_tokens.push(
			event_manager.subscribe('edit_element_'+self.id, edit_element)
		)
		function edit_element(component) {
			// change the value of the current dom element
			//const changed_data = component.data.changed_data
			//const inputs_container = wrapper.querySelector('.inputs_container')
			//input_element(changed_data.key, changed_data.value, inputs_container)
		}

	// remove button element, subscription to the events
		self.events_tokens.push(
			event_manager.subscribe('reset_element_'+self.id, reset_element)
		)
		async function reset_element(instance) {
			// change all elements inside of content_data
			const new_content_data = await get_content_data(instance)
			// replace the content_data with the refresh dom elements (imputs, delete buttons, etc)
			wrapper.childNodes[1].replaceWith(new_content_data)
		}

	// change event, for every change the value in the imputs of the component
		wrapper.addEventListener('change', (e) => {
			// e.stopPropagation()

			// update / remove
				if (e.target.matches('input[type="checkbox"]')) {

					const action 		= (e.target.checked===true) ? 'insert' : 'remove'
					const parsed_value 	= JSON.parse(e.target.value)
					const changed_key 	= self.get_changed_key(action, parsed_value)
					const changed_value = (action==='insert') ? parsed_value : null

					const changed_data = Object.freeze({
						action  : action,
						key 	: changed_key,
						value 	: changed_value
					})
					self.change_value({
						changed_data	: changed_data,
						//label			: e.target.nextElementSibling.textContent,
						refresh			: false,
						remove_dialog	: false
					})
					.then((api_response)=>{
						self.selected_key = e.target.dataset.key
						// event to update the dom elements of the instance
						event_manager.publish('update_value_'+self.id, self)
					})

					return true
				}
		})

	// click event
		wrapper.addEventListener("click", e => {
			// e.stopPropagation()

			// remove all
				if (e.target.matches('.button.reset')) {

					if (self.data.value.length===0) {
						return true
					}

					const changed_data = Object.freeze({
						action  : 'remove',
						key 	: false,
						value 	: null
					})
					self.change_value({
						changed_data : changed_data,
						label  		 : 'All',
						refresh 	 : true
					})
					.then((api_response)=>{
						// rebuild and save the component
						// event_manager.publish('reset_element_'+self.id, self)
						// event_manager.publish('save_component_'+self.id, self)
					})

					return true
				}

			// edit target section
				if (e.target.matches('.button.edit')) {

					// rebuild_nodes. event to render the component again
					event_manager.publish('edit_element_'+self.id, self)

					return true
				}

			// change_mode
				if (e.target.matches('.button.close')) {
					//change mode
					self.change_mode('list', true)

					return true
				}
		})

	// dblclick event
		//wrapper.addEventListener("dblclick", function(e){
		//	e.stopPropagation()
		//
		//	if (self.mode==='edit_in_list') {
		//		// change mode (from 'edit_in_list' to 'list')
		//		self.change_mode('list', true)
		//	}
		//})

	// focus event
		wrapper.addEventListener("focus", e => {
			// e.stopPropagation()

			// selected_node. fix selected node
			self.selected_node = wrapper

			if (e.target.matches('input[type="checkbox"]')) {
			 	event_manager.publish('active_component', self)

			 	return true
			}
		},true)

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
			class_name 		: 'inputs_container',
			parent 			: fragment
		})

		// render all items sequentially
			for (let i = 0; i < datalist_length; i++) {

				const datalist_item = datalist[i];

				if (datalist_item.type==='typology') {
					// grouper
					const grouper_element = get_grouper_element(i, datalist_item, self)
					inputs_container.appendChild(grouper_element)
				}else{
					// input
					const input_element = get_input_element(i, datalist_item, self)
					inputs_container.appendChild(input_element)
				}
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

	// button edit
		ui.create_dom_element({
			element_type	: 'span',
			class_name 		: 'button edit',
			parent 			: fragment
		})

	// button reset
		ui.create_dom_element({
			element_type	: 'span',
			class_name 		: 'button reset',
			parent 			: fragment
		})

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
* GET_GROUPER_ELEMENT
*	Typology element
* @return DOM node li
*/
const get_grouper_element = (i, datalist_item, self) => {

	// grouper
		const grouper = ui.create_dom_element({
			element_type	: 'li',
			class_name		: 'grouper',
			data_set		: {
				id		: datalist_item.section_tipo +'_'+ datalist_item.section_id,
				parent	: datalist_item.parent ? (datalist_item.parent.section_tipo +'_'+ datalist_item.parent.section_id) : ''
			}
		})

		const grouper_label = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'grouper_label',
			inner_html		: datalist_item.label,
			parent			: grouper
		})

	return grouper
};//end get_grouper_element



/**
* GET_INPUT_ELEMENT
* @return DOM node li
*/
const get_input_element = (i, current_value, self) => {

	const value  		 = self.data.value || []
	const value_length   = value.length
	const datalist_item  = current_value
	const datalist_value = datalist_item.value
	const label 		 = datalist_item.label
	const section_id	 = datalist_item.section_id

	// create li
		const li = ui.create_dom_element({
			element_type	: 'li',
			data_set		: {
				id		: datalist_item.section_tipo +'_'+ datalist_item.section_id,
				parent	: datalist_item.parent ? (datalist_item.parent.section_tipo +'_'+ datalist_item.parent.section_id) : ''
			}
		})

	// input checkbox
		const option = ui.create_dom_element({
			element_type	: 'input',
			type			: 'checkbox',
			id				: self.id +"_"+ i,
			dataset			: { key : i },
			value			: JSON.stringify(datalist_value),
			parent			: li
		})
		// checked option set on match
		for (let j = 0; j < value_length; j++) {
			if (value[j] && datalist_value &&
				value[j].section_id===datalist_value.section_id &&
				value[j].section_tipo===datalist_value.section_tipo
				) {
					option.checked = 'checked'
			}
		}

	// label
		const label_string = (SHOW_DEBUG===true) ? label + " [" + section_id + "]" : label
		const option_label = ui.create_dom_element({
			element_type	: 'label',
			inner_html		: label_string,
			parent			: li
		})
		option_label.setAttribute("for", self.id +"_"+ i)


	return li
};//end get_input_element


