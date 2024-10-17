<?php

namespace App\Controllers;

use App\Models\UserField;

class UserFieldsController {

	public static function transfer($type) {
		//Трансфер данных из облака в коробку
		UserField::getCloudUserFieldsToBox($type);

		return true;
	}

	public function test($type){
		UserField::setTestFieldToBox($type);

		return true;
	}

	public function testget($type){
		$res = UserField::getCloudUserField($type, 4260);

		preprint($res);

		return true;
	}

}