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
			'filter' => ['>DATE_CREATE' => '2024-12-05'],
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

		//print_r($deals_data);
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
			'filter' => [">DATE_CREATE" => "2024-10-29"],
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

	public static function hasContactIdByDeal($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID", "CONTACT_ID", "UF_CRM_1720601636"],
			'order' => ["DATE_CREATE" => "DESC"],
			'start' => $start
		];

		$result = Crm::bxBoxCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		foreach($result['result'] as $deal){
			$deal_id = $deal['ID'];
			$hasDealCloud = self::where('new_id', $deal_id);

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

			if(!empty($deal['CONTACT_ID']) && !empty($deal['UF_CRM_1720601636'])){
				$contact = Crm::bxBoxCall('crm.contact.get', ['ID' => $deal['CONTACT_ID']]);

				if(empty($contact['result'])){
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
		}

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::hasContactIdByDeal($next);
	}

	public static function hasContactIdsByDeal($start = 0){ //9800
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID"],
			'order' => ["CONTACT_IDS" => "DESC"],
			'start' => $start
		];

		$result = Crm::bxBoxCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];
		foreach($result['result'] as $deal){
			$deal_id = $deal['ID'];

			$contacts = Crm::bxBoxCall('crm.deal.contact.items.get', ['ID' => $deal_id]);

			if(!empty($contacts['result']) && count($contacts['result']) > 1){
				$delete_contacts = false;
				$contact_id = false;
				foreach($contacts['result'] as $contact){
					$hasContact = Crm::bxBoxCall('crm.contact.get', ['ID' => $contact['CONTACT_ID']]);

					if(!empty($hasContact['result'])){
						$contact_id = $contact['CONTACT_ID'];
					} else {
						$delete_contacts = true;
					}
				}

				if($delete_contacts === true){
					Crm::bxBoxCall('crm.deal.contact.items.delete', ['ID' => $deal_id]);
					Crm::bxBoxCall('crm.deal.contact.add', ['ID' => $deal_id, 'fields' => ['CONTACT_ID' => $contact_id]]);
				}
			}
		}

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::hasContactIdsByDeal($next);
	}

	public static function hasBoxFolderClient($start = 25000){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID", "CONTACT_ID", "UF_CRM_1720601636", "UF_CRM_1722838734", "UF_CRM_1565691799"], //UF_CRM_1722838734 коробка //UF_CRM_1565691799 облако
			'order' => ["DATE_CREATE" => "DESC"],
			'start' => $start
		];

		$result = Crm::bxBoxCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		$editDeals = [];
		foreach($result['result'] as $deal){
			if(!empty($deal['UF_CRM_1722838734'])){
				$pos = strpos($deal['UF_CRM_1722838734'], 'docs/path/Kliyenty');
				if($pos !== false){
					$folderUri = self::hasFolderDisk($deal['UF_CRM_1722838734']);

					$editDeals[] = [
						'ID' => $deal['ID'],
						'UF_CRM_1722838734' => $folderUri
					];
				}
			} elseif (empty($deal['UF_CRM_1722838734']) && !empty($deal['UF_CRM_1565691799'])) {
				$folderUri = self::hasFolderDisk($deal['UF_CRM_1565691799']);

				$editDeals[] = [
					'ID' => $deal['ID'],
					'UF_CRM_1722838734' => $folderUri
				];
			} elseif(empty($deal['UF_CRM_1722838734']) && empty($deal['UF_CRM_1565691799']) && !empty($deal['CONTACT_ID'])){
				$contact = Crm::bxBoxCall('crm.contact.get', ['ID' => $deal['CONTACT_ID']]);

				if(!empty($contact['resilt']) && !empty($contact['resilt']['UF_CRM_1722586545'])){
					$folderUri = self::hasFolderDisk($contact['resilt']['UF_CRM_1722586545']);

					$pos = strpos($contact['resilt']['UF_CRM_1722586545'], 'crm.ru/docs/path/Kliyenty');
					if($pos !== false){
						Crm::bxBoxCall('crm.contact.update', ['ID' => $deal['CONTACT_ID'], 'fields' => ['UF_CRM_1722586545' => $folderUri]]);
					}

					$editDeals[] = [
						'ID' => $deal['ID'],
						'UF_CRM_1722838734' => $folderUri
					];
				}

				if(!empty($contact['resilt']) && empty($contact['resilt']['UF_CRM_1722586545']) && !empty($contact['resilt']['UF_CRM_1624004832'])){
					$folderUri = self::hasFolderDisk($contact['resilt']['UF_CRM_1624004832']);

					Crm::bxBoxCall('crm.contact.update', ['ID' => $deal['CONTACT_ID'], 'fields' => ['UF_CRM_1722586545' => $folderUri]]);

					$editDeals[] = [
						'ID' => $deal['ID'],
						'UF_CRM_1722838734' => $folderUri
					];
				}

				if(!empty($contact['resilt']) && empty($contact['resilt']['UF_CRM_1722586545']) && empty($contact['resilt']['UF_CRM_1624004832'])){
					Crm::bxBoxCall('bizproc.workflow.start', [
						'TEMPLATE_ID' => 246,
						'DOCUMENT_ID' => ['crm', 'CCrmDocumentDeal', 'DEAL_' . $deal['ID']],
						'PARAMETERS' => null
					]);
				}
			}
		}

		unset($result);

		if(!empty($editDeals)){
			$box_batch_list = [];
			foreach($editDeals as $update_deal){
				$box_batch_list[] = [
					'method' => 'crm.deal.update',
					'params' => [
						'ID' => $update_deal['ID'],
						'fields' => [
							'UF_CRM_1722838734' => $update_deal['UF_CRM_1722838734']
						]
					]
				];
			}

			Crm::bxBoxCallBatch($box_batch_list);

			unset($editDeals);
			unset($box_batch_list);
		}

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::hasBoxFolderClient($next);
	}

	public static function hasFolderDisk($editFolderUri){
		$editFolder = urldecode($editFolderUri);
		$editFolderName = explode('/', $editFolder);
		$editFolderName = end($editFolderName);

		$hasCloudDisk = Crm::bxBoxCall('disk.folder.getchildren', [
            'id' => 403,
            'filter' => [
                'NAME' => $editFolderName //Вытянуть имя папки
            ]
        ]);

        //print_r($hasCloudDisk);

        if(!empty($hasCloudDisk['result']) && $hasCloudDisk['total'] == 1){
        	$folderUri = $hasCloudDisk['result'][0]['DETAIL_URL'];
        } else {
        	$addFolderUri = Crm::bxBoxCall('disk.folder.addsubfolder', [
	            'id' => 403,
	            'data' => [
	                'NAME' => $editFolderName //Вытянуть имя папки
	            ]
	        ]);

        	if(!empty($addFolderUri['result'])){
        		$folderUri = $addFolderUri['result']['DETAIL_URL'];
        	}
        }

        return $folderUri;
	}

	public static function hasDealsAuthenticityСheck($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID", "TITLE", "CATEGORY_ID", "STAGE_ID", "ASSIGNED_BY_ID", "UF_CRM_1720601636"],
			'order' => ["DATE_CREATE" => "DESC"],
			'start' => $start
		];

		$result = Crm::bxBoxCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		if(!empty($box_deal['UF_CRM_1720601636'])){
			$data = [];
			foreach($result['result'] as $deal_box){
				$update = [];
				$result_cloud = Crm::bxCloudCall('crm.deal.get', ['ID' => $deal_box['UF_CRM_1720601636']]);
				$deal_cloud = self::handlerFields($result_cloud['result']);

				if($deal_cloud['ASSIGNED_BY_ID'] != 1 && $deal_cloud['ASSIGNED_BY_ID'] != $deal_box['ASSIGNED_BY_ID']){
					$update['assegned_id'] = $deal_cloud['ASSIGNED_BY_ID'];
				}

				if($deal_box['TITLE'] != $deal_cloud['TITLE']){
					$update['title'] = $deal_cloud['TITLE'];
				}

				if($deal_box['CATEGORY_ID'] != $deal_cloud['CATEGORY_ID']){
					$update['category_id'] = $deal_cloud['CATEGORY_ID'];
					$update['stage'] = $deal_cloud['STAGE_ID'];
				}

				if(!empty($update)) $update['deal_id'] = $deal_box['ID'];

				$data[] = $update;
			}
			unset($result);

			if(!empty($data)){
				$box_batch_list = [];
				foreach($data as $value){
					$deal_id = $value['deal_id'];
					unset($value['deal_id']);

					$box_batch_list[] = [
						'method' => 'bizproc.workflow.start',
						'params' => [
							'TEMPLATE_ID' => 208,
							'DOCUMENT_ID' => ['crm', 'CCrmDocumentDeal', 'DEAL_' . $deal_id],
							'PARAMETERS' => $value
						]
					];
				}

				Crm::bxBoxCallBatch($box_batch_list);
			}
		}

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}

		self::hasDealsAuthenticityСheck($next);
	}

	public static function transferElementsDeal($start = 0){
		//self::lastTypeCounter('deal_get_field_data', $start);
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["*","UF_*"],
			'filter' => [">DATE_CREATE" => "2024-12-01"],
			'start' => $start
		];

		$result = Crm::bxCloudCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		$elements = [];
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
							$value[$field_name] = $source_id;
							break;

						case 'STAGE_ID':
							$stage = Capsule::table('stages')->where('old_status_id', $field_value)->value('new_status_id');
							$value['STAGE_ID'] = $stage;
							break;

						case 'CATEGORY_ID':
							$category = Capsule::table('stages')->where('old_category_id', $field_value)->value('new_category_id');
							$value['CATEGORY_ID'] = $category;
							break;

						case 'CREATED_BY_ID':
						case 'MODIFY_BY_ID':
						case 'LAST_ACTIVITY_BY':
						case 'MOVED_BY_ID':
							$value[$field_name] = Crm::getBoxUserId($field_value);
							break;

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

		print_r($elements);

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
			'filter' => [">DATE_CREATE" => "2024-10-29"],
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

	public static function tranferListDeals($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["*", "UF_*"],
			'filter' => [">DATE_MODIFY" => "2024-12-05"],//, "<DATE_CREATE" => "2024-12-03"
			'order' => ["DATE_CREATE" => "DESC"],
			'start' => $start
		];

		$result = Crm::bxCloudCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		$elements = [];
		foreach($result['result'] as $key => $value){
			$deal_cloud_id = $value['ID'];
			print("Текущая сделка: {$deal_cloud_id}\r\n");
			$fields = self::handlerFields($value);
			unset($fields['ID']);

			$deal_box_id = self::where('old_id', $deal_cloud_id)->value('new_id');
			//$deal_box_has_query = Crm::bxBoxCall('crm.deal.get', ['ID' => $deal_box_id_query['new_id']]);

			if(!empty($deal_box_id)){
				$params = [
					'ID' => $deal_box_id,
					'fields' => $fields
				];

				$result = Crm::bxBoxCall('crm.deal.update', $params);

				/*if(!empty($value['CONTACT_ID'])){
					self::hasCloudDealIsContact($deal_box_id, $deal_cloud_id, $value['CONTACT_ID']);
				}*/
			} else {
				$method = 'crm.deal.add';

				$result = Crm::bxBoxCall('crm.deal.add', ['fields' => $fields]);

				if(!empty($value['CONTACT_ID'])){
					self::hasCloudDealIsContact($result['result'], $deal_cloud_id, $value['CONTACT_ID']);
				}
			}
			//die();
		}

		unset($result);

		if(empty($next)){
			print("Трансфер завершен!");
			return true;
		}


		self::tranferListDeals($next);
	}

	public static function handlerFields($value = []){
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
					$value[$field_name] = $source_id;
					break;

				case 'STAGE_ID':
					$stage = Capsule::table('stages')->where('old_status_id', $field_value)->value('new_status_id');
					$value['STAGE_ID'] = $stage;
					break;

				case 'CATEGORY_ID':
					$category = Capsule::table('stages')->where('old_category_id', $field_value)->value('new_category_id');
					$value['CATEGORY_ID'] = $category;
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

				case 'CONTACT_ID':
					$value['CONTACT_ID'] = Contact::where('old_id', $field_value)->value('new_id');
					break;

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

			$value['UF_CRM_1720601636'] = $value['ID'];
		}

		return $value;
	}

	public static function hasCloudDealIsContact($deal_box_id, $deal_cloud_id, $contact_cloud_id){
		$deal_box_query = Crm::bxBoxCall('crm.deal.get', ['ID' => $deal_box_id]);

		$contact_box_id = Contact::where('old_id', $contact_cloud_id)->value('new_id');
		if(empty($deal_box_query['result']['CONTACT_ID'])){
			if(empty($contact_box_id)){
				$contact_box_id = Contact::setContactBox($contact_cloud_id);
			}

			Crm::bxBoxCall('crm.deal.contact.add', ['ID' => $deal_box_id, 'fields' => ['CONTACT_ID' => $contact_box_id]]);
		}

		//Проверим габлюдателей
		if(empty($deal_box_query['result']['UF_CRM_1728898409'])){
			Crm::bxCloudCall('bizproc.workflow.start', [
				'TEMPLATE_ID' => 1187,
				'DOCUMENT_ID' => ['crm', 'CCrmDocumentDeal', 'DEAL_' . $deal_cloud_id],
				'PARAMETERS' => null
			]);
		}
	}

	public static function removeDuplicates(){
		//$result = Capsule::raw('SELECT `old_id`, COUNT(`old_id`) AS `count` FROM `deals` GROUP BY `old_id` HAVING `count` > 1')->get();
		$result = Capsule::table('deals')->selectRaw('old_id, COUNT(`old_id`) AS count')->groupBy('old_id')->having('count', '>', 1)->get();

		foreach($result as $deal){
			$deals_query = self::where('old_id', $deal->old_id)->offset(1)->limit(50);
			$delete_deals = $deals_query;
			$deals = $deals_query->get();

			foreach($deals as $deal_box){
				if($deal_box->old_id != $deal_box->new_id){
					Crm::bxBoxCall('crm.deal.delete', ['ID' => $deal_box->new_id]);
				}
			}

			$delete_deals->delete();
		}

		print('result');
	}

	public static function setObservers($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		$deals = Crm::bxBoxCall('crm.deal.list', [
			'select' => ['ID', 'UF_CRM_1720601636'],
			'start' => $start
		]);

		if(!empty($deals['next'])) $next = $deals['next'];

		$batch = [];
		foreach($deals['result'] as $deal){
			if(self::where('old_id', $deal['UF_CRM_1720601636'])->where('new_id', $deal['ID'])->exists()){
				$batch[] = [
					'method' => 'bizproc.workflow.start',
					'params' => [
						'TEMPLATE_ID' => 1187,
						'DOCUMENT_ID' => ['crm', 'CCrmDocumentDeal', 'DEAL_' . $deal['UF_CRM_1720601636']],
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

	public static function exportDate(){
		$file = fopen(dirname(dirname(__DIR__)) . '/uploads/exportdate/DEAL.csv', 'r');

		$rows = [];
		$i = 0;
		while (($row = fgetcsv($file, 1000, ';')) !== false){
			if($i > 30000){
				$ENTITY_ID = self::where('old_id', $row[0])->value('new_id');

				if(!empty($ENTITY_ID)){
					$setDB = [
						'ENTITY_ID' => $ENTITY_ID,
						'DATE_CREATE' => date('Y-m-d H:i:s', strtotime($row[1])),
						'DATE_MODIFY' => date('Y-m-d H:i:s', strtotime($row[2]))
					];

					$rows[] = $setDB;
					print_r($setDB);
				}
			}

			if($i >= 45000) break;
			$i++;
		}

		Capsule::table('esv_exportdate_deal')->insert($rows);

		//print_r($rows);

		fclose($file);
	}

	public static function replacement($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$count = Capsule::table('finapp_deals')->count();
		$deals = Capsule::table('finapp_deals')->offset($start)->limit(1000)->get();

		if($start < $count) $next = $start + 1000;

		foreach($deals as $deal){
			$update = [];

			if(!empty($deal->deal_sale_id)){
				$deal_sale_id = self::where('old_id', $deal->deal_sale_id)->whereNotNull('new_id');
				if($deal_sale_id->exists()){
					$update['deal_sale_id'] = $deal_sale_id->value('new_id');
				}
			}

			if(!empty($deal->deal_procedure_id)){
				$deal_procedure_id = self::where('old_id', $deal->deal_procedure_id)->whereNotNull('new_id');
				if($deal_procedure_id->exists()){
					$update['deal_procedure_id'] = $deal_procedure_id->value('new_id');
				}
			}

			if(!empty($deal->deal_uchet_id)){
				$deal_uchet_id = self::where('old_id', $deal->deal_uchet_id)->whereNotNull('new_id');
				if($deal_uchet_id->exists()){
					$update['deal_uchet_id'] = $deal_uchet_id->value('new_id');
				}
			}

			if(!empty($deal->deal_procau_id)){
				$deal_procau_id = self::where('old_id', $deal->deal_procau_id)->whereNotNull('new_id');
				if($deal_procau_id->exists()){
					$update['deal_procau_id'] = $deal_procau_id->value('new_id');
				}
			}

			if(!empty($deal->deal_military_id)){
				$deal_military_id = self::where('old_id', $deal->deal_military_id)->whereNotNull('new_id');
				if($deal_military_id->exists()){
					$update['deal_military_id'] = $deal_military_id->value('new_id');
				}
			}

			if(!empty($update)){
				Capsule::table('finapp_deals')->where('id', $deal->id)->update($update);
			}

			unset($update);
		}

		if(empty($next)){
			print("Изменение завершено\r\n");
			return true;
		}

		self::replacement($next);
	}

	public static function replacementDataBase(){
		$clients = Capsule::table('clients')->get();
		foreach($clients as $client){
			$contact = Capsule::table('contacts')->where('old_id', $client->contact_id)->whereNotNull('new_id');
			if($contact->exists()){
				Capsule::table('clients')->where('id', $client->id)->update(['contact_id' => $contact->value('new_id')]);
			}
		}
		unset($clients);
		print("Замена таблицы clients завершена\r\n");
		sleep(2);

		$relations = Capsule::table('relations')->get();
		foreach($relations as $relation){
			$deal = Capsule::table('deals')->where('old_id', $relation->deal_id)->whereNotNull('new_id');
			$contact = Capsule::table('contacts')->where('old_id', $relation->contact_id)->whereNotNull('new_id');

			if($deal->exists() && $contact->exists()){
				Capsule::table('relations')->where('id', $relation->id)->update([
					'deal_id' => $deal->value('new_id'),
					'contact_id' => $contact->value('new_id')
				]);
			}
		}
		unset($relations);
		print("Замена таблицы relations завершена\r\n");
		sleep(2);

		$documents = Capsule::table('documents')->get();
		foreach($documents as $document){
			$deal = Capsule::table('deals')->where('old_id', $document->deal_id)->whereNotNull('new_id');

			if($deal->exists()){
				Capsule::table('documents')->where('id', $document->id)->update([
					'deal_id' => $deal->value('new_id')
				]);
			}
		}
		unset($documents);
		print("Замена таблицы documents завершена\r\n");

		return true;
	}

	public static function replacementDealIdInFields($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ["ID", "UF_CRM_1656395923", "UF_CRM_1656395994", "UF_CRM_1656395958", "UF_CRM_1698743447"],
			'order' => ["DATE_CREATE" => "DESC"],
			'start' => $start
		];

		$result = Crm::bxBoxCall('crm.deal.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		if(!empty($result['result'])){
			$box_batch_list = [];
			foreach($result['result'] as $deal){
				$update = [];

				if(!empty($deal['UF_CRM_1656395923'])){
					$UF_CRM_1656395923 = self::where('old_id', $deal['UF_CRM_1656395923'])->whereNotNull('new_id');
					if($UF_CRM_1656395923->exists()){
						$update['UF_CRM_1656395923'] = $UF_CRM_1656395923->value('new_id');
					} else {
						$update['UF_CRM_1656395923'] = '';
					}
				}

				if(!empty($deal['UF_CRM_1656395994'])){
					$UF_CRM_1656395994 = self::where('old_id', $deal['UF_CRM_1656395994'])->whereNotNull('new_id');
					if($UF_CRM_1656395994->exists()){
						$update['UF_CRM_1656395994'] = $UF_CRM_1656395994->value('new_id');
					} else {
						$update['UF_CRM_1656395994'] = '';
					}
				}

				if(!empty($deal['UF_CRM_1656395958'])){
					$UF_CRM_1656395958 = self::where('old_id', $deal['UF_CRM_1656395958'])->whereNotNull('new_id');
					if($UF_CRM_1656395958->exists()){
						$update['UF_CRM_1656395958'] = $UF_CRM_1656395958->value('new_id');
					} else {
						$update['UF_CRM_1656395958'] = '';
					}
				}

				if(!empty($deal['UF_CRM_1698743447'])){
					$UF_CRM_1698743447 = self::where('old_id', $deal['UF_CRM_1698743447'])->whereNotNull('new_id');
					if($UF_CRM_1698743447->exists()){
						$update['UF_CRM_1698743447'] = $UF_CRM_1698743447->value('new_id');
					} else {
						$update['UF_CRM_1698743447'] = '';
					}
				}

				if(!empty($update)){
					$box_batch_list[] = [
						'method' => 'crm.deal.update',
						'params' => [
							'ID' => $deal['ID'],
							'fields' => $update
						]
					];
				}
			}

			//print_r($box_batch_list);
			Crm::bxBoxCallBatch($box_batch_list);
		}

		if(empty($next)){
			print("Замена значений завершена!\r\n");
			return true;
		}

		self::replacementDealIdInFields($next);
	}

	public static function setBoxDealBiz($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - {$start}\r\n");

		$count = self::count();
		//$deals = self::offset($start)->limit(50)->get();

		$having = self::selectRaw('old_id, COUNT(old_id) as count')->groupBy('old_id')->having('count', '>', 1)->limit(100)->get();

		$deals = [];
		foreach($having as $deal){
			$deals[] = [
				'old_id' => $deal->old_id,
				'count' => $deal->count
			];
		}
		print_r($deals);

		/*if($start < $count) $next = $start + 50;

		$deals = [];
		$deals[] = (object)[
			'old_id' => 62382,
			'new_id' => 44335
		];

		if(!empty($deals)){
			$box_batch_list = [];
			foreach($deals as $deal){
				$box = Crm::bxBoxCall('crm.deal.get', ['ID' => $deal->new_id]);
				$cloud = Crm::bxCloudCall('crm.deal.get', ['ID' => $deal->old_id]);
				$params = [];

				print("Коробка\r\n");
				print_r(['title' => $box['result']['TITLE'], 'category_id' => $box['result']['CATEGORY_ID'], 'stage' => $box['result']['STAGE_ID'], 'assigned_by_id' => $box['result']['ASSIGNED_BY_ID']]);
				print("Облако\r\n");
				print_r(['title' => $cloud['result']['TITLE'], 'category_id' => $cloud['result']['CATEGORY_ID'], 'stage' => $cloud['result']['STAGE_ID'], 'assigned_by_id' => $cloud['result']['ASSIGNED_BY_ID']]);

				$title = $cloud['result']['TITLE'];
				$category = Capsule::table('stages')->where('old_category_id', $cloud['result']['CATEGORY_ID'])->value('new_category_id');
				$stage = Capsule::table('stages')->where('old_status_id', $cloud['result']['STAGE_ID'])->value('new_status_id');
				$assegned_id = Crm::getBoxUserId($cloud['result']['ASSIGNED_BY_ID']);

				if($box['result']['TITLE'] != $title){
					$params['title'] = $title;
				}

				if($box['result']['CATEGORY_ID'] != $category){
					$params['category_id'] = $category;
				}

				if($box['result']['STAGE_ID'] != $stage){
					$params['stage'] = $stage;
				}

				if($box['result']['ASSIGNED_BY_ID'] != $assegned_id){
					$params['assegned_id'] = 'user_' . $assegned_id;
				}

				$pp = [
					'title' => $title,
					'category_id' => $category,
					'stage' => $stage,
					'assigned_by_id' => $assegned_id,
				];

				print("Отформотированные данные из облака\r\n");
				print_r($pp);

				print("Данные для изменения\r\n");
				print_r($params);

				if(!empty($params)){
					$box_batch_list[] = [
						'method' => 'bizproc.workflow.start',
						'params' => [
							'TEMPLATE_ID' => 208,
							'DOCUMENT_ID' => ['crm', 'CCrmDocumentDeal', 'DEAL_' . $deal->new_id],
							'PARAMETERS'  => $params
						]
					];
				}
			}

			if(!empty($box_batch_list)){
				Crm::bxBoxCallBatch($box_batch_list);
			}
		}

		if(empty($next)){
			print("Изменение завершено\r\n");
			return true;
		}

		self::setBoxDealBiz($next);*/
	}

	public static function compareDealBoxID($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		#UF_CRM_1656395923 пр 26/29
		#UF_CRM_1656395958 пц 27/0
		#UF_CRM_1656395994 уч 28
		#UF_CRM_1698743447 ау 44

		$field = 'UF_CRM_1698743447';
		$category_id = 44;

		$deals = Crm::bxBoxCall('crm.deal.list', [
			'select' => ['ID', $field],
			'filter' => ['CATEGORY_ID' => $category_id],
			'start' => $start
		]);

		//if(!empty($deals['next'])) $next = $deals['next'];

		$batch = [];
		foreach($deals['result'] as $deal){
			$deal_id = $deal['ID'];

			if($deal[$field] == $deal_id){
				//print("Сделка - " . $deal_id . " : " . $deal[$field] . "\r\n");

				$field_data = $deal[$field];

				/*$batch[] = [
					'method' => 'crm.deal.update',
					'params' => [
						'ID' => $deal_id,
						'fields' => [$field => $deal_id],
						'params' => ['REGISTER_SONET_EVENT' => 'N', 'REGISTER_HISTORY_EVENT' => 'N']
					]
				];*/

				switch($category_id){
					case 26:
					case 29:
						$column = 'deal_sale_id';
						break;
					case 27:
					case 0:
						$column = 'deal_procedure_id';
						break;
					case 20:
						$column = 'deal_uchet_id';
						break;
					case 44:
						$column = 'deal_procau_id';
						break;
					default:
						$column = 'deal_sale_id';
						break;
				}

				print("Сделка - " . $deal_id . "; fin_deals column: " . $column . " field_data: " . $field_data . "\r\n");
				$has_deal_finuchet = Capsule::table('fin_deals')->where($column, $field_data);
				/*if($has_deal_finuchet->exists()){
					$has_deal_finuchet->update([$column => $deal['ID']]);
				}*/
			}
		}

		/*if(!empty($batch)){
			Crm::bxBoxCallBatch($batch);
			unset($batch);
			unset($deals);
		}*/

		if(empty($next)){
			print("Сопоставление ID сделок завершено!");
			return true;
		}

		self::compareDealBoxID($next);
	}
}