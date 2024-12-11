<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\CrestCloud;
use Esvlad\Bx24copytobox\Models\CrestBox;

class Crm{
	public static function bxCloudCallBatch($batch_list){
		$result = CrestCloud::callBatch($batch_list);
	    if (!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
	        sleep(1);
	        self::bxCloudCallBatch($batch_list);
	    }

	    return $result;
	}

	public static function bxBoxCallBatch($batch_list){
		$result = CrestBox::callBatch($batch_list);
	    if (!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
	        sleep(1);
	        self::bxBoxCallBatch($batch_list);
	    }

	    return $result;
	}

	public static function bxCloudCall($method, $data){
		$result = CrestCloud::call($method, $data);

		if(!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
			sleep(1);
			self::bxCloudCall($method, $data);
		} else {
			return $result;
		}
	}

	public static function bxBoxCall($method, $data){
		$result = CrestBox::call($method, $data);

		if(!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
			sleep(1);
			self::bxBoxCall($method, $data);
		} else {
			return $result;
		}
	}

	///////////////////////////////////////////////////////////

	public static function setLog($arData, $type = '', $folder = ''){
		$path = dirname(dirname(__DIR__)) . '/logs/';
		$path .= $folder . date("Y-m-d/H") . '/';

		if (!file_exists($path)){
			@mkdir($path, 0775, true);
		}

		$path .= time() . '_' . $type . '_' . rand(1, 9999999) . 'log';

		$log = date("d.m.Y H:i:s") . "\n";
	    $log .= print_r($arData, 1);

		file_put_contents($path . '.log', $log);
	}

	///////////////////////////////////////////////////////////

	public static function lastTypeCounter($type, $start, $count = false){
		$data = [];
		$data['start'] = $start;
		if(!empty($count)) $data['count'] = $count;

		Capsule::table('counters')->where('type', $type)->update($data);
	}

	public static function hasFieldList($field_name){
		$query = Capsule::table('user_fields')->where('field_name', $field_name)->whereNotNull('box_list');

		return $query->exists();
	}

	public static function getFieldListData($field_name, $field_value){
		$query = Capsule::table('user_fields')->where('field_name', $field_name)->first();
		$field_cloud_list = json_decode($query->cloud_list, true);
		$field_box_list = json_decode($query->box_list, true);

		foreach($field_cloud_list as $cloud_field){
			if(is_array($field_value)){
				$result = [];
				foreach($field_value as $key => $value){
					if($cloud_field['ID'] == $field_value){
						foreach($field_box_list as $box_field){
							if($box_field['VALUE'] == $cloud_field['VALUE']){
								$result[] = $box_field['ID'];
							}
						}
					}
				}

				if(!empty($result)){
					return $result;
				}
			} else {
				if($cloud_field['ID'] == $field_value){
					foreach($field_box_list as $box_field){
						if($box_field['VALUE'] == $cloud_field['VALUE']){
							return $box_field['ID'];
						}
					}
				}
			}
		}

		return false;
	}

	public static function getBoxUserId($old_user_id){
		$user_id = Capsule::table('users_transfer')->where('old_id', $old_user_id)->value('new_id');
		if(empty($user_id)) $user_id = 1;

		return $user_id;
	}

	public static function dbMerge($table, $offset = 0, $del = 0){
		$element = Capsule::table($table)->offset($offset)->limit(1)->first();

		//print_r($element);
		//print_r(Capsule::table($table)->where('old_id', $element->old_id)->count());
		//print_r(Capsule::table($table)->where('id', '!=', $element->id)->where('old_id', $element->old_id)->get());

		if(Capsule::table($table)->where('old_id', $element->old_id)->count() > 1){
			Capsule::table($table)->were('id', '!=', $element->id)->where('old_id', $element->old_id)->delete();
			$del++;
		}
		print($offset . "\r\n");

		$offset++;

		if(empty($element)){
			print("Удалено элементов: {$del}\r\n");
			return false;
		}

		self::dbMerge($table, $offset, $del);
	}
}