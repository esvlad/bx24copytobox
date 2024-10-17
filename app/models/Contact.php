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
			'filter' => ['>DATE_CREATE' => '2024-10-13'],
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

		print_r($contacts_data);

		/*foreach($contacts_data as $contact){
			if(!self::where('old_id', $contact['old_id'])->where('new_id', $contact['new_id'])->exists()){
				self::insert($contact);
			}
		}*/

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
			'filter' => ['>DATE_CREATE' => '2024-10-13'],
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
		$cloud_contact_result = Crm::bxCloudCall('crm.contact.get', ['ID' => $cloud_contact_id]);

		$cloud_contact = $cloud_contact_result['result'];
		foreach($cloud_contact as $field_name => $field_value){
			switch($field_name){
				case 'ID':
					$cloud_contact['UF_CRM_1720601597'] = $field_value;
					break;
				case 'ASSIGNED_BY_ID':
					$new_user_id = Capsule::table('users_transfer')->where('old_id', $field_value)->value('new_id');
					if(empty($new_user_id)) $new_user_id = 1;
					$cloud_contact['ASSIGNED_BY_ID'] = $new_user_id;
					break;
				case 'UF_CRM_1721830931':
					if(!empty($cloud_contact['UF_CRM_1624004832'])){//UF_CRM_1722586545 UF_CRM_1624004832
						$cloud_contact['UF_CRM_1721830931'] = str_replace('stopzaym.bitrix24.ru/docs/path', 'sz-crm.ru/docs/shared/path', $cloud_contact['UF_CRM_1624004832']);
					}
				case 'UF_CRM_5D53E5845A238':
					$cloud_contact['UF_CRM_5D53E5845A238'] = Crm::getFieldListData($field_name, $cloud_contact['UF_CRM_5D53E5845A238']);
					break;
				default :
					$hasFieldList = Crm::hasFieldList($field_name);
					if($hasFieldList === true){
						$cloud_contact[$field_name] = Crm::getFieldListData($field_name, $field_value);
					} else {
						$cloud_contact[$field_name] = $field_value;
					}
					break;
			}
		}

		$box_contact_id = Crm::bxBoxCall('crm.contact.add', ['fields' => $cloud_contact]);
		if(!self::where('old_id', $cloud_contact_id)->where('new_id', $box_contact_id['result'])->exists()){
			self::insert(['old_id' => $cloud_contact_id, 'new_id' => $box_contact_id['result']]);
		}

		return $box_contact_id['result'];
	}
}