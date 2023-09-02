// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL, DEDALO_API_URL */
/*eslint no-undef: "error"*/



// imports
	import {widget_common} from '../../../../widgets/widget_common/widget_common.js'
	import {render_environment} from './render_environment.js'



/**
* ENVIRONMENT
*/
export const environment = function() {

	this.id

	this.section_tipo
	this.section_id
	this.lang
	this.mode

	this.value

	this.node

	this.events_tokens	= []
	this.ar_instances	= []

	this.status
}//end environment



/**
* COMMON FUNCTIONS
* extend functions from common
*/
// prototypes assign
	// // lifecycle
	environment.prototype.init		= widget_common.prototype.init
	environment.prototype.build		= widget_common.prototype.build
	environment.prototype.render	= widget_common.prototype.render
	environment.prototype.destroy	= widget_common.prototype.destroy
	// // render
	environment.prototype.edit		= render_environment.prototype.list
	environment.prototype.list		= render_environment.prototype.list



// @license-end
