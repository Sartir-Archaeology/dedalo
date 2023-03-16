/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {widget_common} from '../../../widget_common/widget_common.js'
	import {render_media_icons} from '../js/render_media_icons.js'



/**
* MEDIA_ICONS
*/
export const media_icons = function(){

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

	return true
}//end media_icons



/**
* COMMON FUNCTIONS
* extend functions from common
*/
// prototypes assign
	// lifecycle
	media_icons.prototype.init		= widget_common.prototype.init
	media_icons.prototype.build		= widget_common.prototype.build
	media_icons.prototype.render	= widget_common.prototype.render
	media_icons.prototype.destroy	= widget_common.prototype.destroy
	// render
	media_icons.prototype.edit		= render_media_icons.prototype.edit
	media_icons.prototype.list		= render_media_icons.prototype.list
