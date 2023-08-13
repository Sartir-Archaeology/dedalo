// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL, DEDALO_API_URL */
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../../../common/js/ui.js'
	// import {object_to_url_vars} from '../../../../common/js/utils/index.js'



/**
* RENDER_UPDATE_DATA_VERSION
* Manages the widget logic and appearance in client side
*/
export const render_update_data_version = function() {

	return true
}//end render_update_data_version



/**
* LIST
* Creates the nodes of current widget.
* The created wrapper will be append to the widget body in area_development
* @param object options
* 	Sample:
* 	{
*		render_level : "full"
		render_mode : "list"
*   }
* @return HTMLElement wrapper
* 	To append to the widget body node (area_development)
*/
render_update_data_version.prototype.list = async function(options) {

	const self = this

	const render_level = options.render_level || 'full'

	// content_data
		const content_data = await get_content_data(self)
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
* GET_CONTENT_DATA
* @param object self
* @return HTMLElement content_data
*/
const get_content_data = async function(self) {

	// short vars
		const value					= self.value || {}
		const update_version		= value.update_version
		const current_version_in_db	= value.current_version_in_db
		const dedalo_version		= value.dedalo_version
		const updates				= value.updates

	// content_data
		const content_data = ui.create_dom_element({
			element_type : 'div'
		})

	// dedalo_db_management
		if (!update_version) {
			ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'info_text success_text',
				inner_html		: 'Data format is updated: ' + current_version_in_db.join('.'),
				parent			: content_data
			})
			return content_data
		}

	// info
		const text = 'To update data version: ' + current_version_in_db.join('.') + ' ---> ' + update_version.join('.')
		const info = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'info_text error_text',
			inner_html		: text,
			parent			: content_data
		})

	// updates
		if (updates) {
			const updates_container = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'updates_container',
				parent			: content_data
			})
			for (const [key, current_value] of Object.entries(updates)) {
				// console.log(`${key}: `, current_value);
				// if (!Array.isArray(current_value)) {
				// 	continue; // skip non array elements
				// }

				const current_value_length = current_value.length

				switch (key) {
					case 'alert_update':
						for (let i = 0; i < current_value_length; i++) {
							const item = current_value[i]
							ui.create_dom_element({
								element_type	: 'h2',
								class_name		: 'alert_update',
								inner_html		: item.command || item.notification,
								parent			: content_data
							})
						}
						break;

					case 'SQL_update':
					case 'components_update':
					case 'run_scripts':
						for (let i = 0; i < current_value_length; i++) {
							const item = current_value[i]
							if (i===0) {
								ui.create_dom_element({
									element_type	: 'h6',
									class_name		: '',
									inner_html		: key,
									parent			: content_data
								})
							}
							const command_node = ui.create_dom_element({
								element_type	: 'div',
								class_name		: 'command',
								parent			: content_data
							})
							ui.create_dom_element({
								element_type	: 'span',
								class_name		: 'vkey',
								inner_html		: i+1,
								parent			: command_node
							})
							ui.create_dom_element({
								element_type	: 'span',
								class_name		: 'vkey_value',
								inner_html		: typeof item==='string' ? item : JSON.stringify(item, null, 2),
								parent			: command_node
							})
						}
						break;
				}
			}
		}

	// body_response
		const body_response = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'body_response'
		})

	// form init
		self.caller.init_form({
			submit_label	: self.name,
			confirm_text	: get_label.sure || 'Sure?',
			body_info		: content_data,
			body_response	: body_response,
			trigger : {
				dd_api	: 'dd_utils_api',
				action	: 'update_data_version',
				options	: null
			}
		})

	// add at end body_response
		content_data.appendChild(body_response)


	return content_data
}//end get_content_data



// @license-end
