/* global get_label, page_globals, SHOW_DEBUG */
/*eslint no-undef: "error"*/



// imports
	import {data_manager} from '../../common/js/data_manager.js'
	import {ui} from '../../common/js/ui.js'
	import {strip_tags} from '../../../core/common/js/utils/index.js'



/**
* RENDER_LOGIN
* Manages the component's logic and appearance in client side
*/
export const render_login = function() {

	return true
}//end render_login



/**
* EDIT
* Render node for use in edit
* @param object options
* @return HTMLElement wrapper
*/
render_login.prototype.edit = async function(options) {

	const self = this

	// options
		const render_level = options.render_level || 'full'

	// content_data
		const content_data = get_content_data(self)
		if (render_level==='content') {
			return content_data
		}

	// wrapper. ui build_edit returns component wrapper
		const wrapper = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'login'
		})
		wrapper.appendChild(content_data)
		// set pointers
		wrapper.content_data = content_data

	// validate browser version
		validate_browser()

	// auto-focus username
		setTimeout(()=>{
			const username = content_data.querySelector('#username')
			if (username) {
				username.focus()
			}
		}, 600)


	return wrapper
}//end edit



/**
* GET_CONTENT_DATA
* @param instance self
* @return HTMLElement content_data
*/
const get_content_data = function(self) {

	const fragment = new DocumentFragment()

	// top
		const top = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'top hide',
			parent			: fragment
		})
		// const files_loader = render_files_loader()
		// top.appendChild(files_loader)

	// select lang
		const langs			= self.context.properties.dedalo_application_langs
		const select_lang	= ui.build_select_lang({
			langs 	 : langs,
			selected : page_globals.dedalo_application_lang,
			action 	 : async (e) => {
				const lang = e.target.value || null
				if (lang) {
					// data_manager api call
					await data_manager.request({
						body : {
							action	: 'change_lang',
							dd_api	: 'dd_utils_api',
							options	: {
								dedalo_data_lang		: lang,
								dedalo_application_lang	: lang
							}
						}
					})
					window.location.reload(false);
				}
			}
		})
		fragment.appendChild(select_lang)

	// form
		const form = ui.create_dom_element({
			element_type	: 'form',
			class_name		: 'login_form',
			parent			: fragment
		})
		form.addEventListener('submit', (e) => {
			e.preventDefault()
			button_enter.click()
		})

	// login_items
		const login_items = self.context.properties.login_items

	// check login_items. If there were problems with type resolution, maybe the Ontology tables are not reachable
		if (!login_items || !login_items.find(el => el.tipo==='dd255')) {

			return ui.create_dom_element({
				element_type	: 'div',
				class_name		: 'content_data error',
				inner_html		: 'Error on create login form. login_items are invalid. Check your database connection and integrity or reinstall Dédalo',
				parent			: fragment
			})
		}

	// User name input
		const login_item_username = login_items.find(el => el.tipo==='dd255')
		const user_input = ui.create_dom_element({
			id				: 'username',
			element_type	: 'input',
			type			: 'text',
			placeholder		: strip_tags(login_item_username.label),
			parent			: form
		})
		user_input.autocomplete	= 'username'

	// Authorization input
		const login_item_password = login_items.find(el => el.tipo==='dd256')
		const auth_input = ui.create_dom_element({
			id				: 'auth',
			element_type	: 'input',
			type			: 'password',
			placeholder		: strip_tags(login_item_password.label),
			parent			: form
		})
		auth_input.autocomplete= 'current-password'

	// button submit
		const login_item_enter = login_items.find(el => el.tipo==='dd259')
		const button_enter = ui.create_dom_element({
			element_type	: 'button',
			type			: 'submit',
			class_name		: 'button_enter warning',
			parent			: form
		})
		// button_enter_loading
			const button_enter_loading = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'spinner button_enter_loading hide',
				parent			: button_enter
			})
		// button_enter_label
			const button_enter_label = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'button_enter_label',
				inner_html		: strip_tags(login_item_enter.label),
				parent			: button_enter
			})
		// event click
		button_enter.addEventListener('click', function(e) {
			e.preventDefault()

			const username = user_input.value
			if (username.length<2) {
				const message = `Invalid username ${username}!`
				ui.show_message(messages_container, message, 'error', 'component_message', true)
				return false
			}

			const auth = auth_input.value
			if (auth.length<2) {
				const message = `Invalid auth code!`
				ui.show_message(messages_container, message, 'error', 'component_message', true)
				return false
			}

			// show spinner and hide button label
				button_enter_label.classList.add('hide')
				button_enter_loading.classList.remove('hide')
				button_enter.classList.add('white')
				button_enter.blur()

			// check status
				if (self.status==='login') {
					return
				}

			// status update
				self.status = 'login'

			// data_manager API call
				data_manager.request({
					body : {
						action	: 'login',
						dd_api	: 'dd_utils_api',
						options	: {
							username	: username,
							auth		: auth
						}
					}
				})
				.then((api_response)=>{
					if(SHOW_DEBUG===true) {
						console.log('api_response:', api_response);
					}

					// hide spinner and show button label
						button_enter_label.classList.remove('hide')
						button_enter_loading.classList.add('hide')
						button_enter.classList.remove('white')


					if (api_response.errors && api_response.errors.length>0 || api_response.result===false) {

						// errors found

						const message	= api_response.errors || ['Unknown login error happen']
						const msg_type	= 'error'
						ui.show_message(messages_container, message, msg_type, 'component_message', true)

					}else{

						// success case

						const message	= api_response.msg
						const msg_type	= api_response.result===true ? 'ok' : 'error'
						ui.show_message(messages_container, message, msg_type, 'component_message', true)

						self.action_dispatch(api_response)
					}

					// status update
						self.status = 'rendered'
				})
		})//end button_enter.addEventListener('click', function(e)

	// info
		const info = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'info',
			parent			: fragment
		})
		const info_data			= self.context.properties.info || []
		const info_data_length	= info_data.length
		for (let j = 0; j < info_data_length; j++) {

			const item = info_data[j]

			// label
				ui.create_dom_element({
					element_type	: 'span',
					inner_html		: item.label,
					parent			: info
				})

			// class_name custom for value
				let class_name	= ''
				let value		= item.value
				switch(item.type){
					case 'data_version':
						const is_outdated = item.value[0]<6
						if (is_outdated) {
							class_name	= 'error'
							value		= item.value.join('.') + ' - Outdated!'
							// if version is outdated, jump to area development to update
							const area_development_tipo = 'dd770'
							if (window.location.search.indexOf(area_development_tipo)===-1) {
								const base_url = window.location.origin + window.location.pathname
								const target_url = base_url + '?t=' + area_development_tipo
								window.location.replace(target_url)
							}
						}
						break;
					default:
						break;
				}

			// value
				ui.create_dom_element({
					element_type	: 'span',
					inner_html		: value,
					class_name		: class_name,
					parent			: info
				})
		}

	// messages_container
		const messages_container = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'messages_container',
			parent			: fragment
		})

	// content_data
		const content_data = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_data'
		})
		content_data.appendChild(fragment)
		// set pointers
		content_data.top				= top
		content_data.select_lang		= select_lang
		content_data.form				= form
		content_data.info				= info
		content_data.messages_container	= messages_container

	return content_data
}//end get_content_data



