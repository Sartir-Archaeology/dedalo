/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {get_instance, delete_instance} from '../../common/js/instances.js'
	import {ui} from '../../common/js/ui.js'
	import {service_autocomplete} from '../../services/service_autocomplete/js/service_autocomplete.js'

	import {view_autocomplete} from './view_autocomplete.js'



/**
* RENDER_COMPONENT_portal
* Manages the component's logic and apperance in client side
*/
export const render_component_portal = function() {

	return true
};//end  render_component_portal




/**
* MINI
* Render node for use in list
* @return DOM node wrapper
*/
render_component_portal.prototype.mini = async function() {

	const self = this

	const ar_section_record = await self.get_ar_instances()

	// wrapper
		const wrapper = ui.component.build_wrapper_mini(self)

	// add all nodes
		const length = ar_section_record.length
		for (let i = 0; i < length; i++) {
			const child_item = await ar_section_record[i].render()			
			wrapper.appendChild(child_item)
		}

	return wrapper
};//end  mini



/**
* LIST
* Render node for use in list
* @return DOM node wrapper
*/
render_component_portal.prototype.list = async function() {

	const self = this

	const ar_section_record = await self.get_ar_instances()

	const ar_nodes = []

	const fragment = new DocumentFragment();

	// add all nodes
		const length = ar_section_record.length
		for (let i = 0; i < length; i++) {

			//const child_item = await ar_section_record[i].node
			const child_item = await ar_section_record[i].render()

			fragment.appendChild(child_item)
		}
	// events
		// dblclick
			// wrapper.addEventListener("dblclick", function(e){
			// 	// e.stopPropagation()
			//
			// 	// change mode
			// 	self.change_mode('edit_in_list', true)
			// })

	// wrapper
		const wrapper = ui.component.build_wrapper_list(self, {
			autoload : false
		})

	wrapper.appendChild(fragment)

	return wrapper
};//end  list



/**
* EDIT
* Render node for use in edit
* @return DOM node wrapper
*/
render_component_portal.prototype.edit = async function(options={render_level:'full'}) {

	const self = this

	// render_level
		const render_level = options.render_level

	self.view = null // 'view_autocomplete'

	const view = self.view || null

	let wrapper
	switch(view) {

		case 'view_autocomplete99':
			wrapper = view_autocomplete(self, options)
			// return wrapper;
			break;

		default:
			// reset service state portal_active
				// self.portal_active = false

			// content_data
				const content_data = await get_content_data_edit(self)
				if (render_level==='content') {
					return content_data
				}

			// buttons
				const buttons = get_buttons(self)

			// top
				const top = get_top(self)

			// wrapper. ui build_edit returns component wrapper
				wrapper =	ui.component.build_wrapper_edit(self, {
					content_data	: content_data,
					buttons			: buttons
					// top			: top
				})
				wrapper.classList.add("portal")


			// events
				add_events(self, wrapper)
			break;
	}

		

	const js_promise = wrapper

	return js_promise
};//end  edit




/**
* SEARCH
* Render node for use in search
* @return DOM node wrapper
*/
render_component_portal.prototype.search = async function(options={render_level:'full'}) {

	const self = this

	// render_level
		const render_level = options.render_level


	const content_data = await get_content_data_edit(self)
	if (render_level==='content') {
					return content_data
	}

	// wrapper. ui build_edit returns component wrapper
		const wrapper = ui.component.build_wrapper_edit(self, {
			content_data : content_data
		})
		wrapper.classList.add("portal")

	// id
		wrapper.id = self.id

	// events
		add_events(self, wrapper)

	// activate service autocomplete. Enable the service_autocomplete when the user do click
		if(self.autocomplete_active===false){

			// set rqo
				self.rqo_search 	= self.rqo_search || self.build_rqo_search(self.rqo_config, 'search')

			self.autocomplete = new service_autocomplete()
			self.autocomplete.init({
				caller	: self,
				wrapper : wrapper
			})
			self.autocomplete_active = true
			self.autocomplete.search_input.focus()
		}

	return wrapper
};//end search




