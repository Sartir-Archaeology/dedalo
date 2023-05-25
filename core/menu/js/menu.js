// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {common,create_source} from '../../common/js/common.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {render_menu} from './render_menu.js'



export const menu = function(){

	this.id
	this.tipo
	this.mode
	this.model
	this.lang

	this.section_lang
	this.datum
	this.context
	this.data
	this.node
	this.li_nodes
	this.ul_nodes
	this.events_tokens

	this.ar_instances
}//end menu



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// lifecycle
	menu.prototype.render			= common.prototype.render
	menu.prototype.destroy			= common.prototype.destroy
	menu.prototype.refresh			= common.prototype.refresh
	// menu.prototype.create_source	= common.prototype.create_source

	// render
	menu.prototype.list				= render_menu.prototype.list
	menu.prototype.edit				= render_menu.prototype.edit



/**
* INIT
* @param object options
* @return bool
*/
menu.prototype.init = function(options) {

	const self = this

	self.tipo			= options.tipo
	self.model			= options.model
	self.node			= null
	self.li_nodes		= []
	self.ul_nodes		= []
	self.ar_instances	= []
	self.mode			= 'edit'
	self.datum			= options.datum
	self.context		= options.context
	self.data			= options.data
	self.events_tokens	= []

	// status update
		self.status = 'initialized'

	return true
}//end init



/**
* BUILD
* @param bool autoload
* @return true
*/
menu.prototype.build = async function(autoload=true) {
	const t0 = performance.now()

	const self = this

	// status update
		self.status = 'building'

	if (autoload===true) {

		const menu_cache_data = await data_manager.get_local_db_data(self.id, 'data')

		if(menu_cache_data){

			// set the result to the datum
				self.datum = menu_cache_data.value

			// debug
				// const total = (performance.now()-t0).toFixed(3)
				// if (total>20) {
				// 	// console.warn(msg, total, self);
				// 	console.log("__Time [menu.build] returned menu datum from local_db ", self.id, total);
				// }

		}else{

			// rqo build
				// const rqo = {
				// 	action			: 'get_menu',
				// 	dd_api			: 'dd_utils_api',
				// 	source			: create_source(self, null),
				// 	prevent_lock	: true
				// }
				const rqo = {
					action	: 'read',
					source	: create_source(self, 'get_data')
				}

			// load data. get context and data
				const api_response = await data_manager.request({
					body : rqo
				})

			// set the result to the datum
				self.datum = api_response.result

			// cache
				const menu_cache_data = {
					id		: self.id,
					value	: self.datum
				}
				data_manager.set_local_db_data(
					menu_cache_data,
					'data'
				)
		}
	}

	// set context and data to current instance
		self.context	= self.datum.context.find(element => element.model===self.model && element.tipo===self.tipo);
		self.data		= self.datum.data.find(element => element.model===self.model &&  element.tipo===self.tipo)

	// debug
		if(SHOW_DEBUG===true) {
			//console.log("self.context section_group:",self.datum.context.filter(el => el.model==='section_group'));
			console.log(`__Time to build ${self.model} [autoload:${autoload}] ms:`, performance.now()-t0);
		}

	// status update
		self.status = 'built'


	return true
}//end build



// @license-end
