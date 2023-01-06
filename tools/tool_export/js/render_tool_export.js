/*eslint no-unused-vars: "error"*/
/*global get_label, SHOW_DEBUG*/
/*eslint no-undef: "error"*/



// imports
	import {render_components_list} from '../../../core/common/js/render_common.js'
	// import {event_manager} from '../../../core/common/js/event_manager.js'
	import {data_manager} from '../../../core/common/js/data_manager.js'
	// import * as instances from '../../../core/common/js/instances.js'
	import {ui} from '../../../core/common/js/ui.js'
	// import {when_in_dom} from '../../../core/common/js/events.js'



/**
* RENDER_TOOL_EXPORT
* Manages the component's logic and appearance in client side
*/
export const render_tool_export = function() {

	return true
}//end render_tool_export



/**
* EDIT
* Render DOM nodes of the tool
* @return DOM node wrapper
*/
render_tool_export.prototype.edit = async function (options) {

	const self = this

	// render level
		const render_level = options.render_level || 'full'

	// content_data
		const content_data = await get_content_data_edit(self)
		if (render_level==='content') {
			return content_data
		}

	// wrapper. ui build_edit returns component wrapper
		const wrapper = ui.tool.build_wrapper_edit(self, {
			content_data : content_data
		})
		// set pointers
		wrapper.content_data = content_data

	// tool_container container
		// if (!window.opener) {
		// 	const header			= wrapper.tool_header // is created by ui.tool.build_wrapper_edit
		// 	const tool_container	= ui.attach_to_modal(header, wrapper, null, 'big')
		// 	tool_container.on_close	= async () => {
		// 		// tool destroy
		// 			await self.destroy(true, true, true)
		// 		// refresh source component text area
		// 			if (self.caller) {
		// 				self.caller.refresh()
		// 			}
		// 	}
		// }

	return wrapper
}//end render_tool_export



