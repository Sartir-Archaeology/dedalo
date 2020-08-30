/* global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL */
/* eslint no-undef: "error" */



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	import * as instances from '../../common/js/instances.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {common, create_source} from '../../common/js/common.js'
	import {component_common, set_context_vars} from '../../component_common/js/component_common.js'
	import {paginator} from '../../paginator/js/paginator.js'
	import {render_component_portal} from '../../component_portal/js/render_component_portal.js'



/**
* COMPONENT_PORTAL
*/
export const component_portal = function(){

	this.id

	// element properties declare
	this.model
	this.tipo
	this.section_tipo
	this.section_id
	this.mode
	this.lang

	this.section_lang

	this.datum
	this.context
	this.data
	this.parent
	this.node
	this.pagination

	this.modal

	this.autocomplete
	this.autocomplete_active

	return true
};//end  component_portal



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// lifecycle
	// component_portal.prototype.init				= component_common.prototype.init
	// component_portal.prototype.build				= component_common.prototype.build
	component_portal.prototype.render				= common.prototype.render
	component_portal.prototype.refresh				= common.prototype.refresh
	component_portal.prototype.destroy				= common.prototype.destroy

	// change data
	component_portal.prototype.save					= component_common.prototype.save
	component_portal.prototype.update_data_value	= component_common.prototype.update_data_value
	component_portal.prototype.update_datum			= component_common.prototype.update_datum
	component_portal.prototype.change_value			= component_common.prototype.change_value
	component_portal.prototype.get_ar_instances		= component_common.prototype.get_ar_instances
	component_portal.prototype.get_columns			= common.prototype.get_columns
	component_portal.prototype.build_dd_request		= common.prototype.build_dd_request

	// render
	component_portal.prototype.mini					= render_component_portal.prototype.mini
	component_portal.prototype.list					= render_component_portal.prototype.list
	component_portal.prototype.edit					= render_component_portal.prototype.edit
	component_portal.prototype.edit_in_list			= render_component_portal.prototype.edit
	component_portal.prototype.tm					= render_component_portal.prototype.edit
	component_portal.prototype.change_mode			= component_common.prototype.change_mode



/**
* INIT
*/
component_portal.prototype.init = async function(options) {

	const self = this

	// autocomplete. set default values of service autocomplete
		self.autocomplete			= null
		self.autocomplete_active	= false

	// dd_request . Object with all possible request (show,select,search)
		self.dd_request	= {
			show	: null,
			search	: null,
			select	: null
		}

	// columns
		self.columns = []


	// call the generic commom tool init
		const common_init = component_common.prototype.init.call(this, options);

	// events subscribe
		self.events_tokens.push(
			// user click over list record
			event_manager.subscribe('initiator_link_' + self.id, async (locator)=>{

				// add locator selected
					const result = await self.add_value(locator)
					if (result===false) {
						alert("Value already exists!");
						return
					}
				// modal close
					if (self.modal) {
						self.modal.close()
					}
			})
		)


	return common_init
};//end  init



