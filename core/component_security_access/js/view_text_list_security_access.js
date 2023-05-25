// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	// import {data_manager} from '../../common/js/data_manager.js'
	// import {ui} from '../../common/js/ui.js'



/**
* VIEW_TEXT_LIST_SECURITY_ACCESS
* Manages the component's logic and appearance in client side
*/
export const view_text_list_security_access = function() {

	return true
}//end view_text_list_security_access



/**
* RENDER
* Output component value to use as raw text
* @return HTMLElement text_node
*/
view_text_list_security_access.render = async function(self, options) {

	// short vars
		// const data = self.data

	// Value as string
		const value_string = 'View text unavailable'

	const text_node = document.createTextNode(value_string)

	return text_node
}//end render



// @license-end