/**
* GET_CONTENT_DATA_EDIT
* @return DOM node content_data
*/
const get_content_data_edit = async function(self) {

	const fragment = new DocumentFragment()

	// components_list_container (left side)
		const components_list_container = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'components_list_container',
			parent			: fragment
		})
		// fields list . List of section fields usable in search
			// const search_container_selector = ui.create_dom_element({
			// 	element_type	: 'ul',
			// 	class_name		: 'search_section_container target_container',
			// 	parent			: components_list_container
			// })
		// components_list. render section component list [left]
			const section_elements = await self.get_section_elements_context({
				section_tipo			: self.target_section_tipo,
				ar_components_exclude	: [
					'component_password'
				]
			})
			render_components_list({
				self				: self,
				section_tipo		: self.target_section_tipo,
				target_div			: components_list_container,
				path				: [],
				section_elements	: section_elements
			})
			// console.log("get_content_data_edit self.components_list:",self.components_list);

	// user_selection_list (right side)
		const user_selection_list = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'user_selection_list',
			parent			: fragment
		})
		// user_selection_list drag and drop events
		user_selection_list.addEventListener('dragover',function(e){self.on_dragover(this,e)})
		user_selection_list.addEventListener('dragleave',function(e){self.on_dragleave(this,e)})
		// user_selection_list.addEventListener('dragend',function(e){self.on_drag_end(this,e)})
		user_selection_list.addEventListener('drop',function(e){self.on_drop(this,e)})

		// title
			ui.create_dom_element({
				element_type	: 'h1',
				class_name		: 'list_title',
				inner_html		: get_label.elementos_activos || 'Active elements',
				parent			: user_selection_list
			})

		// read saved ddo in local DB and restore elements if found
			const id = 'tool_export_config'
			data_manager.get_local_db_data(
				id,
				'data'
			)
			.then(function(response){
				const target_section_tipo = self.target_section_tipo[0]
				if (response && response.value && response.value[target_section_tipo]) {
					// call for each saved ddo
					for (let i = 0; i < response.value[target_section_tipo].length; i++) {
						const ddo = response.value[target_section_tipo][i]
						self.build_export_component(ddo)
						.then((export_component_node)=>{
							// add DOM node
							user_selection_list.appendChild(export_component_node)
							// Update the ddo_export
							self.ar_ddo_to_export.push(ddo)
						})
					}
					if(SHOW_DEBUG===true) {
						console.log(`Added ddo items from saved local db ${target_section_tipo}. Items:`, response.value[target_section_tipo]);
					}
				}else{
					console.error('Something was wrong were get_local_db_data '+ id)
				}
			})

	// export_buttons_config
		const export_buttons_config = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'export_buttons_config',
			parent			: fragment
		})

		// records info
			const records_info = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'records_info',
				parent			: export_buttons_config
			})
			const section_label = ui.create_dom_element({
				element_type	: 'h1',
				class_name		: 'section_label',
				inner_html		: self.caller.label,
				parent			: export_buttons_config
			})
			const total_records_label = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'total_records',
				inner_html		: (get_label.total_records || 'Total records:') + ': ',
				parent			: export_buttons_config
			})
			const total_records = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'total_records',
				parent			: total_records_label
			})
			self.caller.total()
			.then(function(response){
				total_records.insertAdjacentHTML('afterbegin', response)
			})

		// data_format selectors
			const data_format = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'data_format',
				inner_html		: (get_label.formato || 'Format'),
				parent			: export_buttons_config
			})
			// select
				const select_data_format_export = ui.create_dom_element({
					element_type	: 'select',
					class_name		: 'select_data_format_export',
					parent			: data_format
				})
				select_data_format_export.addEventListener('change', function() {
					// fix value
					self.data_format = select_data_format_export.value
				})
				// fix default value
				self.data_format = 'standard'

				// select_option_standard
				ui.create_dom_element({
					element_type	: 'option',
					inner_html		: get_label.estandar || 'standard',
					value			: 'standard',
					parent			: select_data_format_export
				})
				// select_option_html
				ui.create_dom_element({
					element_type	: 'option',
					inner_html		: get_label.html || 'HTML',
					value			: 'html',
					parent			: select_data_format_export
				})
				// select_option_breakdown
				ui.create_dom_element({
					element_type	: 'option',
					inner_html		: get_label.desglose || 'breakdown',
					value			: 'breakdown',
					parent			: select_data_format_export
				})
				// select_option_breakdown_html
				ui.create_dom_element({
					element_type	: 'option',
					inner_html		: (get_label.desglose || 'breakdown' ) + ' ' +(get_label.html || 'HTML'),
					value			: 'breakdown_html',
					parent			: select_data_format_export
				})
				// select_option_dedalo
				ui.create_dom_element({
					element_type	: 'option',
					inner_html		: 'Dédalo (Raw)',
					value			: 'dedalo',
					parent			: select_data_format_export
				})

		// button_export
			const button_export = ui.create_dom_element({
				element_type	: 'button',
				class_name		: 'button_export success',
				inner_html		: get_label.tool_export || 'Export',
				parent			: export_buttons_config
			})
			button_export.addEventListener('click', async function() {
				// clean target_div
					while (export_data_container.hasChildNodes()) {
						export_data_container.removeChild(export_data_container.lastChild);
					}

				// export_grid API call
					// self.data_format = select_data_format_export.value

				// sort ar_ddo_to_export by DOM items position
					// const ar_ddo_to_export_sorted = []
					// const element_children_length = user_selection_list.children.length
					// for (let i = 0; i < element_children_length; i++) {
					// 	const item = user_selection_list.children[i]
					// 	if (item.ddo) {
					// 		ar_ddo_to_export_sorted.push(item.ddo)
					// 	}
					// }

				// spinner add
					button_export.classList.add('hide')
					const spinner = ui.create_dom_element({
						element_type	: 'div',
						class_name		: 'spinner',
						parent			: export_buttons_config
					})

				// export_grid
					const export_grid_options = {
						data_format			: self.data_format,
						ar_ddo_to_export	: self.ar_ddo_to_export,
						view				: 'table'
					}
					const dd_grid				= await self.get_export_grid(export_grid_options)
					const dd_grid_export_node	= await dd_grid.render()
					if (dd_grid_export_node) {
						export_data_container.appendChild(dd_grid_export_node)
						export_data_container.scrollIntoView(true)
					}

				// spinner remove
					button_export.classList.remove('hide')
					spinner.remove()
			})

	// download_buttons_options
		const export_buttons_options = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'export_buttons_options',
			parent			: fragment
		})

		// csv. button_export_csv
			const button_export_csv = ui.create_dom_element({
				element_type	: 'button',
				class_name		: 'processing_import success',
				inner_html		: (get_label.descargar || 'Export') + ' csv',
				parent			: export_buttons_options
			})
			button_export_csv.addEventListener('click', async function() {

				// dd_grid
					const dd_grid		= self.dd_grid // self.ar_instances.find(el => el.model==='dd_grid')
					dd_grid.view		= 'csv'
					await dd_grid.build(false)
					const csv_string	= await dd_grid.render()

				// Download it
					const filename	= 'export_' + self.caller.section_tipo + '_' + new Date().toLocaleDateString() + '.csv';
					const link		= document.createElement('a');
					link.style.display = 'none';
					link.setAttribute('target', '_blank');
					link.setAttribute('href', 'data	:text/csv;charset=utf-8,' + encodeURIComponent(csv_string));
					link.setAttribute('download', filename);
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
			})

		// tsv. button_export_tsv
			const button_export_tsv = ui.create_dom_element({
				element_type	: 'button',
				class_name		: 'processing_import success',
				inner_html		: (get_label.descargar || 'Export') + ' tsv',
				parent			: export_buttons_options
			})
			button_export_tsv.addEventListener('click', async function() {

				// dd_grid
					const dd_grid		= self.dd_grid // self.ar_instances.find(el => el.model==='dd_grid')
					dd_grid.view		= 'tsv'
					await dd_grid.build(false)
					const tsv_string	= await dd_grid.render()

				// Download it
					const filename	= 'export_' + self.caller.section_tipo + '_' + new Date().toLocaleDateString() + '.tsv';
					const link		= document.createElement('a');
					link.style.display = 'none';
					link.setAttribute('target', '_blank');
					link.setAttribute('href', 'data	:text/tsv;charset=utf-8,' + encodeURIComponent(tsv_string));
					link.setAttribute('download', filename);
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
			})

		// excel. button_export Excel
			const button_export_excel = ui.create_dom_element({
				element_type	: 'button',
				class_name		: 'processing_import success',
				inner_html		: (get_label.descargar || 'Export') + ' Excel',
				parent			: export_buttons_options
			})
			button_export_excel.addEventListener('click', function() {

				// Download it
					const filename	= 'export_' + self.caller.section_tipo + '_' + new Date().toLocaleDateString() + '.xls';
					const link		= document.createElement('a');
					link.style.display = 'none';
					link.setAttribute('target', '_blank');
					link.setAttribute('href', 'data	:text/html;charset=utf-8,' +  export_data_container.innerHTML);
					link.setAttribute('download', filename);
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
			})

		// html. button export html
			const button_export_html = ui.create_dom_element({
				element_type	: 'button',
				class_name		: 'processing_import success',
				inner_html		: (get_label.descargar || 'Export') + ' html',
				parent			: export_buttons_options
			})
			button_export_html.addEventListener('click', function() {

				// Download it
					const filename	= 'export_' + self.caller.section_tipo + '_' + new Date().toLocaleDateString() + '.html';
					const link		= document.createElement('a');
					link.style.display = 'none';
					link.setAttribute('target', '_blank');
					link.setAttribute('href', 'data	:text/html;charset=utf-8,' +  export_data_container.innerHTML);
					link.setAttribute('download', filename);
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
			})

		// print. button export print
			const button_export_print = ui.create_dom_element({
				element_type	: 'button',
				class_name		: 'processing_import success',
				inner_html		: get_label.imprimir || 'Print',
				parent			: export_buttons_options
			})
			button_export_print.addEventListener('click', function(e) {
				console.log('e:', e);
			})

	// grid data container
		const export_data_container = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'export_data_container',
			parent			: fragment
		})

	// content_data
		const content_data = ui.tool.build_content_data(self)
		content_data.appendChild(fragment)


	return content_data
}//end get_content_data_edit



