/*global */
/*eslint no-undef: "error"*/



// imports
	import {view_default_edit_3d} from './view_default_edit_3d.js'



/**
* RENDER_EDIT_COMPONENT_3D
* Manages the component's logic and appearance in client side
*/
export const render_edit_component_3d = function() {

	return true
}//end render_edit_component_3d



/**
* EDIT
* Render node for use in modes: edit
* @return HTMLElement wrapper
*/
render_edit_component_3d.prototype.edit = async function(options) {

	const self = this

	// view
		const view = self.context.view || 'default'

	switch(view) {

		case 'print':
			// for print we need to use read of the content_value and it's necessary force permissions to use read only element render
			self.permissions = 1

		case 'default':
		default:
			return view_default_edit_3d.render(self, options)

	}
}//end edit
