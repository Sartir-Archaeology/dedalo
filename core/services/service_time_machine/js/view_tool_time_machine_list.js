/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL */
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../../../core/common/js/ui.js'
	import {get_ar_instances} from '../../../../core/section/js/section.js'
	import {set_element_css} from '../../../../core/page/js/css.js'
	import {event_manager} from '../../../../core/common/js/event_manager.js'
	import {
		get_content_data
	} from './render_service_time_machine_list.js'



/**
* VIEW_TOOL_TIME_MACHINE_LIST
* Manages the component's logic and appearance in client side
*/
export const view_tool_time_machine_list = function() {

	return true
}//end view_tool_time_machine_list



/**
* RENDER
* Renders main element wrapper for current view
* @param object self
* @param object options
* @return DOM node wrapper
*/
view_tool_time_machine_list.render = async function(self, options) {

	// options
		const render_level 	= options.render_level || 'full'

	// columns_map
		const columns_map = await rebuild_columns_map(self)
		self.columns_map = columns_map

	// ar_section_record. section_record instances (initialized and built)
		const ar_section_record	= await get_ar_instances(self)
		self.ar_instances		= ar_section_record

	// content_data
		const content_data = await get_content_data(ar_section_record, self)
		if (render_level==='content') {
			return content_data
		}

	// fragment
		const fragment = new DocumentFragment()

	// paginator container node
		const paginator_div = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'paginator_container',
			parent			: fragment
		})
		await self.paginator.build()
		self.paginator.render()
		.then(paginator_wrapper =>{
			paginator_div.appendChild(paginator_wrapper)
		})

	// list_body
		const list_body = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'list_body',
			parent			: fragment
		})
		// flat columns create a sequence of grid widths taking care of sub-column space
		// like 1fr 1fr 1fr 3fr 1fr
		const items				= ui.flat_column_items(columns_map)
		const template_columns	= self.config.template_columns
			? self.config.template_columns
			: items.join(' ')
		const css_object = {
			'.list_body' : {
				'grid-template-columns': template_columns
			}
		}
		const selector = `${self.config.id}.${self.section_tipo+'_'+self.tipo}.view_${self.view}`
		set_element_css(selector, css_object)

	// list_header_node. Create and append if ar_instances is not empty
		if (ar_section_record.length>0) {
			const list_header_node = ui.render_list_header(columns_map, self)
			list_body.appendChild(list_header_node)
		}

	// content_data append
		list_body.appendChild(content_data)

	// wrapper
		const wrapper = ui.create_dom_element({
			element_type	: 'div',
			class_name		: `wrapper_${self.model} ${self.model} ${self.config.id} ${self.section_tipo+'_'+self.tipo} view_${self.view}`
		})
		wrapper.appendChild(fragment)
		// set pointers
		wrapper.list_body		= list_body
		wrapper.content_data	= content_data


	return wrapper
}//end render



/**
* GET_CONTENT_DATA
* Render previously built section_records into a content_data div container
* @param array ar_section_record
* 	Array of section_record instances
* @param object self
* 	service_time_machine instance
* @return DOM node content_data
*/
	// const get_content_data = async function(ar_section_record, self) {

	// 	const fragment = new DocumentFragment()

	// 	// add all section_record rendered nodes
	// 		const ar_section_record_length = ar_section_record.length
	// 		if (ar_section_record_length===0) {

	// 			// no records found case
	// 			const no_records_found_node = ui.create_dom_element({
	// 				element_type	: 'div',
	// 				class_name		: 'no_records',
	// 				inner_html		: get_label.no_records || 'No records found'
	// 			})
	// 			fragment.appendChild(no_records_found_node)

	// 		}else{
	// 			// rows

	// 			// parallel render
	// 				const ar_promises = []
	// 				for (let i = 0; i < ar_section_record_length; i++) {
	// 					const render_promise_node = ar_section_record[i].render()
	// 					ar_promises.push(render_promise_node)
	// 				}

	// 			// once rendered, append it preserving the order
	// 				await Promise.all(ar_promises)
	// 				.then(function(section_record_nodes) {
	// 					for (let i = 0; i < ar_section_record_length; i++) {
	// 						const section_record_node = section_record_nodes[i]
	// 						fragment.appendChild(section_record_node)
	// 					}
	// 				});
	// 		}

	// 	// content_data
	// 		const content_data = document.createElement('div')
	// 			  content_data.classList.add('content_data', self.mode, self.type) // ,"nowrap","full_width"
	// 			  content_data.appendChild(fragment)


	// 	return content_data
	// }//end get_content_data



