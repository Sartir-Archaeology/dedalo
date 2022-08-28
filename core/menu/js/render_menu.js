/*global get_label, page_globals, DEDALO_CORE_URL, SHOW_DEBUG, SHOW_DEVELOPER */
/*eslint no-undef: "error"*/



// import
	import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {quit} from '../../login/js/login.js'
	import {open_tool} from '../../../tools/tool_common/js/tool_common.js'
	// import {clone} from '../../common/js/utils/index.js'
	// import {instances} from '../../common/js/instances.js'


/**
* RENDER_MENU
* Manages the component's logic and appearance in client side
*/
export const render_menu = function() {

	return true
}//end render_menu



/**
* EDIT
* Render node for use in edit
* @return DOM node wrapper
*/
render_menu.prototype.edit = async function() {

	const self = this

	// menu_active. set the first state of the menu
		self.menu_active = false

	// username
		const username = self.data.username

	const fragment = new DocumentFragment()

	// quit_button
		const quit_button = ui.create_dom_element({
			element_type	: 'div',
			id				: 'quit',
			parent			: fragment
		})
		quit_button.addEventListener('click', () => {
			// local_db_data remove in all langs
				for (let i = 0; i < self.data.langs_datalist.length; i++) {
					const lang	= self.data.langs_datalist[i].value
					const regex	= /lg-[a-z]{2,5}$/
					const id	= self.id.replace(regex, lang)
					data_manager.delete_local_db_data(id, 'data')
				}
			// exec login quit sequence
				quit()
		})

	// logo image
		const dedalo_icon = ui.create_dom_element({
			element_type	: 'a',
			id				: 'dedalo_icon_top',
			parent			: fragment
		})
		dedalo_icon.addEventListener('click', function(){
			window.open('https://dedalo.dev', 'Dédalo Site', []);
		})

	// areas/sections hierarchy list
		const hierarchy = ui.create_dom_element({
			element_type	: 'div',
			id				: 'menu_hierarchy',
			parent			: fragment
		})
		// render first level (root)
		level_hierarchy({
			self			: self,
			datalist		: self.data.tree_datalist,
			root_ul			: hierarchy,
			current_tipo	: 'dd1',
			parent_tipo		: 'dd1'
		})

		// click . Manages global click action on the menu items
			hierarchy.addEventListener('click', (e) => {
				// e.stopPropagation()
				// e.preventDefault()

				// first menu items only (when the ul is the main menu)

				//close all menu items when the menu change to inactive
				if (self.menu_active===true) {
					close_all_drop_menu(self);
					self.menu_active = false
				}else{
					//reset all nodes to inactive state
					close_all_drop_menu(self);
					// get the main li nodes
					const main_li	= e.target.parentNode
					const nodes_li	= self.li_nodes
					const len		= nodes_li.length
					//get the main ul nodes
					const open_id	=  main_li.dataset.children
					const open_ul	= document.getElementById(open_id)
					//set the css visibility for the ul
					open_ul.classList.remove("menu_ul_hidden");
					open_ul.classList.add("menu_ul_displayed");
					//move the ul to the left posion of the parent li
					open_ul.style.left = (main_li.getBoundingClientRect().left+'px')

					for (let i = len - 1; i >= 0; i--) {
						//inactived all li nodes
						nodes_li[i].classList.add("menu_li_inactive");
						nodes_li[i].classList.remove("menu_li_active");

						// active only the selected li node
						if(nodes_li[i] == main_li){
							nodes_li[i].classList.add("menu_li_active");
							nodes_li[i].classList.remove("menu_li_inactive");
						}
					}
					self.menu_active = true
				}// end if (self.menu_active===true)
			})

		// mousedown. document. do global click action on the document body
			document.addEventListener('mousedown', (e) => {
				// if the menu is inactive nothing to do
				if(self.menu_active===false) {
					return false
				}
				// if the user do click in other node than 'a' node, close all nodes, no other action to do
				if (e.target.tagName.toLowerCase()!=='a') {
					close_all_drop_menu(self)
				}
			})

		// keydown. set the escape key to close al menu nodes
			document.addEventListener('keydown', (e) => {
				if(self.menu_active===false) {
					return false
				}
				if (e.key==='Escape') {
					close_all_drop_menu(self);
				}
			})

	// ontology link
		if (self.data && self.data.show_ontology===true) {
			const ontology_link = ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'ontology',
				parent			: fragment,
				text_content	: 'Ontology'
			})
			ontology_link.addEventListener('click', () => {
				const url = DEDALO_CORE_URL + '/ontology'
				const win = window.open(url, '_blank');
					  win.focus();
			})
		}

	// user name link (go to list)
		const logged_user_name = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'logged_user_name',
			text_content	: username,
			parent			: fragment
		})
		if (username!=='root') {
			logged_user_name.addEventListener('click', (e) => {
				e.stopPropagation();

				// tool_context (minimum created on the fly)
					const tool_context = {
						model		: 'tool_user_admin',
						tool_config	: {
							ddo_map : []
						}
					}

				// open_tool (tool_common)
					open_tool({
						tool_context	: tool_context,
						caller			: self
					})
			})
		}

	// application lang selector
		const lang_datalist = self.data.langs_datalist
		const dedalo_aplication_langs_selector = ui.build_select_lang({
			id			: 'dd_app_lang',
			langs		: lang_datalist,
			action		: change_lang,
			selected	: page_globals.dedalo_application_lang,
			class_name	: 'reset_input dedalo_aplication_langs_selector'
		})
		dedalo_aplication_langs_selector.title = get_label.interface || 'Interface'
		fragment.appendChild(dedalo_aplication_langs_selector)

	// data lang selector
		const lang_datalist_data = lang_datalist.map(item =>{
			return {
				label	: (get_label.data || 'data') + ': ' + item.label,
				value	: item.value
			}
		})
		const dedalo_data_langs_selector = ui.build_select_lang({
			id			: 'dd_data_lang',
			langs		: lang_datalist_data,
			action		: change_lang,
			selected	: page_globals.dedalo_data_lang,
			class_name	: 'reset_input dedalo_aplication_langs_selector'
		})
		fragment.appendChild(dedalo_data_langs_selector)

	// menu_spacer
		ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'menu_spacer',
			parent			: fragment
		})

	// section label button (go to list)
		const section_label = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'section_label',
			parent			: fragment
		})
		// update value, subscription to the changes: if the section or area was changed, observers dom elements will be changed own value with the observable value
			let current_instance
			self.events_tokens.push(
				event_manager.subscribe('render_instance', fn_update_section_label)
			)
			function fn_update_section_label(instance) {
				// console.log("------ fn_update_section_label instance:",instance);
				// console.log("------ instances:", instances.filter(el => el.type==='section'));
				if((instance.type==='section'|| instance.type==='area') && instance.mode!=='tm'){

					if (current_instance && instance.tipo===current_instance.tipo && current_instance.mode!=='edit') {
						// nothing to do. We are already on a list
					}else{
						// update section label
						// change the value of the current DOM element
						// section_label.innerHTML = instance.label
						// clean
						while (section_label.firstChild) {
							section_label.removeChild(section_label.firstChild)
						}
						section_label.insertAdjacentHTML('afterbegin', instance.label);
					}

					// update current instance
					current_instance = instance
				}
			}
			section_label.addEventListener('click', async (e) => {
				e.stopPropagation()
				e.preventDefault()

				// navigate browser from edit to list
				// Note that internal navigation (based on injected browser history) uses the stored local database
				// saved_rqo if exists. Page real navigation (reload page for instance) uses server side sessions to
				// preserve offset and order
				if (current_instance.mode==='edit') {

					// local_db_data. On section paginate, local_db_data is saved. Recover saved rqo here to
					// go to list mode in the same position (offset) that the user saw
						const list_id_expected	= current_instance.id.replace('_edit_','_list_')
						const saved_rqo			= await data_manager.get_local_db_data(
							list_id_expected,
							'rqo'
						)
						if(SHOW_DEBUG===true) {
							// console.log('-------------- saved_rqo:', list_id_expected, saved_rqo);
						}

					// sqo. Note that we are changing from edit to list mode and current offset it's not applicable
					// The list offset will be get from server session if exists
						const sqo = saved_rqo && saved_rqo.sqo
							? saved_rqo.sqo
							: {
								filter	: current_instance.rqo.sqo.filter,
								order	: current_instance.rqo.sqo.order || null,
								offset	: current_instance.offset_list
							 }
						sqo.section_tipo = current_instance.rqo_config.sqo.section_tipo // always use rqo_config format
						if(SHOW_DEBUG===true) {
							// console.log("---- fn_update_section_label sqo:", sqo.offset, sqo);
							// console.log("---- fn_update_section_label current_instance:", current_instance);
						}

					// source
						const source = saved_rqo && saved_rqo.source
							? saved_rqo.source
							: {
								action			: 'search',
								model			: current_instance.model, // section
								tipo			: current_instance.tipo,
								section_tipo	: current_instance.section_tipo,
								mode			: 'list',
								lang			: current_instance.lang
							 }

					// navigation
						const user_navigation_rqo = {
							caller_id	: self.id,
							source		: source,
							sqo			: sqo  // new sqo to use in list mode
						}
						event_manager.publish('user_navigation', user_navigation_rqo)
				}
				self.menu_active = false
			})

	// inspector button toggle
		const toggle_inspector = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'button_toggle_inspector',
			parent			: fragment
		})
		toggle_inspector.addEventListener("click", function(e) {
			ui.toggle_inspector(e)
		})

	// debug info bar
		if(SHOW_DEVELOPER===true) {
			fragment.appendChild( get_debug_info_bar(self) );
		}

	// menu_wrapper
		const menu_wrapper = document.createElement("div")
			  menu_wrapper.classList.add('menu_wrapper','menu')
			  menu_wrapper.appendChild(fragment)
		// menu left band
			if (page_globals.is_root===true) {
				menu_wrapper.classList.add('is_root')
			}
			else if (page_globals.is_global_admin===true) {
				menu_wrapper.classList.add('is_global_admin')
			}


	return menu_wrapper
}//end edit



