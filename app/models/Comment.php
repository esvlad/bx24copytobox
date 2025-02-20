<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\User;
use Esvlad\Bx24copytobox\Models\Disk;
use Esvlad\Bx24copytobox\Models\Crm;

class Comment extends Model{
	protected $table = "comments";
	private $task_folder_id = 945077;

	public static function setTaskCommentsBox($task_id, $comments = []){
		$insert_comments = [];
		if(!empty($comments)){
			foreach($comments as $value){
				$comment_cloud_id = $value['ID'];
				$comment = self::handlerFields($task_id, $value);
				//$comment_box_id = $comment['ID'];
				//unset($comment['ID']);

				if(!empty($value['ATTACHED_OBJECTS'])){
					//$comment['ATTACHED_OBJECTS'] = self::setCommentsFiles($task_id, $fields['ATTACHED_OBJECTS']);
					self::setCommentsFiles($comment_cloud_id, $value['ATTACHED_OBJECTS']);
				}

				/*if($comment_box_id === false){
					$author_id = $comment['AUTHOR_ID'];
					$comment['AUTHOR_ID'] = 1;

					$comment_add = Crm::bxBoxCall('task.commentitem.add', [$task_id, $comment]);
					$comment_box_id = $comment_add['result']['ID'];

					$task_cloud_id = Task::where('new_id', $task_id)->value('old_id');
					self::insert(['task_new_id' => $task_id, 'task_old_id' => $task_cloud_id, 'old_id' => $comment_cloud_id, 'new_id' => $comment_box_id, 'author_id' => $author_id, 'create_comment' => date('Y-m-d H:i:s', strtotime($comment['POST_DATE']))]);
				} else {
					Crm::bxBoxCall('task.commentitem.update', [$task_id, $comment_id, $comment]);
					self::where('task_new_id' => $task_id, 'new_id' => $comment_box_id)->update(['author_id' => $author_id, 'create_comment' => date('Y-m-d H:i:s', strtotime($comment['POST_DATE']))]);
				}*/

				$has_comments_data_db = Capsule::table('comments_data')->where('old_id', $comment_cloud_id);
				if(!$has_comments_data_db->exists()){
					$insert_comments[] = [
						'old_id' => $comment_cloud_id,
						'task_old_id' => $task_id,
						'author_id' => $comment['AUTHOR_ID'],
						'post_date' => $comment['POST_DATE'],
						'post_message' => $comment['POST_MESSAGE']
					];
				}

				unset($comment);
			}

			if(!empty($insert_comments)){
				Capsule::table('comments_data')->insert($insert_comments);
				unset($insert_comments);
			}

			unset($comments);
		}
	}

	public static function setTaskCommentsBoxFromTable($task_id){
		$has_comments = Capsule::table('comments_data')->where('task_old_id', $task_id);

		if($has_comments->exists()){
			foreach($has_comments->get() as $comment){
				$comment_data = [];

				$comment_data['AUTHOR_ID'] = 1;
				$comment_data['POST_DATE'] = $comment->post_date;
				if(!empty($comment->post_message)){
					$comment_data['POST_MESSAGE'] = $comment->post_message;
				} else {
					$comment_data['POST_MESSAGE'] = '';
				}

				$has_comments_file = Capsule::table('comments_files')->where('comments_old_id', $comment->old_id);
				if($has_comments_file->exists()){
					$comment_data['UF_FORUM_MESSAGE_DOC'] = [];
					foreach($has_comments_file->get() as $file){
						$comment_data['UF_FORUM_MESSAGE_DOC'][] = 'n' . $file->id;
					}
				}

				$comment_add = Crm::bxBoxCall('task.commentitem.add', [$task_id, $comment_data]);
				if(!empty($comment_add['result'])){
					$comment_box_id = $comment_add['result'];

					Capsule::table('comments_data')->where('id', $comment->id)->update(['new_id' => $comment_box_id]);
				} else {
					print_r($comment_add);
				}

			}
		}
	}

	public static function fillingСommentsToBox($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		Capsule::table('counters')->where('type', 'comments')->update(['start' => $start]);

		$count = Capsule::table('comments_data')->count();
		$comments = Capsule::table('comments_data')->offset($start)->limit(10)->get();

		if($start < $count) $next = $start + 10;

		if(!empty($comments)){
			foreach($comments as $comment){
				$has_comment_box = self::where('old_id', $comment->old_id);

				if($has_comment_box->exists()){
					$comment_box_id = $has_comment_box->value('new_id');
					$has_comment_files = Capsule::table('comments_files')->where('comments_old_id', $comment->old_id);

					//Если файлы есть
					if($has_comment_files->exists()){
						$count_comment_files = $has_comment_files->count();

						$task_box_id = Capsule::table('comments_data')->leftJoin('tasks_data')

						$has_comment_box_files = self::getTaskCommentsBox($task_box_id, $comment_box_id);

						if($count_comment_files > 0){
							if(!empty($has_task_box_files['ufTaskWebdavFiles'])){
								if(count($has_task_box_files['ufTaskWebdavFiles']) != $count_task_files){
									foreach($has_comment_files->get() as $file){
										self::setTaskFilesAttached($task_box_id, $file->new_id);
									}
								}
							} else {
								foreach($has_comment_files->get() as $file){
									self::setTaskFilesAttached($task_box_id, $file->new_id);
								}
							}
						}
					}
				} else {
					#
				}
			}

			unset($comments);
		}

		if(empty($next)){
			print("Загрузка комментариев завершена!\r\n");
			return true;
		}

		self::fillingСommentsToBox($next);
	}

