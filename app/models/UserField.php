<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\Crm;
use Esvlad\Bx24copytobox\Models\User;

class UserField extends Model {
	protected $table = "user_fields";

	//Трансфер данных из облака в коробку
	public static function getCloudUserFieldsToBox($type){
		$select = [
			'select' => [
				'ID',
				'EDIT_IN_LIST',
				'FIELD_NAME',
				'ENTITY_ID',
				'EDIT_IN_LIST',
				'MANDATORY',
				'MULTIPLE',
				'IS_SEARCHABLE',
				'SETTINGS',
				'SHOW_FILTER',
				'SHOW_IN_LIST',
				'USER_TYPE_ID',
				'EDIT_FORM_LABEL',
				'LIST_COLUMN_LABEL',
				'LIST_FILTER_LABEL',
				'LIST'
			],
		];

		$cloud_userfields = Crm::bxCloudCall('crm.'. $type .'.userfield.list', $select);
		$box_userfields = Crm::bxBoxCall('crm.'. $type .'.userfield.list', $select);
		$result = [];
		$set_fields = [];

		foreach($cloud_userfields['result'] as $key => $cloud_userfield){
			$cloud_userfield_labels = self::getCloudUserField($type, $cloud_userfield['ID']);

			//Переменная для добавления в базу
			$userfield = [];
			$userfield = $cloud_userfield;

			if(!empty($userfield['LIST'])){
				foreach($userfield['LIST'] as $key_list => $value_list){
					if($key_list == 'ID' || $key_list == 'XML_ID'){
						unset($userfield[$key]['LIST'][$key_list][$value]);
					}
				}
			}

			//Проверяем есть ли поле в CRM
			$box_userfield = false;
			foreach($box_userfields['result'] as $key_box => $value_box){
				if($value_box['FIELD_NAME'] == $cloud_userfield['FIELD_NAME']){
					$box_userfield = $value_box;
				}
			}

			//Если поля нет, то добавим
			if($box_userfield === false){
				unset($cloud_userfields['result'][$key]['ID']);
				unset($cloud_userfields['result'][$key]['XML_ID']);

				if(!empty($cloud_userfield['LIST'])){
					foreach($cloud_userfield['LIST'] as $key_list => $value_list){
						if($key_list == 'ID' || $key_list == 'XML_ID'){
							unset($cloud_userfields['result'][$key]['LIST'][$key_list][$value]);
						}
					}
				}

				$cloud_userfields['result'][$key]['EDIT_FORM_LABEL'] = $cloud_userfield_labels['EDIT_FORM_LABEL'];
				$cloud_userfields['result'][$key]['LIST_COLUMN_LABEL'] = $cloud_userfield_labels['LIST_COLUMN_LABEL'];
				$cloud_userfields['result'][$key]['LIST_FILTER_LABEL'] = $cloud_userfield_labels['LIST_FILTER_LABEL'];

				if(!empty($cloud_userfield['USER_TYPE_OWNER'])){
					$pos = strpos($cloud_userfield['USER_TYPE_OWNER'], 'app.');
					$pos2 = strpos($cloud_userfield['USER_TYPE_OWNER'], 'local.');
					if($pos === false && $pos2 === false){
						//$box_userfield = self::setBoxUserField($type, $cloud_userfields['result'][$key]);
						$set_fields[] = $cloud_userfields['result'][$key];
					}
				} else {
					//$box_userfield = self::setBoxUserField($type, $cloud_userfields['result'][$key]);
					$set_fields[] = $cloud_userfields['result'][$key];
				}
			}

			//Поле для БД
			$userfield_data = [
				'entity_type' => $type,
				'cloud_id' => $userfield['ID'],
				'type' => $userfield['USER_TYPE_ID'],
				'field_name' => $userfield['FIELD_NAME']
			];

			if(!empty($userfield['LIST'])){
				$userfield_data['cloud_list'] = json_encode($userfield['LIST']);
			}

			if(!empty($userfield['SETTINGS'])){
				$userfield_data['settings'] = json_encode($userfield['SETTINGS']);
			}

			if(!empty($box_userfield)){
				$userfield_data['box_id'] = $box_userfield['ID'];

				if(!empty($box_userfield['LIST'])){
					$userfield_data['box_list'] = json_encode($box_userfield['LIST']);
				}
			}

			$result[] = $userfield_data;

			//Проверяем есть ли поле в БЛ
			/*$has_userfield = Capsule::table('user_fields')->where('cloud_id', $userfield_data['cloud_id']);

			//Если такое поле есть
			if($has_userfield->exists()){
				Capsule::table('user_fields')->where('cloud_id', $userfield_data['cloud_id'])->update($userfield_data);
			}

			//Если нет
			if(!$has_userfield->exists()){
				Capsule::table('user_fields')->insert($userfield_data);
			}*/
		}

		//preprint('result');
		print_r($set_fields);
	}

	//Получить названия поля из облака при выводе в CRM
	public static function getCloudUserField($type, $id){
		$cloud_userfield = Crm::bxCloudCall('crm.'. $type .'.userfield.get', ['ID' => $id]);

		return [
			'EDIT_FORM_LABEL' => $cloud_userfield['result']['EDIT_FORM_LABEL'],
			'LIST_COLUMN_LABEL' => $cloud_userfield['result']['LIST_COLUMN_LABEL'],
			'LIST_FILTER_LABEL' => $cloud_userfield['result']['LIST_FILTER_LABEL']
		];
	}

	//Получить данные поля из коробки и вернуть список
	public static function getBoxUserFieldList($type, $id){
		$box_userfield = Crm::bxBoxCall('crm.'. $type .'.userfield.get', ['ID' => $id]);

		$list = $box_userfield['result']['LIST'];
		foreach($list as $key => $value){
			if($key == 'XML_ID'){
				unset($list[$key]);
			}
		}

		return $list;
	}

	//Добавим поле и получем добавленную информацию
	private static function setBoxUserField($type, $data){
		sleep(1);
		$result = Crm::bxBoxCall('crm.' . $type . '.userfield.add', $data);
		$field_id = $result['result'];

		$userfield = Crm::bxBoxCall('crm.' . $type . '.userfield.get', ['ID' => $field_id]);

		return $userfield['result'];
	}
}
