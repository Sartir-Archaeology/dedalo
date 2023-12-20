<?php
declare(strict_types=1);
/**
* CLASS COMPONENT_FILTER_MASTER
* Overwrite some methods of component_filter
*
*/
class component_filter_master extends component_filter {



	/**
	* SAVE OVERRIDE
	* Overwrite component_filter method
	* @return int|null $section_id
	*/
	public function Save() : ?int {

		// Reset cache on every save action. IMPORTANT !
			filter::clean_caches(
				get_user_id(),  // user id. Current logged user id
				$this->tipo // DEDALO_FILTER_MASTER_TIPO dd170
			);

		return parent::Save();
	}//end Save



	/**
	* PROPAGATE_FILTER
	* Only to catch calls to parent method
	* @return bool
	*/
	public function propagate_filter() : bool {

		return true;
	}//end propagate_filter



}//end class component_filter_master