/**
* ADD_EVENTS
* @return bool
*/
const add_events = function(self, wrapper) {

	// add element, subscription to the events
	// show the add_value in the instance
		//self.events_tokens.push(
		//	event_manager.subscribe('add_element_'+self.id, add_element)
		//)
		//async function add_element(key) {
		//	self.refresh()
		//	// change the portal service to false and desactive it.
		//
		//	//if(self.portal_active===true){
		//	//	self.portal.destroy()
		//	//	self.portal_active = false
		//	//	self.portal 		 = null
		//	//}
		//
		//	//self.refresh();
		//
		//	// inset the new section_record into the ar_section_record and build the node of the new locator
		//	//ar_section_record.push(current_section_record)
		//	//const inputs_container 	= wrapper.querySelector('.inputs_container')
		//	//input_element(current_section_record, inputs_container)
		//}

	// subscribe to 'update_dom': if the dom was changed by other dom elements the value will be changed
		//self.events_tokens.push(
		//	event_manager.subscribe('update_dom_'+self.id, (value) => {
		//		// change the value of the current dom element
		//	})
		//)

	// click delegated
		wrapper.addEventListener("click", function(e){
			// e.stopPropagation()

			// ignore click on paginator
				//if (e.target.closest('.paginator')) {
				//	return false
				//}


			// remove row
				if (e.target.matches('.button.remove')) {
					e.preventDefault()

					const changed_data = Object.freeze({
						action	: 'remove',
						key		: JSON.parse(e.target.dataset.key),
						value	: null
					})

					switch(self.mode){

						case 'search':

							const update = self.update_data_value(changed_data)
							// publish search. Event to update the dom elements of the instance
								event_manager.publish('change_search_element', self)

							// refresh
								self.refresh()

							break;

						default:
							const changed = self.change_value({
								changed_data	: changed_data,
								label			: e.target.previousElementSibling.textContent,
								refresh			: false
							})
							changed.then(async (api_response)=>{

								// update pagination offset
									self.update_pagination_values('remove')

								// refresh
									self.refresh()

								// event to update the dom elements of the instance
									event_manager.publish('remove_element_'+self.id, e.target.dataset.key)
							})
							break;	
					}

					return true
				}


			// activate service autocomplete. Enable the service_autocomplete when the user do click
				if(self.autocomplete_active===false){

					// set rqo
						self.rqo_search 	= self.rqo_search || self.build_rqo_search(self.rqo_config, 'search')
						// self.rqo.choose 	= self.rqo.choose || self.build_rqo('choose', self.context.request_config, 'get_data')

					self.autocomplete = new service_autocomplete()
					self.autocomplete.init({
						caller	: self,
						wrapper : wrapper
					})
					self.autocomplete_active = true
					self.autocomplete.search_input.focus()

					return true
				}

		})//end click event


	return true
};//end  add_events



/**
* GET_CONTENT_DATA_EDIT
* @return DOM node content_data
*/
const get_content_data_edit = async function(self) {

	const ar_section_record = await self.get_ar_instances()
	const is_inside_tool 	= self.is_inside_tool

	const fragment = new DocumentFragment()

	// inputs contaniner
		const inputs_container = ui.create_dom_element({
			element_type	: 'ul',
			class_name		: 'inputs_container'
		})

	// build values (add all nodes from the rendered_section_record)
		const length = ar_section_record.length
		for (let i = 0; i < length; i++) {

			const current_section_record = ar_section_record[i]; 	//console.log("current_section_record:",current_section_record);
			if (!current_section_record) {
				console.warn("empty current_section_record:",current_section_record);				
			}
			// const child_item = await ar_section_record[i].render()
			// fragment.appendChild(child_item)
			await input_element(current_section_record, inputs_container)

			const section_record_node = await ar_section_record[i].render()
		}
		fragment.appendChild(inputs_container)

	// build references
		if(self.data.references && self.data.references.length > 0){
			const references_node = render_references(self.data.references)
			fragment.appendChild(references_node)
		}

	// content_data
		const content_data = ui.component.build_content_data(self)
			  content_data.classList.add("nowrap")
			  content_data.appendChild(fragment)


	return content_data
};//end  get_content_data_edit

 

