/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// import
	import {ui} from '../../common/js/ui.js'



/**
* RENDER_PAGINATOR
* Manages the component's logic and apperance in client side
*/
export const render_paginator = function() {

	return true
};//end render_paginator



/**
* EDIT
* Render node for use in edit
* @return DOM node wrapper
*/
render_paginator.prototype.edit = async function(options) {

	const self = this

	// options
		const render_level = options.render_level || 'full'

	// refresh case. Only content data is returned
		if (render_level==='content') {
			await self.get_total()
			return get_content_data(self)
		}

	// wrapper
		const wrapper = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'wrapper_paginator edit full_width css_wrap_rows_paginator text_unselectable'
		})

	// content data. Added when total is ready
		self.get_total()
		.then(function(response){
			const content_data_node = get_content_data(self)
			wrapper.appendChild(content_data_node)
		})

	// events
		add_events(wrapper, self)


	return wrapper
};//end edit



/**
* ADD_EVENTS
* Attach element generic events to wrapper
* @return bool
*/
const add_events = (wrapper, self) => {

	// mousedown
		wrapper.addEventListener("mousedown", function(e){
			e.stopPropagation()
			//e.preventDefault()
			// prevent buble event to container element
			return false
		})


	return true
};//end add_events



/**
* GET_CONTENT_DATA
* @return DOM node content_data
*/
const get_content_data = function(self) {

	// build vars
		const total				= self.caller.total
		const limit				= self.get_limit()
		const offset			= self.get_offset()

		const total_pages		= self.total_pages
		const page_number		= self.page_number
		const prev_page_offset	= self.prev_page_offset
		const next_page_offset	= self.next_page_offset
		const page_row_begin	= self.page_row_begin
		const page_row_end		= self.page_row_end
		const offset_first		= self.offset_first
		const offset_prev		= self.offset_prev
		const offset_next		= self.offset_next
		const offset_last		= self.offset_last

		if(SHOW_DEBUG===true) {
			// const model = self.id.split("_")[1] +" "+ self.id.split("_")[2]
			// console.log(`++++++++++++++++++++++ total_pages: ${total_pages}, page_number: ${page_number}, offset: ${offset}, offset_first: ${offset_first}, model: ${model} `);
		}

	// display none with empty case, or when pages are <2
		if (!total_pages || total_pages<2) {
			const wrap_rows_paginator = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'content_data paginator display_none ' +total_pages
			})
			return wrap_rows_paginator
		}

	const fragment = new DocumentFragment()

	// nav_buttons
		const paginator_div_links = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'nav_buttons',
			parent			: fragment
		})

		// btn first
			const paginator_first = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'btn paginator_first_icon',
				parent			: paginator_div_links,
			})
			if(page_number>1) {
				paginator_first.addEventListener("mousedown",function(){
					self.paginate(offset_first)
				})
			}else{
				paginator_first.classList.add("unactive")
			}

		// btn previous
			const paginator_prev = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'btn paginator_prev_icon',
				parent			: paginator_div_links,
			})
			if(prev_page_offset>=0) {
				paginator_prev.addEventListener("mousedown",function(){
					self.paginate(offset_prev)
				})
			}else{
				paginator_prev.classList.add("unactive")
			}

		// btn next
			const paginator_next = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'btn paginator_next_icon',
				parent			: paginator_div_links
			})
			if(next_page_offset<total) {
				paginator_next.addEventListener("mousedown",function(){
					self.paginate(offset_next)
				})
			}else{
				paginator_next.classList.add("unactive")
			}

		// btn last
			const paginator_last = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'btn paginator_last_icon',
				parent			: paginator_div_links
			})
			if(page_number<total_pages) {
				paginator_last.addEventListener("mousedown",function(){
					self.paginate(offset_last)
				})
			}else{
				paginator_last.classList.add("unactive")
			}

	// paginator_info
		const paginator_info = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'paginator_info',
			parent			: fragment
		})

		const page_info = ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'page_info',
			text_content 	: (get_label["pagina"] || "Page") + ` ${page_number} ` + (get_label["de"] || "of") + ` ${total_pages} `,
			parent			: paginator_info
		})

		const displayed_records = ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'displayed_records',
			text_content 	: `Showed ${page_row_begin}-${page_row_end} of ${total}. `,
			parent			: paginator_info
		})

		const goto_page = ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'goto_page',
			text_content 	: get_label["go_to_page"],
			parent			: paginator_info
		})

		// input_go_to_page
			const input_go_to_page = ui.create_dom_element({
				element_type	: 'input',
				class_name		: 'input_go_to_page',
				parent			: goto_page
			})
			input_go_to_page.placeholder = page_number
			// add the Even onchage to the select, whe it change the section selected will be loaded
			input_go_to_page.addEventListener('keyup',function(event){
				self.go_to_page_json(this, event, total_pages, limit)
			})

		// let text = ""
		// 	text += get_label["pagina"] || "Page"
		// 	text += " " + page_number + " "
		// 	text += get_label["de"] || "of"
		// 	text += " " + total_pages
		// //if (modo==="edit") {
		// //	text += '. ' + get_label['go_to_record']  + ' '
		// //}else{
		// 	text += '<span class="displayed_records">. Displayed records from ' + page_row_begin + ' to ' + page_row_end + ' of ' + total + '.</span> '
		// 	text += '<span class="go_to_page_text">' + get_label["go_to_page"] + '</span> '
		// //}

	// content_data
		const content_data = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_data paginator css_rows_paginator_content'
		})
		content_data.appendChild(fragment)


	return content_data
};//end get_content_data


