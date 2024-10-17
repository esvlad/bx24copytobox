<?php

namespace App\Models;

ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '200M');

use Illuminate\Database\Capsule\Manager as Capsule;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

use App\Models\CrestCloud;
use App\Models\Crest;
use App\Models\User;

class Crm{
	public static function updateIdToCloud($type, $start = 0, &$result = []){
		$client = new Client();
		$res = $client->get(env('CLOUD') . 'crm.'. $type .'.list',
			[
			'query' => [
				'select' => [
					'ID'
				],
			]
		]);
		$crm = json_decode($res->getBody(), true);

		if(!empty($crm['next'])) $next = $crm['next'];

		$batch_list = [];
		foreach ($crm['result'] as $value) {
			$batch_list['crm_' . $value['ID']] = [
				'method' => 'crm.'. $type .'.update',
				'params' => [
					'id' => $value['ID'],
					'fields' => [
						'UF_CRM_1720601579' => $value['ID'],
						'UF_CRM_1720601659' => 'https://stopzaym.bitrix24.ru/crm/lead/details/' . $value['ID'] . '/'
					]
				]
			];
		}

		foreach($batch_list as $key => $value){
			$result[] = self::updateCrestCloudID($value);
		}


		if(!empty($next)){
			self::updateIdToCloud($type, $next, $result);
		}

		preprint($batch_list);
	}

	public static function updateIdInCloud($type, $start = 0, $count = 0){
		self::lastTypeCounter($type, $start, $count);

		$client = new Client();
		$res = $client->get(env('CLOUD') . 'crm.'. $type .'.list',
			[
			'query' => [
				'select' => [
					'ID', 'UF_CRM_1720601579', 'UF_CRM_1721656888'
				],
				'start' => $start
			]
		]);
		$crm = json_decode($res->getBody(), true);
		unset($res);

		if(!empty($crm['next'])) $next = $crm['next'];

		$batch_list = [];
		foreach ($crm['result'] as $value) {
			$count++;

			if(!empty($value['UF_CRM_1720601579']) && $value['UF_CRM_1721656888']) continue;

			$batch_list['crm_' . $value['ID']] = [
				'method' => 'crm.'. $type .'.update',
				'params' => [
					'id' => $value['ID'],
					'fields' => [
						'UF_CRM_1720601579' => $value['ID'],
						'UF_CRM_1721656888' => 'https://stopzaym.bitrix24.ru/crm/lead/details/' . $value['ID'] . '/'
					]
				]
			];
		}

		unset($crm['result']);
		print_r($crm);

		if(count($batch_list) > 0){
			$result = self::updateCrestCloudID($batch_list);

			unset($result);
		}

		unset($batch_list);
		unset($crm);

		print_r($count);

		if(!empty($next)){
			self::updateIdInCloud($type, $next, $count);
		} else {
			print_r($count);
		}
	}

	public static function updateDealIdInCloud($type, $start = 0, $count = 0){
		$client = new Client();
		$res = $client->get(env('CLOUD') . 'crm.'. $type .'.list',
			[
			'query' => [
				'select' => [
					'ID', 'UF_CRM_1720601636', 'UF_CRM_669F539037758'
				],
				'start' => $start
			]
		]);
		$crm = json_decode($res->getBody(), true);
		unset($res);

		if(!empty($crm['next'])) $next = $crm['next'];

		$batch_list = [];
		foreach ($crm['result'] as $value) {
			$count++;

			if(!empty($value['UF_CRM_1720601636']) && !empty($value['UF_CRM_669F539037758'])) continue;

			$batch_list['crm_' . $value['ID']] = [
				'method' => 'crm.'. $type .'.update',
				'params' => [
					'id' => $value['ID'],
					'fields' => [
						'UF_CRM_1720601636' => $value['ID'],
						'UF_CRM_669F539037758' => 'https://stopzaym.bitrix24.ru/crm/lead/details/' . $value['ID'] . '/'
					]
				]
			];
		}

		unset($crm['result']);
		print_r($crm);

		if(count($batch_list) > 0){
			$result = self::updateCrestCloudID($batch_list);

			unset($result);
		}

		unset($batch_list);
		unset($crm);

		print_r($count);

		if(!empty($next)){
			self::updateDealIdInCloud($type, $next, $count);
		} else {
			print_r($count);
		}
	}

