/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import * as instances from '../../common/js/instances.js'
	import {common,create_source,load_data_debug} from '../../common/js/common.js'
	import {paginator} from '../../paginator/js/paginator.js'
	import {search} from '../../search/js/search.js'
	import {inspector} from '../../inspector/js/inspector.js'
	import {ui} from '../../common/js/ui.js'
	import {render_section} from './render_section.js'



/**
* SECTION
*/
export const section = function() {

	this.id

	// element properties declare
	this.model
	this.type
	this.tipo
	this.section_tipo
	this.section_id
	this.mode
	this.lang

	this.datum
	this.context
	this.data
	this.total

	this.ar_section_id

	this.node
	this.ar_instances

	this.status
	this.paginator

	this.id_variant

	this.rqo_config
	this.rqo

	return true
};//end section



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// life clycle
	section.prototype.render			= common.prototype.render
	section.prototype.destroy			= common.prototype.destroy
	section.prototype.refresh			= common.prototype.refresh
	section.prototype.build_rqo_show	= common.prototype.build_rqo_show

	// render
	section.prototype.edit				= render_section.prototype.edit
	section.prototype.list				= render_section.prototype.list
	section.prototype.list_portal		= render_section.prototype.list
	section.prototype.tm				= render_section.prototype.list
	section.prototype.list_header		= render_section.prototype.list_header

	section.prototype.get_columns		= common.prototype.get_columns





/**
* INIT
* @return bool
*/
section.prototype.init = async function(options) {

	const self = this

	// instance key used vars
	self.model				= options.model
	self.tipo				= options.tipo
	self.section_tipo		= options.section_tipo
	self.section_id			= options.section_id
	self.mode				= options.mode
	self.lang				= options.lang

	// DOM
	self.node				= []
	self.columns			= []

	self.section_lang		= options.section_lang
	self.parent				= options.parent

	self.events_tokens		= []
	self.ar_instances		= []

	self.caller				= options.caller	|| null

	self.datum				= options.datum		|| null
	self.context			= options.context	|| null
	self.data				= options.data		|| null

	self.type				= 'section'
	self.label				= null

	self.filter				= null // (? used)
	self.inspector			= null

	self.id_column_width	= '7.5em'
	self.permissions		= options.permissions || null
	
	// events subscription


	// status update
		self.status = 'initiated'


	return true
};//end init



