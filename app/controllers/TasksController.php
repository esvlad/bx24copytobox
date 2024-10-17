<?php

namespace Esvlad\Bx24copytobox\Controllers;

use Esvlad\Bx24copytobox\Models\Crm;
use Esvlad\Bx24copytobox\Models\Task;
use Esvlad\Bx24copytobox\Models\Comment;

class TasksController{

	public function synchronizationTask($box_id){
		$json = [];
		$cloud_id = Task::where('new_id', $box_id)->value('old_id');
		if(empty($cloud_id)){
			$json = [
				'status' => 'error',
				'error_message' => 'ID облачной задачи в Базе данный отстуствует.'
			];
		} else {
			$task_rest = Crm::bxCloudCall('tasks.task.get', ['taskId' => $cloud_id]);

			if(empty($task_rest['result']['task'])){
				$json = [
					'status' => 'error',
					'error_message' => 'Такой задачи в облакебольше нет, вероятно её удалили.'
				];
			} else {
				$task = Task::handlerFields($task_rest['result']['task']);

				Crm::bxBoxCall('tasks.task.update', [
					'taskId' => $box_id,
					'fields' => $task
				]);

				//Есть ли комментарии
				if((int)$task_rest['result']['task']['commentsCount'] > 0){
					$comment_rest = Crm::bxCloudCall('task.commentitem.getlist', [$cloud_id, ['POST_DATE' => 'asc']]);

					if(!empty($comment_rest['result'])){
						$comments = $comment_rest['result'];
						$batch_list_comments = [];

						foreach($comments as $comment){
							$comment_fields = Comment::handlerFields($comment);
							$comment_id = $comment_fields['ID'];
							unset($comment_fields['ID']);

							if(!empty($comment_fields['NEW']) && $comment_fields['NEW'] === true){
								unset($comment_fields['NEW']);

								$new_comment_id = Crm::bxBoxCall('task.commentitem.add', ['taskId' => $box_id, 'fields' => $comment_fields]);
								if(empty($new_comment_id['error'])){
									Comment::insert([
										'old_id' => $comment_id,
										'new_id' => $new_comment_id,
										'task_old_id' => $cloud_id,
										'task_new_id' => $box_id
									]);
								}
							} else {
								$batch_list_comments[] = [
									'method' => 'task.commentitem.update',
									'params' => ['taskId' => $box_id, 'itemId' => $comment_id, 'fields' => $comment_fields]
								];
							}
						}

						if(!empty($batch_list_comments)){
							Crm::bxBoxCallBatch($batch_list_comments);
						}
					}
				}

				$json['status'] = 'success';
			}
		}

		return $this->toJson($json);
	}

	private function toJson($value, $options = JSON_UNESCAPED_UNICODE){
		header('Content-Type: application/json');
		echo json_encode($value, $options);
	}
}