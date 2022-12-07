/* global get_label, Promise, SHOW_DEBUG, DEDALO_CORE_URL */
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	// import {data_manager} from '../../common/js/data_manager.js'
	// import {get_instance, delete_instance} from '../../common/js/instances.js'
	import {ui} from '../../common/js/ui.js'
	import {set_element_css} from '../../page/js/css.js'
	// import {service_autocomplete} from '../../services/service_autocomplete/js/service_autocomplete.js'
	// import {view_autocomplete} from './view_autocomplete.js'
	// import {flat_column_items} from '../../common/js/common.js'
	// import {render_edit_component_portal} from '../../component_portal/js/render_edit_component_portal.js'



/**
* VIEW_DEFAULT_LIST_PORTAL
* Manages the component's logic and appearance in client side
*/
export const view_default_list_portal = function() {

	return true
}//end view_default_list_portal



/**
* RENDER
* Render component nodes in current view
* @param component_portal instance self
* @param object options
* @return promise
* 	DOM node wrapper
*/
view_default_list_portal.render = async function(self, options) {

	// options
		const render_level = options.render_level || 'full'

	// view
		const children_view	= self.context.children_view || self.context.view || 'default'

	// ar_section_record
		const ar_section_record	= await self.get_ar_instances({
			view : children_view
		})
		// store to allow destroy later
		self.ar_instances.push(...ar_section_record)

	// content_data
		const content_data = await get_content_data(self, ar_section_record)
		if (render_level==='content') {
			return content_data
		}

	// columns_map
		const columns_map = await self.columns_map

	// fragment container
		const fragment = new DocumentFragment()

	// list_body
		const list_body = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'list_body ' + self.mode +  ' view_'+self.view,
			parent			: fragment
		})
		// const items				= flat_column_items(columns_map)
		// const template_columns	= `auto ${items.join(' ')}`
		// const template_columns = `repeat(${columns_map.length}, 1fr)`
		// flat columns create a sequence of grid widths taking care of sub-column space
		// like 1fr 1fr 1fr 3fr 1fr
		const items				= ui.flat_column_items(columns_map)
		const template_columns	= `${items.join(' ')}`
		// old way inline
			// Object.assign(
			// 	list_body.style,
			// 	{
			// 		"grid-template-columns": template_columns
			// 	}
			// )
		// new way to on-the fly js
			if (self.view!=='mosaic') {
				const css_object = {
					'.list_body' : {
						'grid-template-columns': template_columns
					}
				}
				const selector = `${self.section_tipo}_${self.tipo}.list.view_${self.view}`
				set_element_css(selector, css_object)
			}

	// header
		// const list_header_node = build_header(columns_map, ar_section_record, self)
		// const list_header_node = ui.render_list_header(columns_map, self, false)
		// list_body.appendChild(list_header_node)

	// content_data append
		list_body.appendChild(content_data)

	// wrapper
		// const wrapper = ui.create_dom_element({
		// 	element_type	: 'div',
		// 	id				: self.id,
		// 	//class_name	: self.model + ' ' + self.tipo + ' ' + self.mode
		// 	class_name		: 'wrapper_' + self.type + ' ' + self.model + ' ' + self.tipo + ' portal ' + self.mode
		// })
		const wrapper = ui.component.build_wrapper_list(self, {
			autoload : true // bool set build autoload param on mode change (close button)
		})
		wrapper.classList.add('portal')
		wrapper.appendChild(fragment)
		// set pointers
		wrapper.content_data	= content_data
		wrapper.list_body		= list_body
		// click event capture
		wrapper.addEventListener('click', function(e) {
			e.stopPropagation()
			// nothing to do in list mode, only catch click event
		})


	return wrapper
}//end list



/**
* GET_CONTENT_DATA
* Render all received section records and place it into a new div 'content_data'
* @return DOM node content_data
*/
const get_content_data = async function(self, ar_section_record) {

	// build_values
		const fragment = new DocumentFragment()

	// add all section_record rendered nodes
		const ar_section_record_length	= ar_section_record.length
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

	// content_data
		const content_data = document.createElement('div')
			  content_data.classList.add('content_data', self.mode, self.type)
			  content_data.appendChild(fragment)


	return content_data
}//end get_content_data