/**
* BUILD
* @return promise
*	bool true
*/
section.prototype.build = async function(autoload=false) {
	const t0 = performance.now()

	const self = this

	// status update
		self.status = 'building'

	// self.datum. On building, if datum is not created, creation is needed
		if (!self.data) {
			self.data = []
		}
		if (!self.datum) {
			self.datum = {data:self.data, context:[]}
		}

	// rqo_config
		self.rqo_config	= self.context.request_config.find(el => el.api_engine==='dedalo')

	// rqo build
		self.rqo = self.rqo || await self.build_rqo_show(self.rqo_config, 'search')

	const current_data_manager	= new data_manager()

	// load data if is not already received as option
		if (autoload===true) {

			// get context and data
				// const current_data_manager	= new data_manager()
				const api_response			= await current_data_manager.request({body:self.rqo})
					console.log("api_response:",api_response);

				// // set value
				// 	current_data_manager.set_local_db_data(rqo, 'rqo')

				// // get value
				// const a = await current_data_manager.get_local_db_data(self.id, 'rqo')
				// 	console.log("a:",a);

				// // delete value
				// 	const deleted = await current_data_manager.delete_local_db_data(self.id, 'rqo')
				// 	console.log("deleted:",deleted);
			
			// set the result to the datum
				self.datum = api_response.result

			// set context and data to current instance
				self.context		= self.datum.context.find(el => el.section_tipo===self.section_tipo)
				self.data			= self.datum.data.find(el => el.tipo===el.section_tipo && el.section_tipo===self.section_tipo) || []
				self.section_id	= self.data.length>0
					? self.data.value.find(el => el.section_tipo===self.section_tipo).section_id
					: null

			// rebuild the rqo_config and rqo in the instance
			// rqo_config
				self.rqo_config	= self.context.request_config.find(el => el.api_engine==='dedalo')

			// rqo build
				self.rqo = await self.build_rqo_show(self.rqo_config, 'search')

			// count rows
				if (!self.total) {
					const response = await current_data_manager.count(self.rqo.sqo)
					self.total = response.result.total
					// set value
					current_data_manager.set_local_db_data(self.rqo, 'rqo')
				}

			// debug
				if(SHOW_DEBUG===true) {
					const event_token = event_manager.subscribe('render_'+self.id, show_debug_info)
					function show_debug_info() {
						event_manager.unsubscribe(event_token)
						load_data_debug(self, api_response, self.rqo)
					}
				}
		}
		// else{
		//
		// 	// set context and data to current instance
		// 		self.context	= self.datum.context.filter(element => element.section_tipo===self.section_tipo)
		// 		self.data 		= self.datum.data.find(element => element.tipo===element.section_tipo && element.section_tipo===self.section_tipo)
		// 		self.section_id = self.data
		// 			? self.data.value.find(element => element.section_tipo===self.section_tipo).section_id
		// 			: null
		//
		// 	// set request_config
		// 		self.request_config = self.context.find(item => item.tipo===self.tipo && item.model==='section').request_config
		// }


	// sqo
		// const sqo = self.rqo.show.find(element => element.typo==='sqo')
		const sqo = self.rqo.sqo


	// Update section mode/label with context declarations
		const section_context = self.context || {
			mode		: 'edit',
			label		: 'Section without permissions '+self.tipo,
			permissions	: 0
		}
		self.mode 	= section_context.mode
		self.label 	= section_context.label

	// permissions. calculate and set (used by section records later)
		self.permissions = section_context.permissions || 0

	// initiator . Url defined var or Caller of parent section
	// this is a param that defined who is calling to the section, sometimes it can be a tool or page or ...,
		const searchParams = new URLSearchParams(window.location.href);
		const initiator = searchParams.has("initiator")
			? searchParams.get("initiator")
			: self.caller!==null
				? self.caller.id
				: false
		// fix initiator
			self.initiator = initiator


	// paginator
		if (!self.paginator) {

			const current_paginator = new paginator()
			current_paginator.init({
				caller : self
			})
			current_paginator.build()
			// fix section paginator
			self.paginator = current_paginator

			self.events_tokens.push(
				event_manager.subscribe('paginator_goto_'+current_paginator.id , async (offset) => {

					// loading
						const selector = self.mode==='list' ? '.list_body' : '.content_data.section'
						const node = self.node && self.node[0]
							? self.node[0].querySelector(selector)
							: null
							console.log("node:",node);
						if (node) {
							node.classList.add('loading')
						}

					// self.pagination.offset = offset

					self.rqo.sqo.offset = offset
					// set value
					current_data_manager.set_local_db_data(self.rqo, 'rqo')

					// refresh
						await self.refresh() // refresh current section

					// loading
						if (node) {
							node.classList.remove('loading')
						}
				})
			)//end events push
		}

	// filter
		// if (!self.filter && self.permissions>0) {
		// 	const current_filter = new search()
		// 	current_filter.init({
		// 		caller : self
		// 	})
		// 	current_filter.build()
		// 	// fix section filter
		// 	self.filter = current_filter
		// }
		console.log("section build filter unactive (remember) ");

	// inspector
		if (!self.inspector && self.permissions) {
			// if (initiator && initiator.model==='component_portal') {

			// 	self.inspector = null

			// }else{

				const current_inspector = new inspector()
				current_inspector.init({
					section_tipo	: self.section_tipo,
					section_id		: self.section_id
				})
				current_inspector.caller = self
				current_inspector.build()
				// fix section inspector
				self.inspector = current_inspector
			// }
		}
	// get the column for use into the list.
		self.columns = self.get_columns()

	// debug
		if(SHOW_DEBUG===true) {
			// console.log("self.context section_group:",self.datum.context.filter(el => el.model==='section_group'));
			// load_section_data_debug(self.section_tipo, self.request_config, load_section_data_promise)
			console.log("__Time to build", self.model, " ms:", performance.now()-t0);

			// debug duplicates check
				const ar_used = []
				for(const element of self.datum.data) {
					const index = ar_used.findIndex(item => item.tipo===element.tipo && item.section_tipo===element.section_tipo && item.section_id===element.section_id && item.from_component_tipo===element.from_component_tipo && item.parent_section_id===element.parent_section_id && item.row_section_id===element.row_section_id)
					if (index!==-1) {
						console.error("SECTION ERROR. self.datum.data contains duplicated elements:", self.datum.data);

					}else{
						ar_used.push(element)
					}
				}
		}

	// status update
		self.status = 'builded'

	return true
};//end build



