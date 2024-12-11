<?php

namespace Esvlad\Bx24copytobox\Controllers;

use Esvlad\Bx24copytobox\Models\Contact;
use Esvlad\Bx24copytobox\Models\Crm;

class ContactsController{
	public function addtobox($cloud_contact_id){
		$cloud_contact = Crm::bxCloudCall('crm.contact.get', ["ID" => $cloud_contact_id]);

		$fields = Contact::handlerFields($cloud_contact['result']);
		unset($fields['ID']);

		//Проверка
		$has_contact_db = Contact::where('old_id', $cloud_contact_id)->whereNotNull('new_id');
		if(!$has_contact_db->exists()){
			$box_contact_id_query = Crm::bxBoxCall('crm.contact.add', ['fields' => $fields]);
			$box_contact_id = $box_contact_id_query['result'];

			Contact::insert([
				'old_id' => $cloud_contact_id,
				'new_id' => $box_contact_id,
			]);
		} else {
			$box_contact_id = $has_contact_db->value('new_id');
			Crm::bxBoxCall('crm.contact.update', ['ID' = $box_contact_id, 'fields' => $fields]);
		}

		Contact::setAddressContactToBox($cloud_contact_id, $box_contact_id);

		return true;
	}

	public function synchronization($box_contact_id, $box_user_id){
		$has_contact_db = Contact::where('new_id', $box_contact_id)->whereNotNull('box_id');
		if(!$has_contact_db->exists()){
			Crm::bxBoxCall('im.notify.system.add', [
				'USER_ID' => $box_user_id,
				'MESSAGE' => 'Синхронизация невозможна, так как данного контакта нет в облаке. Если у вас есть вопросы, то обратитесь к администратору.'
			]);
		} else {
			$cloud_contact_id = $has_contact_db->value('old_id');
			$cloud_contact = Crm::bxCloudCall('crm.contact.get', ["ID" => $cloud_contact_id]);

			$fields = Contact::handlerFields($cloud_contact['result']);
			unset($fields['ID']);

			Crm::bxBoxCall('crm.contact.update', ['ID' = $box_contact_id, 'fields' => $fields]);
			Contact::setAddressContactToBox($cloud_contact_id, $box_contact_id);
		}

		return true;
	}

	public function synchronizationAddress($box_contact_id, $box_user_id){
		$has_contact_db = Contact::where('new_id', $box_contact_id)->whereNotNull('box_id');
		if(!$has_contact_db->exists()){
			Crm::bxBoxCall('im.notify.system.add', [
				'USER_ID' => $box_user_id,
				'MESSAGE' => 'Синхронизация невозможна, так как данного контакта нет в облаке. Если у вас есть вопросы, то обратитесь к администратору.'
			]);
		} else {
			$cloud_contact_id = $has_contact_db->value('old_id');
			Contact::setAddressContactToBox($cloud_contact_id, $box_contact_id);
		}

		return true;
	}
}