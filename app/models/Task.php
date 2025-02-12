<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\User;
use Esvlad\Bx24copytobox\Models\Comment;
use Esvlad\Bx24copytobox\Models\Crm;

class Task extends Model{
	protected $table = "tasks";
	private $task_folder_id = 945077;

	public static function getTaskCloudToBox($start = 34700){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		Capsule::table('counters')->where('type', 'task')->update(['start' => $start]);

		$params = [
			'select' => ['*', 'UF_*'],
			'filter' => [">CREATED_DATE" => "2024-01-01", "<CREATED_DATE" => "2024-12-31"],
			'order' => ['ID' => 'asc'],
			'start' => $start
		];

		$result = Crm::bxCloudCall('tasks.task.list', $params);

		if(!empty($result['next'])) $next = $result['next'];

		if(!empty($result['result']['tasks'])){
			//print_r($result['result']['tasks']);

			$tasks = [];
			foreach($result['result']['tasks'] as $task){
				$hasTaskDB = self::where('old_id', $task['id']);

				if(!empty($task['parentId'])){
					$old_parent_id = $task['parentId'];
				}

				$task_cloud = self::handlerFields($task);


				if($hasTaskDB->exists()){
					$task_box_id = $hasTaskDB->value('new_id');

					//$task_box = self::getTaskBox($task_box_id);

					//Проверить наличие чек-листа и добавить к задаче
					/*if(!empty($task_cloud['checklist'])){
						//self::setChekLists($task_box_id, $task_cloud['checklist']);
						self::setChekLists($task['id'], $task_cloud_checklist);
					}*/

					//Проверить наличие файлов и добавить к задаче
					if(!empty($task_cloud['ufTaskWebdavFiles'])){
						//self::setTaskFiles($task_box_id, $task_cloud['ufTaskWebdavFiles']);
						self::setTaskFiles($task['id'], $task_cloud['title'], $task_cloud['ufTaskWebdavFiles'], true);
					}

					$task_data = self::handlerData($task_cloud);

					if(!empty($old_parent_id)){
						$task_data['old_parent_id'] = $old_parent_id;
						//$task_data['new_parent_id'] = $task_cloud['parentId'];
					}

					$task_data['new_id'] = $task_box_id;

					$has_task_data_db = Capsule::table('tasks_data')->where('old_id', $task_data['old_id']);
					if(!$has_task_data_db->exists()){
						$task_data['description'] = iconv("UTF-8","UTF-8//IGNORE", $task_data['description']);
						Capsule::table('tasks_data')->insert($task_data);
					}

					$task_comments_cloud = Comment::getTaskComments($task['id']);
					Comment::setTaskCommentsBox($task['id'], $task_comments_cloud);
					unset($task_comments_cloud);

					/*if($task_box['commentsCount'] != $task_cloud['commentsCount']){
						$task_comments_box = Comment::getTaskComments($task_box_id, true);

						if(!empty($task_comments_box)){
							foreach($task_comments_box as $task_comment_box){
								if(date('Y-m-d', strtotime($task_comment_box['POST_DATE'])) < '2025-01-10'){
									Crm::bxBoxCall('task.commentitem.delete', [$task_box_id, $task_comment_box['ID']]);
									$hasComment = Comment::where('new_id', $task_comment_box['ID']);
									if($hasComment->exists()) $hasComment->delete();
								}
							}
						}

						if(!empty($task_comments_cloud)){
							Comment::setTaskCommentsBox($task_comments_cloud);
						}

						unset($task_comments_box);
						unset($task_comments_cloud);
					}*/

					//unset($task_box);
				} else {
					$task_data = self::handlerData($task_cloud);
					/*$new_task_cloud = [];
					foreach($task_data as $key => $value){
						$new_task_cloud[strtoupper($key)] = $value;
					}

					unset($task_cloud['id']);

					$task_cloud['createdBy'] = 1;
					$task_cloud['responsibleId'] = 1;
					$task_cloud['changedBy'] = 1;

					$new_task_cloud['CREATED_BY'] = 1;
					$new_task_cloud['RESPONSIBLE_ID'] = 1;
					$new_task_cloud['CHANGED_BY'] = 1;*/

					if(!empty($task_cloud['checklist'])){
						$task_cloud_checklist = $task_cloud['checklist'];
						unset($task_cloud['checklist']);
					}

					if(!empty($task_cloud['ufTaskWebdavFiles'])){
						$task_cloud_files = $task_cloud['ufTaskWebdavFiles'];
						unset($task_cloud['ufTaskWebdavFiles']);
					}

					/*if(!empty($task_cloud['accomplices'])){
						unset($task_cloud['accomplices']);
					}

					if(!empty($task_cloud['auditors'])){
						unset($task_cloud['auditors']);
					}

					if(!empty($task_cloud['closedBy'])){
						$task_cloud['closedBy'] = 1;
					}*/

					if(!empty($old_parent_id)){
						$task_data['old_parent_id'] = $old_parent_id;
						//$task_data['new_parent_id'] = $task_cloud['parentId'];
					}

					$has_task_data_db = Capsule::table('tasks_data')->where('old_id', $task_data['old_id']);
					if(!$has_task_data_db->exists()){
						$task_data['description'] = iconv("UTF-8","UTF-8//IGNORE", $task_data['description']);
						Capsule::table('tasks_data')->insert($task_data);
					}

					/*$box_id = Capsule::table('tasks_data')->where('old_id', $task_data['old_id']);
					if($box_id->exists()){
						$task_box_id = $box_id->value('new_id');
					} else {
						$task_box_id = self::setTaskBox($new_task_cloud);
						$task_data['new_id'] = $task_box_id;
						Capsule::table('tasks_data')->insert($task_data);
					}*/

					unset($task_data);
					//unset($new_task_cloud);

					//Проверить наличие чек-листа и добавить к задаче
					/*if(!empty($task_cloud_checklist)){
						//self::setChekLists($task_box_id, $task_cloud_checklist);
						self::setChekLists($task['id'], $task_cloud_checklist);
					}*/

					//Проверить наличие файлов и добавить к задаче
					if(!empty($task_cloud_files)){
						//self::setTaskFiles($task_box_id, $task_cloud['title'], $task_cloud_files, true);
						self::setTaskFiles($task['id'], $task_cloud['title'], $task_cloud_files, true);
					}

					//Проверить наличие комментариев и добавить к задаче

					$task_comments_cloud = Comment::getTaskComments($task['id']);
					Comment::setTaskCommentsBox($task['id'], $task_comments_cloud);
					unset($task_comments_cloud);

					/*$task_comments_cloud = Comment::getTaskComments($task_box_id);
					if(!empty($task_comments_cloud)){
						Comment::setTaskCommentsBox($task_box_id, $task_comments_cloud);
						unset($task_comments_cloud);
					}*/
				}

				unset($task_cloud);
			}
		}

		unset($result);

		//return true;

		if(empty($next)){
			print("Заполнения базы задач завершено\r\n");
			return true;
		}

		self::getTaskCloudToBox($next);
	}

