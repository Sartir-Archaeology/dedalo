// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0



// import



/**
* VIEW_TEXT_INVERSE
* Manage the components logic and appearance in client side
*/
export const view_text_inverse = function() {

	return true
}//end view_text_inverse



/**
* RENDER
* Render node as text. URL is return as text node
* @param object self
* @param object options
* @return HTMLElement wrapper
*/
view_text_inverse.render = async function(self, options) {

	// short vars
		const data			= self.data || {}
		const value_string	= data.value && data.value[0] && data.value[0].locator
			? data.value[0].locator.from_section_id
			: null

	const wrapper = document.createElement('span')
	wrapper.insertAdjacentHTML('afterbegin', value_string)


	return wrapper
}//end render



// @license-end
