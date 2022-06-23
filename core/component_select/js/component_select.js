/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {common} from '../../common/js/common.js'
	import {component_common} from '../../component_common/js/component_common.js'
	import {render_edit_component_select} from '../../component_select/js/render_edit_component_select.js'
	import {render_search_component_select} from '../../component_select/js/render_search_component_select.js'
	import {render_mini_component_select} from '../../component_select/js/render_mini_component_select.js'
	import {render_list_component_select} from '../../component_select/js/render_list_component_select.js'



export const component_select = function(){

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

	this.tools

	this.datum

	return true
}//end component_select



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// lifecycle
	component_select.prototype.init					= component_common.prototype.init
	component_select.prototype.build				= component_common.prototype.build
	component_select.prototype.render				= common.prototype.render
	component_select.prototype.refresh				= common.prototype.refresh
	component_select.prototype.destroy				= common.prototype.destroy

	// change data
	component_select.prototype.save					= component_common.prototype.save
	component_select.prototype.update_data_value	= component_common.prototype.update_data_value
	component_select.prototype.update_datum			= component_common.prototype.update_datum
	component_select.prototype.change_value			= component_common.prototype.change_value
	// component_select.prototype.build_rqo			= common.prototype.build_rqo
	// component_select.prototype.build_rqo_show	= common.prototype.build_rqo_show

	// render
	component_select.prototype.mini					= render_mini_component_select.prototype.mini
	component_select.prototype.list					= render_list_component_select.prototype.list
	component_select.prototype.edit					= render_edit_component_select.prototype.edit
	component_select.prototype.edit_in_list			= render_edit_component_select.prototype.edit
	component_select.prototype.search				= render_search_component_select.prototype.search
	component_select.prototype.change_mode			= component_common.prototype.change_mode