/**
* GET_AR_INSTANCES
*/
section.prototype.get_ar_instances = async function(){

	const self = this

	// self data verification
		// if (typeof self.data==="undefined") {
		// 	self.data = {
		// 		value : []
		// 	}
		// }
	
	// iterate records
		const lang 			= self.lang
		const value			= self.data.value || []
		const value_length	= value.length

		// const offset = self.rqo.sqo.offset
	

		const ar_instances = []
		for (let i = 0; i < value_length; i++) {
			// console.groupCollapsed("section: section_record " + self.tipo +'-'+ value[i]);
			const current_section_id	= value[i].section_id
			const current_section_tipo	= value[i].section_tipo
			// const current_data			= (self.mode==='tm')
			// 	? self.datum.data.filter(element => element.matrix_id===value[i].matrix_id && element.section_tipo===current_section_tipo && element.section_id===current_section_id)
			// 	: self.datum.data.filter(element => element.section_tipo===current_section_tipo && element.section_id===current_section_id)
			const current_context 		= (typeof self.datum.context!=="undefined")
				? self.datum.context.filter(el => el.section_tipo===current_section_tipo && el.parent===self.tipo)
				: []

			const instance_options = {
					model			: 'section_record',
					tipo			: current_section_tipo,
					section_tipo	: current_section_tipo,
					section_id		: current_section_id,
					mode			: self.mode,
					lang			: lang,
					context			: current_context,
					// data			: current_data,
					datum			: self.datum,
					caller			: self,
					// offset			: (offset+i),
					columns 		: self.columns,
					column_id		: self.column_id
			}

			// id_variant . Propagate a custom instance id to children
				if (self.id_variant) {
					instance_options.id_variant = self.id_variant
				}

			// time machine options
				if (self.mode==='tm') {
					instance_options.matrix_id			= value[i].matrix_id
					instance_options.modification_date	= value[i].timestamp
					// instance_options.state			= value[i].state
				}

			// section_record. init and build
				const current_section_record = await instances.get_instance(instance_options)
				await current_section_record.build(true)


			// add instance
				ar_instances.push(current_section_record)
				
		}//end for loop

	// set
		self.ar_instances = ar_instances

	return self.ar_instances
};//end get_ar_instances



