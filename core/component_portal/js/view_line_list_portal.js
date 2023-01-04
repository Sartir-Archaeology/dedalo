/*global Promise */
/*eslint no-undef: "error"*/



// imports
	import {get_section_records} from '../../section/js/section.js'
	import {ui} from '../../common/js/ui.js'
	import {
		// activate_autocomplete,
		render_references
	} from './render_edit_component_portal.js'



/**
* VIEW_LINE_LIST_PORTAL
* Manage the components logic and appearance in client side
*/
export const view_line_list_portal = function() {

	return true
}//end view_line_list_portal




/**
* RENDER
* Render component nodes in current view
* @param component_portal instance self
* @param object options
* @return promise
* 	DOM node wrapper
*/
view_line_list_portal.render = async function(self, options) {

	// options
		const render_level = options.render_level || 'full'

	// view
		const children_view	= self.context.children_view || self.context.view || 'default'

	// ar_section_record
		const ar_section_record	= await get_section_records({
			caller	: self,
			view	: children_view
		})
		// store to allow destroy later
		self.ar_instances.push(...ar_section_record)

	// content_data
		const content_data = await get_content_data(self, ar_section_record)
		if (render_level==='content') {
			return content_data
		}

	// wrapper. ui build_edit returns component wrapper.
	// Note: Use 'build_wrapper_list' instead 'build_wrapper_edit' because allow user to change mode on dblclick
		const wrapper = ui.component.build_wrapper_list(self, {
			autoload : true // bool set autoload when change mode is called (close button)
		})
		wrapper.classList.add('portal')
		wrapper.appendChild(content_data)
		// set pointers
		wrapper.content_data = content_data

	// change_mode
		// wrapper.addEventListener('click', function(e) {
		// 	e.stopPropagation()

		// 	const change_mode = self.context.properties.with_value
		// 		&& self.context.properties.with_value.mode !== self.mode
		// 			? self.context.properties.with_value.mode
		// 			: 'edit'

		// 	const change_view = self.context.properties.with_value
		// 		&& self.context.properties.with_value.view !== self.context.view
		// 			? self.context.properties.with_value.view
		// 			: 'line'

		// 	self.change_mode({
		// 		mode	: change_mode,
		// 		view	: change_view
		// 	})
		// })


	return wrapper
}//end render



/**
* GET_CONTENT_DATA
* Render all received section records and place it into a new div 'content_data'
* @return DOM node content_data
*/
const get_content_data = async function(self, ar_section_record) {

	// build_values
		const fragment = new DocumentFragment()

	// add all section_record rendered nodes
		const ar_section_record_length = ar_section_record.length
		if (ar_section_record_length===0) {

			// no records found case
			// const row_item = no_records_node()
			// fragment.appendChild(row_item)
		}else{

			const ar_promises = []
			for (let i = 0; i < ar_section_record_length; i++) {
				const render_promise = ar_section_record[i].render()
				ar_promises.push(render_promise)
			}
			await Promise.all(ar_promises).then(function(values) {
			  for (let i = 0; i < ar_section_record_length; i++) {

				const section_record = values[i]
				fragment.appendChild(section_record)
			  }
			});
		}//end if (ar_section_record_length===0)

	// build references
		if(self.data.references && self.data.references.length > 0){
			const references_node = render_references(self.data.references)
			fragment.appendChild(references_node)
		}

	// content_data
		const content_data = ui.component.build_content_data(self, {
			autoload : true
		})
		content_data.appendChild(fragment)


	return content_data
}//end get_content_data
