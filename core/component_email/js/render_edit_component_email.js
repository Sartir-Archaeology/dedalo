/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../common/js/ui.js'
	import {view_default_edit_email} from './view_default_edit_email.js'
	import {view_line_edit_email} from './view_line_edit_email.js'
	import {view_mini_email} from './view_mini_email.js'



/**
* RENDER_EDIT_COMPONENT_EMAIL
* Manage the components logic and appearance in client side
*/
export const render_edit_component_email = function() {

	return true
}//end render_edit_component_email



/**
* EDIT
* Render node for use in edit
* @param object options
* @return HTMLElement|null
*/
render_edit_component_email.prototype.edit = async function(options) {

	const self = this

	// view
		const view = self.context.view || 'default'

	switch(view) {

		case 'line':
			return view_line_edit_email.render(self, options)

		case 'mini':
			return view_mini_email.render(self, options)

		case 'print':
			// view print use the same view as default, except it will use read only to render content_value
			// as different view as default it will set in the class of the wrapper
			// sample: <div class="wrapper_component component_input_text oh14 oh1_oh14 edit view_print disabled_component">...</div>
			// take account that to change the css when the component will render in print context
			// for print we need to use read of the content_value and it's necessary force permissions to use read only element render
			self.permissions = 1

		case 'default':
		default:
			return view_default_edit_email.render(self, options)
	}

	return null
}//end edit



/**
* GET_CONTENT_DATA
* @return HTMLElement content_data
*/
export const get_content_data = function(self) {

	// short vars
		const data	= self.data || {}
		const value	= data.value || []

	// content_data
		const content_data = ui.component.build_content_data(self)

	// build values
		const inputs_value = value
		const value_length = inputs_value.length || 1
		for (let i = 0; i < value_length; i++) {
			const input_element_node = (self.permissions===1)
				? get_content_value_read(i, inputs_value[i], self)
				: get_content_value(i, inputs_value[i], self)
			content_data.appendChild(input_element_node)
			// set the pointer
			content_data[i] = input_element_node
		}

	return content_data
}//end get_content_data



/**
* GET_CONTENT_VALUE
* @return HTMLElement content_value
*/
const get_content_value = (i, current_value, self) => {

	const mode				= self.mode
	const is_inside_tool	= self.is_inside_tool
	// check if the component is mandatory and it doesn't has value
	const add_class			= self.context.properties.mandatory && !current_value
		? ' mandatory'
		: ''

	// content_value
		const content_value = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_value'
		})

	// input field
		const input_email = ui.create_dom_element({
			element_type	: 'input',
			type			: 'text',
			class_name		: 'input_value' + add_class,
			value			: current_value,
			parent			: content_value
		})
		// focus event
			input_email.addEventListener('focus', function() {
				// force activate on input focus (tabulating case)
				if (!self.active) {
					ui.component.activate(self)
				}
			})
		// blur event
			// input_email.addEventListener('blur', function() {
			// 	// force to save current input if changed (prevents override changed_data
			// 	// in multiple values cases)
			// 	if (self.data.changed_data) {
			// 		// change_value
			// 		self.change_value({
			// 			changed_data	: self.data.changed_data,
			// 			refresh			: false
			// 		})
			// 	}
			// })
		// change event
			input_email.addEventListener('change',function(e) {
				e.preventDefault();

				// validate
					const validated = self.verify_email(input_email.value)
					ui.component.error(!validated, input_email)
					if (!validated) {
						return false
					}

				return

				// save value
					// set the changed_data for replace it in the instance data
					// new_value. key is the position in the data array, the value is the new value
					const new_value = (input_email.value.length>0) ? input_email.value : null
					// set the changed_data for update the component data and send it to the server for change when save
					const changed_data = [Object.freeze({
						action	: 'update',
						key		: i,
						value	: new_value
					})]
					// update the data in the instance previous to save
					self.change_value({
						changed_data	: changed_data,
						refresh			: false
					})
					// check if the new value is empty or not to remove the mandatory class
					if(new_value){
						input_email.classList.remove('mandatory')
					}else{
						input_email.classList.add('mandatory')
					}
			})//end change
		// keyup event
			input_email.addEventListener('keyup', async function(e) {

				// Enter key force to save changes
					if (e.key==='Enter') {
						// force to save current input if changed
						if (self.data.changed_data.length>0) {
							// change_value
							self.change_value({
								changed_data	: self.data.changed_data,
								refresh			: false
							})
						}
						return false
					}

				// change data
					const changed_data_item = Object.freeze({
						action	: 'update',
						key		: i,
						value	: (this.value.length>0) ? this.value : null
					})
				// fix instance changed_data
					self.set_changed_data(changed_data_item)
			})//end keyup

	// add buttons to the email row
		if((mode==='edit') && !is_inside_tool) {

			// button_remove
				const button_remove = ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'button remove hidden_button',
					parent			: content_value
				})
				button_remove.addEventListener('mouseup', function(e) {
					// force possible input change before remove
					document.activeElement.blur()

					const changed_data = [Object.freeze({
						action	: 'remove',
						key		: i,
						value	: null
					})]
					self.change_value({
						changed_data	: changed_data,
						label			: current_value || ' ',
						refresh			: true
					})
					.then(()=>{
					})
				})

			// button email
				const button_email = ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'button email hidden_button',
					parent			: content_value
				})
				button_email.addEventListener('mouseup', function(e) {
					self.send_email(current_value)
				})
		}


	return content_value
}//end get_content_value



