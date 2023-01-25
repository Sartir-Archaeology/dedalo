/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/


// UNDER CONSTRUCTION. (!) NOTE THAT CURRENTLY, THIS COMPONENT IS NOT SHOWED IN SEARCH LIST


// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'
	import {
		get_content_data_edit
	}
	from './view_default_edit_geolocation.js'


/**
* RENDER_SEARCH_COMPONENT_GEOLOCATION
* Manages the component's logic and appearance in client side
*/
export const render_search_component_geolocation = function() {

	return true
}//end render_search_component_geolocation



/**
* SEARCH
* Render node for use in edit
* @return DOM node wrapper
*/
render_search_component_geolocation.prototype.search = async function(options) {

	const self = this

	// render_level
		const render_level = options.render_level || 'full'

	// content_data
		const content_data = get_content_data_edit(self)
		if (render_level==='content') {
			return content_data
		}

	// wrapper. ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_search(self, {
			content_data : content_data
		})
		// set pointers
		wrapper.content_data = content_data


	return wrapper
}//end search
