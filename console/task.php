<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('log_errors', 'On');
ini_set('error_log', 'D:\Webservers\OSPanel\domains\bx24copytobox\logs/php-errors.log');
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
//Task::changeUsersIdInDBTasks();
Task::getTaskCloudToBox();

//Comment::setDuplicatesComments();
//Comment::removeDuplicatesComments();


//print_r($task->synchronizationTask(121742));
//print("Заполнение Базы данных лидов завершен!");