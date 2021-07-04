// imports
	import {common,load_data_debug} from '../../common/js/common.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import {area_common} from '../../area_common/js/area_common.js'
	import {search} from '../../search/js/search.js'
	import {render_area_thesaurus} from './render_area_thesaurus.js'



/**
* AREA_THESAURUS
*/
export const area_thesaurus = function() {
	
	this.id

	// element properties declare
	this.model
	this.type
	this.tipo
	this.section_tipo
	this.mode
	this.lang

	this.datum
	this.context
	this.data

	this.widgets

	this.node
	this.status

	this.filter = null

	this.rqo_config
	this.rqo

	this.build_options = {
		terms_are_model : false //false = the terms are descriptors terms // true = the terms are models (context model of the terms)
	}

	// display mode: 'default' | 'relation'
	this.thesaurus_mode

	return true
};//end area_thesaurus



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	// area_thesaurus.prototype.init		= area_common.prototype.init
	// area_thesaurus.prototype.build		= area_common.prototype.build
	area_thesaurus.prototype.render			= common.prototype.render
	area_thesaurus.prototype.refresh		= common.prototype.refresh
	area_thesaurus.prototype.destroy		= common.prototype.destroy
	area_thesaurus.prototype.build_rqo_show	= common.prototype.build_rqo_show
	area_thesaurus.prototype.edit			= render_area_thesaurus.prototype.edit
	area_thesaurus.prototype.list			= render_area_thesaurus.prototype.list



/**
* INIT
* @return bool
*/
area_thesaurus.prototype.init = async function(options) {

	const self = this

	// ts_object adds on
		const css_url = DEDALO_CORE_URL + "/ts_object/css/ts_object.css"
		common.prototype.load_style(css_url)

		const css_url2 = DEDALO_CORE_URL + "/area_thesaurus/css/area_thesaurus.css"
		common.prototype.load_style(css_url2)

	// thesaurus mode
		self.thesaurus_mode = options.thesaurus_mode || 'default' // 'relation'

	// call the generic commom tool init
		const common_init = area_common.prototype.init.call(this, options);

	return common_init
};//end init



/**
* BUILD
* @return promise
*	bool true
*/
area_thesaurus.prototype.build = async function(autoload=true) {
	const t0 = performance.now()

	const self = this

	// call the generic commom tool build
		// const common_build = await area_common.prototype.build.call(this, options);

	// status update
		self.status = 'building'

	// self.datum. On building, if datum is not created, creation is needed
		self.datum = self.datum || {
			data	: [],
			context	: []
		}
		self.data = self.data || {}

	const current_data_manager = new data_manager()

	// // rqo_config
	// 	self.rqo_config	= self.context.request_config.find(el => el.api_engine==='dedalo')

	// // rqo build
	// 	self.rqo = self.rqo || await self.build_rqo_show(self.rqo_config, 'get_data')

	// rqo
		const generate_rqo = async function(){
			// rqo_config. get the rqo_config from context
			self.rqo_config	= self.context.request_config
				? self.context.request_config.find(el => el.api_engine==='dedalo')
				: {}
			
			// rqo build
			const action	= 'get_data'
			const add_show	= false
			self.rqo = self.rqo || await self.build_rqo_show(self.rqo_config, action, add_show)
		}
		await generate_rqo()

	// old
		// // dd_request
		// 	const request_config	= self.context ? self.context.request_config : null
		// 	self.dd_request.show	= self.build_rqo('show', request_config, 'get_data')

		// // debug
		// 	const dd_request_show_original = JSON.parse(JSON.stringify(self.dd_request.show))

		// // build_options. Add custom area build_options to source
		// 	const source = self.dd_request.show.find(element => element.typo==='source')
		// 		  source.build_options = self.build_options	

	// load data if not yet received as an option
		if (autoload===true) {

			// get context and data
				// const api_response = await current_data_manager.read(self.dd_request.show)
				const api_response = await current_data_manager.request({body:self.rqo})
					console.log("AREA_THESAURUS api_response:", self.id, api_response);

			// set the result to the datum
				self.datum = api_response.result

			// set context and data to current instance
				self.context	= self.datum.context.find(element => element.tipo===self.tipo)
				self.data		= self.datum.data.filter(element => element.tipo===self.tipo)
				self.widgets	= self.datum.context.filter(element => element.parent===self.tipo && element.typo==='widget')
					console.log("self.widgets:",self.widgets);

			// dd_request
				// self.dd_request.show = self.build_rqo('show', self.context.request_config, 'get_data')
				// console.log("-----------------------self.dd_request.show", self.dd_request.show);

			// // rebuild the rqo_config and rqo in the instance
			// // rqo_config
			// 	self.rqo_config	= self.context.request_config.find(el => el.api_engine==='dedalo')

			// // rqo build
			// 	self.rqo = await self.build_rqo_show(self.rqo_config, 'get_data')

			// rqo regenerate
				await generate_rqo()
				console.log("SECTION self.rqo after load:", JSON.parse( JSON.stringify(self.rqo) ) );
	}//end if (autoload===true)
		

	// label
		self.label = self.context.label

	// permissions. calculate and set (used by section records later)
		self.permissions = self.context.permissions || 0

	// section tipo
		self.section_tipo = self.context.section_tipo || null

	// filter
		if (!self.filter) {
			self.filter = new search()
			self.filter.init({
				caller	: self,
				mode	: self.mode
			})
			self.filter.build()
		}
		// console.log("Remember. Filter unactive");

	// debug
		if(SHOW_DEBUG===true) {

			// event_manager.subscribe('render_'+self.id, function(){
				// load_data_debug(self, api_response, dd_request_show_original)
			// })

			//console.log("self.context section_group:",self.datum.context.filter(el => el.model==='section_group'));
			console.log("+ Time to build", self.model, " ms:", performance.now()-t0);
			//load_section_data_debug(self.section_tipo, self.request_config, load_section_data_promise)
		}

	// status update
		self.status = 'builded'


	return true
};//end build



/**
* GET_SECTIONS_SELECTOR_DATA
* @return array of objects sections_selector_data
*/
area_thesaurus.prototype.get_sections_selector_data = function() {

	const self = this

	const sections_selector_data = self.data.find(item => item.tipo===self.tipo).value


	return sections_selector_data
};//end get_sections_selector_data
