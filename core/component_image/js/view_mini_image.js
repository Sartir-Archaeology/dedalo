// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'



/**
* VIEW_MINI_IMAGE
* Manage the components logic and appearance in client side
*/
export const view_mini_image = function() {

	return true
}//end view_mini_image



/**
* RENDER
* Render node to be used by service autocomplete or any datalist
* @return HTMLElement wrapper
*/
view_mini_image.render = function(self, options) {

	// short vars
		const datalist = self.data.datalist || []

	// wrapper
		const wrapper = ui.component.build_wrapper_mini(self)

	// url
		const quality		= 'thumb'
		const url_object	= datalist.find(item => item.quality===quality)
		const url			= url_object
			? url_object.file_url
			: DEDALO_CORE_URL + '/themes/default/0.jpg'

	// image
		ui.create_dom_element({
			element_type	: 'img',
			class_name		: 'view_' + self.view,
			src				: url,
			parent			: wrapper
		})
		// ui.component.add_image_fallback(image)


	return wrapper
}//end render



// @license-end