/**
* GET_BUTTONS
* @param object instance
* @return DOM node buttons_container
*/
const get_buttons = (self) => {
	
	const is_inside_tool		= self.is_inside_tool
	const mode					= self.mode
	const show					= self.rqo.show
	// const target_section		= self.context.request_config.find(el => el.api_engine==='dedalo').sqo.section_tipo
	const target_section		= self.target_section
	const target_section_lenght	= target_section.length
		  // sort section by label asc
		  target_section.sort((a, b) => (a.label > b.label) ? 1 : -1)

	const fragment = new DocumentFragment()

	// button_add
		// const button_add = ui.create_dom_element({
		// 	element_type	: 'span',
		// 	class_name		: 'button add',
		// 	parent			: fragment
		// })
		// button_add.addEventListener("click", async function(e){

		// 	// data_manager. create new record
		// 		const api_response = await data_manager.prototype.request({
		// 			body : {
		// 				action			: 'create',
		// 				section_tipo	: select_section.value
		// 			}
		// 		})
		// 		// add value to current data
		// 		if (api_response.result && api_response.result>0) {
		// 			const value = {
		// 				section_tipo	: select_section.value,
		// 				section_id		: api_response.result
		// 			}
		// 			self.add_value(value)
		// 		}else{
		// 			console.error("Error on api_response on try to create new row:", api_response);
		// 		}
		// })

	// button_link
		const button_link = ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'button link',
			parent			: fragment
		})
		button_link.addEventListener("click", async function(e){
			// const section_tipo	= select_section.value
			// const section_label	= select_section.options[select_section.selectedIndex].innerHTML;
			const section_tipo	= target_section[0].tipo
			const section_label	= target_section[0].label;

			// iframe
				( () => {

					const iframe_url = (tipo) => {
						return '../page/?tipo=' + tipo + '&mode=list&initiator=' + self.id
					}

					const iframe_container = ui.create_dom_element({element_type : 'div', class_name : 'iframe_container'})
					const iframe = ui.create_dom_element({
						element_type	: 'iframe',
						class_name		: 'fixed',
						src				: iframe_url(section_tipo),
						parent			: iframe_container
					})

					// select_section
						const select_section = ui.create_dom_element({
							element_type	: 'select',
							class_name		: 'select_section' + (target_section_lenght===1 ? ' mono' : '')
						})
						select_section.addEventListener("change", function(){
							iframe.src = iframe_url(this.value)
						})
						// options for select_section
							for (let i = 0; i < target_section_lenght; i++) {
								const item = target_section[i]
								ui.create_dom_element({
									element_type	: 'option',
									value			: item.tipo,
									inner_html		: item.label + " [" + item.tipo + "]",
									parent			: select_section
								})
							}

					// header label
						const header = ui.create_dom_element({
							element_type	: 'span',
							text_content	: get_label.seccion,
							class_name		: 'label'
						})

					// header custom
						const header_custom = ui.create_dom_element({
							element_type	: 'div',
							class_name		: 'header_custom'
						})
						header_custom.appendChild(header)
						header_custom.appendChild(select_section)

					// fix modal to allow close later, on set value
					self.modal = ui.attach_to_modal(header_custom, iframe_container, null, 'big')

				})()
				return
		})


	// button tree terms selector
		if( self.rqo_config.show.interface &&
			self.rqo_config.show.interface.button_tree &&
			self.rqo_config.show.interface.button_tree=== true){
			const button_tree_selector = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'button gear',
				parent			: fragment
			})
			// add listener to the select
			button_tree_selector.addEventListener('mouseup',function(){

			},false)
		}


		if( self.rqo_config.show.interface &&
			self.rqo_config.show.interface.button_external &&
			self.rqo_config.show.interface.button_external === true){

			// button_update data external
				const button_update_data_external = ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'button sync',
					parent			: fragment
				})
				button_update_data_external.addEventListener("click", async function(e){
					const source = self.rqo_config.show.find(item => item.typo === 'source')
					source.build_options = {
						get_dato_external : true
					}
					const builded = await self.build(true)
					// render
					if (builded) {
						self.render({render_level : 'content'})
					}
				})
		}

	// buttons tools
		if (!is_inside_tool) {
			ui.add_tools(self, fragment)
		}

	// buttons container
		const buttons_container = ui.component.build_buttons_container(self)
		buttons_container.appendChild(fragment)


	return buttons_container
};//end  get_buttons



