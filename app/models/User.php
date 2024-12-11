<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\Crm;

class User extends Model {
	protected $table = "users";

	public static function getBoxUserId($cloud_id){
		$user_id = self::where('old_id', $cloud_id)->value('new_id');
		if(empty($user_id)) $user_id = 1;

		return $user_id;
	}

	public static function getCloudUsersToBox($start = 0){
		$result = Crm::bxCloudCall('user.get', ['filter' => ['ACTIVE' => true], 'start' => $start]);

		//Если есть еще записи
		if(!empty($result['next'])) $next = $result['next'];

		$batch = [];
		$list_id = [];
		$new_users = [];
		foreach($result['result'] as $user){
			$hasUser = Capsule::table('users_transfer')->where('old_id', $user['ID'])->whereNotNull('new_id');

			if(!$hasUser->exists()){
				$user_id = $user['ID'];

				if(count($user['UF_DEPARTMENT']) > 0){
					$departments = [];
					foreach($user['UF_DEPARTMENT'] as $k => $department_cloud_id){
						$department_id = Department::where('old_id', $department_cloud_id)->value('new_id');
						if(!empty($department_id)){
							$departments[] = $department_id;
						} else {
							$departments[] = Department::setCloudDepartmentToBox($department_cloud_id);
						}
					}
				}

				$new_user = [
					'NAME' => $user['NAME'],
					'EMAIL' => $user['EMAIL'],
					'PERSONAL_GENDER' => $user['PERSONAL_GENDER'],
					'PERSONAL_BIRTHDAY' => $user['PERSONAL_BIRTHDAY'],
					'USER_TYPE' => $user['USER_TYPE'],
					'UF_DEPARTMENT' => $user['UF_DEPARTMENT'] ? $departments : null
				];

				foreach($user as $k => $v){
					switch($k){
						case 'TIME_ZONE':
						case 'LAST_NAME':
						case 'SECOND_NAME':
						case 'TIME_ZONE_OFFSET':
						case 'PERSONAL_CITY':
						case 'WORK_POSITION':
						case 'PERSONAL_GENDER':
						case 'PERSONAL_BIRTHDAY':
							if(!empty($v)) $new_user[$k] = $v;
							break;
					}
				}

				$new_users[] = $new_user;

				$batch['usr_' . $user_id] = [
					'method' => 'user.add',
					'params' => $new_user
				];


				$list_id['usr_' . $user_id] = [
					'old_id' => $user_id
				];

				//print_r($new_user);

				Capsule::table('users_transfer')->insert(['old_id' => $user['ID'], 'email' => $user['EMAIL']]);
			}
		}

		if(!empty($batch)){
			$result = Crm::bxBoxCallBatch($batch);

			foreach($result['result']['result'] as $key => $value){
				$list_id[$key]['new_id'] = $value;
			}

			$user_list = [];
			foreach($list_id as $user_id){
				Capsule::table('users_transfer')->where('old_id', $user_id['old_id'])->update(['new_id' => $user_id['new_id']]);
			}
		}

		print_r($new_users);

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::getCloudUsersToBox($next);
	}

	public static function replacement($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$count = Capsule::table('fg3re_users')->count();
		$users = Capsule::table('fg3re_users')->offset($start)->limit(1000)->get();

		if($start < $count) $next = $start + 1000;

		foreach($users as $user){
			$bitrix_id = Capsule::table('users_transfer')->where('old_id', $user->bitrix_id)->whereNotNull('new_id');
			if($bitrix_id->exists()){
				$user_id = $bitrix_id->value('new_id');
				Capsule::table('fg3re_users')->where('id', $user->id)->update(['bitrix_id' => $user_id]);
			}
		}

		if(empty($next)){
			print('Изменение завершено');
			return true;
		}

		self::replacement($next);
	}
}