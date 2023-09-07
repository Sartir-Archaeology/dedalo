// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, Promise, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {get_section_records} from '../../section/js/section.js'
	import {
		render_column_component_info
	} from './render_edit_component_portal.js'



/**
* VIEW_TEXT_LIST_PORTAL
* Manages the component's logic and appearance in client side
*/
export const view_text_list_portal = function() {

	return true
}//end view_text_list_portal



/**
* RENDER
* Render node as text view
* @return HTMLElement wrapper
*/
view_text_list_portal.render = async function(self, options) {

	// // Value as string
	// 	const data	= self.data || {}
	// 	const value	= data.value || []
	// 	console.log('self:', self);

	// const value_string = value.join(self.context.fields_separator)

	// const text_node = document.createTextNode(value_string)

	// return text_node

	// column component_info
	if (self.add_component_info===true) {
		self.columns_map.push({
			id			: 'ddinfo',
			label		: 'Info',
			callback	: render_column_component_info
		})
	}

	// options
		const render_level = options.render_level || 'full'

	// ar_section_record
		const ar_section_record = await get_section_records({
			caller	: self,
			mode	: 'list',
			view	: self.context.view
		})
		// store to allow destroy later
		self.ar_instances.push(...ar_section_record)


	// wrapper. Set as span
		const wrapper = document.createElement('span')
		wrapper.className = 'portal'

	// add all nodes
		const ar_section_record_length = ar_section_record.length


		for (let i = 0; i < ar_section_record_length; i++) {

			// child
				const child_item = await ar_section_record[i].render()
				wrapper.append(...child_item.childNodes)

			// records_separator
				if(i < ar_section_record_length-1) {
					const node_records_separator = document.createTextNode(self.context.records_separator)
					wrapper.appendChild(node_records_separator)
				}
		}

	return wrapper
}//end render



// @license-end