	public static function getTaskBox($task_id){
		$result = Crm::bxBoxCall('tasks.task.get', ['taskId' => $task_id, 'select' => ["*"]]);

		if(!empty($result['result']['task'])){
			return $result['result']['task'];
		}

		return false;
	}

	public static function setTaskBox($task){
		$result = Crm::bxBoxCall('tasks.task.add', ['fields' => $task]);

		if(!empty($result['result'])){
			return $result['result']['task']['id'];
		}

		print_r($result);

		return false;
	}

	public static function setTaskBoxFromTable($start = 33380){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		Capsule::table('counters')->where('type', 'task')->update(['start' => $start]);

		$count = Capsule::table('tasks_data')->count();
		$tasks = Capsule::table('tasks_data')->offset($start)->limit(10)->get();

		if($start < $count) $next = $start + 10;

		if(!empty($tasks)){
			foreach($tasks as $task){
				$has_task_box = self::where('old_id', $task->old_id);

				//Если задача есть
				if($has_task_box->exists()){
					$task_box_id = $has_task_box->value('new_id');
					$has_task_files = Capsule::table('tasks_files')->where('task_old_id', $task_box_id);

					//Если файлы есть
					if($has_task_files->exists()){
						$count_task_files = $has_task_files->count();
						$has_task_box_files = self::getTaskBox($task_box_id);

						if($count_task_files > 0){
							if(!empty($has_task_box_files['ufTaskWebdavFiles'])){
								if(count($has_task_box_files['ufTaskWebdavFiles']) != $count_task_files){
									foreach($has_task_files->get() as $file){
										self::setTaskFilesAttached($task_box_id, $file->new_id);
									}
								}
							} else {
								foreach($has_task_files->get() as $file){
									self::setTaskFilesAttached($task_box_id, $file->new_id);
								}
							}
						}
					}

					Capsule::table('tasks_data')->where('id', $task->id)->update(['new_id' => $task_box_id]);
				} else {
					//Подготовка перед добавления задачи
					$task_data = [];

					$task_data['CREATED_DATE'] = $task->created_date;
					$task_data['CHANGED_DATE'] = $task->changed_date;
					$task_data['STATUS'] = $task->status;

					$task_data['TITLE'] = $task->title;
					$task_data['DESCRIPTION'] = $task->description;

					$task_data['CREATED_BY'] = 1;
					$task_data['RESPONSIBLE_ID'] = 1;
					$task_data['CHANGED_BY'] = 1;

					if(!empty($task->changed_by)) $task_data['CHANGED_BY'] = 1;
					if(!empty($task->closed_by)) $task_data['CLOSED_BY'] = 1;
					if(!empty($task->priority)) $task_data['PRIORITY'] = $task->priority;
					if(!empty($task->status_changed_date)) $task_data['STATUS_CHANGED_DATE'] = $task->status_changed_date;
					if(!empty($task->closed_date)) $task_data['CLOSED_DATE'] = $task->closed_date;
					if(!empty($task->date_start)) $task_data['DATE_START'] = $task->date_start;
					if(!empty($task->deadline)) $task_data['DEADLINE'] = $task->deadline;
					if(!empty($task->task_control)) $task_data['TASK_CONTROL'] = $task->task_control;
					if(!empty($task->subordinate)) $task_data['SUBORDINATE'] = $task->subordinate;
					if(!empty($task->favorite)) $task_data['FAVORITE'] = $task->favorite;
					if(!empty($task->allow_change_deadline)) $task_data['ALLOW_CHANGE_DEADLINE'] = $task->allow_change_deadline;

					if(!empty($task->uf_crm_id)){
						$task_data['UF_CRM_TASK'] = $task->uf_crm_type . '_' .  $task->uf_crm_id;
					}

					$has_task_files = Capsule::table('tasks_files')->where('task_old_id', $task_box_id);
					if($has_task_files->exists()){
						$uf_task_webdav_files = [];
						foreach($task_files->get() as $file){
							$uf_task_webdav_files[] = 'n' . $file->new_id;
						}
					}

					if(!empty($uf_task_webdav_files)){
						$task_data['UF_TASK_WEBDAV_FILES'] = $uf_task_webdav_files;
					}

					//Добавить задачу
					$task_box_id = self::setTaskBox($task_data);
					Capsule::table('tasks_data')->where('id', $task->id)->update(['new_id' => $task_box_id]);

					//добавить комментарии
					Comment::setTaskCommentsBoxFromTable($task->old_id);

					unset($task_data);
				}
			}

			unset($tasks);
		}

		if(empty($next)){
			print("Загрузка задач завершена!\r\n");
			return true;
		}

		self::setTaskBoxFromTable($next);
	}