	public static function getTaskCommentsBox($task_id, $comment_id){
		$result = Crm::bxBoxCall('task.commentitem.get', [$task_id, $comment_id]);
	}

	public static function getTaskComments($task_id, $box = false){
		$params = [$task_id, ['ID' => 'asc'], []];

		if($box === true){
			$result = Crm::bxBoxCall('task.commentitem.getlist', $params);
		} else {
			$result = Crm::bxCloudCall('task.commentitem.getlist', $params);
		}

		if(!empty($result['result'])) {
			return $result['result'];
		}

		return false;
	}

	public static function handlerFields($task_id, $fields){
		$comment = [];

		if(!self::where('new_id', $fields['ID'])->exists()){
			$comment['ID'] = false;
		} else {
			$comment['ID'] = self::where('old_id', $fields['ID'])->value('new_id');
		}

		$author_id = Crm::getBoxUserId($fields['AUTHOR_ID']);
		if($author_id === 1){
			$author_name = $fields['AUTHOR_NAME'];
		}
		$comment['AUTHOR_ID'] = $author_id;
		$comment['POST_DATE'] = date('Y-m-d H:i:s', strtotime($fields['POST_DATE']));

		if(!empty($author_name)){
			$comment['POST_MESSAGE'] = '[B]' . $author_name . ':[/B] ' . self::remove_bbcode($fields['POST_MESSAGE']);
		} else {
			$comment['POST_MESSAGE'] = self::remove_bbcode($fields['POST_MESSAGE']);
		}

		return $comment;
	}

	public static function setCommentsFiles($comment_cloud_id, $attached_objects){ //$task_title -> comment_cloud_id
		$attached = [];
		//$folder_id = Disk::hasFolderBox(945077, $task_id);

		$insert_task_comments_files = [];
		foreach($attached_objects as $attached_object){
			if($attached_object['SIZE'] < '1024000'){
				//$attached[] = Disk::setFileBox($folder_id, $attached_object['NAME'], $attached_object['DOWNLOAD_URL']);
				$file_cloud_info = Disk::getFile($attached_object['FILE_ID'], 'cloud');

				if($file_cloud_info !== false){
					$has_file_db = Capsule::table('comments_files')->where('old_id', $file_cloud_info['ID']);
					if(!$has_file_db->exists()){
						$insert_task_comments_files[] = [
							'old_id' => $file_cloud_info['ID'],
							'comments_old_id' => $comment_cloud_id,
							'name' => $file_cloud_info['NAME'],
							'create_time' => date('Y-m-d H:i:s', strtotime($file_cloud_info['CREATE_TIME'])),
							'update_time' => date('Y-m-d H:i:s', strtotime($file_cloud_info['UPDATE_TIME'])),
							'created_by' => Crm::getBoxUserId($file_cloud_info['CREATED_BY']),
							'updated_by' => Crm::getBoxUserId($file_cloud_info['UPDATED_BY']),
							'download_url' => $file_cloud_info['DOWNLOAD_URL'],
							'detail_url' => $file_cloud_info['DETAIL_URL'],
						];
					}
				}
			}
		}

		if(!empty($insert_task_comments_files)){
			Capsule::table('comments_files')->insert($insert_task_comments_files);
			unset($insert_task_comments_files);
			unset($attached_objects);
		}

		//return $attached;
	}

	public static function setDuplicatesComments(){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$comments = self::selectRaw('old_id, COUNT(`old_id`) AS count')->groupBy('old_id')->having('count', '>', 1)->get();

		if(!empty($comments)){
			foreach($comments as $comment){
				$comment_query = self::where('old_id', $comment->old_id)->offset(1)->limit(500);
				$delete_comment = $comment_query;
				$comment_db = $comment_query->get();
				unset($comment_query);
				$comments_insert = [];

				foreach($comment_db as $comment_box){
					if(!empty($comment_box->new_id) && !empty($comment_box->task_new_id)){
						$comments_insert[] = [
							'new_id' => $comment_box->new_id,
							'task_new_id' => $comment_box->task_new_id
						];
					}
				}

				if(!empty($comments_insert)){
					Capsule::table('comments_remove')->insert($comments_insert);
					unset($comments_insert);
				}

				$delete_comment->delete();
			}
		} else unset($next);

		unset($comments);

		if(empty($next)){
			print("Удаление дубликатов завершено!\r\n");
			return true;
		}

		//self::setDuplicatesComments($next);
	}

	public static function removeDuplicatesComments($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$count = Capsule::table('comments_remove')->count();
		$tasks = Capsule::table('comments_remove')->offset($start)->limit(50)->get();

		if($start < $count) $next = $start + 50;

		$box_batch_list = [];
		foreach($comments as $comment){
			$box_batch_list[] = [
				'method' => 'task.commentitem.delete',
				'params' => [$comment->task_new_id, $comment->new_id]
			];
		}

		if(!empty($box_batch_list)){
			Crm::bxBoxCallBatch($box_batch_list);
			unset($box_batch_list);
		}

		if(empty($next)){
			print("Удаление задач завершено\r\n");
			return true;
		}

		sleep(1);
		self::removeDuplicatesComments($next);
	}

	private static function remove_bbcode($string) {
	    $pattern = '~\[[^]]+]~';
	    $replace = '';
	    return preg_replace($pattern, $replace, $string);
	}
}