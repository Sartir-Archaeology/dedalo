// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {common} from '../../common/js/common.js'
	import {component_common} from '../../component_common/js/component_common.js'
	import {render_edit_component_radio_button} from './render_edit_component_radio_button.js'
	import {render_list_component_radio_button} from './render_list_component_radio_button.js'
	import {render_search_component_radio_button} from './render_search_component_radio_button.js'



export const component_radio_button = function(){

	this.id

	// element properties declare
	this.model
	this.tipo
	this.section_tipo
	this.section_id
	this.mode
	this.lang

	this.section_lang
	this.context
	this.data
	this.parent
	this.node

	// ui
	this.minimum_width_px = 90 // integer pixels
}//end component_radio_button



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// lifecycle
	component_radio_button.prototype.init				= component_common.prototype.init
	component_radio_button.prototype.build				= component_common.prototype.build
	component_radio_button.prototype.render				= common.prototype.render
	component_radio_button.prototype.refresh			= common.prototype.refresh
	component_radio_button.prototype.destroy			= common.prototype.destroy

	// change data
	component_radio_button.prototype.save				= component_common.prototype.save
	component_radio_button.prototype.update_data_value	= component_common.prototype.update_data_value
	component_radio_button.prototype.update_datum		= component_common.prototype.update_datum
	component_radio_button.prototype.change_value		= component_common.prototype.change_value
	component_radio_button.prototype.set_changed_data	= component_common.prototype.set_changed_data
	component_radio_button.prototype.build_rqo			= common.prototype.build_rqo

	// render
	component_radio_button.prototype.list				= render_list_component_radio_button.prototype.list
	component_radio_button.prototype.tm					= render_list_component_radio_button.prototype.list
	component_radio_button.prototype.edit				= render_edit_component_radio_button.prototype.edit
	component_radio_button.prototype.search				= render_search_component_radio_button.prototype.search

	component_radio_button.prototype.change_mode		= component_common.prototype.change_mode



/**
* GET_CHECKED_VALUE_LABEL
* @return string label
*/
component_radio_button.prototype.get_checked_value_label = function() {

	const self = this

	if (typeof self.data.value[0]==='undefined' || self.data.value[0]===null) {
		return ''
	}

	const checked_key = self.data.datalist.findIndex( (item) => {
		return (item.section_id===self.data.value[0].section_id)
	})

	const label = self.data.datalist[checked_key].label

	return label
}//end get_checked_value_label



/**
* FOCUS_FIRST_INPUT
* Captures ui.component.activate calls
* to prevent default behavior
* @return bool
*/
component_radio_button.prototype.focus_first_input = function() {

	return true
}//end focus_first_input



// @license-end