/**
* BUILD_EXPORT_COMPONENT
* Creates export_component DOM item
* @param object ddo
* @return DOM node export_component
*/
render_tool_export.prototype.build_export_component = async function(ddo) {

	const self = this

	// short vars
		const path = ddo.path

	// export_component container. Create DOM element before load html from trigger
		const export_component = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'export_component'
		})
		export_component.ddo = ddo
		do_sortable(export_component, self)

		// export_component component_label
			const label = path.map((el)=>{
				return el.name
			}).join(' > ')
			ui.create_dom_element({
				element_type	: 'li',
				class_name		: 'component_label',
				inner_html		: label,
				parent			: export_component
			})

	// button close
		const export_component_button_close = ui.create_dom_element({
			element_type	: 'span',
			parent			: export_component,
			class_name		: 'button close'
		})
		export_component_button_close.addEventListener('click', function(e) {
			e.stopPropagation()

			// remove search box and content (component) from DOM
			export_component.parentNode.removeChild(export_component)

			// delete the ddo from the array to export ddos
			const delete_ddo_index = self.ar_ddo_to_export.findIndex( el => el.id === ddo.id )
			self.ar_ddo_to_export.splice(delete_ddo_index, 1)

			// save local db data
			self.update_local_db_data()
		})

	// label component source if exists
		// if (first_item!==last_item) {
		// 	console.log("first_item:",first_item);
		// 	const label_add = parent_div.querySelector('span.label_add')
		// 	if (label_add) {
		// 		label_add.innerHTML = first_item.name +' '+ label_add.innerHTML
		// 	}
		// }

	// show hidden parent container
		// parent_div.classList.remove('hide')

	// store ddo in local DB


	return export_component
}//end build_export_component



