<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\Crm;
use Esvlad\Bx24copytobox\Models\User;

class Lead extends Model{
	protected $table = "leads";
	public $timestamps = false;

	public static function setLeadsDB($start = 0){//413100
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		$leads = Crm::bxBoxCall('crm.lead.list', [
			'select' => ['ID', 'UF_CRM_1720601579'],
			'filter' => [">DATE_CREATE" => "2024-12-0"],
			'start' => $start
		]);

		if(!empty($leads['next'])) $next = $leads['next'];

		$leads_data = [];
		foreach($leads['result'] as $lead){
			if(!empty($lead['UF_CRM_1720601579']))
			$leads_data[] = [
				'old_id' => $lead['UF_CRM_1720601579'],
				'new_id' => $lead['ID']
			];
		}

		//print_r($leads_data);
		foreach($leads_data as $lead){
			if(!self::where('old_id', $lead['old_id'])->where('new_id', $lead['new_id'])->exists()){
				self::insert($lead);
			}
		}

		//self::insert($leads_data);

		if(empty($next)){
			print("Сбор завершен!");
			return true;
		}

		self::setLeadsDB($next);
	}

	public static function setStatuses($type){
		$params = [
			'filter' => ['ENTITY_ID' => 'STATUS'],
			'order' => ['SORT' => 'ASC']
		];

		if($type == 'cloud'){
			$result_query = Crm::bxCloudCall('crm.status.list', $params);
		} else {
			$result_query = Crm::bxBoxCall('crm.status.list', $params);
		}

		if(!empty($result_query['result'])){
			$new_statuses = [];
			$update_statuses = [];
			foreach($result_query['result'] as $status){
				if($type == 'cloud'){
					$new_statuses[] = [
						'old_id' => $status['ID'],
						'name' => $status['NAME'],
						'old_status_id' => $status['STATUS_ID']
					];
				} else {
					$statuses = [
						'new_id' => $status['ID'],
						'new_status_id' => $status['STATUS_ID']
					];

					$update_statuses[] = $statuses;

					Capsule::table('lead_status')->where('name', $status['NAME'])->update($statuses);
				}
			}

			if(!empty($new_statuses)){
				Capsule::table('lead_status')->insert($new_statuses);

				print_r($new_statuses);
			}

			if(!empty($update_statuses)){
				print_r($update_statuses);
			}
		}

		return true;
	}

	public static function tranferListLeads($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["*", "PHONE", "UF_*"],
			'filter' => [">DATE_MODIFY" => "2024-12-02", "<DATE_MODIFY" => "2024-12-08"],
			'order' => ["DATE_CREATE" => "DESC"],
			'start' => $start
		];

		$result = Crm::bxCloudCall('crm.lead.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		$elements = [];
		foreach($result['result'] as $key => $value){
			$lead_cloud_id = $value['ID'];
			print("Текущий ID лида: {$lead_cloud_id}\r\n");
			$fields = self::handlerFields($value);
			unset($fields['ID']);

			$lead_box_id = self::where('old_id', $lead_cloud_id)->value('new_id');

			if(!empty($lead_box_id)){
				$params_lead = [
					'ID' => $lead_box_id,
					'fields' => $fields
				];

				$result = Crm::bxBoxCall('crm.lead.update', $params_lead);
			} else {
				$result = Crm::bxBoxCall('crm.lead.add', ['fields' => $fields]);
				self::insert([
					'old_id' => $lead_cloud_id,
					'new_id' => $result['result']
				]);
			}

			self::getBizProcObserver($lead_cloud_id);
		}

		unset($result);

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::tranferListLeads($next);
	}

	public static function updateLeadsBoxDB($start = 0){
		$count = self::whereNull('new_id')->count();
		$leads = self::whereNull('new_id')->offset($start)->limit(50)->get();
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		if($count > $start) $next = $start + 50;

		if(!empty($leads)){
			$batch = [];
			foreach($leads as $lead){
				$batch[$lead->id] = [
					'method' => 'crm.lead.add',
					'params' => [
						'fields' => json_decode($lead->fields_data, true)
					]
				];
			}

			//print_r($batch);
			$result = Crm::bxBoxCallBatch($batch);
			unset($batch);

			if(!empty($result['result']['result'])){
				$result_batch = $result['result']['result'];
				unset($result);
				//print_r($result_batch);
				foreach($result_batch as $key => $value){
					self::where('id', $key)->update(['new_id' => $value]);
				}
			}
		}

		if(empty($next)) return true;

		self::updateLeadsBoxDB($next);
	}

