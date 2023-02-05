/*global get_label, page_globals, SHOW_DEBUG, DEDALO_CORE_URL, DEDALO_API_URL, Promise */
/*eslint no-undef: "error"*/



/**
* DATA_LOADER
*/
export const data_manager = function() {

}//end data_manager



/**
* REQUEST
* Make a fetch request to server api
* @param object options
* @return promise api_response
*/
data_manager.request = async function(options) {
	// console.log("// request options:",options);

	// options
		this.url			= options.url || DEDALO_API_URL
		this.method			= options.method || 'POST' // *GET, POST, PUT, DELETE, etc.
		this.mode			= options.mode || 'cors' // no-cors, cors, *same-origin
		this.cache			= options.cache || 'no-cache' // *default, no-cache, reload, force-cache, only-if-cached
		this.credentials	= options.credentials || 'same-origin' // include, *same-origin, omit
		this.headers		= options.headers || {'Content-Type': 'application/json'}// 'Content-Type': 'application/x-www-form-urlencoded'
		this.redirect		= options.redirect || 'follow' // manual, *follow, error
		this.referrer		= options.referrer || 'no-referrer' // no-referrer, *client
		this.body			= options.body // body data type must match "Content-Type" header

		// this.url			= options.url || DEDALO_API_URL
		// this.method		= options.method || 'POST' // *GET, POST, PUT, DELETE, etc.
		// this.mode		= options.mode || 'cors' // no-cors, cors, *same-origin
		// this.cache		= options.cache || 'no-cache' // *default, no-cache, reload, force-cache, only-if-cached
		// this.credentials	= options.credentials || 'include' // include, *same-origin, omit
		// this.headers		= options.headers || {'Content-Type': 'application/json'}// 'Content-Type': 'application/x-www-form-urlencoded'
		// this.redirect	= options.redirect || 'follow' // manual, *follow, error
		// this.referrer	= options.referrer || 'no-referrer' // no-referrer, *client
		// this.body		= options.body // body data type must match "Content-Type" header

	// check url
		if (!this.url || !this.url.length) {
			const msg = 'Error: empty or invalid API URL'
			console.error(msg + '. typeof:', typeof this.url, 'value:', this.url);
			return {
				result	: false,
				msg		: msg,
				error	: 'URL is not valid'
			}
		}

	// handle_errors
		const handle_errors = function(response) {
			if (!response.ok) {
				console.warn("-> HANDLE_ERRORS response:",response);
				throw Error(response.statusText);
			}
			return response;
		}

	const api_response = fetch(
		this.url,
		{
			method		: this.method,
			mode		: this.mode,
			cache		: this.cache,
			credentials	: this.credentials,
			headers		: this.headers,
			redirect	: this.redirect,
			referrer	: this.referrer,
			body		: JSON.stringify(this.body)
		})
		.then(handle_errors)
		.then(response => {
			// console.log("-> json response 1 ok:",response);
			// return Promise.reject("*** PHP file not found");
			const json_parsed = response.json().then((result)=>{
				// console.log("-> json result 2:",result);
				if (result.error) {

					// debug console message
						if (SHOW_DEBUG) {
							console.error("result error:",result);
						}

					// alert msg to user
						const msg = result.msg || result.error
						// alert("An error occurred in the connection with the API (data_manager). \n" + msg);

					// custom behaviors
						switch (result.error) {
							case 'not_logged':
								// redirect to login page
								location.reload();
								break;

							default:
								// write message to the console
								break;
						}
				}

				return result
			})
			// console.log("-> api_response json_parsed:",json_parsed);
			return json_parsed
		})// parses JSON response into native JavaScript objects
		.catch(error => {
			console.warn('request url:', typeof this.url, this.url);
			console.warn("request options:", options);
			console.error("!!!!! [data_manager.request] SERVER ERROR. Received data is not JSON valid. See your server log for details. catch ERROR:\n")
			console.error('error:', error);
			return {
				result	: false,
				msg		: error.message,
				error	: error
			}
		});


	return api_response
}//end request



