<?php

namespace Esvlad\Bx24copytobox\Controllers;

use Esvlad\Bx24copytobox\Models\Lead;
use Esvlad\Bx24copytobox\Models\Crm;

class LeadsController{
	public function addtobox($cloud_lead_id){
		$cloud_lead = Crm::bxCloudCall('crm.lead.get', ["ID" => $cloud_lead_id]);

		$fields = Lead::handlerFields($cloud_lead['result']);
		unset($fields['ID']);

		//Проверка
		$has_lead_db = Lead::where('old_id', $cloud_lead_id)->whereNotNull('new_id');
		if(!$has_lead_db->exists()){
			$box_lead_id_query = Crm::bxBoxCall('crm.lead.add', ['fields' => $fields]);
			$box_lead_id = $box_lead_id_query['result'];

			Lead::insert([
				'old_id' => $cloud_lead_id,
				'new_id' => $box_lead_id,
			]);
		} else {
			Crm::bxBoxCall('crm.lead.update', ['ID' = $box_lead_id, 'fields' => $fields]);
		}

		return true;
	}

	public function synchronization($box_lead_id, $box_user_id){
		$has_lead_db = Lead::where('new_id', $box_lead_id)->whereNotNull('box_id');
		if(!$has_lead_db->exists()){
			Crm::bxBoxCall('im.notify.system.add', [
				'USER_ID' => $box_user_id,
				'MESSAGE' => 'Синхронизация невозможна, так как данного лида нет в облаке. Если у вас есть вопросы, то обратитесь к администратору.'
			]);
		} else {
			$cloud_lead_id = $has_lead_db->value('old_id');
			$cloud_lead = Crm::bxCloudCall('crm.lead.get', ["ID" => $cloud_lead_id]);

			$fields = Lead::handlerFields($cloud_lead['result']);
			unset($fields['ID']);

			Crm::bxBoxCall('crm.lead.update', ['ID' = $box_lead_id, 'fields' => $fields]);
		}

		return true;
	}
}