/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'
	import {
		render_links_list
	} from './render_list_component_iri.js'



/**
* VIEW_MINI_IRI
* Manage the components logic and appearance in client side
*/
export const view_mini_iri = function() {

	return true
}//end view_mini_iri



/**
* RENDER
* Render node to be used by service autocomplete or any datalist
* @return HTMLElement wrapper
*/
view_mini_iri.render = async function(self, options) {

	// short vars
		const data	= self.data || {}

		data.fields_separator = self.context.fields_separator

	// DOM fragment. Use common function render_links_list
		const fragment = render_links_list(data)

	// wrapper
		const wrapper = ui.component.build_wrapper_mini(self, {})
		wrapper.appendChild(fragment)


	return wrapper
}//end render