/**
* GET_DEBUG_INFO_BAR
* @param object instance self
* @return dom node debug_info_bar
*/
const get_debug_info_bar = (self) => {

	const debug_info_bar = ui.create_dom_element({
		element_type	: 'div',
		class_name		: 'debug_info_bar'
	})

	const dedalo_version = ui.create_dom_element({
		element_type	: 'div',
		class_name		: 'dedalo_version',
		text_content	: 'Dédalo v. ' + page_globals.dedalo_version,
		parent			: debug_info_bar
	})

	const dedalo_db_name = ui.create_dom_element({
		element_type	: 'div',
		class_name		: 'dedalo_db_name',
		text_content	: 'Database: ' + page_globals.dedalo_db_name,
		parent			: debug_info_bar
	})

	const pg_version = ui.create_dom_element({
		element_type	: 'div',
		class_name		: 'pg_version',
		text_content	: 'PG v. ' + page_globals.pg_version,
		parent			: debug_info_bar
	})

	const php_version = ui.create_dom_element({
		element_type	: 'div',
		class_name		: 'php_version',
		text_content	: 'PHP v. ' + page_globals.php_version,
		parent			: debug_info_bar
	})

	const php_memory = ui.create_dom_element({
		element_type	: 'div',
		class_name		: 'php_memory',
		text_content	: 'PHP memory: ' + page_globals.php_memory,
		parent			: debug_info_bar
	})

	const php_sapi_name = ui.create_dom_element({
		element_type	: 'div',
		class_name		: 'php_sapi_name',
		text_content	: 'PHP sapi. ' + self.data.info_data.php_sapi_name,
		parent			: debug_info_bar
	})


	return debug_info_bar
}//end get_debug_info_bar



