// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global SHOW_DEBUG, QUOTA_EXCEEDED_ERR */
/*eslint no-undef: "error"*/


/**
* CREATE_COOKIE
*/
export const create_cookie = (name, value) => {

	try {
		return localStorage.setItem(name, value)
	}catch (e) {
		console.log(e);
		if (e===QUOTA_EXCEEDED_ERR) {
			 alert('Quota exceeded!'); //data wasn't successfully saved due to quota exceed so throw an error
		}
	}

	return false
}//end  create_cookie



/**
* READ_COOKIE
*/
export const read_cookie = (name) => {

	try {
		return localStorage.getItem(name)
	}catch (e) {
		alert('get_localStorage error: ' + e); //data wasn't successfully readed and so throw an error
	}

	return null
}//end  read_cookie



/**
* ERASE_COOKIE
*/
export const erase_cookie = (name) => {

	try {
		return localStorage.removeItem(name); //saves to the database, "key", "value"
	}catch (e) {
		alert('remove_localStorage error: ' + e); //data wasn't successfully readed and so throw an error
	}

	return false
}//end  erase_cookie



// @license-end