/**
* GET_TOP
* Used to add special elements to the component,like custom buttons or info
* @param object instance
* @return DOM node top
*/
const get_top = function(self) {

	if (self.mode!=='edit') {
		return null;
	}

	// sort vars
		const is_inside_tool		= self.is_inside_tool
		const mode					= self.mode
		const current_data_manager	= new data_manager()
		const show					= self.rqo.show
		const target_section		= self.rqo.sqo.section_tipo //filter(item => item.model==='section')
		const target_section_lenght	= target_section.length
		// sort section by label asc
			target_section.sort((a, b) => (a.label > b.label) ? 1 : -1)

	const fragment = new DocumentFragment()

	// select_section
		// const select_section = ui.create_dom_element({
		// 	element_type	: 'select',
		// 	class_name		: 'select_section' + (target_section_lenght===1 ? ' mono' : ''),
		// 	// parent			: fragment
		// })
		// select_section.addEventListener("click", function(e){
		// 	// e.stopPropagation()
		// })
		// select_section.addEventListener("change", function(e){
		// 		console.log("iframe_container:",iframe_container);
		// })

		// // options for select_section
		// 	for (let i = 0; i < target_section_lenght; i++) {
		// 		const item = target_section[i]
		// 		ui.create_dom_element({
		// 			element_type	: 'option',
		// 			value			: item.tipo,
		// 			inner_html		: item.label + " [" + item.tipo + "]",
		// 			parent			: select_section
		// 		})
		// 	}

	// button_add
		// const button_add = ui.create_dom_element({
		// 	element_type	: 'span',
		// 	class_name		: 'button add',
		// 	parent			: fragment
		// })
		// button_add.addEventListener("click", async function(e){

		// 	// data_manager. create new record
		// 		const api_response = await data_manager.prototype.request({
		// 			body : {
		// 				action			: 'create',
		// 				section_tipo	: select_section.value
		// 			}
		// 		})
		// 		// add value to current data
		// 		if (api_response.result && api_response.result>0) {
		// 			const value = {
		// 				section_tipo	: select_section.value,
		// 				section_id		: api_response.result
		// 			}
		// 			self.add_value(value)
		// 		}else{
		// 			console.error("Error on api_response on try to create new row:", api_response);
		// 		}
		// })

	// button_link
		// const button_link = ui.create_dom_element({
		// 	element_type	: 'span',
		// 	class_name		: 'button link',
		// 	parent			: fragment
		// })
		// button_link.addEventListener("click", async function(e){

		// 	// const section_tipo	= select_section.value
		// 	// const section_label	= select_section.options[select_section.selectedIndex].innerHTML;
		// 	const section_tipo	= target_section[0].tipo
		// 	const section_label	= target_section[0].label;

		// 	// iframe
		// 		( async () => {
		// 			const iframe_container = ui.create_dom_element({element_type : 'div', class_name : 'iframe_container'})
		// 			const iframe = ui.create_dom_element({
		// 				element_type	: 'iframe',
		// 				class_name		: 'fixed',
		// 				src				: '../page/?tipo=' + section_tipo + '&mode=list&initiator='+ self.id,
		// 				parent			: iframe_container
		// 			})

		// 			// select_section
		// 				const select_section = ui.create_dom_element({
		// 					element_type	: 'select',
		// 					class_name		: 'select_section' + (target_section_lenght===1 ? ' mono' : ''),
		// 					// parent			: fragment
		// 				})
		// 				select_section.addEventListener("click", function(e){
		// 					// e.stopPropagation()
		// 				})
		// 				select_section.addEventListener("change", function(){
		// 					iframe.src = '../page/?tipo=' + this.value + '&mode=list&initiator='+ self.id
		// 				})
		// 				// options for select_section
		// 					for (let i = 0; i < target_section_lenght; i++) {
		// 						const item = target_section[i]
		// 						ui.create_dom_element({
		// 							element_type	: 'option',
		// 							value			: item.tipo,
		// 							inner_html		: item.label + " [" + item.tipo + "]",
		// 							parent			: select_section
		// 						})
		// 					}

		// 			// header label
		// 				const header = ui.create_dom_element({
		// 					element_type	: 'span',
		// 					text_content	: get_label.seccion,
		// 					class_name		: 'label'
		// 				})

		// 			// header custom
		// 				const header_custom = ui.create_dom_element({
		// 					element_type	: 'div',
		// 					class_name		: 'header_custom'
		// 				})
		// 				header_custom.appendChild(header)
		// 				header_custom.appendChild(select_section)

		// 			// fix modal to allow close later, on set value
		// 			self.modal = ui.attach_to_modal(header_custom, iframe_container, null, 'big')

		// 		})()
		// 		return

		// 	// page
		// 		// ( async () => {

		// 		// 	const options = {
		// 		// 		model 			: 'section',
		// 		// 		type 			: 'section',
		// 		// 		tipo  			: section_tipo,
		// 		// 		section_tipo  	: section_tipo,
		// 		// 		section_id 		: null,
		// 		// 		mode 			: 'list',
		// 		// 		lang 			: page_globals.dedalo_data_lang
		// 		// 	}
		// 		// 	const page_element_call = await current_data_manager.get_page_element(options)
		// 		// 	const page_element 		= page_element_call.result

		// 		// 	const page = await get_instance({
		// 		// 		model 		: 'page',
		// 		// 		id_variant  : 'PORTAL_VARIANT',
		// 		// 		elements 	: [page_element_call.result]
		// 		// 	})
		// 		// 	page.caller = self.caller
		// 		// 	const build 		= await page.build()
		// 		// 	const wrapper_page 	= await page.render()
		// 		// 	const header = ui.create_dom_element({element_type : 'div',text_content : section_label})
		// 		// 	const modal  = ui.attach_to_modal(header, wrapper_page, null, 'big')
		// 		// 		console.log("page:",page);
		// 		// })()
		// 		// return

		// 	// section
		// 		// // find_section options. To create a complete set of options (including sqo), call API requesting a page_elemen
		// 		// 	const options = {
		// 		// 		model 			: 'section',
		// 		// 		type 			: 'section',
		// 		// 		tipo  			: section_tipo,
		// 		// 		section_tipo  	: section_tipo,
		// 		// 		section_id 		: null,
		// 		// 		mode 			: 'list',
		// 		// 		lang 			: page_globals.dedalo_data_lang
		// 		// 	}
		// 		// 	const page_element_call = await current_data_manager.get_page_element(options)
		// 		// 	const page_element 		= page_element_call.result
		// 		// 	// id_variant avoid instances id collisions
		// 		// 		page_element.id_variant = 'ID_VARIANT_PORTAL'
		// 		// 	const find_section_options = page_element

		// 		// // find_section instance. Create target section page element and instance
		// 		// 	const find_section = await get_instance(find_section_options)

		// 		// 	// set self as find_section caller (!)
		// 		// 		find_section.caller = self

		// 		// 	// load data and render wrapper
		// 		// 		await find_section.build(true)
		// 		// 		const find_section_wrapper = await find_section.render()

		// 		// // modal container
		// 		// 	const header = ui.create_dom_element({
		// 		// 		element_type	: 'div',
		// 		// 		text_content 	: section_label
		// 		// 	})
		// 		// 	// fix modal to allow close later, on set value
		// 		// 		self.modal = ui.attach_to_modal(header, find_section_wrapper, null, 'big')
		// 		// 		self.modal.on_close = () =>{
		// 		// 			find_section.destroy(true, true, true)
		// 		// 		}
		// })

	// top container
		const top = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'top'
		})
		// top.addEventListener("click", function(e){
		// 	e.stopPropagation()
		// })
		top.appendChild(fragment)


	return top
};//end  get_top



