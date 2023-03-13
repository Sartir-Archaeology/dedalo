/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL */
/*eslint no-undef: "error"*/



// imports
	import {view_default_list_text_area} from './view_default_list_text_area.js'
	import {view_mini_text_area} from './view_mini_text_area.js'
	import {view_note_text_area} from './view_note_text_area.js'
	import {view_text_list_text_area} from './view_text_list_text_area.js'



/**
* RENDER_LIST_COMPONENT_TEXT_AREA
* Manage the components logic and appearance in client side
*/
export const render_list_component_text_area = function() {

	return true
}//end render_list_component_text_area



/**
* LIST
* Render node for use in list
* @param object options
* @return HTMLElement wrapper
*/
render_list_component_text_area.prototype.list = async function(options) {

	const self = this

	// view
		const view	= self.context.view || 'default'

	switch(view) {

		case 'mini':
			return view_mini_text_area.render(self, options)

		case 'note':
			return view_note_text_area.render(self, options)

		case 'text':
			return view_text_list_text_area.render(self, options)

		case 'default':
		default:
			return view_default_list_text_area.render(self, options)
	}
}//end list