/**
* GET_BROWSER_INFO
* @return object
*/
const get_browser_info = function() {

	let ua = navigator.userAgent,tem,M=ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
	if(/trident/i.test(M[1])){
		tem=/\brv[ :]+(\d+)/g.exec(ua) || [];
		return {name:'IE',version:(tem[1]||'')};
		}
	if(M[1]==='Chrome'){
		tem=ua.match(/\bOPR|Edge\/(\d+)/)
		if(tem!=null)   {return {name:'Opera', version:tem[1]};}
		}
	M=M[2]? [M[1], M[2]]: [navigator.appName, navigator.appVersion, '-?'];
	if((tem=ua.match(/version\/(\d+)/i))!=null) {M.splice(1,1,tem[1]);}

	const target_div = document.getElementById('login_ajax_response');
	if (target_div) {
		target_div.innerHTML = "Using " + M[0] + " " + M[1] + ""
	}

	return {
		name	: M[0],
		version	: M[1]
	};
}//end get_browser_info



/**
* VALIDATE_BROWSER
* @return bool
*/
const validate_browser = function() {

	const browser_info = get_browser_info()
	const min_version  = {
		Chrome		: 76,
		Firefox		: 65,
		AppleWebKit	: 10
	}

	const msg = (browser, version, min_version) => {
		return `Sorry, your ${browser} browser version is too old (${version}). \nPlease update your ${browser} version to ${min_version} or never`
	}

	try {
	   // Browser warning
		switch(true) {
			case (navigator.userAgent.indexOf('Chrome')!==-1) :

				if (browser_info && browser_info.version && parseInt(browser_info.version) < min_version.Chrome) {
					alert( msg('Chrome', browser_info.version, min_version.Chrome) );
					return false;
				}

			case (navigator.userAgent.indexOf('AppleWebKit')!==-1) :
				if (browser_info && browser_info.version && parseInt(browser_info.version) < min_version.AppleWebKit) {
					alert( msg('AppleWebKit', browser_info.version, min_version.AppleWebKit) );
					return false;
				}
				break;

			case (navigator.userAgent.indexOf('Firefox')!==-1) :

				if (browser_info && browser_info.version && parseInt(browser_info.version) < min_version.Firefox) {
					alert( msg('Firefox', browser_info.version, min_version.Firefox) );
					return false;
				}
				break;

			default:
				alert("Sorry. Your browser is not verified to work with Dédalo. \n\nOnly Webkit browsers are tested by now. \n\nPlease download the last version of official Dédalo browser (Google Chrome - Safari) to sure a good experience.")
				break;
		}

	}catch (e) {
		console.log("error",e)
	}

	return true;
}//end validate_browser