/**
* GET_CONTENT_VALUE_READ
* Render a element based on passed value
* @param int i
* 	data.value array key
* @param string current_value
* @param object self
*
* @return HTMLElement content_value
*/
const get_content_value_read = (i, current_value, self) => {

	// create content_value
		const content_value = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'content_value read_only',
			inner_html		: current_value
		})

	return content_value
}//end get_content_value_read



/**
* GET_BUTTONS
* @param object instance
* @return HTMLElement buttons_container
*/
export const get_buttons = (self) => {

	const is_inside_tool	= self.is_inside_tool
	const mode				= self.mode

	const fragment = new DocumentFragment()

	// button add input
		if(!is_inside_tool) {
			// button_add_input
			const add_button = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'button add',
				parent			: fragment
			})
			add_button.addEventListener('mouseup',function() {
				const changed_data = [Object.freeze({
					action	: 'insert',
					key		: self.data.value.length,//self.data.value.length>0 ? self.data.value.length : 1,
					value	: null
				})]
				self.change_value({
					changed_data	: changed_data,
					refresh			: false
				})
				.then(()=>{
					const new_input	= get_content_value(changed_data.key, changed_data.value, self)
					self.node.content_data.appendChild(new_input)
					const input_value = new_input.querySelector('.input_value')
					if (input_value) {
						input_value.focus()
					}
				})
			})

		// button send_multiple_email
			const send_multiple_email = ui.create_dom_element({
				element_type	: 'span',
				class_name		: 'button email_multiple',
				parent			: fragment
			})
			send_multiple_email.addEventListener('mouseup', async function (e) {
				const ar_emails		= await self.get_ar_emails()
				const mailto_prefix	= 'mailto:?bcc=';
				// ar_mails could be an array with 1 string item with all addresses or more than 1 string when the length is more than length supported by the SO (in Windows 2000 charts)
				// if the maximum chars is surpassed the string it was spliced in sorted strings and passed as string items of the array
				// every item of the array will be opened by the user to create the email
				if(ar_emails.length > 1){

					const body = ui.create_dom_element({
						element_type	: 'span',
						class_name		: 'body'
					})

					const body_title = ui.create_dom_element({
						element_type	: 'span',
						class_name		: 'body_title',
						text_node		: get_label.email_limit_explanation,
						parent			: body
					})
					// create the mail with the addresses and create the buttons to open the email app
					for (let i = 0; i < ar_emails.length; i++) {

						const current_emails = ar_emails[i]
						// find the separator to count the total of emails for every chunk of emails.
						const regex = /;/g;
						const search_number_of_email =  current_emails.match(regex) || []
						const number_of_email = search_number_of_email.length > 0
							? search_number_of_email.length +1
							: 1
						const buton_option = ui.create_dom_element({
							element_type	: 'button',
							class_name		: 'warning',
							inner_html		: (get_label.email || 'email') + ': ' + number_of_email,
							parent			: body
						})

						buton_option.addEventListener('mouseup', function (e) {
							// when the user click in the button remove the option and open the email with the addresses
							buton_option.remove()
							window.location.href = mailto_prefix + current_emails
						})
					}

					// modal. create new modal with the email buttons
						ui.attach_to_modal({
							header	: get_label.alert_limit_of_emails || 'emails limitation',
							body	: body,
							footer	: null,
							size	: 'small'
						})

				}else{
					window.location.href = mailto_prefix + ar_emails[0]
				}
			})
		}

	// buttons tools
		if( self.show_interface.tools === true){
			if (!is_inside_tool && mode==='edit') {
				ui.add_tools(self, fragment)
			}
		}

	// buttons container
		const buttons_container = ui.component.build_buttons_container(self)
		buttons_container.appendChild(fragment)


	return buttons_container
}//end get_buttons
