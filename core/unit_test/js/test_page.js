// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global it, describe, assert, page_globals */
/*eslint no-undef: "error"*/


import {section} from '../../section/js/section.js'
import {get_instance} from '../../common/js/instances.js'
import {ui} from '../../common/js/ui.js'
import {render_relogin} from '../../login/js/render_login.js'



describe("PAGE TEST", async function() {

	const container = document.getElementById('content');

	const section_tipo = 'XXtest3'; // fake non existing section_tipo

	const page = await get_instance({
		model			: 'page',
		menu			: true
	});

	await page.build(true)

	// page.running_with_errors = [
	// 	{
	// 		msg		: `Unknown error`,
	// 		error	: 'unknown_error'
	// 	}
	// ]

	const node = await page.render()
	container.appendChild(node)
});



// @license-end
