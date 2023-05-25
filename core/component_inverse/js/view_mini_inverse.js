// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0


// import
	import {ui} from '../../common/js/ui.js'
	// import {common} from '../../common/js/common.js'



/**
* VIEW_MINI_INVERSE
* Manage the components logic and appearance in client side
*/
export const view_mini_inverse = function() {

	return true
}//end view_mini_inverse



/**
* RENDER
* Render node to be used by service autocomplete or any datalist
* @return HTMLElement wrapper
*/
view_mini_inverse.render = async function(self, options) {

	// short vars
		const data = self.data

	// wrapper
		const wrapper = ui.component.build_wrapper_mini(self)

	// Value as string
		const value_string = data.value && data.value[0] && data.value[0].locator
			? data.value[0].locator.from_section_id
			: null

	// Set value
		wrapper.insertAdjacentHTML('afterbegin', value_string)


	return wrapper
}//end render



// @license-end
