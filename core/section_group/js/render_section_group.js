/*global get_label, page_globals, SHOW_DEBUG*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	import {ui} from '../../common/js/ui.js'



/**
* RENDER_SECTION_GROUP
* Manage the components logic and appearance in client side
*/
export const render_section_group = function() {
	
	return true
};//end render_section_group



/**
* EDIT
* Render node for use in edit
* @return DOM node
*/
render_section_group.prototype.edit = async function(options) {

	const self = this

	const render_level = options.render_level || 'full'

	// content_data
		const current_content_data = get_content_data(self)
		if (render_level==='content') {
			return current_content_data
		}

	// wrapper options
		const wrapper_options = {
			content_data : current_content_data
		}
		// properties label set to null, avoid header label is added
		if (self.context.properties.label===null) {
			wrapper_options.label = null
		}

	// wrapper. ui build_edit returns component wrapper
		const wrapper =	ui.component.build_wrapper_edit(self, wrapper_options)

	// events
		wrapper.addEventListener("click", (e) => {
			e.stopPropagation()

			if (e.target.matches('.label')) {
				e.target.nextSibling.classList.toggle('hide')
			}
		})

	// content data state (closed / opened). // UNDER CONSTRUCTION .... !!
		self.get_panels_status()
		.then(function(ui_status){
			/*
			if (ui_status) {
				// search_panel cookie state track
				// if(self.cookie_track("search_panel")===true) {
					if(ui_status.value.search_panel && ui_status.value.search_panel.is_open) {
						// Open search panel
						toggle_search_panel(self) // toggle to open from defult state close
					}
				// fields_panel cookie state track
					// if(self.cookie_track("fields_panel")===true) {
					if(ui_status.value.fields_panel && ui_status.value.fields_panel.is_open) {
						// Open search panel
						toggle_fields(self) // toggle to open from defult state close
					}
				// presets_panel cookie state track
					// if(self.cookie_track("presets_panel")===true) {
					if(ui_status.value.presets_panel && ui_status.value.presets_panel.is_open) {
						// Open search panel
						toggle_presets(self) // toggle to open from defult state close
					}
			}//end if (ui_status)
			*/
		})

	return wrapper
};//end edit



/**
* GET_CONTENT_DATA
* @return DOM node content_data
*/
const get_content_data = function(self) {

	// content_data
		const content_data = document.createElement("div")
			  content_data.classList.add("content_data", self.type)


	return content_data
};//end get_content_data



/**
* LIST
* Render node for use in list
* @return DOM node
*/
// render_section_group.prototype.list = render_section_group.prototype.edit


