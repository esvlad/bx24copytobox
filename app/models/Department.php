<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model {
	protected $table = "departments";

	public static function setCloudDepartmentToBox($cloud_id){
		$departament = Crm::bxCloudCall('department.get', ['ID' => $cloud_id]);

		$box_departament = [
			'NAME' => $departament['result'][0]['NAME'],
			'SORT' => $departament['result'][0]['SORT'],
			'PARENT' => self::getNewDepartmentId($departament['result'][0]['PARENT'])
		];

		$box_departament_id = Crm::bxBoxCall('department.add', $box_departament);

		self::insert([
			'old_id' => $cloud_id,
			'new_id' => $box_departament_id['result'],
			'name' => $departament['result'][0]['NAME'],
			'old_parent_id' => $departament['result'][0]['PARENT'],
			'old_user_id' => $departament['result'][0]['UF_HEAD']
		]);

		return $box_departament_id['result'];
	}

	/*public static function getCloudDepartments($start = 0){
		$client = new Client();
		$data = [];

		//Получаю данные из облака
		$res = $client->request('POST', env('CLOUD') . 'department.get', ['query' => ['start' => $start]]);
		$result = json_decode($res->getBody(), true);
		unset($res);

		//Если есть еще записи
		if(!empty($result['next'])) $next = $result['next'];

		//Формируем данные для запроса
		$batch = [];
		$list_id = [];
		foreach($result['result'] as $value){
			if($value['ID'] == 1) continue;

			$params = [
				'NAME' => $value['NAME'],
				'SORT' => $value['SORT'],
				'PARENT' => 1
			];

			$batch['cmd']['dep_' . $value['ID']] = 'department.add?';
			$batch['cmd']['dep_' . $value['ID']] .= http_build_query($params);

			$list_id['dep_' . $value['ID']] = [
				'old_id' => $value['ID'],
				'name' => $value['NAME'],
				'old_parent_id' => !empty($value['PARENT']) ? $value['PARENT'] : null,
				'old_user_id' => !empty($value['UF_HEAD']) ? $value['UF_HEAD'] : null
			];
		}

		//Заполняю коробку данными
		$batch_list = [];
		$i = 1;
		foreach($batch['cmd'] as $key => $value){
			$j = 0;
			if($i >= 25) $j = 1;

			$batch_list[$j]['cmd'][$key] = $value;
			$i++;
		}

		$result = [];
		$result[] = self::setBoxDepartament($batch_list[0]);
		sleep(1);
		$result[] = self::setBoxDepartament($batch_list[1]);

		foreach($result[0] as $key => $value){
			$list_id[$key]['new_id'] = $value;
		}

		foreach($result[1] as $key => $value){
			$list_id[$key]['new_id'] = $value;
		}

		//Формирую данный для записи в БД
		$departament_list = [];
		foreach($list_id as $key => $value){
			$departament_list[] = $value;
		}

		//self::preprint($departament_list);

		Department::insert($departament_list);

		if(!empty($next)){
			self::getCloudDepartments($next);
		}
	}

	private static function setBoxDepartament($batch_list){
		$client = new Client();
		$res = $client->request('POST', env('BOX') . 'batch', ['query' => $batch_list]);
		$result = json_decode($res->getBody(), true);

		return $result['result']['result'];
	}

	public static function updateBoxDepartmentsParents($offset = 0){
		$client = new Client();
		$count = Department::whereNotNull('old_parent_id')->count();
		$departments = Department::whereNotNull('old_parent_id')->offset($offset)->limit(25)->get();

		$batch = [];
		foreach($departments as $department){
			$params = [
				'ID' => $department['new_id'],
				'NAME' => $department['name'],
				'PARENT' => Department::where('old_id', $department['old_parent_id'])->value('new_id')
			];

			$batch['cmd']['dep_' . $department['new_id']] = 'department.update?';
			$batch['cmd']['dep_' . $department['new_id']] .= http_build_query($params);
		}

		//self::preprint($batch);

		$res = $client->request('POST', env('BOX') . 'batch', ['query' => $batch]);
		//$result = json_decode($res->getBody(), true);

		$offset += 25;
		if($count > $offset){
			sleep(1);
			self::updateBoxDepartmentsParents($offset);
		}
	}

	public static function getBoxDepartments($start, &$arr = []){
		$client = new Client();

		$res = $client->request('POST', env('BOX') . 'department.get', ['query' => ['PARENT' => 1, 'start' => $start]]);
		$result = json_decode($res->getBody(), true);

		foreach($result['result'] as $value){
			$arr[] = $value['ID'];
		}

		if(!empty($result['next'])){
			self::getBoxDepartments($result['next'], $arr);
			sleep(1);
		}

		return $arr;
	}*/

	public static function getNewDepartmentId($cloud_id){
		return self::where('old_id', $cloud_id)->value('new_id');
	}
}