/**
* LEVEL HIERARCHY
* @return bool
*/
const level_hierarchy = (options) => {

	// options
		const self			= options.self
		const datalist		= options.datalist
		const root_ul		= options.root_ul
		const current_tipo	= options.current_tipo

	// ul container
		const ul = ui.create_dom_element({
			element_type	: 'ul',
			parent			: root_ul,
			id				: current_tipo
		})

	// store in the instance the new ul node
		self.ul_nodes.push(ul)

	// values (li nodes dependents of the ul)
		const root_areas		= datalist.filter(item => item.parent===current_tipo)
		const root_areas_length	= root_areas.length
		for (let i = 0; i < root_areas_length; i++) {
			//create the li and a nodes inside the current ul
			item_hierarchy({
				self			: self,
				datalist		: datalist,
				root_ul			: root_ul,
				ul_container	: ul,
				item			: root_areas[i],
				current_tipo	: current_tipo
			})
		}

	return true
}//end level_hierarchy



/**
* ITEM_HIERARCHY
* @return DOM element li
*/
const item_hierarchy = (options) => {

	// options
		const self			= options.self
		const datalist		= options.datalist
		const ul_container	= options.ul_container
		const root_ul		= options.root_ul
		const item			= options.item
		const current_tipo	= options.current_tipo

	// li
		const li = ui.create_dom_element({
			element_type	: 'li',
			class_name		: 'menu_li_inactive',
			parent			: ul_container
		})

		self.li_nodes.push(li)

	// events
		// mouseover
			li.addEventListener('mouseover', (e) => {
				//e.stopPropagation();
				if(self.menu_active===false) {
					return false
				}//end if self.menu_active

				// get current node mouse is over
				const active_li = e.target.nodeName==='A' ? e.target.parentNode : e.target
				// get all nodes inside ul
				const nodes_li	= ul_container.getElementsByTagName('li')
				const len		= nodes_li.length
				for (let i = len - 1; i >= 0; i--) {

					// inactive all nodes
					nodes_li[i].classList.add("menu_li_inactive")
					nodes_li[i].classList.remove("menu_li_active")

					// close all ul nodes dependent of the current li
					const close_id = nodes_li[i].dataset.children
					close_all_children(close_id)

					// check if the active li is the current loop node.
					if(nodes_li[i]===active_li){

						// active the current li
						nodes_li[i].classList.add("menu_li_active");
						nodes_li[i].classList.remove("menu_li_inactive");
						// if the active li has children
						const open_id = active_li.dataset.children

						if(open_id){

							//get the ul node and active it
							const open_ul = document.getElementById(open_id)

							open_ul.classList.remove("menu_ul_hidden");
							open_ul.classList.add("menu_ul_displayed");

							//first menu li nodes has parent 'dd1' and the position in the screen is calculated by the end of the parent li node
							if(active_li.parentNode.id === 'dd1'){
								open_ul.style.left = (active_li.getBoundingClientRect().left -1 )+'px'
							}else{
								// the node is totally visible and don't need move to the top
								open_ul.style.top = active_li.getBoundingClientRect().top+'px'
								// normal calculation for the hierarchy menus
								// get the botton positon of the ul and remove the height of the window
								const ul_bottom_dif = open_ul.getBoundingClientRect().bottom - window.innerHeight//document.documentElement.clientHeight
								// if the position is outside of the window (>0)
								if (ul_bottom_dif>0) {
									// get the top of the current li and remove the oversize outsize of the window
									const total_top = active_li.getBoundingClientRect().top - ul_bottom_dif
									open_ul.style.top = total_top +'px'
								}
								// move the node to the right position of the selected li
								open_ul.style.left = active_li.getBoundingClientRect().right+'px'
							}//end if(active_li.parentNode.id === 'dd1')
						}//end if(open_id)

					}//end if(nodes_li[i] == active_li)
				}//end for
			});//end mouseover

		// mouseout
			li.addEventListener('mouseout', (e) => {
				// e.stopPropagation();
				if (e.clientY<0 || e.srcElement.id==='menu_wrapper') {
					close_all_drop_menu(self);
				}

				return true
			});//end mouseout


		// remove the html <mark> sended by the server
		// when the label is not in the current language
		// and get the label with fallback
		// and replace it for italic style
			const is_fallback	= item.label.indexOf('<mark>')
			const text_fallback	= is_fallback === -1 ? '' : 'mark'
			const label_text	= item.label.replace(/(<([^>]+)>)/ig,"");

		// a element with the link to the area or section to go
			const link = ui.create_dom_element({
				element_type	: 'a',
				class_name		: 'area_label ' + text_fallback,
				inner_html		: label_text,
				parent			: li
			})

		// click
		// when the user do click publish the tipo to go and set the mode in list
		// the action can be executed mainly in page, but it can be used for any instance.
			link.addEventListener('click', (e) => {
				// e.preventDefault()
				// e.stopPropagation()

				// nonactive menu case
				if (self.menu_active===false) {
					return false
				}//end if self.menu_active

				if (e.altKey===true) {
					// open in new tab
					const base_url = window.location.pathname
					const url = base_url + "?tipo=" + item.tipo + "&mode=list"
					const win = window.open(url, '_blank');
						  win.focus();
				}else{
					// navigate
					event_manager.publish('user_navigation', {
						source : {
							tipo	: item.tipo,
							model	: item.model,
							mode	: 'list',
							// this config comes from properties (used by section_tool to define the config of the section that its called)
							config	: item.config || null
						}
					})
				}

				return true
			})//end link.addEventListener("click")

	// children_item. recursive generation of children nodes of the current li node.
		const children_item	= datalist.find(children_item => children_item.parent===item.tipo)
		if (children_item) {

			li.classList.add ('has-sub')
			li.dataset.children	= item.tipo
			level_hierarchy({
				self			: self,
				datalist		: datalist,
				root_ul			: root_ul,
				current_tipo	: item.tipo,
				parent_tipo		: current_tipo
			})

		}//end children_item

	return li
}//end item_hierarchy



