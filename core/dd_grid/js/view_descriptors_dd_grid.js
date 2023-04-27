/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	// import {clone,dd_console} from '../../common/js/utils/index.js'
	import {ui} from '../../common/js/ui.js'
	import {
		get_text_column,
		get_av_column,
		get_img_column,
		get_label_column,
		get_button_column,
		get_json_column,
		get_section_id_column,
		get_iri_column
	} from './render_list_dd_grid.js'



/**
* VIEW_DESCRIPTORS_DD_GRID
* Manage the components logic and appearance in client side
*/
export const view_descriptors_dd_grid = function() {

	return true
}//end view_descriptors_dd_grid



/**
* RENDER
* Render node for use in this view
* @return HTMLElement wrapper
*/
view_descriptors_dd_grid.render = async function(self, options) {

	// data
		const data = self.data

	// wrapper
		const wrapper = ui.create_dom_element({
			element_type	: 'div',
			class_name		: `wrapper_dd_grid ${self.tipo} ${self.mode} view_${self.view}`
		})

	// grid. Value as string
		const grid = get_grid_nodes( data )
		wrapper.appendChild(grid)


	return wrapper
}//end render



/**
* GET_GRID_NODES
* Recursively build grid nodes from value
* @param array data
* 	If data item type is 'column', generates a node, else recursively resolve the value
* @return DocumentFragment
*/
const get_grid_nodes = function(data) {

	const fragment = new DocumentFragment()

	const ar_values = []
	function get_grid_values(data) {

		const data_len = data.length
		for (let i = 0; i < data_len; i++) {
			const current_data = data[i]

			if (current_data && current_data.type) {

				// column
					if(current_data.type==='column' && current_data.cell_type) {

						const current_value = current_data.value[0] || current_data.fallback_value[0]
						const found = ar_values.find(el => el.label===current_value)
						if (found) {
							found.total++
						}else{
							ar_values.push({
								label : current_value,
								total : 1
							})
						}
					}//end if(current_data.type==='column' && current_data.cell_type)

				// value. Recursion
					if(current_data.value) {
						get_grid_values(current_data.value)
					}
			}
		}//end for (let i = 0; i < data_len; i++)
	}
	get_grid_values(data)

	ar_values.sort((a, b) => a.label.localeCompare(b.label, undefined, { numeric: true, sensitivity: 'base' }));
	console.log('ar_values:', ar_values);

	const ar_values_length = ar_values.length
	for (let i = 0; i < ar_values_length; i++) {
		const item = ar_values[i]
		const text_node = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'descriptors_item',
			inner_html		: `${item.label} [${item.total}]`
		})
		fragment.appendChild(text_node)
	}



	return fragment
}//end get_grid_nodes



/**
* GET_DIV_CONTAINER
* @param object current_data
* @return HTMLElement div_container
*/
const get_div_container = function(current_data) {

	const class_list = (current_data.class_list)
		? current_data.type + ' ' + current_data.class_list
		: current_data.type

	const div_container = ui.create_dom_element({
		element_type	: 'div',
		class_name		: class_list
	})

	return div_container
}//end get_div_container
