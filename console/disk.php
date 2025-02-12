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

require dirname(__DIR__) . "/vendor/autoload.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use Esvlad\Bx24copytobox\Models\Database;
$db = new Database();

use Esvlad\Bx24copytobox\Models\Disk;

//Disk::uploadFileTaskToBox();
//Disk::uploadFileCommentsToBox();

print("Закачка файлов задач завершена!");