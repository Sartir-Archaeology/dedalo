// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {ui} from '../../../core/common/js/ui.js'
	import {data_manager} from '../../../core/common/js/data_manager.js'



/**
* RENDER_TOOL_IMPORT_RDF
* Manages the component's logic and appearance in client side
*/
export const render_tool_import_rdf = function() {

	return true
}//end render_tool_import_rdf



/**
* RENDER_TOOL_IMPORT_RDF
* Render node for use like button
* @return HTMLElement wrapper
*/
render_tool_import_rdf.prototype.edit = async function(options={render_level:'full'}) {

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


	return wrapper
}//end render_tool_import_rdf



/**
* GET_CONTENT_DATA_EDIT
* @return HTMLElement content_data
*/
const get_content_data_edit = async function(self) {

	const fragment = new DocumentFragment()

	// components container
		const components_container = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'components_container',
			parent 			: fragment
		})

	// get the component_iri data
		const iri_node = render_component_dato(self)
		components_container.appendChild(iri_node)


	// application lang selector

		// default_lang_of_file_to_import

		const default_lang_of_file_to_import = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'default_lang',
			inner_html 		: get_label.default_lang_of_file_to_import || 'Default language of the file to import. Data without specified language will be imported in:',
			parent 			: components_container
		})


		const lang_datalist = page_globals.dedalo_projects_default_langs
		const dedalo_aplication_langs_selector = ui.build_select_lang({
			langs		: lang_datalist,
			selected	: page_globals.dedalo_application_lang,
			class_name	: 'dedalo_aplication_langs_selector'
		})
		components_container.appendChild(dedalo_aplication_langs_selector)

		dedalo_aplication_langs_selector.addEventListener('change', async function(){
			const api_response = await data_manager.request({
				body : {
					action	: 'change_lang',
					dd_api	: 'dd_utils_api',
					options	: {
						dedalo_data_lang		: dedalo_aplication_langs_selector.value,
						dedalo_application_lang	: dedalo_aplication_langs_selector.value
					}
				}
			})
			// window.location.reload(false);
		})

	// buttons container
		const buttons_container = ui.create_dom_element({
			element_type	: 'div',
			class_name 		: 'buttons_container',
			parent 			: components_container
		})

		const btn_validate = ui.create_dom_element({
			element_type	: 'button',
			class_name		: 'success button_apply',
			inner_html		: 'ok',
			parent			: buttons_container
		})

		const view_rdf_data_wrapper = ui.create_dom_element({
			element_type	: 'div',
			class_name		: 'view_rdf_data_wrapper',
			parent			: fragment
		})

		// when user click the button do the import of the data.
		btn_validate.addEventListener('click',()=>{
				const component_data_value = iri_node.querySelectorAll('.component_data:checked')

				const spinner = ui.create_dom_element({
					element_type	: 'span',
					class_name		: 'spinner',
					parent			: view_rdf_data_wrapper
				})

				const len = component_data_value.length
				const ar_values = []
				for (let i = 0; i < len; i++) {
					ar_values.push(component_data_value[i].value)
				}

				if (ar_values.length > 0){

					const ontology_tipo = self.main_element.context.properties.ar_tools_name.tool_import_rdf.external_ontology
						? self.main_element.context.properties.ar_tools_name.tool_import_rdf.external_ontology
						: null


					const result = self.get_rdf_data(ontology_tipo, ar_values).then(function(response){
							if(SHOW_DEBUG===true) {
								console.log("response:",response);
							}
							spinner.remove()

							const len = ar_values.length
							for (let i = 0; i < len; i++) {
								const current_data = ar_values[i]

								view_rdf_data_wrapper.innerHTML = response.result[i].ar_rdf_html

								// const node = self.render_dd_data(response.rdf_data[i].dd_obj, 'root')

								view_dd_data_wrapper.appendChild(node)
							}

							// update list
								// self.load_section(section_tipo)
						})

				}else{
					// spinner.remove()
				}

			})

	// content_data
		const content_data = ui.tool.build_content_data(self)
		content_data.appendChild(fragment)


	return content_data
}//end get_content_data_edit



/**
* RENDER_COMPONENT_DATO
* @return
*/
const render_component_dato = function(self) {

	const component_data	= self.main_element.data.value
	const len				= component_data.length

	const source_component_container = ui.create_dom_element({
					element_type	: 'div',
					class_name		: 'source_component_container'
	})

	for (let i = 0; i < len; i++) {

		const current_component = component_data[i]

		const radio_label = ui.create_dom_element({
						element_type	: 'label',
						class_name		: 'component_data_label',
						inner_html		: current_component.iri,
						parent 			: source_component_container
		})

		const radio_input = ui.create_dom_element({
						element_type	: 'input',
						type 			: 'radio',
						class_name		: 'component_data',
						name			: 'radio_selector',
						value 			: current_component.iri,
		})

		radio_label.prepend(radio_input)
	}

	return source_component_container
}//end render_component_dato



// @license-end
