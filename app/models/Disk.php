<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Capsule\Manager as Capsule;
use Esvlad\Bx24copytobox\Models\Crm;

class Disk{
	public static function getFolderBox($folder_name){
		return true;
	}

	public static function hasFolderBox($parent_id, $task_id){
		$task_title = Crm::bxBoxCall('tasks.task.get', ['taskId' => $task_id, 'select' => ["ID", "TITLE"]])['result']['title'];

		$result = Crm::bxBoxCall('disk.folder.getchildren', [
			'id' => $parent_id,
			'filter' => [
				'NAME' => $task_title
			]
		]);

		if(!empty($result['result'])) return $result['result']['ID'];

		return false;
	}

	public static function hasFolder($parent_id, $task_title){
		$result = Crm::bxBoxCall('disk.folder.getchildren', [
			'id' => $parent_id,
			'filter' => [
				'NAME' => $task_title
			]
		]);

		if(!empty($result['result'])) return $result['result'];

		return false;
	}

	public static function setFolderBox($parent_id, $folder_name){
		//$folder_name = str_replace(':', '', $folder_name);
		$folder_name = preg_replace('/[^a-zа-яё\d]/ui', '', $folder_name);

		$result = Crm::bxBoxCall('disk.folder.addsubfolder', [
			'id' => $parent_id,
			'data' => [
				'NAME' => $folder_name
			]
		]);

		//print_r($result);

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

	public static function uploadFileTaskToBox($start = 30){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		Capsule::table('counters')->where('type', 'disk_tasks')->update(['start' => $start]);

		$count = Capsule::table('tasks_files')->count();
		$files = Capsule::table('tasks_files')->offset($start)->limit(10)->get();

		if($start < $count) $next = $start + 10;

		if(!empty($files)){
			foreach($files as $file){
				$has_folder_task_box_id = Capsule::table('tasks_folder')
				->select('tasks_folder.task_folder_box_id')
				->leftJoin('tasks_data', 'tasks_data.id', '=', 'tasks_folder.task_data_id')
				->leftJoin('tasks_files', 'tasks_files.task_old_id', '=', 'tasks_data.old_id')
				->where('tasks_files.id', $file->id);

				if($has_folder_task_box_id->exists()){
					$folder_id = $has_folder_task_box_id->value('tasks_folder.task_folder_box_id');
				} else {
					$task = Capsule::table('tasks_data')->where('tasks_data.old_id', $file->task_old_id)->first();

					$task_title = preg_replace('/[^a-zа-яё\d]/ui', '', $task->title);
					$has_folder = self::hasFolder(945077, $task_title);
					if(!empty($has_folder)){
						$folder_id = $has_folder[0]['ID'];
					} else {
						$folder_id = self::setFolderBox(945077, $task->title);
					}

					Capsule::table('tasks_folder')->insert(['task_data_id' => $task->id, 'task_folder_box_id' => $folder_id]);
				}

				$file_box = self::setFileBox($folder_id, $file->name, $file->download_url);

				Capsule::table('tasks_files')->where('id', $file->id)->update(['new_id' => $file_box['ID']]);
			}

			unset($files);
		}

		if(empty($next)){
			print("Закачка файлов задач завершена!\r\n");
			return true;
		}

		self::uploadFileTaskToBox($next);
	}

	public static function uploadFileCommentsToBox($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		Capsule::table('counters')->where('type', 'disk_comments')->update(['start' => $start]);

		$count = Capsule::table('comments_files')->count();
		$files = Capsule::table('comments_files')->offset($start)->limit(10)->get();

		if($start < $count) $next = $start + 10;

		if(!empty($files)){
			foreach($files as $file){
				$has_folder_task_box_id = Capsule::table('tasks_folder')
				->select('tasks_folder.task_folder_box_id')
				->leftJoin('tasks_data', 'tasks_data.id', '=', 'tasks_folder.task_data_id')
				->leftJoin('comments_data', 'comments_data.task_old_id', '=', 'tasks_data.old_id')
				->leftJoin('comments_files', 'comments_files.comments_old_id', '=', 'comments_data.old_id')
				->where('comments_files.id', $file->id);

				if($has_folder_task_box_id->exists()){
					$folder_id = $has_folder_task_box_id->value('tasks_folder.task_folder_box_id');
				} else {
					$task = Capsule::table('tasks_data')
					->select('tasks_data.id', 'tasks_data.title')
					->leftJoin('comments_data', 'comments_data.task_old_id', '=', 'tasks_data.old_id')
					->where('comments_data.old_id', $file->comments_old_id)->first();

					$task_title = preg_replace('/[^a-zа-яё\d]/ui', '', $task->title);
					$has_folder = self::hasFolder(945077, $task_title);
					if(!empty($has_folder)){
						$folder_id = $has_folder[0]['ID'];
					} else {
						$folder_id = self::setFolderBox(945077, $task->title);
					}

					Capsule::table('tasks_folder')->insert(['task_data_id' => $task->id, 'task_folder_box_id' => $folder_id]);
				}

				$file_box = self::setFileBox($folder_id, $file->name, $file->download_url);

				Capsule::table('comments_files')->where('id', $file->id)->update(['new_id' => $file_box['ID']]);
			}

			unset($files);
		}

		if(empty($next)){
			print("Закачка файлов комментариев завершена!\r\n");
			return true;
		}

		self::uploadFileCommentsToBox($next);
	}
}