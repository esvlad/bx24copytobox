<?php

namespace Esvlad\Bx24copytobox\Models;

use Esvlad\Bx24copytobox\Models\Crm;

class Disk{
	public static function getFolderBox($folder_name){
		return true;
	}

	public static function hasFolderBox($parent_id, $task_id){
		$task_title = Crm::bxBoxCall('tasks.task.get', ['taskId' => $task_id, 'select' => ["ID", "TITLE"]])['result']['title'];

		$result = Crm::bxBoxCall('disk.folder.getchildren', [
			'id' => $parent_id,
			'data' => [
				'NAME' => $task_title
			]
		]);

		if(!empty($result['result'])) return $result['result']['ID'];

		return false;
	}

	public static function setFolderBox($parent_id, $folder_name){
		$folder_name = str_replace(':', '', $folder_name);
		$result = Crm::bxBoxCall('disk.folder.addsubfolder', [
			'id' => $parent_id,
			'data' => [
				'NAME' => $folder_name
			]
		]);

		return $result['result']['ID'];
	}

	public static function hasFileBox($folder_box_id, $file_name){
		$result = Crm::bxBoxCall('disk.folder.getchildren', [
			'id' => $folder_box_id,
			'filter' => ['NAME' => $file_name]
		]);

		if(!empty($result['result'])) return $result['result'];

		return false;
	}

	public static function getFile($file_id, $crm = 'box'){
		$method = ($crm == 'box') ? 'bxBoxCall' : 'bxCloudCall';
		$result = Crm::$method('disk.file.get', ['id' => $file_id]);

		if(!empty($result['result'])){
			return $result['result'];
		}

		return false;
	}

	public static function setFileBox($folder_box_id, $file_name, $file_download_url){
		$result = Crm::bxBoxCall('disk.folder.uploadfile', [
			'id' => $folder_box_id,
			'data' => ['NAME' => $file_name],
			'fileContent' => [$file_name, base64_encode($file_download_url)],
			'generateUniqueName' => true
		]);

		return $result['result'];
	}

}