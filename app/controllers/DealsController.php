<?php

namespace Esvlad\Bx24copytobox\Controllers;

use Esvlad\Bx24copytobox\Models\Contact;
use Esvlad\Bx24copytobox\Models\Deal;
use Esvlad\Bx24copytobox\Models\Crm;

class DealsController{
	public function addtobox($cloud_deal_id){
		$cloud_deal = Crm::bxCloudCall('crm.deal.get', ["ID" => $cloud_deal_id]);

		$fields = Deal::handlerFields($cloud_deal['result']);
		unset($fields['ID']);

		//Проверка
		$has_deal_db = Deal::where('old_id', $cloud_deal_id)->whereNotNull('new_id');
		if(!$has_deal_db->exists()){
			$box_deal_id_query = Crm::bxBoxCall('crm.deal.add', ['fields' => $fields]);
			$box_deal_id = $box_deal_id_query['result'];

			Deal::insert([
				'old_id' => $cloud_deal_id,
				'new_id' => $box_deal_id,
			]);

			if(!empty($cloud_deal['result']['CONTACT_ID'])){
				$cloud_contact_id = $cloud_deal['result']['CONTACT_ID'];

				$has_contact_db = Contact::where('old_id', $cloud_contact_id)->whereNotNull('new_id');
				if(!$has_contact_db->exists()){
					$box_contact_id = Contact::setContactBox($cloud_contact_id);
					Contact::setAddressContactToBox($cloud_contact_id, $box_contact_id);
				} else {
					$box_contact_id = $has_contact_db->value('new_id');
				}

				Crm::bxBoxCall('crm.deal.contact.add', ['ID' => $box_deal_id, 'fields' => ['CONTACT_ID' => $box_contact_id]]);

				Crm::bxCloudCall('bizproc.workflow.start', [
					'TEMPLATE_ID' => 1187,
					'DOCUMENT_ID' => ['crm', 'CCrmDocumentDeal', 'DEAL_' . $cloud_deal_id],
					'PARAMETERS' => null
				]);
			}
		} else {
			Crm::bxBoxCall('crm.deal.update', ['ID' = $box_deal_id, 'fields' => $fields]);
		}

		return true;
	}

	public function synchronization($box_deal_id, $box_user_id){
		$has_deal_db = Deal::where('new_id', $box_deal_id)->whereNotNull('box_id');
		if(!$has_deal_db->exists()){
			Crm::bxBoxCall('im.notify.system.add', [
				'USER_ID' => $box_user_id,
				'MESSAGE' => 'Синхронизация невозможна, так как данной сделки нет в облаке. Если у вас есть вопросы, то обратитесь к администратору.'
			]);
		} else {
			$cloud_deal_id = $has_deal_db->value('old_id');
			$cloud_deal = Crm::bxCloudCall('crm.deal.get', ["ID" => $cloud_deal_id]);

			$fields = Deal::handlerFields($cloud_deal['result']);
			unset($fields['ID']);

			Crm::bxBoxCall('crm.deal.update', ['ID' = $box_deal_id, 'fields' => $fields]);
			$box_deal = Crm::bxBoxCall('crm.deal.get', ["ID" => $box_deal_id]);

			if(!empty($box_deal['result']) && empty($box_deal['result']['CONTACT_ID'])){
				$has_contact_db = Contact::where('old_id', $cloud_contact_id)->whereNotNull('new_id');
				if(!$has_contact_db->exists()){
					$box_contact_id = Contact::setContactBox($cloud_contact_id);
					Contact::setAddressContactToBox($cloud_contact_id, $box_contact_id);
				} else {
					$box_contact_id = $has_contact_db->value('new_id');
				}

				Crm::bxBoxCall('crm.deal.contact.add', ['ID' => $box_deal_id, 'fields' => ['CONTACT_ID' => $box_contact_id]]);
			}
		}

		return true;
	}
}