// /**
// * GET_AR_ROWS
// */
// section.prototype.get_ar_rows = async function(){
//
// 	const self = this
//
// 	// self data veification
// 		if (typeof self.data==="undefined") {
// 			self.data = {
// 				value : []
// 			}
// 		}
//
// 	// iterate records
// 		const value			= self.data.value || []
// 		const value_length	= value.length
//
// 		const offset = self.pagination.offset
//
// 		for (let i = 0; i < value_length; i++) {
// 			//console.groupCollapsed("section: section_record " + self.tipo +'-'+ ar_section_id[i]);
//
// 			const current_section_id	= value[i].section_id
// 			const current_section_tipo	= value[i].section_tipo
// 			const current_data			= (self.mode==='tm')
// 				? self.datum.data.filter(element => element.matrix_id===value[i].matrix_id && element.section_tipo===current_section_tipo && element.section_id===current_section_id)
// 				: self.datum.data.filter(element => element.section_tipo===current_section_tipo && element.section_id===current_section_id)
// 			const current_context 		= self.context
//
// 			const instance_options = {
// 					model			: 'section_record',
// 					tipo			: current_section_tipo,
// 					section_tipo	: current_section_tipo,
// 					section_id		: current_section_id,
// 					mode			: 'list',
// 					lang			: self.lang,
// 					context			: current_context,
// 					data			: current_data,
// 					datum			: self.datum,
// 					caller			: self,
// 					offset			: (offset+i),
// 					columns 		: self.columns
// 			}
//
// 			// id_variant . Propagate a custom instance id to children
// 				if (self.id_variant) {
// 					instance_options.id_variant = self.id_variant
// 				}
//
// 			// time machine options
// 				if (self.mode==='tm') {
// 					instance_options.matrix_id			= value[i].matrix_id
// 					instance_options.modification_date	= value[i].timestamp
// 					// instance_options.state			= value[i].state
// 				}
//
// 			// section_record. init and build
// 				const current_section_record = await instances.get_instance(instance_options);
// 				await current_section_record.build()
//
// 			// add
// 				// self.ar_instances.push(current_section_record)
//
// 		}//end for loop
//
//
// 	// return self.ar_instances
// };//end get_ar_instances
//





/**
* RENDER
* @return promise render_promise
*//*
section.prototype.render__DES = async function(){

	const self = this

	// status update
		self.status = 'rendering'

	// self data veification
	if (typeof self.data==="undefined") {
		self.data = {
			value : []
		}
	}

	// iterate records
		const value 		= self.data.value || []
		const value_length 	= value.length

		for (let i = 0; i < value_length; i++) {
			//console.groupCollapsed("section: section_record " + self.tipo +'-'+ ar_section_id[i]);

			const current_section_id 	= value[i].section_id
			const current_section_tipo 	= value[i].section_tipo
			const current_data			= self.datum.data.filter(element => element.section_tipo===current_section_tipo && element.section_id===current_section_id)
			const current_context 		= self.context

			// section_record
				const current_section_record = await instances.get_instance({
					model 			: 'section_record',
					tipo 			: current_section_tipo,
					section_tipo	: current_section_tipo,
					section_id		: current_section_id,
					mode			: self.mode,
					lang			: self.lang,
					context 		: current_context,
					data			: current_data,
					datum 			: self.datum
				})

			// add
				self.ar_instances.push(current_section_record)

		}//end for loop


		// render using external proptotypes of 'render_component_input_text'
			// const mode = self.mode
			// //self.ar_instances.push(ar_section_record)
			// let node = null
			// switch (mode){
			// 	case 'list':
			// 		// add prototype list function from render_component_input_text
			// 		section.prototype.list 			= render_section.prototype.list
			// 		section.prototype.list_header 	= render_section.prototype.list_header
			// 		const list_node = await self.list(self.ar_instances)
 			// 		// set
 			// 		self.node.push(list_node)
 			// 		node = list_node
 			// 		break
 			//
 			// 	case 'edit':
 			// 	default :
 			// 		// add prototype edit function from render_section
 			// 		section.prototype.edit =  render_section.prototype.edit
 			// 		const edit_node = await self.edit(self.ar_instances)
 			// 		// set
 			// 		self.node.push(edit_node)
 			// 		node = edit_node
		 	// 		break
			// }

	// get node
		//const get_node = async () => {
		//
		//	switch (self.mode){
		//		case 'list':
		//			return self.list(self.ar_instances)
		//			break
		//
		//		case 'edit':
		//		default :
		//
		//			return self.edit(self.ar_instances)
		//			break
		//	}
		//}
		//const node = await get_node()

	// node
		const node = await self[self.mode]()

	// set
		self.node.push(node)

	// status update
		self.status = 'rendered'

	// event publish
		event_manager.publish('render_'+self.id, node)


	return node
};//end render
*/