/**
* BUILD
* @param object value (locator)
* @return bool
*/
component_portal.prototype.build  = async function(autoload=false){
	const t0 = performance.now()

	const self = this

	// status update
		self.status = 'building'

	// self.datum. On building, if datum is not created, creation is needed
		if (!self.datum) self.datum = {data:[],context:[]}

	// set dd_request
		self.dd_request.show = self.dd_request.show || self.build_dd_request('show', self.context.request_config, 'get_data')
			console.log("/// PORTAL BUILD self.dd_request.show:",self.dd_request.show);

	// debug check
		if(SHOW_DEBUG===true) {
			// console.log("-- component_portal.prototype.build self.context.request_config", self.context.request_config);
			// console.log("/// update_datum --------------------------- first self.datum.data:",JSON.parse(JSON.stringify(self.datum.data)));
			const ar_used = []
			for(const element of self.datum.data) {
				const index = ar_used.findIndex(item => item.tipo===element.tipo && item.section_tipo===element.section_tipo && item.section_id===element.section_id && item.from_component_tipo===element.from_component_tipo && item.parent_section_id===element.parent_section_id)
				if (index!==-1) {
					console.error("PORTAL ERROR. self.datum.data contains duplicated elements:", self.datum.data);
				}else{
					ar_used.push(element)
				}
			}
		}


	// load data if not yet received as an option
		if (autoload===true) {

				console.log("// portal request (autoload=true): self.dd_request.show:",self.dd_request.show);

			// get context and data
				const current_data_manager	= new data_manager()
				const api_response			= await current_data_manager.read(self.dd_request.show)

			// debug
				if(SHOW_DEBUG===true) {
					console.log("portal build api_response:", api_response)
				}

			// set context and data to current instance
				self.update_datum(api_response.result.data)
				self.context = api_response.result.context.find(el => el.tipo===self.tipo && el.section_tipo===self.section_tipo)

			// update instance properties from context (type, label, tools, divisor, permissions)
				set_context_vars(self, self.context)

			// update element pagination vars when are used
				if (self.data.pagination && typeof self.pagination.total!=="undefined") {
					// console.log("+++++++++++++++++++++++++++++++++++++++++++++++++++ self.data.pagination:",self.data.pagination);
					self.pagination.total	= self.data.pagination.total
					self.pagination.offset	= self.data.pagination.offset
				}
		}

	// pagination vars only in edit mode
		if (self.mode==="edit") {

			// pagination safe defaults
				self.pagination.total 	= self.pagination.total  || 0
				self.pagination.offset 	= self.pagination.offset || 0
				self.pagination.limit 	= self.pagination.limit  || (self.dd_request.show.sqo_config ? self.dd_request.show.sqo_config.limit : 5)
			// sqo update filter_by_locators
				// if(self.pagination.total>self.pagination.limit){

				// 	const show 	= self.dd_request.show
				// 	const sqo 	= show.find(item => item.typo==='sqo')

				// 	const data_value = self.data.value

				// 	sqo.filter_by_locators = data_value
				// }//end if(self.pagination.total>self.pagination.limit)

			// paginator
				if (!self.paginator) {
					// create new
					const current_paginator = new paginator()
					current_paginator.init({
						caller : self
					})
					await current_paginator.build()
					self.paginator = current_paginator

					self.events_tokens.push(
						event_manager.subscribe('paginator_goto_'+current_paginator.id , async (offset) => {
							self.pagination.offset = offset
							self.refresh()
						})
					)//end events push

				}else{
					// refresh existing
					self.paginator.offset = self.pagination.offset
					self.paginator.total  = self.pagination.total
					// self.paginator.refresh()
					// await self.paginator.build()
					// self.paginator.render()
				}
				console.log("//////////\\ PORTAL "+self.tipo+" self.paginator:",self.paginator);

			// autocomplete destroy. change the autocomplete service to false and desactive it.
				if(self.autocomplete && self.autocomplete_active===true){
					self.autocomplete.destroy()
					self.autocomplete_active = false
					self.autocomplete 		 = null
				}
		}//end if (self.mode==="edit")

	// permissions. calculate and set (used by section records later)
		self.permissions = self.context.permissions


	// columns
		if(self.mode === 'edit'){
			self.columns = self.get_columns()
		}

	// debug
		if(SHOW_DEBUG===true) {
			// console.log("/// component_portal build self.datum.data:",self.datum.data);
			// console.log("__Time to build", self.model, " ms:", performance.now()-t0);
			// console.log("component_portal self +++++++++++ :",self);
			//console.log("========= build self.pagination.total:",self.pagination.total);
		}

	// status update
		self.status = 'builded'


	return true
};//end  component_portal.prototype.build



