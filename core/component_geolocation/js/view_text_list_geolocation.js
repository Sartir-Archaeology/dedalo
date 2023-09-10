// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global */
/*eslint no-undef: "error"*/



/**
* VIEW_TEXT_list_GEOLOCATION
* Manages the component's logic and appearance in client side
*/
export const view_text_list_geolocation = function() {

	return true
}//end view_text_list_geolocation



/**
* RENDER
* Render node as text. URL is return as text node
* @param object self
* @param object options
* @return HTMLElement wrapper
*/
view_text_list_geolocation.render = async function(self, options) {

	// value fallback
		const data			= self.data || {}
		const value			= data.value || []
		const string_values	= value.map(el => {
			return JSON.stringify(el)
		})
		const value_string	= string_values.join(self.context.fields_separator)

	const wrapper = document.createElement('span')
	wrapper.insertAdjacentHTML('afterbegin', value_string)


	return wrapper
}//end render



// @license-end
