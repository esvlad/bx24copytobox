<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\Crm;
use Esvlad\Bx24copytobox\Models\User;
use Esvlad\Bx24copytobox\Models\Contact;

class Deal extends Model{
	protected $table = "deals";

	public static function setDealsDB($start = 0){//28100
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		$deals = Crm::bxBoxCall('crm.deal.list', [
			'select' => ['ID', 'TITLE', 'UF_CRM_1720601636'],
			'filter' => ['>DATE_CREATE' => '2024-10-14'],
			'start' => $start
		]);

		if(!empty($deals['next'])) $next = $deals['next'];

		$deals_data = [];
		foreach($deals['result'] as $deal){
			if(!empty($deal['UF_CRM_1720601636'])){
				$deals_data[] = [
					'old_id' => $deal['UF_CRM_1720601636'],
					'new_id' => $deal['ID']
				];
			}
		}

		print_r($deals_data);
		foreach($deals_data as $deal){
			if(!self::where('old_id', $deal['old_id'])->where('new_id', $deal['new_id'])->exists()){
				self::insert($deal);
			}
		}

		//self::insert($deals_data);

		if(empty($next)){
			print("Сбор завершен!");
			return true;
		}

		self::setDealsDB($next);
	}

	public static function getContactIdByDeal($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID", "TITLE", "CONTACT_ID", "UF_CRM_1720601636"],
			'filter' => [">DATE_CREATE" => "2024-10-14"],
			'start' => $start
		];