/**
* GET_ELEMENT_CONTEXT
* Resolves full element context based on minimal source vars
* Like:
*	source = {
*		model: "component_input_text"
*		tipo: "test159"
*		section_tipo: "test65"
*		section_id: null
*		mode: "search"
*	}
* @param object source
* @return promise api_response
*/
data_manager.get_element_context = async function(source) {

	// api request
		// const api_response = await this.request({
		const api_response = this.request({
			body : {
				action	: 'get_element_context',
				source	: source
			}
		})


	return api_response
}//end get_element_context



/**
* GET_PAGE_ELEMENT
* Get full page element
* Expected options:
*
*	$tipo 			= $options->tipo ?? null;
*	$model 			= $options->model ?? (isset($tipo) ? RecordObj_dd::get_modelo_name_by_tipo($tipo,true) : null);
*	$lang 			= $options->lang ?? DEDALO_DATA_LANG;
*	$mode 			= $options->mode ?? 'list';
*	$section_id 	= $options->section_id ?? null;
*	$component_tipo = $options->component_tipo ?? null;
*
* @param object options
* @return promise api_response
*/
data_manager.prototype.get_page_element = async function(options) {

	// api request
		// const api_response = await this.request({
		const api_response = this.request({
			body : {
				action	: 'get_page_element',
				options	: options
			}
		})


	return api_response
}//end get_page_element



/**
* GET_LOCAL_DB
*/
data_manager.get_local_db = async function() {

	// db storage
		// In the following line, you should include the prefixes of implementations you want to test.
		const current_indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
		// DON'T use "var indexedDB = ..." if you're not in a function.
		// Moreover, you may need references to some window.IDB* objects:
		// const IDBTransaction = window.IDBTransaction || window.webkitIDBTransaction || window.msIDBTransaction || {READ_WRITE: "readwrite"}; // This line should only be needed if it is needed to support the object's constants for older browsers
		// const IDBKeyRange = window.IDBKeyRange || window.webkitIDBKeyRange || window.msIDBKeyRange;
		// (Mozilla has never prefixed these objects, so we don't need window.mozIDB*)

	// invalid local db case
		if (!current_indexedDB) {
			console.error("Your browser doesn't support a stable version of IndexedDB. Such and such feature will not be available.");
		}

	// open db. Let us open our database (name, version)
		const db_request = current_indexedDB.open("dedalo", 6);


	return new Promise(function(resolve, reject){

		// error case
			db_request.onerror = function(event) {
				if(SHOW_DEBUG){
					console.log("-> get_local_db error:", event.target);
				}else{
					console.log("-----> It's not possible get_local_db, IndexedDB is blocked, Dédalo will run slowly without cache.");
				}

				reject(false)
			};

		// success case
			db_request.onsuccess = function(event) {

				const db = event.target.result;
				resolve(db)
			};

		// onupgradeneeded event
			db_request.onupgradeneeded = function(event) {
				console.log("-> get_local_db onupgradeneeded:", event.target);

				const db = event.target.result;

				// objectStore
				// Create an objectStore to hold information about our customers. We're
				// going to use "ssn" as our key path because it's guaranteed to be
				// unique - or at least that's what I was told during the kickoff meeting.
					db.createObjectStore('rqo', { keyPath: "id" });
					// context. Context information about some elements like 'search'
					db.createObjectStore('context', { keyPath: "id" });
					// status. Collapse status of section groups, inspector blocks,etc.
					db.createObjectStore('status', { keyPath: "id" });
					db.createObjectStore('data', { keyPath: "id" });
					db.createObjectStore('ontology', { keyPath: "id" });

				// index
				// Create an index to search customers by name. We may have duplicates
				// so we can't use a unique index.
					// objectStore.createIndex("name", "name", { unique: false });

				// oncomplete. Use transaction oncomplete to make sure the objectStore creation is
				// finished before adding data into it.
					// objectStore.transaction.oncomplete = function(event) {
						// Store values in the newly created objectStore.
						// const customerObjectStore = db.transaction(table, "readwrite").objectStore(table);
						// customerData.forEach(function(customer) {
						//	customerObjectStore.add(customer);
						// });
					// };
			};
	})
	.catch(err => {
		console.error(err)
	});
}//end local_db



