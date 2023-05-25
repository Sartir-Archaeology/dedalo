// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'



/**
* VIEW_MINI_LIST_FILTER_RECORDS
* Manage the components logic and appearance in client side
*/
export const view_mini_list_filter_records = function() {

	return true
}//end view_mini_list_filter_records



/**
* MINI
* Render node to be used in current mode
* @return HTMLElement wrapper
*/
view_mini_list_filter_records.render = async function(self, options) {

	// short vars
		const data			= self.data || {}
		const value			= data.value || []
		// const value_flat	= value.flat()
		const string_values = value.map(el => {
			return JSON.stringify(el)
		})
		const value_string = string_values.join(self.context.fields_separator)

	// wrapper
		const wrapper = ui.component.build_wrapper_mini(self)

	// Value as string
		// const value_string = value.join(' | ')

	// Set value
		wrapper.insertAdjacentHTML('afterbegin', value_string)


	return wrapper
}//end min



// @license-end
