<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\Crm;
use Esvlad\Bx24copytobox\Models\User;

class Contact extends Model{
	protected $table = "contacts";

	public static function setContactsDB($start = 0){//16450
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		$contacts = Crm::bxBoxCall('crm.contact.list', [
			'select' => ['ID', 'UF_CRM_1720601597'],
			'filter' => ['>DATE_CREATE' => '2024-12-01'],
			'start' => $start
		]);

		if(!empty($contacts['next'])) $next = $contacts['next'];

		$contacts_data = [];
		foreach($contacts['result'] as $contact){
			if(!empty($contact['UF_CRM_1720601597'])){
				$contacts_data[] = [
					'old_id' => $contact['UF_CRM_1720601597'],
					'new_id' => $contact['ID']
				];
			}
		}

		//print_r($contacts_data);

		foreach($contacts_data as $contact){
			if(!self::where('old_id', $contact['old_id'])->where('new_id', $contact['new_id'])->exists()){
				self::insert($contact);
			}
		}

		//self::insert($contacts_data);

		if(empty($next)){
			print("Сбор завершен!");
			return true;
		}

		self::setContactsDB($next);
	}

	public static function updateCloudContactsToBox($start = 0){ //16650
		Crm::lastTypeCounter('contact_get_field_data', $start);
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID","BIRTHDATE", "POST", "PHONE", "ASSIGNED_BY_ID", "UF_CRM_5D53E5845A238", "UF_CRM_1663670733026", "UF_CRM_629F51D7AE750", "UF_CRM_629F51D7F1D30", "UF_CRM_629F51D85F1A7", "UF_CRM_629F51D834666", "UF_CRM_629F51D88AC70", "UF_CRM_62CD365DB2DED", "UF_CRM_62CD365D51F74", "UF_CRM_62CD365DECFE7", "UF_CRM_1669116983", "UF_CRM_62CF8EDE9F89D", "UF_CRM_1669198652", "UF_CRM_5D53E5846CE99", "UF_CRM_1624004832"],
			'filter' => ['>DATE_CREATE' => '2024-12-01'],
			'start' => $start
		];