	public static function updateContactIdInCloud($type, $start = 0, $count = 0){
		$client = new Client();
		$res = $client->get(env('CLOUD') . 'crm.'. $type .'.list',
			[
			'query' => [
				'select' => [
					'ID', 'UF_CRM_1720601597', 'UF_CRM_669F538FF3FBF'
				],
				'start' => $start
			]
		]);
		$crm = json_decode($res->getBody(), true);
		unset($res);

		if(!empty($crm['next'])) $next = $crm['next'];

		$batch_list = [];
		foreach ($crm['result'] as $value) {
			$count++;

			if(!empty($value['UF_CRM_1720601597']) && !empty($value['UF_CRM_669F538FF3FBF'])) continue;

			$batch_list['crm_' . $value['ID']] = [
				'method' => 'crm.'. $type .'.update',
				'params' => [
					'id' => $value['ID'],
					'fields' => [
						'UF_CRM_1720601597' => $value['ID'],
						'UF_CRM_669F538FF3FBF' => 'https://stopzaym.bitrix24.ru/crm/lead/details/' . $value['ID'] . '/'
					]
				]
			];
		}

		unset($crm['result']);
		print_r($crm);

		if(count($batch_list) > 0){
			$result = self::updateCrestCloudID($batch_list);

			unset($result);
		}

		unset($batch_list);
		unset($crm);

		print_r($count);

		if(!empty($next)){
			self::updateContactIdInCloud($type, $next, $count);
		} else {
			print_r($count);
		}
	}

	public static function getBoxElementId($type, $start = 0){
		self::lastTypeCounter($type, $start);

		switch ($type) {
			case 'deal':
				$field = 'UF_CRM_1720601636';
				break;
			case 'contact':
				$field = 'UF_CRM_1720601597';
				break;

			default:
				$field = 'UF_CRM_1720601579';
				break;
		}

		$client = new Client();
		$res = $client->get(env('BOX') . 'crm.'. $type .'.list',
			[
			'query' => [
				'select' => [
					'ID', $field
				],
				'start' => $start
			]
		]);
		$crm = json_decode($res->getBody(), true);
		unset($res);

		if(!empty($crm['next'])) $next = $crm['next'];

		//собрать id и заполнить базу
		$data = [];
		foreach ($crm['result'] as $value){
			$data[] = [
				'new_id' => $value['ID'],
				'old_id' => $value[$field]
			];
		}

		unset($crm['result']);
		print_r($crm);

		if(count($data) > 0){
			Capsule::table($type . 's')->insert($data);
		}
		unset($crm);

		if(!empty($next)){
			self::getBoxElementId($type, $next);
		} else {
			print_r('Действие завершено!');

			return true;
		}
	}

	public static function transferElements($type, $start = 0){
		$cloud_batch_list = [];

		for($i = 0; $i < 50; $i++){
			$cloud_batch_list['crm' . $i] = [
				'method' => 'crm.'. $type .'.list',
				'params' => [
					'select' => ['*', 'UF_*'],
					'fields' => [],
					'start' => $start
				]
			];

			$start += 50;
		}

		preprint($cloud_batch_list);

		$result = self::updateCrestCloud($cloud_batch_list);

		$elements = [];
		foreach($result['result']['result'] as $key => $value){
			foreach($value as $k => $v){
				//unset($v['ID']);
				$element = $v['ID'];

				$elements[] = $element;
			}
		}

		unset($result['result']['result']);
		preprint($result['result']);

		preprint($elements);

		$result_next = array_pop($result['result']['result_next']);
		if(!empty($result_next) && $result_next > 0){
			self::transferElements($type, $result_next);
		} else {
			print('Трансфер данных завершен!');
		}

		return true;
	}

