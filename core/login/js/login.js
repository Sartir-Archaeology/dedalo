/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	import {data_manager} from '../../common/js/data_manager.js'
	import * as instances from '../../common/js/instances.js'
	import {common,create_source} from '../../common/js/common.js'
	import {render_login} from './render_login.js'



/**
* LOGIN
*/
export const login = function() {

	this.id

	// element properties declare
	this.model
	this.type
	this.tipo
	this.mode
	this.lang

	this.datum
	this.context
	this.data

	this.node
	//this.ar_instances

	this.status

	return true
};//end login



/**
* COMMON FUNCTIONS
* extend component functions from component common
*/
// prototypes assign
	login.prototype.edit	= render_login.prototype.edit
	login.prototype.render	= common.prototype.render
	login.prototype.destroy	= common.prototype.destroy
	login.prototype.refresh	= common.prototype.refresh



/**
* INIT
* @return bool
*/
login.prototype.init = async function(options) {

	const self = this

	// instance key used vars
	self.model			= options.model
	self.tipo			= options.tipo
	self.mode			= options.mode
	self.lang			= options.lang

	// DOM
	self.node			= []

	self.events_tokens	= []
	self.context		= options.context	|| null
	self.data			= options.data		|| null
	self.datum			= options.datum		|| null

	self.type			= 'login'
	self.label			= null


	// status update
		self.status = 'initiated'


	return true
};//end init



/**
* BUILD
* @return promise
*	bool true
*/
login.prototype.build = async function(autoload=true) {
	const t0 = performance.now()

	const self = this

	// status update
		self.status = 'building'


	if (autoload===true) {

		// rqo build
			const rqo = {
				action : 'get_login',
				dd_api : 'dd_utils_api',
				source : create_source(self, null)
			}

		// load data. get context and data
			const current_data_manager	= new data_manager()
			const api_response			= await current_data_manager.request({
				body : rqo
			})		

		// set the result to the datum
			self.datum = api_response.result
	}

	// set context and data to current instance
		self.context	= self.datum.context.find(element => element.tipo===self.tipo);
		self.data		= self.datum.data.find(element => element.tipo===self.tipo);

	// debug
		if(SHOW_DEBUG===true) {
			//console.log("self.context section_group:",self.datum.context.filter(el => el.model==='section_group'));
			console.log("__Time to build", self.model, " ms:", performance.now()-t0);
		}

	// status update
		self.status = 'builded'


	return true
};//end build



/**
* QUIT
*/
export const quit = async function() {

	// data_manager API call
		const api_response = await data_manager.prototype.request({
			body : {
				action	: 'quit',
				dd_api	: 'dd_utils_api',
				options	: {}
			}
		})

	// manage result
		if (api_response.result===true) {

			// SAML redirection check
			if (typeof api_response.saml_redirect!=="undefined" && api_response.saml_redirect.length>2) {

				window.location.href = api_response.saml_redirect

			}else{
				//window.location.href = window.location
				window.location.reload(false)
			}

		}else{

			console.error(api_response.msg);
		}


	return api_response
};//end quit



// expose login functions to window
	// window.login = {
	// 	quit : quit
	// }