		$result = Crm::bxCloudCall('crm.contact.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		$elements = [];
		foreach($result['result'] as $key => $value){
			$element_id = self::where('old_id', $value['ID'])->value('new_id');

			if(!empty($element_id)){
				foreach($value as $field_name => $field_value){
					switch($field_name){
						case 'ID':
							$value['ID'] = $element_id;
							break;
						case 'ASSIGNED_BY_ID':
							$new_user_id = Capsule::table('users_transfer')->where('old_id', $field_value)->value('new_id');
							if(empty($new_user_id)) $new_user_id = 1;
							$value['ASSIGNED_BY_ID'] = $new_user_id;
							break;
						case 'UF_CRM_1721830931':
							if(!empty($value['UF_CRM_1624004832'])){//UF_CRM_1722586545 UF_CRM_1624004832
								$value['UF_CRM_1721830931'] = str_replace('stopzaym.bitrix24.ru', 'sz-crm.ru', $value['UF_CRM_1624004832']);
							}
						case 'UF_CRM_5D53E5845A238':
							$value['UF_CRM_5D53E5845A238'] = Crm::getFieldListData($field_name, $value['UF_CRM_5D53E5845A238']);
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
				}

				$elements[] = $value;
			}
		}

		//print_r($elements);

		if(!empty($elements)){
			$cloud_batch_list = [];
			for($i = 0; $i < count($elements); $i++){
				$cloud_batch_list['contact' . $i] = [
					'method' => 'crm.contact.update',
					'params' => $elements[$i]
				];
			}

			Crm::bxBoxCallBatch($cloud_batch_list);
		}

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::updateCloudContactsToBox($next);
	}

	public static function setContactBox($cloud_contact_id){
		$cloud_contact = Crm::bxCloudCall('crm.contact.get', ['ID' => $cloud_contact_id]);

		$fields = Contact::handlerFields($cloud_contact['result']);
		unset($fields['ID']);

		$box_contact_id = Crm::bxBoxCall('crm.contact.add', ['fields' => $fields]);

		if(!self::where('old_id', $cloud_contact_id)->where('new_id', $box_contact_id['result'])->exists()){
			self::insert(['old_id' => $cloud_contact_id, 'new_id' => $box_contact_id['result']]);
		}

		return $box_contact_id['result'];
	}

	public static function setAddress($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$contact_count = self::where('id', '>', 16840)->where('address', 0)->orderBy('id', 'desc')->offset(0)->limit(50)->count();
		//$contacts = self::where('id', '>', 16840)->where('address', 0)->orderBy('id', 'desc')->offset(0)->limit(50)->get();

		if($start < $contact_count) $next = $start + 50;

		$cloud_batch_list = [];
		foreach($contacts as $contact){
			$cloud_batch_list[$contact['id']] = [
				'method' => 'crm.address.list',
				'params' => ['filter' => ['ENTITY_TYPE_ID' => 3, 'ENTITY_ID' => $contact['old_id']]]
			];


		}

		$address_query = Crm::bxCloudCallBatch($cloud_batch_list);
		unset($cloud_batch_list);

		if(!empty($address_query['result']['result'])){
			$box_batch_list = [];
			foreach($address_query['result']['result'] as $key => $value){
				if(!empty($value)){
					$contact_id = self::where('id', $key)->value('new_id');

					$address = [];
					foreach($value[0] as $k => $v){
						switch($k){
							case 'ENTITY_TYPE_ID':
								$address['ENTITY_TYPE_ID'] = 3;
								break;
							case 'ENTITY_ID':
								$address['ENTITY_ID'] = $contact_id;
								break;
							default :
								if(!empty($v)){
									$address[$k] = $v;
								}
								break;
						}
					}

					$box_batch_list[$key] = [
						'method' => 'crm.address.add',
						'params' => [
							'fields' => $address
						]
					];

					self::where('id', $key)->update(['address' => 1]);
				}
			}

			Crm::bxBoxCallBatch($box_batch_list);
		}

		if(empty($next)){
			print("Трансфер адресов завершен!");
			return true;
		}

		sleep(1);
		self::setAddress($next);
	}

	public static function setAddressContactToBox($cloud_contact_id, $box_contact_id){
		$address_query = Crm::bxCloudCall('crm.address.list', [
			'filter' => [
				'ENTITY_TYPE_ID' => 3,
				'ENTITY_ID' => $cloud_contact_id
			]
		]);

		if(!empty($address_query['result'])){
			$address_batch_list = [];
			foreach($address_query['result'] as $address_array){
				$address = [];
				foreach($address_array as $key => $value){
					switch($key){
						case 'ENTITY_TYPE_ID':
							$address['ENTITY_TYPE_ID'] = 3;
							break;
						case 'ENTITY_ID':
							$address['ENTITY_ID'] = $box_contact_id;
							break;
						default :
							if(!empty($v)){
								$address[$k] = $v;
							}
							break;
					}
				}

				$address_batch_list[] = [
					'method' => 'crm.address.add',
					'params' => ['fields' => $address]
				];
			}

			Crm::bxBoxCall('crm.address.delete', ['fields' => ['ENTITY_TYPE_ID' => 3, 'ENTITY_ID' => $box_contact_id]]);
			Crm::bxBoxCallBatch($address_batch_list);
		} else {
			return false;
		}

		return true;
	}

	public static function handlerFields($value = []){
		foreach($value as $field_name => $field_value){
			switch($field_name){
				case 'ASSIGNED_BY_ID':
					$new_user_id = Crm::getBoxUserId($value['ASSIGNED_BY_ID']);
					if(empty($new_user_id)) $new_user_id = 1;
					$value['ASSIGNED_BY_ID'] = $new_user_id;
					break;

				case 'UF_CRM_1720601597':
					$value[$field_name] = $value['ID'];
					break;

				case 'UF_CRM_1624004832': //Ссылка на папку Облака, добовляем для коробки
					if(!empty($field_value)){
						$value['UF_CRM_1722586545'] = str_replace('stopzaym.bitrix24.ru/docs/path', 'sz-crm.ru/docs/shared/path', $value['UF_CRM_1624004832']);
					}
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

			$value['UF_CRM_1721830931'] = 'https://stopzaym.bitrix24.ru/crm/contact/details/' . $value['ID'] . '/';
		}

		return $value;
	}

	public static function exportDate(){
		$file = fopen(dirname(dirname(__DIR__)) . '/uploads/exportdate/CONTACT.csv', 'r');

		$rows = [];
		while (($row = fgetcsv($file, 1000, ';')) !== false){
			$ENTITY_ID = self::where('old_id', $row[0])->value('new_id');

			if(!empty($ENTITY_ID)){
				$setDB = [
					'ENTITY_ID' => $ENTITY_ID,
					'DATE_CREATE' => date('Y-m-d H:i:s', strtotime($row[1])),
					'DATE_MODIFY' => date('Y-m-d H:i:s', strtotime($row[2]))
				];

				$rows[] = $setDB;
			}

			print_r($setDB);
		}

		Capsule::table('esv_exportdate_contact')->insert($rows);

		//print_r($rows);

		fclose($file);
	}

	public static function replacement($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$count = Capsule::table('fg3re_clients')->count();
		$clients = Capsule::table('fg3re_clients')->offset($start)->limit(1000)->get();

		if($start < $count) $next = $start + 1000;

		foreach($clients as $client){
			$contact = self::where('old_id', $client->contact_id)->whereNotNull('new_id');
			if($contact->exists()){
				$contact_id = $contact->value('new_id');
				Capsule::table('fg3re_clients')->where('contact_id', $client->contact_id)->update(['contact_id' => $contact_id]);
			}
		}

		if(empty($next)){
			print('Изменение завершено');
			return true;
		}

		self::replacement($next);
	}
}