	public static function transferElementsContact($start = 650){
		self::lastTypeCounter('contact_get_field_data', $start);
		print("Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID","BIRTHDATE", "POST", "PHONE", "ADDRESS", "ASSIGNED_BY_ID", "UF_CRM_5D53E5845A238", "UF_CRM_1663670733026", "UF_CRM_629F51D7AE750", "UF_CRM_629F51D7F1D30", "UF_CRM_629F51D85F1A7", "UF_CRM_629F51D834666", "UF_CRM_629F51D88AC70", "UF_CRM_62CD365DB2DED", "UF_CRM_62CD365D51F74", "UF_CRM_62CD365DECFE7", "UF_CRM_1669116983", "UF_CRM_62CF8EDE9F89D", "UF_CRM_1669198652", "UF_CRM_5D53E5846CE99", "UF_CRM_1624004832"],
			'start' => $start
		];

		$result = self::bxCloudCall('crm.contact.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		$elements = [];
		foreach($result['result'] as $key => $value){
			$element_id = self::getContactBoxId($value['ID']);
			if(!empty($element_id)){
				$value['ID'] = $element_id;

				$new_user_id = User::where('old_id', $value['ASSIGNED_BY_ID'])->value('new_id');
				if(empty($new_user_id)) $new_user_id = 1;

				$value['ASSIGNED_BY_ID'] = $new_user_id;
				if(!empty($value['UF_CRM_1624004832'])){
					$value['UF_CRM_1721830931'] = str_replace('stopzaym.bitrix24.ru', 'sz-crm.ru', $value['UF_CRM_1624004832']);
				}

				$elements[] = $value;
			}
		}

		$cloud_batch_list = [];
		for($i = 0; $i < count($elements); $i++){
			$cloud_batch_list['contact' . $i] = [
				'method' => 'crm.contact.update',
				'params' => $elements[$i]
			];
		}

		self::updateCrest($cloud_batch_list);

		if(!empty($next)){
			self::transferElementsContact($next);
		} else {
			print("Перезалив данных контактов завершен!\r\n");
			return true;
		}
	}

	public static function transferElementsDeal($start = 0){
		self::lastTypeCounter('deal_get_field_data', $start);
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["*","UF_*"],
			'start' => $start
		];

		$result = self::bxCloudCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		//print_r(array_pop($result['result']));

