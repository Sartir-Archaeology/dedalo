// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, Promise */
/*eslint no-undef: "error"*/



// imports
	import {get_section_records} from '../../section/js/section.js'
	import {event_manager} from '../../common/js/event_manager.js'
	import {clone} from '../../common/js/utils/index.js'
	import {ui} from '../../common/js/ui.js'
	import {open_tool} from '../../../tools/tool_common/js/tool_common.js'
	import {set_element_css} from '../../page/js/css.js'
	import {no_records_node} from './render_common_section.js'
	import {
		render_column_id
	} from './render_list_section.js'



/**
* VIEW_DEFAULT_LIST_SECTION
* Manages the component's logic and appearance in client side
*/
export const view_default_list_section = function() {

	return true
}//end view_default_list_section



/**
* RENDER
* Render node for use current view
* @param object self
* @para object options
* sample:
* {
*    "render_level": "full",
*    "render_mode": "list"
* }
* @return HTMLElement wrapper
*/
view_default_list_section.render = async function(self, options) {

	// options
		const render_level = options.render_level || 'full'

	// columns_map
		const columns_map	= await rebuild_columns_map(self)
		self.columns_map	= columns_map

	// ar_section_record. section_record instances (initialized and built)
		self.ar_instances = self.ar_instances && self.ar_instances.length>0
			? self.ar_instances
			: await get_section_records({caller: self})

	// content_data
		const content_data = await get_content_data(self, self.ar_instances)
		if (render_level==='content') {

			// list_header_node. Remove possible style 'hide' if not empty
				if (self.ar_instances.length>0) {
					const wrapper = self.node
					if (wrapper.list_header_node && wrapper.list_header_node.classList.contains('hide')) {
						wrapper.list_header_node.classList.remove('hide')
					}
				}

			return content_data
		}

	// DocumentFragment
		const fragment = new DocumentFragment()

	// buttons add
		if (self.buttons && self.mode!=='tm') {
			const buttons_node = get_buttons(self);
			if(buttons_node){
				fragment.appendChild(buttons_node)
			}
		}

	// search filter node
		if (self.filter && self.mode!=='tm') {
			const search_container = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'search_container',
				parent			: fragment
			})
			// set pointers
			self.search_container = search_container
		}

	// paginator container node
		if (self.paginator) {
			const paginator_container = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'paginator_container',
				parent			: fragment
			})
			self.paginator.build()
			.then(function(){
				self.paginator.render()
				.then(paginator_wrapper =>{
					paginator_container.appendChild(paginator_wrapper)
				})
			})
		}

	// list body
		const list_body = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'list_body',
			parent			: fragment
		})
		// fix last list_body (for pagination selection)
		self.node_body = list_body

		// list_body css
			const selector = `${self.section_tipo}_${self.tipo}.list`

		// custom properties defined css
			// if (self.context.css) {
				// use defined section css
				// set_element_css(selector, self.context.css)
			// }
			// flat columns create a sequence of grid widths taking care of sub-column space
			// like 1fr 1fr 1fr 3fr 1fr
			const items				= ui.flat_column_items(columns_map)
			const template_columns	= items.join(' ')

			// direct assign DES
				// Object.assign(
				// 	list_body.style,
				// 	{
				// 		"grid-template-columns": template_columns
				// 	}
				// )

			// re-parse template_columns as percent
				// const items_lenght = items.length
				// const percent_template_columns = items.map(el => {
				// 	if (el==='1fr') {
				// 		return Math.ceil(90 / (items_lenght -1)) + '%'
				// 	}
				// 	return el
				// }).join(' ')
				// console.log("percent_template_columns:",percent_template_columns);

			const css_object = {
				'.list_body' : {
					'grid-template-columns' : template_columns
				}
			}
			if (self.context?.css) {
				// use defined section css
				for(const property in self.context.css) {
					css_object[property] = self.context.css[property]
				}
			}
			// use calculated css
			set_element_css(selector, css_object)

	// list_header_node. Create and append if ar_instances is not empty
		const list_header_node = ui.render_list_header(columns_map, self)
		list_body.appendChild(list_header_node)
		if (self.ar_instances.length<1) {
			list_header_node.classList.add('hide')
		}

	// content_data append
		list_body.appendChild(content_data)

	// wrapper
		const wrapper = ui.create_dom_element({
			element_type	: 'section',
			id				: self.id,
			class_name		: `wrapper_${self.type} ${self.model} ${self.section_tipo}_${self.tipo} ${self.tipo} ${self.mode} view_${self.view}`
		})
		wrapper.appendChild(fragment)
		// set pointers
		wrapper.content_data		= content_data
		wrapper.list_body			= list_body
		wrapper.list_header_node	= list_header_node


	return wrapper
}//end render



/**
* GET_CONTENT_DATA
* @param array ar_section_record
* @para object self
* @return HTMLElement content_data
*/
const get_content_data = async function(self, ar_section_record) {

	const fragment = new DocumentFragment()

	// add all section_record rendered nodes
		const ar_section_record_length = ar_section_record.length
		if (ar_section_record_length===0) {

			// no records found case
			const row_item = no_records_node()
			fragment.appendChild(row_item)

		}else{
			// rows
			// parallel mode
				const ar_promises = []
				for (let i = 0; i < ar_section_record_length; i++) {
					const render_promise_node = ar_section_record[i].render({
						add_hilite_row : true
					})
					ar_promises.push(render_promise_node)
				}
				await Promise.all(ar_promises).then(function(values) {
				  for (let i = 0; i < ar_section_record_length; i++) {
				  	const section_record_node = values[i]
					fragment.appendChild(section_record_node)
				  }
				});
		}

	// content_data
		const content_data = document.createElement('div')
			  content_data.classList.add('content_data', self.mode, self.type)
			  content_data.appendChild(fragment)


	return content_data
}//end get_content_data