/**
* ADD_VALUE
* @param object value (locator)
* @return bool
*/
component_portal.prototype.add_value = async function(value) {

	const self = this

	// console.log("self", self);

	// check if value already exists
		// const current_value = self.data.value
		// const exists 		= current_value.find(item => item.section_tipo===value.section_tipo && item.section_id===value.section_id)
		// if (typeof exists!=="undefined") {
		// 	console.log("[add_value] Value already exists !");
		// 	return false
		// }


	const key = self.pagination.total || 0

	const changed_data = Object.freeze({
		action	: 'insert',
		key		: key,
		value	: value
	})

	if(SHOW_DEBUG===true) {
		console.log("[component_portal.add_value] value:", value, " - changed_data:", changed_data);
	}

	// change_value
		const api_response = await self.change_value({
			changed_data : changed_data,
			refresh		 : false
		})

	// update pagination offset
		self.update_pagination_values('add')

	// refresh self component
		self.refresh()


	return true
};//end  add_value



/**
* UPDATE_PAGINATION_VALUES
*/
component_portal.prototype.update_pagination_values = function(action) {

	const self = this

		console.log("self.data.pagination:",self.data.pagination);
		console.log("self.pagination:",self.pagination);

	// update self.data.pagination
		switch(action) {
			case 'remove' :
				// update pagination total
				if(self.data.pagination.total && self.data.pagination.total>0) {
					// self.data.pagination.total--
					self.pagination.total--
				}
				break;
			case 'add' :
				// update self.data.pagination
				if(self.data.pagination && self.data.pagination.total && self.data.pagination.total>=0) {
					// self.data.pagination.total++
					self.pagination.total++
				}
				break;
		}
		// self.pagination.total = self.data.pagination.total


	// last_offset
		const last_offset = (()=>{

			const total = self.pagination.total
			const limit = self.pagination.limit

			if (total>0 && limit>0) {

				const total_pages = Math.ceil(total / limit)

				return parseInt( limit * (total_pages -1) )
			}

			return 0
		})()

	// self pagination update
		self.pagination.offset 	= last_offset


	self.data.pagination = self.pagination // sync pagination info

	// // paginator object update
		self.paginator.offset 	= self.pagination.offset
		self.paginator.total 	= self.pagination.total
	// console.log("update_pagination_values self.pagination:",self.pagination);

	return true
};//end update_pagination_values



/**
* GET_PORTAL_ITEMS
* @return array of components context
*/
component_portal.prototype.get_portal_items = function() {

	const self = this

	const portal_items = []

	// ddo map
		const rqo = self.context.request_config.find(item => item.typo==='rqo')
		if (rqo) {
			const ddo_map			= rqo.show.ddo_map
			const ddo_map_length	= ddo_map.length
			for (let j = 0; j < ddo_map_length; j++) {

				const component_tipo = ddo_map[j]
					console.log("component_tipo:",component_tipo);

				const item_context = self.datum.context.find(item => item.tipo===component_tipo && item.parent===self.tipo)

				portal_items.push(item_context)
				// // iterate portal records
				// for (let k = 0; k < portal_data.length; k++) {
				// 	// if (!portal_data[k] || !portal_data[k].section_id) continue;

				// 	const portal_section_id		= portal_data[k].section_id
				// 	const portal_section_tipo	= portal_data[k].section_tipo
				// 		console.log("portal_section_id:",portal_section_id,portal_section_tipo);

				// 	break;
				// }

				// await add_instance(current_context, section_id)

				// const current_data = portal_data.find(item => item.from_component_tipo===component_tipo)
					// console.log("////// current_data "+component_tipo, current_data);
			}
		}


	return portal_items
}; //end get_portal_items



/**
* GET_LAST_OFFSET
*/
	// component_portal.prototype.get_last_offset = function() {
	// 	//console.log("[get_last_offset] self:",self);

	// 	const self = this

	// 	const total = self.pagination.total
	// 	const limit = self.pagination.limit

	// 	const _calculate = () => {

	// 		if (total>0 && limit>0) {

	// 			const total_pages = Math.ceil(total / limit)

	// 			return parseInt( limit * (total_pages -1) )

	// 		}else{

	// 			return 0
	// 		}
	// 	}
	// 	const offset_last = _calculate()

	// 	if(SHOW_DEBUG===true) {
	// 		console.log("====get_last_offset offset_last:",offset_last, "total",total, "limit",limit);
	// 	}

	// 	return offset_last
	// };//end  get_last_offset
