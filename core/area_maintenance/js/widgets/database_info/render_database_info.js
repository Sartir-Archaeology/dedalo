// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL, DEDALO_API_URL */
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../../../common/js/ui.js'
	// import {object_to_url_vars} from '../../../../common/js/utils/index.js'



/**
* RENDER_DATABASE_INFO
* Manages the component's logic and appearance in client side
*/
export const render_database_info = function() {

	return true
}//end render_database_info



/**
* LIST
* Creates the nodes of current widget.
* The created wrapper will be append to the widget body in area_maintenance
* @param object options
* 	Sample:
* 	{
*		render_level : "full"
		render_mode : "list"
*   }
* @return HTMLElement wrapper
* 	To append to the widget body node (area_maintenance)
*/
render_database_info.prototype.list = async function(options) {

	const self = this

	const render_level = options.render_level || 'full'

	// content_data
		const content_data = await get_content_data_edit(self)
		if (render_level==='content') {
			return content_data
		}

	// wrapper. ui build_edit returns widget wrapper
		const wrapper = ui.widget.build_wrapper_edit(self, {
			content_data : content_data
		})
		// set pointers
		wrapper.content_data = content_data


	return wrapper
}//end list



/**
* GET_CONTENT_DATA_EDIT
* @param object self
* @return HTMLElement content_data
*/
const get_content_data_edit = async function(self) {

	// short vars
		const value		= self.value || {}
		const info		= value.info || {}
		const database	= info.IntervalStyle || ''
		const server	= info.server || ''
		const host		= info.host || ''

	// content_data
		const content_data = ui.create_dom_element({
			element_type : 'div'
		})

	// version
		ui.create_dom_element({
			element_type	: 'div',
			class_name		: '',
			inner_html		: `Database ${database} ${server} ${host}`,
			parent			: content_data
		})

	// version
		ui.create_dom_element({
			element_type	: 'pre',
			class_name		: '',
			inner_html		: JSON.stringify(info, null, 2),
			parent			: content_data
		})


	return content_data
}//end get_content_data_edit


// @license-end
