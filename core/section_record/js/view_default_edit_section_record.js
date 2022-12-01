/*global get_label, page_globals, SHOW_DEBUG*/
/*eslint no-undef: "error"*/



// imports
	import {event_manager} from '../../common/js/event_manager.js'
	// import {data_manager} from '../../common/js/data_manager.js'
	// import {get_instance} from '../../common/js/instances.js'
	import {ui} from '../../common/js/ui.js'



/**
* VIEW_DEFAULT_EDIT_SECTION_RECORD
* Manage the components logic and appearance in client side
*/
export const view_default_edit_section_record = function() {

	return true
}//end view_default_edit_section_record



/**
* RENDER
* Render the node to use in edit mode
* @param object self
* @param object options
* @return DOM node
*/
view_default_edit_section_record.render = async function(self, options) {

	// options
		const render_level = options.render_level || 'full'

	const ar_instances = await self.get_ar_instances_edit()

	// content_data
		const content_data = await get_content_data_edit(self, ar_instances)
		if (render_level==='content') {
			return content_data
		}

	// wrapper. ui build_edit returns component wrapper
		const wrapper =	ui.component.build_wrapper_edit(self, {
			label			: null,
			content_data	: content_data
		})

	// debug
		if(SHOW_DEBUG===true) {
			wrapper.addEventListener("click", function(e){
				if (e.altKey) {
					e.stopPropagation()
					e.preventDefault()
					// common.render_tree_data(instance, document.getElementById('debug'))
					console.log("/// selected instance:", self);
				}
			})
			// wrapper.classList.add('_'+self.id)
		}


	return wrapper
}//end render



/**
* GET_CONTENT_DATA_EDIT
* Iterates the received instances rendering each of them into the content_data container node
* @param object self
* 	Component instance pointer
* @param array ar_instances
* 	Initialized and built instances
* @return DOM node content_data
*/
const get_content_data_edit = async function(self, ar_instances) {

	const fragment = new DocumentFragment()

	// render . Render all instances node in parallel
		const ar_instances_length = ar_instances.length
		const ar_promises = []
		for (let i = 0; i < ar_instances_length; i++) {
			const current_promise = new Promise(function(resolve){

				const current_instance = ar_instances[i]

				// already rendered case
				if (current_instance.status==='rendered' && current_instance.node!==null) {
					resolve(true)
				}else{

					current_instance.render()
					.then(function(){
						// current_instance.instance_order_key = i
						resolve(true)
					})
					.catch((errorMsg) => {
						console.error(errorMsg);
						resolve(false)
					})
				}
			})
			ar_promises.push(current_promise)
		}
		// nodes. Await all instances are parallel rendered
		await Promise.all(ar_promises) // render work done safely

	// hierarchize nodes. Distribute nodes to parents
		for (let i = 0; i < ar_instances_length; i++) {

			if (typeof ar_instances[i]==='undefined') {
				console.warn(`Skipped undefined instance key ${i} from ar_instances:`, ar_instances);
				console.log("self:",self);
				continue;
			}

			const current_instance		= ar_instances[i]

			// component_filter case . Send to inspector
				if (current_instance.model==='component_filter') {
					// render_component_filter_xx event is observed by inspector init
					// to get the component DOM node and to place it into the inspector container
					event_manager.publish('render_component_filter_' + current_instance.section_tipo, current_instance)
					continue;
				}



			const current_instance_node	= current_instance.node || await current_instance.render()



			// parent_grouper. get the parent node inside the context
				const parent_grouper = current_instance.context.parent_grouper

			// if the item has the parent, the section_tipo is direct children of the section_record
			// else it has another item parent
			if(parent_grouper===self.section_tipo) { //  || self.mode==='list'

				// direct root level case
				fragment.appendChild(current_instance_node)

			}else{

				// get the parent instance like section group or others
				const parent_instance = ar_instances.find(
					instance => instance.tipo===parent_grouper
							&&  instance.section_id==current_instance.section_id
							&&  instance.section_tipo===current_instance.section_tipo
				)
				// if parent_istance exist, go to append the current instance to it.
				if(typeof parent_instance!=='undefined') {

					const parent_node = parent_instance.node || await parent_instance.render()
					// move the node to his father
					if (parent_instance.type==='grouper') { //  && self.mode!=='list'
						// check valid parameters
							// if (!parent_node || !current_instance_node) {
							// 	console.error("---error: parent_node:",parent_node, ' - current_instance_node:',current_instance_node);
							// }
						// append inside content data of grouper
						// Note that 'content_data' is attached to grouper wrapper as a property to avoid DOM search
						// const grouper_content_data_node = parent_node.querySelector(':scope >.content_data')
						const grouper_content_data_node = parent_node.content_data
						grouper_content_data_node.appendChild(current_instance_node)
					}else{
						// direct attach (safe fallback)
						parent_node.appendChild(current_instance_node)
					}
				}else{
					// direct attach (safe fallback)
					fragment.appendChild(current_instance_node)
				}
			}


			// portals case
			if (current_instance.context.legacy_model==='component_portal') {
				// setTimeout(async function(){
					// current_instance.standalone = true
				 	 current_instance.refresh()
				 // }, 1000)
			}
		}//end for (let i = 0; i < ar_instances_length; i++)


	// content_data (section_record)
		const content_data = document.createElement('div')
			  content_data.classList.add('content_data', self.mode, self.type)
			  content_data.appendChild(fragment)


	return content_data
}//end get_content_data_edit