/**
* RENDER_CONTENT
* @return promise render_promise
*//*
section.prototype.render_content = async function(){

	const self = this

	// status update
		self.status = 'rendering'

	// instances
		self.ar_instances = await self.get_section_record_instances()

	// node
		const new_content_data_node = await self.render_content_data()

	// replace
		for (let i = 0; i < self.node.length; i++) {

			const wrapper 				 = self.node[i]
			const old_content_data_node  = wrapper.querySelector(":scope > .content_data")

				//console.log("wrapper:",wrapper);
				//console.log("old_content_data_node:",old_content_data_node);
				//console.log("new_content_data_node:",new_content_data_node);

			wrapper.replaceChild(new_content_data_node, old_content_data_node)
		}

	// status update
		self.status = 'rendered'


	// event publish
		event_manager.publish('render_'+self.id, self.node[0])


	return self.node[0]
};//end render_content
*/




/**
* CREATE_request_config
* @return
*//*
section.prototype.create_request_config = function(){

	const self = this

		console.log("sqo_in JS:");

	// filter
		let filter = null
		if (self.section_id) {
			filter = {
				"$and": [{
					q: self.section_id,
					path: [{
						section_tipo : self.section_tipo,
						modelo 		 : "component_section_id"
					}]
				}]
			}
		}
	// sqo_show
		const show = [
			{ // source object
				typo			: "source",
				action			: "search",
				model 			: 'section',
				tipo 			: self.section_tipo,
				mode 			: self.mode,
				lang 			: self.lang,
				pagination		: {offset : 0},
			},
			{ // search query object in section 'test65'
				typo			: "sqo",
				id				: "query_"+self.section_tipo+"_sqo",
				section_tipo	: [self.section_tipo],
				limit			: (self.mode==="list") ? 10 : 1,
				order			: null,
				offset			: 0,
				full_count		: false,
				filter			: filter
			},
			{ // section 'test65'
				typo			: "ddo",
				model			: self.model,
				tipo 			: self.section_tipo,
				section_tipo 	: self.section_tipo,
				mode 			: self.mode,
				lang 			: self.lang,
				parent			: "root"
			}
		]
	// request_config
		const request_config = {
			show : show,
			search : []
		}

	return request_config
};//end create_request_config
*/



/**
* LOAD_DATA
* @return
*//*
section.prototype.load_data = async function() {

	const self = this

	const current_datum = self.datum

	// set data to current instance
		self.context	= current_datum.context.filter(element => element.section_tipo===self.section_tipo)
		self.data 		= current_datum.data.filter(element => element.section_tipo===self.section_tipo)

		// Update section mode with context declaration
			const section_context = self.context.filter(element => element.tipo===self.section_tipo)[0]
			self.mode = section_context.mode

		const section_data		= current_datum.data.filter(item => item.tipo===self.section_tipo && item.section_tipo===self.section_tipo)
		const ar_section_id		= section_data[0].value
		self.ar_section_id 		= ar_section_id

	return true
};//end load_data
*/



/**
* LOAD_SECTION_RECORDS
* @return promise loaded
*//*
section.prototype.load_section_records = function() {

	const self = this

	const context 		= self.context
	const data 			= self.data
	const section_tipo 	= self.section_tipo

	const section_data		= data.filter(item => item.tipo===section_tipo && item.section_tipo===section_tipo)
	const ar_section_id		= section_data[0].value
	self.ar_section_id 		= ar_section_id
	const data_lenght 		= ar_section_id.length
	const context_lenght 	= context.length


	const loaded = new Promise(function(resolve){

		const section_record_promises =[]
		// for every section_id
		for (let i = 0; i < data_lenght; i++) {

			// init component
				const item_options = {
					model 			: 'section_record',
					data			: data,
					context 		: context,
					section_tipo	: section_tipo,
					section_id		: ar_section_id[i],
					tipo 			: section_tipo,
					mode			: self.mode,
					lang			: self.lang,
					global_context 	: self.context,
					global_data 	: self.context,
				}

			const current_instance = instances.get_instance(item_options).then(function(section_record){
				return section_record.build()
			})

			// add the instances to the cache
				section_record_promises.push(current_instance)

		}// end for

		return Promise.all(section_record_promises).then(function(){
			resolve(true)
		})
	})//end loaded

	return loaded
};//end load_section_records
*/
