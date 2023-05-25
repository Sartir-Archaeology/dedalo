// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
 /*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../common/js/ui.js'
	import {
		get_content_data,
		get_buttons
	} from './render_edit_component_number.js'



/**
* VIEW_DEFAULT_EDIT_NUMBER
* Manage the components logic and appearance in client side
*/
export const view_default_edit_number = function() {

	return true
}//end view_default_edit_number



/**
* RENDER
* Render node for use in modes: edit, edit_in_list
* @return HTMLElement wrapper
*/
view_default_edit_number.render = async function(self, options) {

	// options
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

	// wrapper. ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_edit(self, {
			content_data : content_data,
			buttons 	 : buttons
		})
		// set pointers
		wrapper.content_data = content_data


	return wrapper
}//end render



// @license-end