		$elements = [];
		$contacts = [];
		foreach($result['result'] as $key => $value){
			print("Текущая сделка: {$value['ID']}\r\n");

			$element_id = self::getDealBoxId($value['ID']);
			if(!empty($element_id)){
				if(!empty($value['CONTACT_ID'])){
					if(is_array($field_value)){
						$contact_id = Capsule::table('contacts')->where('old_id', $value['CONTACT_ID'][0])->value('new_id');
					} else {
						$contact_id = Capsule::table('contacts')->where('old_id', $value['CONTACT_ID'])->value('new_id');
					}
				}

				unset($value['CONTACT_ID']);

				foreach($value as $field_name => $field_value){
					if(empty($field_value)){
						unset($value[$field_name]);
						continue;
					}

					switch($field_name){
						case 'ID':
							$value['ID'] = $element_id;
							break;

						case 'ASSIGNED_BY_ID':
							$new_user_id = User::where('old_id', $value['ASSIGNED_BY_ID'])->value('new_id');
							if(empty($new_user_id)) $new_user_id = 1;
							$value['ASSIGNED_BY_ID'] = $new_user_id;
							break;

						case 'UF_CRM_1565691799':
							$value['UF_CRM_1722838734'] = str_replace('stopzaym.bitrix24.ru', 'sz-crm.ru', $value['UF_CRM_1565691799']);
							break;

						case 'LEAD_ID':
							$lead_id = Capsule::table('leads')->where('old_id', $field_value)->value('new_id');
							if(empty($lead_id)){
								$value['LEAD_ID'] = '';
							} else {
								$value['LEAD_ID'] = $lead_id;
							}
							break;

						case 'UF_CRM_1720601636':
							$deal_id = Capsule::table('deals')->where('old_id', $field_value)->value('new_id');
							if(empty($deal_id)){
								$value[$field_name] = '';
							} else {
								$value[$field_name] = $deal_id;
							}
							break;

						case 'SOURCE_ID':
							$source_id = Capsule::table('sources')->where('old_value', $field_value)->value('new_value');
							if(!empty($source_id)){
								$value[$field_name] = $source_id;
							}
							break;

						case 'STAGE_ID':
							$stage = Capsule::table('stages')->where('old_status_id', $field_value)->value('new_status_id');
							if(!empty($stage)){
								$value['STAGE_ID'] = $stage;
							} else {
								unset($value['STAGE_ID']);
							}
							break;

						case 'ASSIGNED_BY_ID':
						case 'CREATED_BY_ID':
						case 'MODIFY_BY_ID':
						case 'LAST_ACTIVITY_BY':
						case 'MOVED_BY_ID':
							$value[$field_name] = self::getBoxUserId($field_value);
							break;

						case 'UF_CRM_1720601597':
						case 'UF_CRM_669F538FF3FBF':
						case 'STAGE_ID':
						case 'CATEGORY_ID':
						case 'STAGE_SEMANTIC_ID':
						case 'LAST_ACTIVITY_BY':
						case 'LOCATION_ID':
						case 'UF_CRM_1720601636':
						case 'UF_CRM_60A39EE36DB80':
						case 'UF_CRM_6094E0E726214':
							unset($value[$field_name]);
							break;

						case 'UF_CRM_1656395923':
						case 'UF_CRM_1656395958':
						case 'UF_CRM_1656395994':
							$deal_id = Capsule::table('deals')->where('old_id', $field_value)->value('new_id');
							if(empty($deal_id)){
								$value[$field_name] = '';
							} else {
								$value[$field_name] = $deal_id;
							}
							break;

						default :
							if(empty($field_value)){
								unset($value[$field_name]);
							}
							break;
					}
				}

				unset($value['ID']);

				$value['UF_CRM_1721830990'] = 'https://sz-crm.ru/crm/deal/details/'. $element_id .'/';

				$deal_id = $element_id;

				if(!empty($contact_id)){
					$contacts[] = [
						'ID' => $deal_id,
						'fields' => ['CONTACT_ID' => $contact_id]
					];
				}

				$elements[] = [
					'ID' => $deal_id,
					'fields' => $value
				];
			}
		}

		unset($result);

		//print_r($elements);

		if(!empty($elements)){
			$cloud_batch_list = [];
			for($i = 0; $i < count($elements); $i++){
				$cloud_batch_list['deal' . $i] = [
					'method' => 'crm.deal.update',
					'params' => $elements[$i]
				];
			}

			self::updateCrest($cloud_batch_list);
			unset($elements);
			unset($cloud_batch_list);
		}

		if(!empty($contacts)){
			$contact_batch_list = [];
			for($i = 0; $i < count($contacts); $i++){
				$contact_batch_list['dealcontact' . $i] = [
					'method' => 'crm.deal.contact.add',
					'params' => $contacts[$i]
				];
			}

			self::updateCrest($contact_batch_list);
			unset($contacts);
			unset($contact_batch_list);
		}