/**
* REBUILD_COLUMNS_MAP
* Adding control columns to the columns_map that will processed by section_recods
* @return obj columns_map
*/
const rebuild_columns_map = async function(self) {

	const columns_map = []

	// column section_id check
		columns_map.push({
			id			: 'section_id',
			label		: 'Id',
			width		: 'auto',
			callback	: render_column_id
		})

	// columns base
		const base_columns_map = await self.columns_map

	// ignore_columns
		const ignore_columns = (self.config.ignore_columns
			? self.config.ignore_columns
			: [
				'dd1573', // matrix_id
				'dd547', // when
				'dd543', // who
				'dd546' // where
			  ])
		// map names to tipo (columns already parse id for another uses)
		.map(el => {
			switch (el) {
				case 'matrix_id': return 'dd1573';
				case 'when'		: return 'dd547';
				case 'who'		: return 'dd543';
				case 'where'	: return 'dd546';
				default			: return el;
			}
		})

	// modify list and labels
		const base_columns_map_length = base_columns_map.length
		for (let i = 0; i < base_columns_map_length; i++) {
			const el = base_columns_map[i]

			// ignore some columns
				if (ignore_columns.includes(el.tipo)) {
					continue;
				}

			columns_map.push(el)
		}


	return columns_map
}//end rebuild_columns_map



/**
* RENDER_COLUMN_ID
* @param object options
* @return DOM DocumentFragment
*/
const render_column_id = function(options) {

	// options
		const service_time_machine	= options.caller
		const section_id			= options.section_id
		const section_tipo			= options.section_tipo
		// const offset				= options.offset
		const matrix_id				= options.matrix_id
		const modification_date		= options.modification_date

	// short vars
		// const permissions	= service_time_machine.permissions
		const tool				= service_time_machine.caller
		const main_caller		= tool.caller
		const fragment			= new DocumentFragment()

	// button_view
		const button_view = ui.create_dom_element({
			element_type	: 'button',
			class_name		: 'button_view',
			parent			: fragment
		})
		button_view.addEventListener('click', function() {

			if (main_caller.model==='section') {

				// section case

				// user confirmation
					const msg = tool.get_tool_label('recover_section_alert') || '*Are you sure you want to restore this section?'
					if (!confirm(msg)) {
						return
					}

				// apply recover record
					tool.apply_value({
						section_id		: section_id,
						section_tipo	: section_tipo,
						tipo			: section_tipo,
						lang			: page_globals.dedalo_data_nolan,
						matrix_id		: matrix_id
					})
					.then(function(response){
						if (response.result===true) {
							main_caller.refresh()
							.then(function(){
								service_time_machine.refresh()
								// success case
								// if (window.opener) {
								// 	// close this window when was opened from another
								// 	window.close()
								// }
							})
						}else{
							// error case
							console.warn("response:",response);
							alert(response.msg || 'Error. Unknown error on apply tm value');
						}
					})
			}else{
				// component case

				// publish event
					const data = {
						tipo		: section_tipo,
						section_id	: section_id,
						matrix_id	: matrix_id,
						date		: modification_date || null,
						mode		: 'tm'
					}
					event_manager.publish('tm_edit_record', data)
			}
		})

	// section_id
		ui.create_dom_element({
			element_type	: 'span',
			text_content	: section_id,
			class_name		: 'section_id',
			parent			: button_view
		})

	// icon eye time machine preview (eye)
		ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'button icon ' + (main_caller.model==='section' ? 'history' : 'eye'),
			parent			: button_view
		})


	return fragment
}//end render_column_id()
