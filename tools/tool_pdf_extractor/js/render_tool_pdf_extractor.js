/*global get_tool_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../../core/common/js/event_manager.js'
	import {ui} from '../../../core/common/js/ui.js'



/**
* RENDER_TOOL_PDF_EXTRACTOR
* Manages the component's logic and appearance in client side
*/
export const render_tool_pdf_extractor = function() {

	return true
}//end render_tool_pdf_extractor



/**
* EDIT
* Render node
* @return DOM node
*/
render_tool_pdf_extractor.prototype.edit = async function (options) {

	const self = this

	// options
		const render_level 	= options.render_level

	// content_data
		const current_content_data = await get_content_data(self)
		if (render_level==='content') {
			return current_content_data
		}

	// wrapper. ui build_edit returns component wrapper
		const wrapper = ui.tool.build_wrapper_edit(self, {
			content_data : current_content_data
		})

	// // buttons container
		// 	const buttons_container = ui.create_dom_element({
		// 		element_type	: 'div',
		// 		class_name 		: 'buttons_container',
		// 		parent 			: wrapper
		// 	})


	// tool_container
		//const tool_container = document.getElementById('tool_container')
		//if(tool_container!==null){
		//	tool_container.appendChild(wrapper)
		//}else{
		//	const main = document.getElementById('main')
		//	const new_tool_container = ui.create_dom_element({
		//		id 				: 'tool_container',
		//		element_type	: 'div',
		//		parent 			: main
		//	})
		//	new_tool_container.appendChild(wrapper)
		//}

	// modal container
		// if (!window.opener) {
		// 	const header	= wrapper.tool_header // is created by ui.tool.build_wrapper_edit
		// 	const modal		= ui.attach_to_modal(header, wrapper, null)
		// 	modal.on_close	= () => {
		// 		self.destroy(true, true, true)
		// 	}
		// }

	// events
		// click
			// wrapper.addEventListener("click", function(e){
			// 	e.stopPropagation()
			// 	console.log("e:",e);
			// 	return
			// })


	return wrapper
}//end render_tool_pdf_extractor



/**
* GET_CONTENT_DATA
* @return DOM node content_data
*/
const get_content_data = async function(self) {

	const fragment = new DocumentFragment()

	// range page
		const page_range = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'page_in',
			inner_html		: '',
			parent			: fragment
		})

		// page_in
			const page_in_label = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'page_in',
				inner_html		: self.get_tool_label('page_in'),
				parent			: page_range
			})
			const page_in = ui.create_dom_element({
				element_type	: 'input',
				type 			: 'number',
				class_name		: 'page_in',
				parent 			: page_range
			})
			page_in.addEventListener('change',(e)=>{
				self.config.page_in = (!e.target.value || e.target.value==='')
					? false
					: e.target.value
			})
		// page_out
			const page_out_label = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'page_in',
				inner_html		: self.get_tool_label('page_out'),
				parent			: page_range
			})
			const page_out = ui.create_dom_element({
				element_type	: 'input',
				type 			: 'number',
				class_name		: 'page_out',
				parent 			: page_range
			})
			page_out.addEventListener('change',(e)=>{
				self.config.page_out = (!e.target.value || e.target.value==='')
					? false
					: e.target.value
			})
		// method
		// the user can choose the methof of the extaction, it can be "text" or "html", the process will change the daemon into the server
			const method_label = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'page_in',
				inner_html		: self.get_tool_label('proces_method'),
				parent			: page_range
			})
				// ul
				const radio_ul = ui.create_dom_element({
					element_type	: 'ul',
					parent 			: page_range
				})
				// li
					const radio_li = ui.create_dom_element({
						element_type	: 'li',
						parent 			: radio_ul
					})
					// option txt
						const option_txt = ui.create_dom_element({
							element_type	: 'input',
							type			: 'radio',
							value			: 'txt',
							name			: self.id,
							parent			: radio_li
						})
						option_txt.checked = 'checked'
						option_txt.addEventListener('change', ()=>{
							self.config.method = 'text_engine'
						})
						const option_txt_label = ui.create_dom_element({
							element_type	: 'label',
							inner_html		: 'txt',
							parent			: radio_li
						})
					// option html
						const option_html = ui.create_dom_element({
							element_type	: 'input',
							type			: 'radio',
							value			: 'html',
							name			: self.id,
							parent			: radio_li
						})
						option_html.addEventListener('change',()=>{
							self.config.method = 'html_engine'
						})
						const option_html_label = ui.create_dom_element({
							element_type	: 'label',
							inner_html		: 'html',
							parent			: radio_li
						})
		// buton submit

		const button_submit = ui.create_dom_element({
			element_type	: 'button',
			class_name		: 'warning',
			inner_html		: self.get_tool_label('do_process'),
			parent			: page_range
		})
		button_submit.addEventListener('mouseup', async ()=>{
			const extracted_data 	= await self.get_pdf_data(self)
			const pdf_data 			= await self.process_pdf_data(extracted_data.result)
			const changed_data 		= {
				key 	: 0,
				value 	: pdf_data
			}
			event_manager.publish('set_pdf_data' +'_'+ self.caller.id_base, changed_data)
		})


	// response_container
		const response_container = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'response_container',
			parent 			: fragment
		})
		// response_msg
		const response_msg = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'response_msg',
			parent 			: response_container
		})

	// info
		// container info
		const info = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'info',
			// inner_html 	: '',
			parent 			: fragment
		})
		// caller component
		ui.create_dom_element({
			element_type	: 'div',
			inner_html	 	: '<label>Caller component</label>' + self.caller.model,
			parent 			: info
		})

	// buttons container
		// 	const buttons_container = ui.create_dom_element({
		// 		element_type	: 'div',
		// 		class_name 		: 'buttons_container',
		// 		parent 			: components_container
		// 	})

	// content_data
		const content_data = ui.tool.build_content_data(self)
		content_data.appendChild(fragment)


	return content_data
}//end get_content_data