/**
* REBUILD_COLUMNS_MAP
* Adding control columns to the columns_map that will processed by section_recods
* @param object self
* @return obj columns_map
*/
const rebuild_columns_map = async function(self) {

	// columns_map already rebuilt case
		if (self.fixed_columns_map===true) {
			return self.columns_map
		}

	const columns_map = []

	// column section_id check
		columns_map.push({
			id			: 'section_id',
			label		: 'Id',
			tipo		: 'section_id', // used to sort only
			sortable	: true,
			width		: 'minmax(auto, 6rem)',
			path		: [{
				// note that component_tipo=section_id is valid here
				// because section_id is a direct column in search
				component_tipo	: 'section_id',
				// optional. Just added for aesthetics
				model			: 'component_section_id',
				name			: 'ID',
				section_tipo	: self.section_tipo
			}],
			callback	: render_column_id
		})

	// button_remove
		// 	if (self.permissions>1) {
		// 		columns_map.push({
		// 			id			: 'remove',
		// 			label		: '', // get_label.delete || 'Delete',
		// 			width 		: 'auto',
		// 			callback	: render_column_remove
		// 		})
		// 	}

	// columns base
		const base_columns_map = await self.columns_map
		columns_map.push(...base_columns_map)

	// fixed as calculated
		self.fixed_columns_map = true


	return columns_map
}//end rebuild_columns_map



/**
* GET_BUTTONS
* @param object self
* @return HTMLElement fragment
*/
const get_buttons = function(self) {

	// ar_buttons list from context
		const ar_buttons = self.context?.buttons
		if(!ar_buttons) {
			return null;
		}

	// DocumentFragment
		const fragment = new DocumentFragment()

	// buttons_container
		const buttons_container = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'buttons_container',
			parent			: fragment
		})

	// filter button (search) . Show and hide all search elements
		const filter_button	= ui.create_dom_element({
			element_type	: 'button',
			class_name		: 'warning search',
			inner_html		: get_label.find || 'Search',
			parent			: buttons_container
		})
		filter_button.addEventListener('mousedown', function(e) {
			e.stopPropagation()
			// Note that self section is who is observing this event (init)
			event_manager.publish('toggle_search_panel_'+self.id)
		})

	// non_editable_sections. Activity section 'dd542'
		const non_editable_sections = [
			'dd542', // activity
			'dd1324' // registered tools
		]
		if (non_editable_sections.includes(self.tipo)) {
			return fragment
		}

	// other_buttons_block
		const other_buttons_block = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'other_buttons_block hide',
			parent			: buttons_container
		})

	// other buttons
		const ar_buttons_length = ar_buttons.length;
		for (let i = 0; i < ar_buttons_length; i++) {

			const current_button = ar_buttons[i]

			// button_delete multiple
			// check if user is global admin to activate the button delete (avoid users to delete multiple sections)
				if(current_button.model==='button_delete' && page_globals.is_global_admin===false){
					continue
				}

			// button node
				const class_name	= 'warning ' + current_button.model.replace('button_', '')
				const button_node	= ui.create_dom_element({
					element_type	: 'button',
					class_name		: class_name,
					inner_html		: current_button.label,
					parent			: other_buttons_block
				})
				button_node.addEventListener('click', (e) => {
					e.stopPropagation()

					switch(current_button.model){
						case 'button_new':
							event_manager.publish('new_section_' + self.id)
							break;

						case 'button_delete': {
							// sqo conform
							const delete_sqo = clone(self.rqo.sqo)
							delete_sqo.limit = null
							delete delete_sqo.offset

							// delete_record
							self.delete_record({
								section			: self,
								section_id		: null,
								section_tipo	: self.section_tipo,
								sqo				: delete_sqo
							})
							break;
						}
						// button_import and button_trigger cases for compatibility with v5 ontology
						// in future version will be merge both with new model button_tool
						// in the mid-time use button_trigger for general cases to dispatch tools.
						case 'button_import':
						case 'button_trigger':
							// open_tool (tool_common)
							open_tool({
								tool_context	: current_button.tools[0],
								caller			: self
							})
							break;

						default:
							event_manager.publish('click_' + current_button.model)
							break;
					}
				})
		}//end for (let i = 0; i < ar_buttons_length; i++)

	// tools buttons
		ui.add_tools(self, other_buttons_block)

	// show_other_buttons_button
		const show_other_buttons_label	= get_label.show_buttons || 'Show buttons'
		const show_other_buttons_button	= ui.create_dom_element({
			element_type	: 'button',
			class_name		: 'icon_arrow show_other_buttons_button',
			title			: show_other_buttons_label,
			dataset			: {
				label : show_other_buttons_label
			},
			parent			: buttons_container
		})
		show_other_buttons_button.addEventListener('click', function(e) {
			e.stopPropagation()
		})

		// track collapse toggle state of content
		ui.collapse_toggle_track({
			toggler				: show_other_buttons_button,
			container			: other_buttons_block,
			collapsed_id		: 'section_other_buttons_block',
			collapse_callback	: collapse,
			expose_callback		: expose,
			default_state		: 'closed'
		})
		function collapse() {
			show_other_buttons_button.classList.remove('up')
		}
		function expose() {
			show_other_buttons_button.classList.add('up')
		}


	return fragment
}//end get_buttons



// @license-end
