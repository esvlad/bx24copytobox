<?php

namespace Esvlad\Bx24copytobox\Controllers;

use Esvlad\Bx24copytobox\Models\Lead;

class Leads{

	public function add($cloud_id){
		Lead::setLeadToBox($cloud_id);

		return true;
	}

	public function add($cloud_id){
		Lead::updateLeadToBox($cloud_id);

		return true;
	}
}