/**
* SET_LOCAL_DB_DATA
* Save data into the browser local database (IndexdDB)
* @param object data
* @param string table
* 	Tables:
* 		status : element stored status like collapsed, etc.
* 		rqo : rqo cache data
* 		ontology ;: ontology cache data
* 		data : generic data like menu resolution
* 		context : context cache data
* Calling sample:
* 	current_data_manager.set_local_db_data(
* 		rqo, // mixed data
* 		'rqo' // string table
* 	)
* @return promise
*/
data_manager.set_local_db_data = async function(data, table) {

	const self = this

	// get local db
		const db = await self.get_local_db()

	// check if is possible create and use IndexDB, if not, the promise will return undefined and we use false
		if(!db){
			return false
		}

	return new Promise(function(resolve, reject){

		// transaction
			const transaction = db.transaction(table, "readwrite");
			// oncomplete. Do something when all the data is added to the database.
				// transaction.oncomplete = function(event) {
				// 	console.log("All done!");
				// };
			// error
				transaction.onerror = function(event) {
					if(SHOW_DEBUG){
						console.log("-> get_local_db error:", event.target);
					}else{
						console.log("-----> It's not possible get_local_db, IndexedDB is blocked, Dédalo will run slowly without cache.");
					}
					reject(false)
				};

		// request
			const objectStore	= transaction.objectStore(table);
			// const request	= objectStore.add(data);
			// Put this updated object back into the database.
			const request = objectStore.put(data);

			request.onsuccess = function(event) {
				// event.target.result === customer.ssn;
				// console.log("Yuppiii:", event.target);
				resolve(event.target.result)
			};
			request.onerror = function(event) {
				// console.error("-> set_local_db_data error:", event.target);
				reject(event.target.error);
			};
	})
}//end set_local_db_data



/**
* GET_LOCAL_DB_DATA
* @param string id
* @param string table
* Calling sample:
*	current_data_manager.get_local_db_data('tool_export_config', 'data')
* @return promise
*/
const db_table = {}
data_manager.get_local_db_data = async function(id, table, cache=false) {
	// const t0 = performance.now()

	const self = this

	// get local db
		const db = cache===true
			? await (async ()=>{
				if (!db_table[table]) {
					db_table[table] = await self.get_local_db()
				}
				return db_table[table]
			  })()
			: await self.get_local_db()

	// check if is possible create and use IndexDB, if not, the promise will return undefined and we use false
		if(!db){
			return false
		}
		// console.log(`__Time [data_manager.get_local_db_data] table:${table} id:${id} ms: `, performance.now()-t0);

	return new Promise(function(resolve, reject){

		// transaction
			const transaction = db.transaction(table, 'readwrite');
			// oncomplete. Do something when all the data is added to the database.
				// transaction.oncomplete = function(event) {
				// 	console.log("All done!");
				// };
			// error
				transaction.onerror = function(event) {
					if(SHOW_DEBUG){
						console.log("-> get_local_db_data error:", event.target);
						console.log('table:', table, 'db:',db);
					}else{
						console.log("-----> It's not possible get_local_db, IndexedDB is blocked, Dédalo will run slowly without cache.");
					}

					reject(false)
				};

		// request
			const objectStore	= transaction.objectStore(table);
			const request		= objectStore.get(id);

			request.onsuccess = function(event) {
				// event.target.result === customer.ssn;
				// console.log("Yuppiii:", event.target);
				resolve(event.target.result)
			};
			request.onerror = function(event) {
				// console.error("-> get_local_db_data error:", event.target);
				reject(event.target.error);
			};
	})
}//end get_local_db_data



