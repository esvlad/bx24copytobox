<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\User;
use Esvlad\Bx24copytobox\Models\Crm;

class Task extends Model{
	protected $table = "tasks";

	public static function handlerFields($task = []){
		$fields = [];
		foreach($task as $key => $value){
			switch($key){
				case 'accomplices':
				case 'auditors':
					$users = [];
					foreach($value as $k => $v){
						$users[$k] = Crm::getBoxUserId($v);
					}
					$task[$key] = $users;

					break;
				case 'changedBy':
				case 'createdBy':
				case 'responsibleId':
					$task[$key] = Crm::getBoxUserId($value);
					break;
				case 'accomplicesData':
				case 'auditorsData':
				case 'commentsCount':
				case 'creator':
				case 'forumId':
				case 'forumTopicId':
				case 'group':
				case 'groupId':
				case 'guid':
				case 'responsible':
				case 'serviceCommentsCount':
				case 'siteId':
				case 'xmlId':
					unset($task[$key]);
					break;
				default : break;
			}
		}

		return $task;
	}
}