/**
* INPUT_ELEMENT
* @return dom element li
*/
const input_element = async function(current_section_record, inputs_container){

	const caller_mode = current_section_record.caller.mode
	const key = caller_mode==='search' ? 0 : current_section_record.paginated_key

	// li
		const li = ui.create_dom_element({
			element_type	: 'li',
			dataset			: { key : key },
			parent			: inputs_container
		})

	// input field
		const section_record_node = await current_section_record.render()
		li.appendChild(section_record_node)

	// button remove
		const button_remove = ui.create_dom_element({
			element_type	: 'span',
			class_name		: 'button remove',
			dataset			: { key : key },
			parent			: li
		})


	return li
};//end  input_element



/**
* RENDER_REFERENCES
* @return DOM node fragment
*/
const render_references = function(ar_references) {

	const fragment = new DocumentFragment()

	// ul
		const ul = ui.create_dom_element({
			element_type	: 'ul',
			class_name		: 'references',
			parent			: fragment
		})

	// references label
		ui.create_dom_element({
			element_type	: 'div',
			inner_html 		: get_label.references,
			parent			: ul
		})

	const ref_length = ar_references.length
	for (let i = 0; i < ref_length; i++) {

		const reference = ar_references[i]

		// li
			const li = ui.create_dom_element({
				element_type	: 'li',
				parent			: ul
			})
			// button_link
				const button_link = ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'button link',
					parent			: li
				})
				button_link.addEventListener("click", function(e){
					e.stopPropagation()
					window.location.href = '../page/?tipo=' + reference.value.section_tipo + '&id='+ reference.value.section_id
					// window.open(url,'ref_edit')
				})
			// label
				const button_edit = ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'label',
					inner_html		: reference.label,
					parent			: li
				})
	}

	return fragment
};//end render_references