/**
* RENDER_FILES_LOADER
* Creates the files loader nodes
* @see login.action_dispatch
* @return DOM DocumentFragment
*/
export const render_files_loader = function() {

	const fragment = new DocumentFragment()

	// cont
		const cont = ui.create_dom_element({
			element_type	: 'div',
			id				: 'cont',
			class_name		: 'cont',
			dataset			: {
				pct : 'Loading..'
			},
			parent			: fragment
		})

	// svg circle
		const svg_string = `
		<svg id="svg" width="200" height="200" viewPort="0 0 100 100" version="1.1" xmlns="http://www.w3.org/2000/svg">
			<circle r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
			<circle id="bar" class="hide" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
		</svg>`

		const parser	= new DOMParser();
		const svg		= parser.parseFromString(svg_string, 'image/svg+xml').firstChild;
		cont.appendChild( svg )

	// update. receive worker messages data
		let loaded = 0
		fragment.update = function( data ) {

			const total_files	= data.total_files
			const rate			= data.status==='loading'
				? 100/total_files
				: 0

			// update loaded
			loaded = rate + loaded
			if (loaded>99) {
				loaded = 100
			}

			// animate
			animate_circle(loaded)
		}

	// bar_circle animation
		const bar_circle	= svg.querySelector('#bar')
		const radio			= bar_circle.getAttribute('r');
		const cst			= Math.PI*(radio*2);
		function animate_circle(value) {

			if (value>0 && value<2) {
				bar_circle.classList.remove('hide')
			}

			const val = (value > 100)
				? 100
				: Math.abs(parseInt(value))

			const offset = ((100-val)/100)*cst

			// change circle stroke offset
			bar_circle.style.strokeDashoffset = offset

			// updates number as 50%
			cont.dataset.pct = val + '%'
		}

	// loader_label
		ui.create_dom_element({
			element_type	: 'h2',
			class_name		: 'loader_label',
			inner_html		: 'Loading Dédalo files',
			parent			: fragment
		})

	return fragment
}//end render_files_loader
