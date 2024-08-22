<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Capsule\Manager as Capsule;

//use Esvlad\Bx24copytobox\Models\Crm;

class User extends Model {
	protected $table = "users";

	public static function getBoxUserId($cloud_id){
		$user_id = User::where('old_id', $cloud_id)->value('new_id');
		if(empty($user_id)) $user_id = 1;

		return $user_id;
	}
}