/**
* CLOSE_ALL_DROP_MENU
* Select all nodes in the menu instance and set the css to remove the visualization
* @return bool
*/
const close_all_drop_menu = function(self) {

	self.menu_active = false

	// close all ul nodes stored in the menu instance
	if (typeof self.ul_nodes!=="undefined") {

		const len = self.ul_nodes.length
		for (let i = len - 1; i >= 0; i--) {
			const ul = self.ul_nodes[i]
				  ul.classList.add("menu_ul_hidden");
				  ul.classList.remove("menu_ul_displayed");
		}
	}
	// close all li nodes stored in the menu instance
	if (typeof self.li_nodes!=="undefined") {

		const len = self.li_nodes.length
		for (let i = len - 1; i >= 0; i--) {
			const li = self.li_nodes[i]
				  li.classList.add("menu_li_inactive");
				  li.classList.remove("menu_li_active");
		}
	}

	return true
}//end close_all_drop_menu



/**
* CLOSE_ALL_CHILDREN
* Get all nodes children of the tipo set to them the css to remove the visualization
* @param string tipo
* @return bool
*/
const close_all_children = function(tipo){

	if(tipo){
		//get the children nodes of the sent tipo and add/remove the css
		const close_ul = document.getElementById(tipo)
			  close_ul.classList.remove("menu_ul_displayed");
			  close_ul.classList.add("menu_ul_hidden");

		// get the child nodes of the current ul
		const ar_children_nodes	= close_ul.childNodes
		const child_len			= ar_children_nodes.length
		for (let i = child_len - 1; i >= 0; i--) {
			// get the children link node of the current li
			const new_tipo = ar_children_nodes[i].dataset.children
			// recursive action of the current children ul tipo
			close_all_children(new_tipo)
		}
	}

	return true
}//end close_all_children



/**
* CHANGE_LANG
* @return promise
* 	API request response
*/
const change_lang = async function(e) {
	e.stopPropagation()
	e.preventDefault()

	const current_lang = e.target.value

	const api_response = await data_manager.request({
		body : {
			action	: 'change_lang',
			dd_api	: 'dd_utils_api',
			options	: {
				dedalo_data_lang		: current_lang,
				dedalo_application_lang	: e.target.id==='dd_data_lang' ? null : current_lang
			}
		}
	})
	window.location.reload(false);

	//event_manager.publish('user_navigation', {lang: current_lang})

	return api_response
}//end change_lang
