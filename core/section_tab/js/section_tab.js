// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG*/
/*eslint no-undef: "error"*/



// imports
	// import {event_manager} from '../../common/js/event_manager.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {common} from '../../common/js/common.js'
	// import {component_common} from '../../component_common/js/component_common.js'
	import {render_section_tab} from './render_section_tab.js'



/**
* SECTION_tab
*/
export const section_tab = function(){

	this.id				= null

	// element properties declare
	this.model			= null
	this.tipo			= null
	this.section_tipo	= null
	this.section_id		= null
	this.mode			= null
	this.lang			= null

	this.context		= null
	this.parent			= null
	this.type			= null
	this.label			= null

	this.node			= null

	this.id_variant		= null
	this.children		= null

	return true
}//end section_tab



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	section_tab.prototype.build		= common.prototype.build
	section_tab.prototype.render	= common.prototype.render
	section_tab.prototype.destroy	= common.prototype.destroy
	section_tab.prototype.list		= render_section_tab.prototype.list
	section_tab.prototype.edit		= render_section_tab.prototype.edit



/**
* INIT
* @return bool true
*/
section_tab.prototype.init = function(options) {

	const self = this

	self.model			= options.model
	self.tipo			= options.tipo
	self.section_tipo	= options.section_tipo
	self.section_id		= options.section_id
	self.mode			= options.mode
	self.lang			= options.lang

	self.context		= options.context || null
	self.parent			= options.parent
	self.type			= options.type
	self.events_tokens	= []
	self.ar_instances	= []

	self.node			= null

	self.label			= self.context.label


	return true
}//end init



/**
* GET_PANELS_STATUS
* Get local DDBB record if exists and return result object
* @return object | undefined
*/
section_tab.prototype.get_panels_status = async function() {

	const self = this

	// unic id for current section_tab
		// const uid = self.model + '_' + self.section_tipo + '_' +  self.tipo

	// local_db_data. get value if exists
		const panels_status = await data_manager.get_local_db_data('section_tab', 'context')
			// console.log("----- section_tab panels_status:", uid, panels_status);

		// UNDER CONSTRUCTION .... !!

	return panels_status
}//end get_panels_status



// @license-end