/**
* DO_SORTABLE
* Add drag and drop features to the element
* @param DOM node element
* @return void
*/
const do_sortable = function(element, self) {

	// sortable
		element.draggable = true

	// reset all items
		function reset() {
			const element_children_length = element.parentNode.children.length
			for (let i = 0; i < element_children_length; i++) {
				const item = element.parentNode.children[i]
				if (item.classList.contains('displaced')) {
					item.classList.remove('displaced')
				}
			}
		}

	// events fired on the draggable target

		// drag start. Fix dragged element to recover later
			element.addEventListener('dragstart', (event) => {
				event.stopPropagation()

				reset()

				element.classList.add('dragging');

				// fix dragged element
					self.dragged = element

				// dataTransfer
					const data = {
						drag_type : 'sort'
					}
					// event.dataTransfer.effectAllowed = 'move';
					event.dataTransfer.dropEffect = 'move';
					event.dataTransfer.setData(
						'text/plain',
						JSON.stringify(data)
					)
			});

		// drag end
			element.addEventListener('dragend', (event) => {
				reset()
				// reset the dragging style
				event.target.classList.remove('dragging');
			});

	//  events fired on the drop targets

		// drag enter - add displaced padding
			element.addEventListener('dragenter', (event) => {
				event.preventDefault();

				reset()

				element.classList.add('displaced')
			});

		// on drop
			element.addEventListener('drop', (event) => {
				event.preventDefault();
				event.stopPropagation()

				reset()

				// data transfer
					const data			= event.dataTransfer.getData('text/plain');// element that move
					const parsed_data	= JSON.parse(data)
					// console.log('[sort] parsed_data.drag_type:', parsed_data);

				if (parsed_data.drag_type==='sort') {

					// sort case

					// place drag item
					const dragged = self.dragged
					if(element.parentNode.lastChild===element) {
						element.parentNode.appendChild(dragged)
					}else{
						element.parentNode.insertBefore(dragged, element)
					}

					dragged.classList.add('active')

					// Update the ddo_export. Move to the new array position
						const from_index	= self.ar_ddo_to_export.findIndex(el => el.id===dragged.ddo.id)
						const to_index		= [...element.parentNode.children].indexOf(dragged) -1 // exclude title node
						// remove
						const item_moving_ddo = self.ar_ddo_to_export.splice(from_index, 1)[0];
						// add
						self.ar_ddo_to_export.splice(to_index, 0, item_moving_ddo);

						// save local db data
						self.update_local_db_data()

				}else if (parsed_data.drag_type==='add') {

					// add case

					// short vars
						const path	= parsed_data.path
						const ddo	= parsed_data.ddo

					// rebuild ddo
						const new_ddo = {
							id				: ddo.section_tipo +'_'+ ddo.tipo +'_list_'+ ddo.lang,
							tipo			: ddo.tipo,
							section_tipo	: ddo.section_tipo,
							model			: ddo.model,
							parent			: ddo.parent,
							lang			: ddo.lang,
							mode			: ddo.mode,
							label			: ddo.label,
							path			: path // full path from current section replaces ddo single path
						}

					// exists
						const found = self.ar_ddo_to_export.find(el => el.id===new_ddo.id)
						if (found) {
							console.log('Ignored already included item ddo:', found);
							return
						}

					// Build component html
					self.build_export_component(new_ddo)
					.then((export_component_node)=>{

						// add DOM node
						element.parentNode.insertBefore(export_component_node, element)

						export_component_node.classList.add('active')

						// Add ddo in the current position
						// self.ar_ddo_to_export.push(new_ddo)
						const to_index = [...element.parentNode.children].indexOf(export_component_node) -1 // exclude title node
						// add
						self.ar_ddo_to_export.splice(to_index, 0, export_component_node.ddo);

						// save local db data
						self.update_local_db_data()
					})
				}
			});
}//end do_sortable
