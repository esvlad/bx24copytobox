<?php

namespace Esvlad\Bx24copytobox\Controllers;

use Esvlad\Bx24copytobox\Models\Contact;
use Esvlad\Bx24copytobox\Models\Deal;
use Esvlad\Bx24copytobox\Models\Crm;

class DealsController{

	public function addtobox($cloud_id){
		header('Content-Type: application/json');
		$json = [];
		$deal_rest = Crm::bxCloudCall('crm.deal.get', ['ID' => $cloud_id]);

		$deal = $this->syncFileds($deal_rest['result']);
		unset($deal_rest);
		unset($deal['ID']);

		if(!Deal::where('old_id', $cloud_id)->exists()){
			$deal_box_rest = Crm::bxBoxCall('crn.deal.add', [
				'fields' => $deal
			]);
			$box_id = $deal_box_rest['result'];

			$contact_query = Contact::where('old_id', $deal['CONTACT_ID'])->first();
			if(!empty($contact_query->new_id)){
				$contact_id = $contact_query->new_id;
			} else {
				$contact_id = Contact::setContactBox($deal['CONTACT_ID']);
			}

			Crm::bxBoxCall('crm.deal.contact.add', [
				'ID' => $box_id,
				'fields' => ['CONTACT_ID' => $contact_id]
			]);

			Deal::insert(['old_id' => $cloud_id, 'new_id' => $box_id]);
		} else {
			$box_query = Deal::where('old_id', $cloud_id)->first();
			$box_id = $box_query->new_id;

			$deal_box_rest = Crm::bxBoxCall('crn.deal.update', [
				'ID' => $box_id,
				'fields' => $deal
			]);
		}

		$json['status'] = 'success';

		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}

	public function synchronizationDeal($box_id){
		header('Content-Type: application/json');
		$json = [];

		$deal_query = Deal::where('new_id', $box_id)->whereNotNull('old_id')->first();
		if(!empty($deal_query)){
			$deal_rest = Crm::bxCloudCall('crm.deal.get', ['ID' => $deal_query->old_id]);

			if(empty($deal_rest['result'])){
				$json = [
					'status' => 'error',
					'error_message' => 'Такой сделки в облаке нет.'
				];
			} else {
				$deal = $this->syncFileds($deal_rest['result']);
				unset($deal_rest);
				unset($deal['ID']);

				Crm::bxBoxCall('crn.deal.update', [
					'ID' => $box_id,
					'fields' => $deal
				]);

				$json['status'] = 'success';
			}
		} else {
			$json = [
				'status' => 'error',
				'error_message' => 'Облачного ID нет, вероятно это новая сделка.'
			];
		}

		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}

	public function synchronizationContact($box_id){
		header('Content-Type: application/json');
		$json = [];

		$deal_query = Deal::where('new_id', $box_id)->whereNotNull('old_id')->first();
		if(!empty($deal_query)){
			$deal_rest = Crm::bxCloudCall('crm.deal.get', ['ID' => $deal_query->old_id]);

			if(empty($deal_rest['result'])){
				$json = [
					'status' => 'error',
					'error_message' => 'Такой сделки в облаке нет.'
				];
			} else {
				$deal = $deal_rest['result'];
				unset($deal_rest);
				unset($deal['ID']);

				if(empty($deal['CONTACT_ID'])){
					$json = [
						'status' => 'error',
						'error_message' => 'К этой сделке в облаке не прикреплен контакт.'
					];
				} else {
					$contact_query = Contact::where('old_id', $deal['CONTACT_ID'])->first();
					if(!empty($contact_query->new_id)){
						$contact_id = $contact_query->new_id;
					} else {
						$contact_id = Contact::setContactBox($deal['CONTACT_ID']);
					}

					Crm::bxBoxCall('crm.deal.contact.add', [
						'ID' => $box_id,
						'fields' => ['CONTACT_ID' => $contact_id]
					]);

					$json['status'] = 'success';
				}
			}
		} else {
			$json = [
				'status' => 'error',
				'error_message' => 'Облачного ID нет, вероятно это новая сделка.'
			];
		}

		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}

	private function syncFileds($deal){
		foreach($deal as $key => $value){
			switch($key){
				case 'ASSIGNED_BY_ID':
					$new_user_id = Crm::getBoxUserId($deal['ASSIGNED_BY_ID']);
					if(empty($new_user_id)) $new_user_id = 1;
					$deal['ASSIGNED_BY_ID'] = $new_user_id;
					break;

				case 'UF_CRM_1720601636':
					$deal[$key] = $deal['ID'];
					break;

				case 'SOURCE_ID':
					$source_id = Capsule::table('sources')->where('old_value', $value)->value('new_value');
					if(!empty($source_id)){
						$deal[$key] = $source_id;
					}
					break;

				case 'STAGE_ID':
					$stage = Capsule::table('stages')->where('old_status_id', $value)->value('new_status_id');
					if(!empty($stage)){
						$deal['STAGE_ID'] = $stage;
					} else {
						unset($deal['STAGE_ID']);
					}
					break;

				case 'CREATED_BY_ID':
				case 'MODIFY_BY_ID':
				case 'LAST_ACTIVITY_BY':
				case 'MOVED_BY_ID':
					$deal[$key] = Crm::getBoxUserId($value);
					break;

				case 'CATEGORY_ID':
				case 'STAGE_SEMANTIC_ID':
				case 'LAST_ACTIVITY_BY':
				case 'UF_CRM_1720601597':
				case 'UF_CRM_669F538FF3FBF':
				case 'LOCATION_ID':
				case 'UF_CRM_1720601636':
				case 'UF_CRM_60A39EE36DB80':
				case 'UF_CRM_6094E0E726214':
					unset($deal[$key]);
					break;
				case 'UF_CRM_1721830990':
					$deal[$key] = 'https://stopzaym.bitrix24.ru/crm/deal/details/' . $deal['UF_CRM_1720601636'] . '/';
					break;
				case 'UF_CRM_1722838734':
					$deal[$key] = str_replace('stopzaym.bitrix24.ru/docs/path', 'sz-crm.ru/docs/shared/path', $deal['UF_CRM_1565691799']);
					break;
				default :
					if(empty($value)){
						unset($deal[$key]);
					}

					$hasFieldList = Crm::hasFieldList($key);
					if($hasFieldList === true){
						$deal[$key] = Crm::getFieldListData($key, $value);
					}
					break;
			}
		}

		return $deal;
	}
}