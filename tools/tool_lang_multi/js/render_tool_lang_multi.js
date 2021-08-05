/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../../core/common/js/event_manager.js'
	import {ui} from '../../../core/common/js/ui.js'
	import {clone, dd_console} from '../../../core/common/js/utils/index.js'



/**
* RENDER_TOOL_LANG_MULTI
* Manages the component's logic and apperance in client side
*/
export const render_tool_lang_multi = function() {
	
	return true
};//end render_tool_lang_multi



/**
* RENDER_TOOL_LANG_MULTI
* Render node for use like button
* @return DOM node
*/
render_tool_lang_multi.prototype.edit = async function (options={render_level:'full'}) {

	const self = this

	const render_level = options.render_level || 'full'

	// content_data
		const content_data = await get_content_data_edit(self)
		if (render_level==='content') {
			return content_data
		}

	// wrapper. ui build_edit returns component wrapper
		const wrapper = await ui.tool.build_wrapper_edit(self, {
			content_data : content_data
		})

	// modal container
		const header = wrapper.querySelector('.tool_header')
		const modal  = ui.attach_to_modal(header, wrapper, null)
		modal.on_close = () => {
			self.destroy(true, true, true)
		}


	return wrapper
};//end render_tool_lang_multi



/**
* GET_CONTENT_DATA_EDIT
* @return DOM node content_data
*/
const get_content_data_edit = async function(self) {

	const fragment = new DocumentFragment()


	// components container
		const components_container = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'components_container',
			parent 			: fragment
		})

	// target
		const length = self.langs.length
		for (let i = 0; i < length; i++) {
			add_target_component(self.langs[i], components_container, self)
		}

	// buttons container
		const buttons_container = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'buttons_container',
			parent 			: components_container
		})

		// automatic_translation
			const translator_engine = self.simple_tool_object.translator_engine
			if (translator_engine) {
				const automatic_tranlation_node = build_automatic_tranlation(self, translator_engine, source_select_lang, target_select_lang, components_container)
				buttons_container.appendChild(automatic_tranlation_node)
			}


	// content_data
		const content_data = document.createElement("div")
			  content_data.classList.add("content_data", self.type)
			  content_data.appendChild(fragment)


	return content_data
};//end get_content_data_edit



const build_automatic_tranlation = (self, translator_engine, source_select_lang, target_select_lang, components_container) => {

	// container
		const automatic_translation_container = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'automatic_translation_container'
		})

	// button
		const button_automatic_translation = document.createElement('button');
			  button_automatic_translation.type = 'button'
			  button_automatic_translation.textContent = get_label['traduccion_automatica'] || "Automatic translation"
			  automatic_translation_container.appendChild(button_automatic_translation)
			  button_automatic_translation.addEventListener("click", (e) => {

			  	components_container.classList.add("loading")

			  	const translator  = translator_engine_select.value
			  	const source_lang = source_select_lang.value
			  	const target_lang = target_select_lang.value
			  	const translation = self.automatic_translation(translator, source_lang, target_lang, automatic_translation_container).then(()=>{
			  		components_container.classList.remove("loading")
			  	})
			  })

	// select
		const translator_engine_select = ui.create_dom_element({
			element_type	: 'select',
			parent 			: automatic_translation_container
		})
		for (let i = 0; i < translator_engine.length; i++) {
			const translator = translator_engine[i]
			ui.create_dom_element({
				element_type	: 'option',
				value 			: JSON.stringify(translator),
				text_content 	: translator.label,
				parent 			: translator_engine_select
			})
		}

	return automatic_translation_container
};//end build_automatic_tranlation



/**
* ADD_COMPONENT
*/
export const add_component = async (self, component_container, value) => {

	const component = await self.load_component(value)
	const node = await component.render()

	component_container.appendChild(node)

	return true
};//end add_component



/**
* ADD_TARGET_COMPONENT
*/
export const add_target_component = async (lang, components_container, self) => {

	const target_component_container = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'target_component_container',
			parent 			: components_container
		})

	const target_component_title = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'target_component_title',
			text_content	: lang.label,
			parent 			: target_component_container
		})

	if (lang.value===self.source_lang){
		target_component_title.className = 'target_component_title source'
	}

	const component = await self.load_component(lang.value)
	const node = await component.render()

	target_component_title.appendChild(node)

	return true
};//end add_target_component