		$result = Crm::bxBoxCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		$contacts = [];
		foreach($result['result'] as $deal){
			$deal_id = $deal['ID'];
			if(empty($deal['CONTACT_ID']) && !empty($deal['UF_CRM_1720601636'])){
			print(date('d.m.Y H:i:s') . " Сделка - " . $deal_id . "\r\n");
				$deal_cloud_result = Crm::bxCloudCall('crm.deal.get', ['ID' => $deal['UF_CRM_1720601636']]);
				$deal_cloud = $deal_cloud_result['result'];

				if(!empty($deal_cloud['CONTACT_ID'])){
					$hasContact = Contact::where('old_id', $deal_cloud['CONTACT_ID'])->whereNotNull('new_id');
					if(!$hasContact->exists()){
						$box_contact_id = Contact::setContactBox($deal_cloud['CONTACT_ID']);
					} else {
						$box_contact_id = Contact::where('old_id', $deal_cloud['CONTACT_ID'])->value('new_id');
					}

					Crm::bxBoxCall('crm.deal.contact.add', ['ID' => $deal_id, 'fields' => ['CONTACT_ID' => $box_contact_id]]);
				}
			}
		}

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::getContactIdByDeal($next);
	}

	public static function transferElementsDeal($start = 0){
		//self::lastTypeCounter('deal_get_field_data', $start);
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["*","UF_*"],
			'filter' => [">DATE_CREATE" => "2024-10-13"],
			'start' => $start
		];

		$result = Crm::bxCloudCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		$elements = [];
		$contacts = [];
		foreach($result['result'] as $key => $value){
			print("Текущая сделка: {$value['ID']}\r\n");

			$element_id = self::select('new_id')->where('old_id', $value['ID'])->first();
			if(!empty($element_id['new_id'])){

				foreach($value as $field_name => $field_value){
					switch($field_name){
						case 'ASSIGNED_BY_ID':
							$new_user_id = Crm::getBoxUserId($value['ASSIGNED_BY_ID']);
							if(empty($new_user_id)) $new_user_id = 1;
							$value['ASSIGNED_BY_ID'] = $new_user_id;
							break;

						case 'UF_CRM_1720601636':
							$value[$field_name] = $value['ID'];
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

						case 'CREATED_BY_ID':
						case 'MODIFY_BY_ID':
						case 'LAST_ACTIVITY_BY':
						case 'MOVED_BY_ID':
							$value[$field_name] = Crm::getBoxUserId($field_value);
							break;

						case 'STAGE_ID':
						case 'CATEGORY_ID':
						case 'STAGE_SEMANTIC_ID':
						case 'LAST_ACTIVITY_BY':
						case 'UF_CRM_1720601597':
						case 'UF_CRM_669F538FF3FBF':
						case 'LOCATION_ID':
						case 'UF_CRM_1720601636':
						case 'UF_CRM_60A39EE36DB80':
						case 'UF_CRM_6094E0E726214':
							unset($value[$field_name]);
							break;
						case 'UF_CRM_1721830990':
							$value[$field_name] = 'https://stopzaym.bitrix24.ru/crm/deal/details/' . $value['UF_CRM_1720601636'] . '/';
							break;
						case 'UF_CRM_1722838734':
							$value[$field_name] = str_replace('stopzaym.bitrix24.ru/docs/path', 'sz-crm.ru/docs/shared/path', $value['UF_CRM_1565691799']);
							break;
						default :
							if(empty($field_value)){
								unset($value[$field_name]);
							}

							$hasFieldList = Crm::hasFieldList($field_name);
							if($hasFieldList === true){
								$value[$field_name] = Crm::getFieldListData($field_name, $field_value);
							}
							break;
					}
				}

				$cloud_id = $value['ID'];
				unset($value['ID']);

				$elements[] = [
					'box_id' => $element_id['new_id'],
					'cloud_id' => $cloud_id,
					'fields' => $value
				];
			}
		}

		unset($result);

		//print_r($elements);

		if(!empty($elements)){
			$cloud_batch_list = [];
			for($i = 0; $i < count($elements); $i++){
				$cloud_batch_list['deal_' . $elements[$i]['cloud_id']] = [
					'method' => 'crm.deal.update',
					'params' => ['ID' => $elements[$i]['box_id'], 'fields' => $elements[$i]['fields']]
				];
			}

			$result = Crm::bxBoxCallBatch($cloud_batch_list);
			//print_r($result . "\r\n");
			unset($elements);
			unset($cloud_batch_list);
		}

		if(empty($next)){
			print("Перезалив данных сделок завершен!\r\n");
			return true;
		}

		self::transferElementsDeal($next);
	}

	public static function updateCloudDealsToBox($start = 0){ //28100
		//Crm::lastTypeCounter('deal_get_field_data', $start);
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID","CONTACT_ID"],
			'filter' => [">DATE_CREATE" => "2024-10-13"],
			'start' => $start
		];

		$result = Crm::bxCloudCall('crm.deal.list', $params);
		//print_r($result['result']);

		if(!empty($result['next'])) $next = $result['next'];

		$elements = [];
		$contacts = [];
		foreach($result['result'] as $key => $value){
			//print("Текущая сделка: {$value['ID']}\r\n");
			//print("Текущий контакт: {$value['CONTACT_ID']}\r\n");

			$element_id = self::select('new_id')->where('old_id', $value['ID'])->first();
			print("Текущий элемент: {$value['ID']} - {$element_id['new_id']}\r\n");
			if(!empty($element_id['new_id'])){
				print("Текущая сделка: {$element_id['new_id']}\r\n");
				if(!empty($value['CONTACT_ID'])){
					if(is_array($value['CONTACT_ID'])){
						$contact_id = Contact::where('old_id', $value['CONTACT_ID'][0])->value('new_id');
					} else {
						$contact_id = Contact::where('old_id', $value['CONTACT_ID'])->value('new_id');
					}
				}
				print("Текущий контакт: {$contact_id}\r\n");

				//unset($value['CONTACT_ID']);

				/*foreach($value as $field_name => $field_value){
					if(empty($field_value)){
						unset($value[$field_name]);
						continue;
					}

					switch($field_name){
						case 'ID':
							$value['ID'] = $element_id;
							break;

						case 'ASSIGNED_BY_ID':
							$new_user_id = Capsule::table('users_transfer')->where('old_id', $value['ASSIGNED_BY_ID'])->value('new_id');
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
							$value[$field_name] = Crm::getBoxUserId($field_value);
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

							$hasFieldList = Crm::hasFieldList($field_name);
							if($hasFieldList === true){
								$value[$field_name] = Crm::getFieldListData($field_name, $field_value);
							}
							break;
					}
				}*/

				//unset($value['ID']);

				//$value['UF_CRM_1721830990'] = 'https://sz-crm.ru/crm/deal/details/'. $element_id .'/';

				//$deal_id = $element_id;

				if(!empty($contact_id)){
					$contacts[] = [
						'ID' => $element_id['new_id'],
						'fields' => ['CONTACT_ID' => $contact_id]
					];
				}

				/*$elements[] = [
					'ID' => $deal_id,
					'fields' => $value
				];*/
			}
		}

		unset($result);

		//print_r($contacts);

		if(!empty($elements)){
			$cloud_batch_list = [];
			for($i = 0; $i < count($elements); $i++){
				$cloud_batch_list['deal' . $i] = [
					'method' => 'crm.deal.update',
					'params' => $elements[$i]
				];
			}

			Crm::bxBoxCallBatch($cloud_batch_list);
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

			Crm::bxBoxCallBatch($contact_batch_list);
			unset($contacts);
			unset($contact_batch_list);
		}

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::updateCloudDealsToBox($next);
	}

	public static function setDealToBox($cloud_id){
		$cloud = Crm::bxCloudCall('crm.deal.get', ['ID' => $cloud_id]);
		unset($cloud['result']['ID']);

		$box = $cloud['result'];

		$box['UF_CRM_1720601579'] = $cloud_id;

		$box['ASSIGNED_BY_ID'] = User::getBoxUserId($box['ASSIGNED_BY_ID']);
		$box['CREATED_BY_ID'] = User::getBoxUserId($box['CREATED_BY_ID']);
		$box['LAST_ACTIVITY_BY'] = User::getBoxUserId($box['LAST_ACTIVITY_BY']);

		$box['SOURCE_ID'] = self::getBoxSourceId($box['SOURCE_ID']); //источник
		$box['STATUS_ID'] = self::getBoxStatusId($box['STATUS_ID']); //статус

		if(!empty($box['UF_CRM_1647594109'])){
			$operators = [];
			for($i = 0; $i < count($box['UF_CRM_1647594109']); $i++){
				$operators[$i] = User::getBoxUserId($box['UF_CRM_1647594109'][$i]);
			}

			$box['UF_CRM_1647594109'] = $operators;
		}

		$box['UF_CRM_1571200098221'] = Crm::getFieldData('UF_CRM_1571200098221', $box['UF_CRM_1571200098221']); //услуга
		$box['UF_CRM_1650282793'] = Crm::getFieldData('UF_CRM_1650282793', $box['UF_CRM_1650282793']); //Подразделение
		$box['UF_CRM_1720601659'] = Crm::getFieldData('UF_CRM_1720601659', $box['UF_CRM_1720601659']); //Ссылка на облако

		unset($box['UF_CRM_1620044726']);
		unset($box['UF_CRM_1620131706']);
		unset($box['UF_CRM_1655891961']);

		$box_id = Crm::bxBoxCall('crm.lead.add', $box);
		Capsule::table('leads')->insert(['old_id' => $cloud_id, 'new_id' => $box_id]);

		return true;
	}
}