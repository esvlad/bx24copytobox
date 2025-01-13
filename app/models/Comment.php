<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\User;
use Esvlad\Bx24copytobox\Models\Crm;

class Comment extends Model{
	protected $table = "comments";

	public static function setTaskCommentsBox($task_id, $comments = []){
		foreach($comments as $value){
			$comment_cloud_id = $value['ID'];
			$comment = self::handlerFields($value);
			$comment_box_id = $comment['ID'];
			unset($comment['ID']);

			if($comment_box_id === false){
				$author_id = $comment['AUTHOR_ID'];
				$comment['AUTHOR_ID'] = 1;

				$comment_add = Crm::bxBoxCall('task.commentitem.add', [$task_id, $comment]);
				$comment_box_id = $comment_add['result']['ID'];

				$task_cloud_id = Task::where('new_id', $task_id)->value('old_id');
				self::insert(['task_new_id' => $task_id, 'task_old_id' => $task_cloud_id, 'old_id' => $comment_cloud_id, 'new_id' => $comment_box_id, 'author_id' => $author_id, 'create_comment' => date('Y-m-d H:i:s', strtotime($comment['POST_DATE']))]);
			} else {
				Crm::bxBoxCall('task.commentitem.update', [$task_id, $comment_id, $comment]);
				self::where('task_new_id' => $task_id, 'new_id' => $comment_box_id)->update(['author_id' => $author_id, 'create_comment' => date('Y-m-d H:i:s', strtotime($comment['POST_DATE']))]);
			}
		}
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

	public static function handlerFields($fields){
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
		$comment['POST_DATE'] = $fields['POST_DATE'];

		if(!empty($author_name)){
			$comment['POST_MESSAGE'] = '[B]' . $author_name . ':[/B] ' . self::remove_bbcode($fields['POST_MESSAGE']);
		} else {
			$comment['POST_MESSAGE'] = self::remove_bbcode($fields['POST_MESSAGE']);
		}

		#########################################
		if(!empty($fields['ATTACHED_OBJECTS'])){
			$comment['ATTACHED_OBJECTS'] = self::setCommentsFiles($fields['ATTACHED_OBJECTS']);
		}

		return $comment;
	}

	public static function setCommentsFiles($attached_objects){
		$attached = [];
		foreach($attached_objects as $attached_object){
			//Проверить наличие папки клиента или добавить
			//Проверить наличие папки Файлы к задачам или добавить
			//Проверить наличие папки задачи или добавить
			//$file_box_id = Загрузить файл к задаче
		}

		return $attached;
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