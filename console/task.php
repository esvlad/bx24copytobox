<?php
ini_set('max_execution_time', 0);
set_time_limit(0);

function preprint($data){
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

require "../vendor/autoload.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use Esvlad\Bx24copytobox\Models\Database;
$db = new Database();

$task = new Esvlad\Bx24copytobox\Controllers\TasksController;
print_r($task->synchronizationTask(121742));
//print("Заполнение Базы данных лидов завершен!");