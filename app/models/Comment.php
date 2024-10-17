<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\User;
use Esvlad\Bx24copytobox\Models\Crm;

class Comment extends Model{
	protected $table = "comments";

	public static function handlerFields($fields){
		$comment = [];

		if(!self::where('new_id', $fields['ID'])->exists()){
			$comment['ID'] = $fields['ID'];
			$comment['NEW'] = true;
		} else {
			$comment['ID'] = self::where('old_id', $fields['ID'])->value('new_id');
		}

		$author_id = Crm::getBoxUserId($fields['AUTHOR_ID']);
		if($author_id === 1){
			$author_name = $fields['AUTHOR_NAME'];
		}
		$comment['AUTHOR_ID'] = $author_id;
		$comment['POST_DATE'] = $fields['POST_DATE'];

		if(!empty($author_name)){
			$comment['POST_MESSAGE'] = '[B]' . $author_name . ':[/B] ' . $fields['POST_MESSAGE'];
		} else {
			$comment['POST_MESSAGE'] = $fields['POST_MESSAGE'];
		}

		return $comment;
	}
}