		if(!empty($next)){
			self::transferElementsDeal($next);
		} else {
			print("Перезалив данных сделок завершен!\r\n");
			return true;
		}
	}

	public static function transferSource($type, $start = 0){
		if($type == 'cloud'){
			$result = self::bxCloudCall('crm.status.list', [
				'filter' => ['ENTITY_ID' => 'SOURCE'],
				'start' => $start
			]);
		} else {
			$result = self::bxBoxCall('crm.status.list', [
				'filter' => ['ENTITY_ID' => 'SOURCE'],
				'start' => $start
			]);
		}

		if(!empty($result['next'])) $next = $result['next'];

		foreach($result['result'] as $value){
			$name = Capsule::table('sources')->where('name', $value['NAME']);

			if($name->exists()){
				if($type == 'cloud'){
					Capsule::table('sources')->where('name', $value['NAME'])->update(['old_id' => $value['ID'], 'old_value' => $value['STATUS_ID']]);
				} else {
					Capsule::table('sources')->where('name', $value['NAME'])->update(['new_id' => $value['ID'], 'new_value' => $value['STATUS_ID']]);
				}
			} else {
				if($type == 'cloud'){
					Capsule::table('sources')->insert(['old_id' => $value['ID'], 'old_value' => $value['STATUS_ID'], 'name' => $value['NAME']]);
				} else {
					Capsule::table('sources')->insert(['new_id' => $value['ID'], 'new_value' => $value['STATUS_ID'], 'name' => $value['NAME']]);
				}
			}
		}

		print_r($result);

		if(!empty($next)){
			self::transferSource($type, $next);
		} else {
			print("Перезалив данных завершен!\r\n");
			return true;
		}
	}

	 public static function getCategoryStage($type){
        if($type == 'cloud'){
			$resultCategory = CrestCloud::call('crm.dealcategory.list', []);
		} else {
			$resultCategory = Crest::call('crm.dealcategory.list', []);
		}

		print_r($resultCategory);

        foreach ($resultCategory['result'] as $valueCategory) {
            if($type == 'cloud'){
				$resultStatus = self::bxCloudCall('crm.status.list', [
					'filter' => ['ENTITY_ID' => 'DEAL_STAGE_' . $valueCategory['ID']]
				]);

				$insert = [];
				foreach ($resultStatus['result'] as $valueStatus){
					$insert[] = [
						'old_id' => $valueStatus['ID'],
						'old_category_id' => $valueCategory['ID'],
						'old_category_name' => $valueCategory['NAME'],
	                    'name' => $valueStatus['NAME'],
	                    'old_status_id' => $valueStatus['STATUS_ID']
					];
				}

				Capsule::table('stages')->insert($insert);
			} else {
				$resultStatus = self::bxBoxCall('crm.status.list', [
					'filter' => ['ENTITY_ID' => 'DEAL_STAGE_' . $valueCategory['ID']]
				]);

				foreach ($resultStatus['result'] as $valueStatus){
					$update = [
						'new_id' => $valueStatus['ID'],
						'new_category_id' => $valueCategory['ID'],
						'new_category_name' => $valueCategory['NAME'],
						'new_status_id' => $valueStatus['STATUS_ID']
					];

					Capsule::table('stages')->where('old_category_name', $valueCategory['NAME'])->where('name', $valueStatus['NAME'])->update($update);
				}
			}
        }
    }

	private static function getContactBoxId($cloud_id){
		return Capsule::table('contacts')->where('old_id', $cloud_id)->value('new_id');
	}

	private static function getDealBoxId($cloud_id){
		return Capsule::table('deals')->where('old_id', $cloud_id)->value('new_id');
	}

	public static function updateCrestCloud($batch_list){
		$result = CrestCloud::callBatch($batch_list);
	    if (!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
	        sleep(1);
	        self::updateCrestCloud($batch_list);
	    }

	    return $result;
	}

	public static function updateCrest($batch_list){
		$result = Crest::callBatch($batch_list);
	    if (!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
	        sleep(1);
	        self::updateCrest($batch_list);
	    }

	    return $result;
	}

	public static function bxCloudCall($method, $data){
		$result = CrestCloud::call($method, $data);

		if(!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
			sleep(1);
			self::bxCloudCall($method, $data);
		} else {
			return $result;
		}
	}

	public static function bxBoxCall($method, $data){
		$result = Crest::call($method, $data);

		if(!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
			sleep(1);
			self::bxBoxCall($method, $data);
		} else {
			return $result;
		}
	}

	public static function lastTypeCounter($type, $start, $count = false){
		$data = [];
		$data['start'] = $start;
		if(!empty($count)) $data['count'] = $count;

		Capsule::table('counters')->where('type', $type)->update($data);
	}

	private static function getBoxUserId($old_user_id){
		$user_id = User::where('old_id', $old_user_id)->value('new_id');
		if(empty($user_id)) $user_id = 1;

		return $user_id;
	}
}