	public static function setTaskFilesAttached($task_id, $file_id){
		Crm::bxBoxCall('tasks.task.files.attach', [
			'taskId' => $task_id,
			'fileId' => $file_id
		]);
	}

	public static function setChekLists($task_box_id, $checklists){
		//$box_batch_list = [];
		$insert_checklist = [];
		foreach($checklists as $checklist){
			$insert_checklist[] = [
				'task_old_id' => $task_box_id,
				'title' => $checklist['TITLE'],
				'created_by' => Crm::getBoxUserId($checklist['CREATED_BY']),
				'is_complete' => $checklist['IS_COMPLETE'],
				'is_important' => $checklist['IS_IMPORTANT'],
				'sort_index' => $checklist['SORT_INDEX'],
				'toggled_by' => $checklist['TOGGLED_BY'],
			];
		}

		if(!empty($insert_checklist)){
			Capsule::table('tasks_checklist')->insert($insert_checklist);
		}
	}

	public static function setTaskFiles($task_id, $task_title, $task_files, $new = false){ //$task_box_id
		$files_box = [];
		$insert_task_files = [];
		foreach($task_files as $key => $file_cloud_id){
			$file_cloud_info = Disk::getFile($file_cloud_id, 'cloud');

			if($file_cloud_info !== false){
				$has_file_db = Capsule::table('tasks_files')->where('old_id', $file_cloud_id);
				if(!$has_file_db->exists()){
					$insert_task_files[] = [
						'old_id' => $file_cloud_id,
						'task_old_id' => $task_id,
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

			unset($file_cloud_info);
		}

		if(!empty($insert_task_files)){
			Capsule::table('tasks_files')->insert($insert_task_files);
			unset($insert_task_files);
			unset($task_files);
		}
	}

	public static function handlerFields($task = []){
		foreach($task as $key => $value){
			switch($key){
				case 'accomplices':
				case 'auditors':
					$users = [];
					foreach($value as $k => $v){
						$users[$k] = Crm::getBoxUserId($v);
					}
					$task[$key] = $users;

					break;
				case 'changedBy':
				case 'createdBy':
				case 'responsibleId':
					$task[$key] = Crm::getBoxUserId($value);
					break;
				case 'accomplicesData':
				case 'auditorsData':
				case 'creator':
				case 'forumId':
				case 'forumTopicId':
				case 'group':
				case 'groupId':
				case 'guid':
				case 'responsible':
				case 'serviceCommentsCount':
				case 'siteId':
				case 'xmlId':
					unset($task[$key]);
					break;
				case 'ufCrmTask':
					if(!empty($task[$key])){
						foreach($value as $crm){
							$crm_explode = explode('_', $crm);
							switch($crm_explode[0]){
								case 'L':
									$lead_box_id = Lead::where('old_id', $crm_explode[1]);
									if($lead_box_id->exists()){
										$crm_data = 'L_' . $lead_box_id->value('new_id');
									}
									break;
								case 'D':
									$deal_box_id = Deal::where('old_id', $crm_explode[1]);
									if($deal_box_id->exists()){
										$crm_data = 'D_' . $deal_box_id->value('new_id');
									}
									break;
								case 'C':
									$contact_box_id = Contact::where('old_id', $crm_explode[1]);
									if($contact_box_id->exists()){
										$crm_data = 'C_' . $contact_box_id->value('new_id');
									}
									break;
							}
						}

						if(!empty($crm_data)){
							$crm_data_explode = explode('_', $crm_data);
							$task['uf_crm_type'] = $crm_data_explode[0];
							$task['uf_crm_id'] = $crm_data_explode[1];
						} else {
							unset($task[$key]);
						}
					}
				default :
					$task[$key] = $value;
					break;
			}
		}

		return $task;
	}

	private static function handlerData($task_cloud){
		$task_data = [];

		$task_data['old_id'] = $task_cloud['id'];
		$task_data['created_date'] = date('Y-m-d H:i:s', strtotime($task_cloud['createdDate']));
		$task_data['changed_date'] = date('Y-m-d H:i:s', strtotime($task_cloud['changedDate']));
		$task_data['status'] = $task_cloud['status'];

		$task_data['title'] = $task_cloud['title'];
		$task_data['description'] = $task_cloud['description'];

		$task_data['created_by'] = $task_cloud['createdBy'];
		$task_data['responsible_id'] = $task_cloud['responsibleId'];
		$task_data['changed_by'] = $task_cloud['changedBy'];

		if(!empty($task_cloud['accomplices'])) $task_data['accomplices'] = json_encode($task_cloud['accomplices']);
		if(!empty($task_cloud['auditors'])) $task_data['auditors'] = json_encode($task_cloud['auditors']);
		if(!empty($task_cloud['closedBy'])) $task_data['closed_by'] = $task_cloud['closedBy'];
		if(!empty($task_cloud['priority'])) $task_data['priority'] = $task_cloud['priority'];
		if(!empty($task_cloud['statusChangedDate'])) $task_data['status_changed_date'] = date('Y-m-d H:i:s', strtotime($task_cloud['statusChangedDate']));
		if(!empty($task_cloud['closedDate'])) $task_data['closed_date'] = date('Y-m-d H:i:s', strtotime($task_cloud['closedDate']));
		if(!empty($task_cloud['dateStart'])) $task_data['date_start'] = date('Y-m-d H:i:s', strtotime($task_cloud['dateStart']));
		if(!empty($task_cloud['deadline'])) $task_data['deadline'] = date('Y-m-d H:i:s', strtotime($task_cloud['deadline']));
		if(!empty($task_cloud['commentsCount'])) $task_data['comments_count'] = $task_cloud['commentsCount'];
		if(!empty($task_cloud['taskControl'])) $task_data['task_control'] = $task_cloud['taskControl'];
		if(!empty($task_cloud['subordinate'])) $task_data['subordinate'] = $task_cloud['subordinate'];
		if(!empty($task_cloud['favorite'])) $task_data['favorite'] = $task_cloud['favorite'];
		if(!empty($task_cloud['viewedDate'])) $task_data['viewed_date'] = date('Y-m-d H:i:s', strtotime($task_cloud['viewedDate']));
		if(!empty($task_cloud['allowChangeDeadline'])) $task_data['allow_change_deadline'] = $task_cloud['allowChangeDeadline'];

		if(!empty($task_cloud['uf_crm_type'])) $task_data['uf_crm_type'] = $task_cloud['uf_crm_type'];
		if(!empty($task_cloud['uf_crm_id'])) $task_data['uf_crm_id'] = $task_cloud['uf_crm_id'];

		return $task_data;
	}

	public static function getCloudTasksID($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ['ID', 'PARENT_ID', 'CREATED_DATE', 'CHANGED_DATE', 'PRIORITY', 'STATUS', 'CREATED_BY', 'RESPONSIBLE_ID', 'CHANGED_BY', 'STATUS_CHANGED_DATE', 'CLOSED_BY', 'CLOSED_DATE', 'DATE_START', 'DEADLINE', 'COMMENTS_COUNT', 'TASK_CONTROL', 'SUBORDINATE', 'FAVORITE', 'VIEWED_DATE'],
			'filter' => ['<CREATED_DATE' => '2024-12-10'],
			'order' => ['ID' => 'DESC'],
			'start' => $start
		];

		$tasks_query = Crm::bxCloudCall('tasks.task.list', $params);

		if(!empty($tasks_query['next'])) $next = $tasks_query['next'];

		if(!empty($tasks_query['result']['tasks'])){
			foreach($tasks_query['result']['tasks'] as $task){
				$task_id = $task['id'];
				$task_data = [];

				$task_data['old_id'] = $task_id;
				$task_data['created_date'] = date('Y-m-d H:i:s', strtotime($task['createdDate']));
				$task_data['changed_date'] = date('Y-m-d H:i:s', strtotime($task['changedDate']));
				$task_data['status'] = $task['status'];
				$task_data['created_by'] = $task['createdBy'];
				$task_data['responsible_id'] = $task['responsibleId'];
				$task_data['changed_by'] = $task['changedBy'];

				if(!empty($task['parentId'])) $task_data['old_parent_id'] = $task['parentId'];
				if(!empty($task['priority'])) $task_data['priority'] = $task['priority'];
				if(!empty($task['statusChangedDate'])) $task_data['status_changed_date'] = date('Y-m-d H:i:s', strtotime($task['statusChangedDate']));
				if(!empty($task['closedBy'])) $task_data['closed_by'] = $task['closedBy'];
				if(!empty($task['closedDate'])) $task_data['closed_date'] = date('Y-m-d H:i:s', strtotime($task['closedDate']));
				if(!empty($task['dateStart'])) $task_data['date_start'] = date('Y-m-d H:i:s', strtotime($task['dateStart']));
				if(!empty($task['deadline'])) $task_data['deadline'] = date('Y-m-d H:i:s', strtotime($task['deadline']));
				if(!empty($task['commentsCount'])) $task_data['comments_count'] = $task['commentsCount'];
				if(!empty($task['taskControl'])) $task_data['task_control'] = $task['taskControl'];
				if(!empty($task['subordinate'])) $task_data['subordinate'] = $task['subordinate'];
				if(!empty($task['favorite'])) $task_data['favorite'] = $task['favorite'];
				if(!empty($task['viewedDate'])) $task_data['viewed_date'] = date('Y-m-d H:i:s', strtotime($task['viewedDate']));

				Capsule::table('tasks_data')->insert($task_data);
				unset($task_data);

				$comments_query = Crm::bxCloudCall('task.commentitem.getlist', [$task_id, ['POST_DATE' => 'asc']]);
				if(!empty($comments_query['result'])){
					$comments_data = [];
					foreach($comments_query['result'] as $comment){
						$comment_id = $comment['ID'];
						$comment_data = [
							'old_id' => $comment_id,
							'task_old_id' => $task_id,
							'author_id' => $comment['AUTHOR_ID'],
							'post_date' => date('Y-m-d H:i:s', strtotime($comment['POST_DATE']))
						];

						if(!empty($comment['ATTACHED_OBJECTS'])){
							$attached_objects = [];
							foreach($comment['ATTACHED_OBJECTS'] as $attached_object){
								$attached_objects[] = $attached_object['FILE_ID'];
							}

							$comment_data['attached_objects'] = json_encode($attached_objects);
						}

						Capsule::table('comments_data')->insert($comment_data);
						unset($comment_data);
					}
				}
			}
		}

		if(empty($next)){
			print("Заполнения базы задач завершено\r\n");
			return true;
		}

		self::getCloudTasksID($next);
	}

	public static function export($user_id, $type, $start = 0, $tasks_data = []){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$params = [
			'select' => ['ID','TITLE','RESPONSIBLE_ID', 'CREATED_DATE','CLOSED_DATE','STATUS'],
			'filter' => ['>CLOSED_DATE' => '2024-09-30', 'STATUS' => 5, $type => $user_id],
			'order'	 => ['CLOSED_DATE' => 'desc'],
			'start' => $start
		];

		$task_query = Crm::bxCloudCall('tasks.task.list', $params);

		if(!empty($task_query['next'])) $next = $task_query['next'];

		if(!empty($task_query['result']['tasks'])){
			$tasks = $task_query['result']['tasks'];

			foreach($tasks as $task){
				$histories_query = Crm::bxCloudCall('tasks.task.history.list', ['taskId' => $task['id']]);

				//print_r($histories_query);

				if(!empty($histories_query['result']['list'])){
					$histories = $histories_query['result']['list'];
					$histories_data = [];

					foreach($histories as $history){
						if(
							$history['user']['id'] != 2398 &&
							$history['field'] == 'STATUS' &&
							$history['value']['to'] == 5 &&
							strtotime($history['createdDate']) >= strtotime('01.10.2024 00:00:00')
						){
							$histories_data = [
								'user' => $history['user'],
								'date' => $history['createdDate']
							];
						}
					}
				}

				if(!empty($histories_data)){
					$tasks_data[] = [
						'task_id' => $task['id'],
						'task_link' => "https://stopzaym.bitrix24.ru/company/personal/user/{$user_id}/tasks/task/view/{$task['id']}/",
						'title'	 => $task['title'],
						'create_date' => $task['createdDate'],
						'histories_data' => $histories_data
					];
				}
			}
		}

		if(empty($next)){
			if(!empty($tasks_data)){
				$user_query = Crm::bxCloudCall('user.get', ['ID' => $user_id]);
				$user = $user_query['result'][0];
				$user_name = $user['LAST_NAME'] . ' ' . $user['NAME'];

				//print_r($tasks_data);

				$table_data = [];
				$table_data[] = ['ID задачи', 'Название', 'Дата создания', 'Кто закрыл', 'Дата закрытия', 'Ссылка'];
				foreach($tasks_data as $task_data){
					$name = $task_data['histories_data']['user']['lastName'] . ' ' . $task_data['histories_data']['user']['name'];
					$table_data[] = [
						$task_data['task_id'],
						$task_data['title'],
						date('d.m.Y H:i:s', strtotime($task_data['create_date'])),
						$name,
						date('d.m.Y H:i:s', strtotime($task_data['histories_data']['date'])),
						$task_data['task_link']
					];
				}

				//Создадим новый файл
				$spreadsheet = new Spreadsheet();
				$spreadsheet->getActiveSheet()->fromArray($table_data, null, 'A1');

				$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
				$folder = 'exports';
				$file_name = "Отчет по задачам {$user_name} - {$type}.xlsx";
				$file_patn = storage_set($folder) . $file_name;
				$writer->save($file_patn);
			}

			print("Экспорт завершен!");
			return true;
		}

		self::export($user_id, $type, $next, $tasks_data);
	}

	public static function setDuplicatesTask(){
		//$result = Capsule::raw('SELECT `old_id`, COUNT(`old_id`) AS `count` FROM `deals` GROUP BY `old_id` HAVING `count` > 1')->get();
		$tasks = Capsule::table('tasks')->selectRaw('old_id, COUNT(`old_id`) AS count')->groupBy('old_id')->having('count', '>', 1)->get();

		if(!empty($tasks)){
			$box_batch_list = [];
			$i = 1;
			foreach($tasks as $task){
				$tasks_query = self::where('old_id', $task->old_id)->offset(1)->limit(50);
				$delete_tasks = $tasks_query;
				$tasks_db = $tasks_query->get();

				foreach($tasks_db as $tasks_box){
					if(!empty($tasks_box->new_id)){
						Capsule::table('tasks_remove')->insert(['new_id' => $tasks_box->new_id]);

						//Удалим связанные комментарии
						Capsule::table('comments')->where('task_new_id', $tasks_box->new_id)->delete();
					}
				}

				if(!empty($box_batch_list)){
					//Crm::bxBoxCallBatch($box_batch_list);
				}


				$delete_tasks->delete();
			}

			self::removeDuplicatesTask();
		}

		print("Удаление дубликатов завершено!\r\n");
		return true;
	}

	public static function removeDuplicatesTask($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$count = Capsule::table('tasks_remove')->count();
		$tasks = Capsule::table('tasks_remove')->offset($start)->limit(50)->get();

		if($start < $count) $next = $start + 50;

		$box_batch_list = [];
		foreach($tasks as $task){
			$box_batch_list[] = [
				'method' => 'tasks.task.delete',
				'params' => ['taskId' => $task->new_id]
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
		self::removeDuplicatesTask($next);
	}

	public static function changeUsersIdInDBTasks($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");

		$count = Capsule::table('tasks_data')->count();
		$tasks = Capsule::table('tasks_data')->select(['id', 'created_by', 'responsible_id', 'changed_by', 'closed_by'])->offset($start)->limit(100)->get();

		if($start < $count) $next = $start + 100;

		if(!empty($tasks)){
			foreach($tasks as $task){
				$update = [];

				if(!empty('created_by')){
					$update['created_by'] = Crm::getBoxUserId($task->created_by);
				}

				if(!empty('responsible_id')){
					$update['responsible_id'] = Crm::getBoxUserId($task->responsible_id);
				}

				if(!empty('changed_by')){
					$update['changed_by'] = Crm::getBoxUserId($task->changed_by);
				}

				if(!empty('closed_by')){
					$update['closed_by'] = Crm::getBoxUserId($task->closed_by);
				}

				if(!empty($update)){
					Capsule::table('tasks_data')->where('id', $task->id)->update($update);
				}
			}
		}

		if(empty($next)){
			print("Удаление задач завершено\r\n");
			return true;
		}

		self::changeUsersIdInDBTasks($next);
	}
}