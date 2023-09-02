// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// import
	import {ui} from '../../common/js/ui.js'



/**
* VIEW_DEFAULT_LIST_SECTION_ID
* Manage the components logic and appearance in client side
*/
export const view_default_list_section_id = function() {

	return true
}//end view_default_list_section_id



/**
* RENDER
* Render node for use in list
* @return HTMLElement wrapper
*/
view_default_list_section_id.render = function(self, options) {

	// Value as string
		const value_string = self.data.value

	// wrapper
		const wrapper = ui.component.build_wrapper_list(self, {
			value_string : value_string
		})

	return wrapper
}//end render



// @license-end
