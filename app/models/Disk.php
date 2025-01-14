<?php

namespace Esvlad\Bx24copytobox\Models;

use Esvlad\Bx24copytobox\Models\Crm;

class Disk{
	public static function getFolderBox($folder_name){
		return true;
	}

	public static function setFolderBox($parent_id, $folder_name){
		$result = Crm::bxBoxCall('disk.folder.addsubfolder',, [
			'ID' => $parent_id,
			'data' => [
				'NAME' => $folder_name
			]
		]);

		return $result['result']['ID'];
	}

	public static function getFile($file_id, $crm = 'box'){
		$method = ($crm == 'box') ? 'bxBoxCall' : 'bxCloudCall';
		$result = Crm::$method('disk.file.get', ['ID' => $file_id]);

		return $result['result'];
	}

	public static function setFileBox($folder_box_id, $file_name, $file_download_url){
		$result = Crm::bxBoxCall('disk.folder.uploadfile', [
			'ID' => $folder_box_id,
			'data' => ['NAME' => $file_name],
			'fileContent' => [$file_name, base64_encode($file_download_url)],
			'generateUniqueName' => true
		]);

		return $result['result'];
	}

}