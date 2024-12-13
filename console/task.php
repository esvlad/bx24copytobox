<?php
ini_set('max_execution_time', 0);
set_time_limit(0);

function preprint($data){
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}


function storage_set($path){
    $dir = dirname(__DIR__);

    if(!file_exists($dir . '/uploads/' . $path)){
        mkdir($dir . '/uploads/' . $path, 0777, True);
    }

    return $dir  . '/uploads/' . $path . '/';
}

require dirname(__DIR__) . "/vendor/autoload.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use Esvlad\Bx24copytobox\Models\Database;
$db = new Database();

use Esvlad\Bx24copytobox\Models\Task;
use Esvlad\Bx24copytobox\Models\Comment;
//use Esvlad\Bx24copytobox\Controllers\TasksController;
//$task = new TasksController;
//$task->setTask();
//$task->exportCloudTask(20774);
//Task::export(13348, 'RESPONSIBLE_ID');
//Task::export(13348, 'ACCOMPLICE');
//Task::setDuplicatesTask();
//Task::removeDuplicatesTask();
Task::changeUsersIdInDBTasks();

//Comment::setDuplicatesComments();
//Comment::removeDuplicatesComments();


//print_r($task->synchronizationTask(121742));
//print("Заполнение Базы данных лидов завершен!");