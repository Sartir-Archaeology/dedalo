/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'
	import {
		get_content_data
	} from './render_edit_component_filter.js'



/**
* VIEW_LINE_EDIT_FILTER
* Manage the components logic and appearance in client side
*/
export const view_line_edit_filter = function() {

	return true
}//end view_line_edit_filter



/**
* RENDER
* Render node for use in current view
* @return HTMLElement wrapper
*/
view_line_edit_filter.render = async function(self, options) {

	// options
		const render_level 	= options.render_level || 'full'

	// button_exit_edit
		const button_exit_edit = ui.component.build_button_exit_edit(self)

	// content_data
		const content_data = get_content_data(self)
		content_data.appendChild(button_exit_edit)
		if (render_level==='content') {
			return content_data
		}


	// ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_edit(self, {
			content_data	: content_data,
			label			: null
		})
		// set pointers
		wrapper.content_data = content_data




	return wrapper
}//end render
