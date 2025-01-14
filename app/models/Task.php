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

	public static function getTaskCloudToBox($start = 0){
		print(date('d.m.Y H:i:s') . " Выполнено шагов - 1000" . $start . "\r\n");
		Capsule::table('counters')->where('type', 'task')->update(['start' => $start]);

		$params = [
			'select' => ['*', 'UF_*'],
			'filter' => ["ID" => "267334"],
			'order' => ['ID' => 'DESC'],
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
				print_r($task_cloud);

				if($hasTaskDB->exists()){
					$task_box_id = $hasTaskDB->value('new_id');

					$task_box = self::getTaskBox($task_box_id);
					print_r($task_box);

					//Проверить наличие чек-листа и добавить к задаче
					if(!empty($task_cloud['checklist'])){
						self::setChekLists($task_box_id, $task_cloud['checklist']);
					}

					//Проверить наличие файлов и добавить к задаче
					if(!empty($task_cloud['ufTaskWebdavFiles'])){
						self::setTaskFiles($task_box_id, $task_cloud['ufTaskWebdavFiles']);
					}

					if($task_box['commentsCount'] != $task_cloud['commentsCount']){
						$task_comments_cloud = Comment::getTaskComments($task['id']);
						$task_comments_box = Comment::getTaskComments($task_box_id, true);

						if(!empty($task_comments_box)){
							foreach($task_comments_box as $task_comment_box){
								Crm::bxBoxCall('task.commentitem.delete', [$task_box_id, $task_comment_box['ID']]);
								$hasComment = Comment::where('new_id', $task_comment_box['ID']);
								if($hasComment->exists()) $hasComment->delete();
							}
						}

						if(!empty($task_comments_cloud)){
							Comment::setTaskCommentsBox($task_comments_cloud);
						}

						unset($task_comments_cloud);
						unset($task_comments_box);
					}

					unset($task_box);					
				} else {
					$task_data = [];

					$task_data['old_id'] = $task_cloud['id'];
					unset($task_cloud['id']);

					$task_data['created_date'] = date('Y-m-d H:i:s', strtotime($task_cloud['createdDate']));
					$task_data['changed_date'] = date('Y-m-d H:i:s', strtotime($task_cloud['changedDate']));
					$task_data['status'] = $task_cloud['status'];

					$task_data['created_by'] = $task_cloud['createdBy'];
					$task_cloud['createdBy'] = 1;
					$task_data['responsible_id'] = $task_cloud['responsibleId'];
					$task_cloud['responsibleId'] = 1;
					$task_data['changed_by'] = $task_cloud['changedBy'];
					$task_cloud['changedBy'] = 1;

					if(!empty($task_cloud['checklist'])){
						$task_cloud_checklist = $task_cloud['checklist'];
						unset($task_cloud['checklist']);
					}

					if(!empty($task_cloud['ufTaskWebdavFiles'])){
						$task_cloud_files = $task_cloud['ufTaskWebdavFiles'];
						unset($task_cloud['ufTaskWebdavFiles']);
					}

					if(!empty($task_cloud['accomplices'])){
						$task_data['accomplices'] = $task_cloud['accomplices'];
						unset($task_cloud['accomplices']);
					}

					if(!empty($task_cloud['auditors'])){
						$task_data['auditors'] = $task_cloud['auditors'];
						unset($task_cloud['auditors']);
					}

					if(!empty($task_cloud['closedBy'])){
						$task_data['closed_by'] = $task_cloud['closedBy'];
						$task_cloud['closedBy'] = 1;
					}

					if(!empty($old_parent_id)){
						$task_data['old_parent_id'] = $old_parent_id;
						$task_data['new_parent_id'] = $task_cloud['parentId'];
					}

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


					$task_box_id = self::setTaskBox($task_cloud);
					$task_data['new_id'] = $task_box_id;
					self::insert($task_data);
					unset($task_data);

					//Проверить наличие чек-листа и добавить к задаче
					if(!empty($task_cloud_checklist)){
						self::setChekLists($task_box_id, $task_cloud_checklist);
					}

					//Проверить наличие файлов и добавить к задаче
					if(!empty($task_cloud_files)){
						self::setTaskFiles($task_box_id, $task_cloud['title'], $task_cloud_files, true);
					}
					
					//Проверить наличие комментариев и добавить к задаче
					$task_comments_cloud = Comment::getTaskComments($task_box_id);
					if(!empty($task_comments_cloud)){
						Comment::setTaskCommentsBox($task_box_id, $task_comments_cloud);
						unset($task_comments_cloud);
					}
				}

				unset($task_cloud);
			}
		}

		unset($result);

		return true;

		/*if(empty($next)){
			print("Заполнения базы задач завершено\r\n");
			return true;
		}

		self::getTaskCloudToBox($next);*/
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

		return $result['result']['task']['id'];
	}

	public static function setChekLists($task_box_id, $checklists){
		$task_box = Crm::bxBoxCall('task.checklistitem.getlist', [$task_box_id]);

		if(!empty($task_box['result'])){
			if(count($task_box['result']) == count($checklists)){
				return true;
			} else {
				$box_batch_list = [];
				foreach($task_box['result'] as $checklist_box){
					$box_batch_list[] = [
						'method' => 'task.checklistitem.delete',
						'params' => [$task_box_id, $checklist_box['ID']]
					];
				}
				Crm::bxBoxCallBatch($box_batch_list);
				unset($box_batch_list);
			}			
		}

		unset($task_box);

		$box_batch_list = [];
		foreach($checklists as $checklist){
			$box_batch_list[] = [
				'method' => 'task.checklistitem.add',
				'params' => [
					$task_box_id,
					[
						'TITLE' => $checklist['TITLE'],
						'CREATED_BY' => Crm::getBoxUserId($checklist['CREATED_BY']),
						'IS_COMPLETE' => $checklist['IS_COMPLETE'],
						'IS_IMPORTANT' => $checklist['IS_IMPORTANT'], 
						'SORT_INDEX' => $checklist['SORT_INDEX'],
						'TOGGLED_BY' => $checklist['TOGGLED_BY'],
						'PARENT_ID' => '$result[0]'
					]
				]
			];
		}

		Crm::bxBoxCallBatch($box_batch_list);
				unset($box_batch_list);
	}

	public static function setTaskFiles($task_box_id, $task_title, $task_files, $new = false){
		//Добавить папку
		$folder_id = Disk::setFolderBox($this->task_folder_id, $task_title);

		if($new === false){
			$task_box = Crm::bxBoxCall('tasks.task.get', [
				'taskId' => $task_box_id,
				'select' => ['ID', 'UF_*']
			]);

			if(!empty($task_box['ufTaskWebdavFiles'])){
				$task_files_box = [];
				foreach($task_box['ufTaskWebdavFiles'] as $key => $file_box_id){
					$file_box_info = Disk::getFile($file_box_id, 'box');
					$task_files_box[] = $file_box_info['NAME'];
				}
			}

			unset($task_box);
		}

		$files_box = [];
		foreach($task_files as $key => $file_cloud_id){
			$file_cloud_info = Disk::getFile($file_cloud_id, 'cloud');

			//Проверить существует ли файл (чтобы не загружать повторно)
			if(!empty($task_files_box)){
				$has_file_box = in_array($file_cloud_info['NAME'], $task_files_box);
			}

			//Добавляем файл
			if(empty($has_file_box)){
				$file_box = Disk::setFileBox($folder_id, $file_cloud_info['NAME'], $file_cloud_info['DOWNLOAD_URL']);
				$files_box[] = $file_box['ID'];
			}

			unset($file_cloud_info);
			unset($task_files_box);
		}
		
		//Добавить файлы к задаче
		/*if(!empty($files_box)){
			$box_batch_list = [];
			foreach($files_box as $key => $file_id){
				$box_batch_list[] = [
					'method' => 'tasks.task.files.attach',
					'params' => [
						'taskId' => $task_box_id,
						'fileId' => $file_id
					]
				];
			}

			Crm::bxBoxCallBatch($box_batch_list);
			
			unset($files_box);
			unset($box_batch_list);
		}*/
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
						$crm_data = [];
						foreach($value as $crm){
							$crm_explode = explode('_', $crm);
							switch($crm_explode[0]){
								case 'L':
									$lead_box_id = Lead::where('old_id', $crm_explode[1]);
									if($lead_box_id->exists()){
										$crm_data[] = 'L_' . $lead_box_id->value('new_id');
									} else {
										//Добавить лид
									}
									break;
								case 'D':
									$deal_box_id = Deal::where('old_id', $crm_explode[1]);
									if($deal_box_id->exists()){
										$crm_data[] = 'D_' . $deal_box_id->value('new_id');
									} else {
										//Добавить сделку
									}
									break;
								case 'C':
									$contact_box_id = Contact::where('old_id', $crm_explode[1]);
									if($contact_box_id->exists()){
										$crm_data[] = 'C_' . $contact_box_id->value('new_id');
									} else {
										//Добавить контакт
									}
									break;
							}
						}

						$task[$key] = $crm_data;
					}
				default :
					$task[$key] = $value;
					break;
			}
		}

		return $task;
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