	public static function handlerFields($value = []){
		foreach($value as $field_name => $field_value){
			switch($field_name){
				case 'ASSIGNED_BY_ID':
					$new_user_id = Crm::getBoxUserId($value['ASSIGNED_BY_ID']);
					if(empty($new_user_id)) $new_user_id = 1;
					$value['ASSIGNED_BY_ID'] = $new_user_id;
					break;

				case 'PHONE':
					$value['PHONE'][0] = [
						'TYPE_ID' => $field_value[0]['TYPE_ID'],
						'VALUE' => $field_value[0]['VALUE'],
						'VALUE_TYPE' => $field_value[0]['VALUE_TYPE'],
					];
					break;

				case 'SOURCE_ID':
					$source_id = Capsule::table('sources')->where('old_value', $field_value)->value('new_value');
					$value[$field_name] = $source_id;
					break;

				case 'STATUS_ID':
					$status_id = Capsule::table('lead_status')->where('old_status_id', $field_value)->value('new_status_id');
					$value['STATUS_ID'] = $status_id;
					break;

				case 'CREATED_BY_ID':
				case 'MODIFY_BY_ID':
				case 'LAST_ACTIVITY_BY':
				case 'MOVED_BY_ID':
					$value[$field_name] = Crm::getBoxUserId($field_value);
					break;

				case 'DATE_CREATE':
				case 'DATE_MODIFY':
					$value[$field_name] = date('Y-m-d H:i:s', strtotime($field_value));
					break;

				default :
					$hasFieldList = Crm::hasFieldList($field_name);
					if($hasFieldList === true){
						$value[$field_name] = Crm::getFieldListData($field_name, $field_value);
					} else {
						$value[$field_name] = $field_value;
					}
					break;
			}

			if(empty($value[$field_name])){
				unset($value[$field_name]);
			}

			$value['UF_CRM_1720601579'] = $value['ID'];
			$value['UF_CRM_1721831023'] = 'https://stopzaym.bitrix24.ru/crm/lead/details/' . $value['ID'] . '/';
		}

		return $value;
	}

	public static function setObservers($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		$leads = Crm::bxCloudCall('crm.lead.list', [
			'select' => ['ID', 'UF_CRM_1720601579'],
			'filter' => [">DATE_CREATE" => "2024-11-19"],
			'start' => $start
		]);

		if(!empty($leads['next'])) $next = $leads['next'];

		$batch = [];
		foreach($leads['result'] as $lead){
			if(self::where('old_id', $lead['ID'])->whereNotNull('new_id')->exists()){
				$batch[] = [
					'method' => 'bizproc.workflow.start',
					'params' => [
						'TEMPLATE_ID' => 1193,
						'DOCUMENT_ID' => ['crm', 'CCrmDocumentLead', 'LEAD_' . $lead['ID']],
						'PARAMETERS' => null
					]
				];
			}
		}

		if(!empty($batch)){
			Crm::bxCloudCallBatch($batch);
		}

		if(empty($next)){
			print("Сбор завершен!");
			return true;
		}

		sleep(1);
		self::setObservers($next);
	}

	public static function getBizProcObserver($lead_id, $observer = []){
		if(!empty($observer)){
			Crm::bxCloudCall('bizproc.workflow.start', [
				'TEMPLATE_ID' => 1193,
				'DOCUMENT_ID' => ['crm', 'CCrmDocumentLead', 'LEAD_' . $lead_id],
				'PARAMETERS' => null
			]);
		}
	}

	public static function exportDate($start = 30000){//105000
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		$file = fopen(dirname(dirname(__DIR__)) . '/uploads/exportdate/LEAD_NOT_WORK.csv', 'r');

		$rows = [];
		$i = 0;
		$not_row = false;
		while (($row = fgetcsv($file, 1000, ';')) !== false){
			if(empty($row[0])){
				$not_row = true;
				break;
			}

			if($i > $start){ //90000
				$ENTITY_ID = self::where('old_id', $row[0])->value('new_id');

				if(!empty($ENTITY_ID)){
					$setDB = [
						'ENTITY_ID' => $ENTITY_ID,
						'DATE_CREATE' => date('Y-m-d H:i:s', strtotime($row[1])),
						'DATE_MODIFY' => date('Y-m-d H:i:s', strtotime($row[2]))
					];

					$rows[] = $setDB;
					print_r($ENTITY_ID . "\r\n");
				}
			}

			$next = ($start + 15000);

			if($i >= $next) break;
			$i++;
		}

		Capsule::table('esv_exportdate_lead')->insert($rows);

		//print_r($rows);

		fclose($file);

		sleep(5);
		if($not_row !== true) self::exportDate($next);
	}
}
