// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global it, describe, assert */
/*eslint no-undef: "error"*/

import {
	mode,
	lang
} from './elements.js'
import {get_instance} from '../../common/js/instances.js'
import {page} from '../../page/js/page.js'
import {component_input_text} from '../../component_input_text/js/component_input_text.js'
import {tool_lang} from '../../../tools/tool_lang/js/tool_lang.js'



describe("INSTANCES : GET_INSTANCE (PAGE/COMPONENT/TOOL)", function() {

	function make_test(elements, expected) {
		it(`${JSON.stringify(elements)} => '${expected.name}'`, async function() {
			const instance = await get_instance(elements)
			assert.instanceOf(instance, expected, 'result is an instance of expected '+ instance.name);
		});
	}

	// page instance
		describe("Builds page instance from options", function() {
			make_test(
				{
					model : 'page'
				},
				page
			);
		});

	// component_input_text instance
		describe("Builds component_input_text instance from options", function() {
			make_test(
				{
					model	: 'component_input_text',
					tipo	: 'test52',
					mode	: mode,
					lang	: lang
				},
				component_input_text
			);
		});

	// tool_lang instance
		describe("Builds tool_lang instance from options", function() {
			make_test(
				{
					model		: 'tool_lang',
					mode		: mode,
					lang		: lang,
					tool_object	: {},
					caller		: {}
				},
				tool_lang
			);
		});
});



// @license-end