/**
* GET_LOCAL_DB_DATA
*/
data_manager.delete_local_db_data = async function(id, table) {

	const self = this

	// get local db
		const db = await self.get_local_db()

	// check if is possible create and use IndexDB, if not, the promise will return undefined and we use false
		if(!db){
			return false
		}

	return new Promise(function(resolve, reject){

		// transaction
			const transaction = db.transaction(table, "readwrite");
			// oncomplete. Do something when all the data is added to the database.
				// transaction.oncomplete = function(event) {
				// 	console.log("All done!");
				// };
			// error
				transaction.onerror = function(event) {
					if(SHOW_DEBUG){
						console.log("-> get_local_db_data error:", event.target);
					}else{
						console.log("-----> It's not possible get_local_db, IndexedDB is blocked ");
					}
					reject(false)
				};

		// request
			const objectStore	= transaction.objectStore(table);
			const request		= objectStore.delete(id);

			request.onsuccess = function(event) {
				// success
				resolve(event.target.result)
			};
			request.onerror = function(event) {
				// console.error("-> get_local_db_data error:", event.target);
				reject(event.target.error);
			};
	})
}//end get_local_db_data



/**
* DOWNLOAD_URL
* @param string url
* @param string filename
* Download url blob data and create a temporal auto-fired link
*/
export function download_url(url, filename) {
	fetch(url).then(function(t) {
		return t.blob().then((b)=>{
			var a = document.createElement("a");
			a.href = URL.createObjectURL(b);
			a.setAttribute("download", filename);
			a.click();
			a.remove();
		}
		);
	});
}//end download_url



/**
* DOWNLOAD_DATA
* @param mixed data
* @param string filename
* Download data blob data and create a temporal auto-fired link
*/
export function download_data(data, filename) {

	const json	= JSON.stringify(data, null, 2)
	const blob	= new Blob([json], {type: "octet/stream"})
	const url	= window.URL.createObjectURL(blob)

	const a = document.createElement("a")
	a.style = "display: none"
	document.body.appendChild(a)
	a.href = url
	a.download = filename
	a.click()
	window.URL.revokeObjectURL(url)

	return true
}//end download_data



/**
* AREA_LOAD_DATA
* Generic area data loader
* @param object context
* @return promise api_response
*//*
data_manager.prototype.area_load_data = async function(basic_context) {

	// data_manager
		const api_response = this.request({
			body : {
				context : basic_context,
				action 	: 'read'
			}
		})

	// debug
		if(SHOW_DEBUG===true) {
			console.log("[data_manager.area_load_data] api_response for dd_request:", api_response, dd_request);
		}

	return api_response
}//end area_load_data
*/



/**
* COMPONENT_LOAD_DATA
* Generic component data loader from section_record
* @param object component
* @return promise data
*//*
data_manager.prototype.component_load_data = async function() {

	const component = this

	// section_record instance
		const section_record = await instances.get_instance({
			model			: 'section_record',
			tipo			: component.section_tipo,
			section_tipo	: component.section_tipo,
			section_id		: component.section_id,
			mode			: component.mode,
			lang			: component.section_lang
		})

	// get data from section_record
		const data = section_record.get_component_data(component.tipo)

	// inject property
		component.data = data

	return data
}//end component_load_data
*/



/**
* COMPONENT_LOAD_CONTEXT
* Generic component context loader from section_record
* @param object component
* @return promise context
*//*
data_manager.prototype.component_load_context = async function(component) {

	// section_record instance
		const section_record = await instances.get_instance({
			model			: 'section_record',
			tipo			: component.section_tipo,
			section_tipo	: component.section_tipo,
			section_id		: component.section_id,
			mode			: component.mode,
			lang			: component.section_lang
		})

	// get context from section_record
		const context = section_record.get_component_context(component.tipo)

	// inject property
		component.context = context

	return context
}//end component_load_context
*/
