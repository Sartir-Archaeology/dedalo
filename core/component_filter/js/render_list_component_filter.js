/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL */
/*eslint no-undef: "error"*/



// imports
	import {view_default_list_filter} from './view_default_list_filter.js'
	import {view_mini_list_filter} from './view_mini_list_filter.js'
	import {view_text_list_filter} from './view_text_list_filter.js'
	import {view_collapse_list_filter} from './view_collapse_list_filter.js'


/**
* RENDER_LIST_COMPONENT_FILTER
* Manage the components logic and appearance in client side
*/
export const render_list_component_filter = function() {

	return true
}//end render_list_component_filter



/**
* LIST
* Render node for use in list
* @return DOM node wrapper
*/
render_list_component_filter.prototype.list = async function(options) {

	const self = this

	// view
		const view	= self.context.view || 'default'

	switch(view) {

		case 'mini':
			return view_mini_list_filter.render(self, options)

		case 'text':
			return view_text_list_filter.render(self, options)

		case 'collapse':
			return view_collapse_list_filter.render(self, options)

		case 'default':
		default:
			return view_default_list_filter.render(self, options)
	}

	return null
}//end list
