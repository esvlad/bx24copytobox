<?php

namespace Esvlad\Bx24copytobox\Controllers;

use Esvlad\Bx24copytobox\Models\Lead;

class LeadsController{

	public function add($cloud_id){
		sleep(60);

		Lead::setLeadToBox($cloud_id);

		return true;
	}

	public function update($cloud_id){
		sleep(60);
		$box_id = Lead::getLead($cloud_id);

		if(empty($box_id)){
			Lead::setLeadToBox($cloud_id);
		} else {
			Lead::updateLeadToBox($cloud_id, $box_id);
		}

		return true;
	}
}