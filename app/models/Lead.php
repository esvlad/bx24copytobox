<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\Crm;
use Esvlad\Bx24copytobox\Models\User;
use Esvlad\Bx24copytobox\Models\User;

class Lead{
	public static function setLeadToBox($cloud_id){
		$cloud = Crm::bxCloudCall('crm.lead.get', ['ID' => $cloud_id]);
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

	public static function updateLeadToBox($cloud_id, $box_id){
		//MODIFY_BY_ID
	}

	private static function getBoxSourceId($cloud_source_id){
		return $source_id;
	}

	private static function getBoxStatusId($cloud_status_id){
		return $status_id;
	}
}