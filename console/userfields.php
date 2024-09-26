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

use Esvlad\Bx24copytobox\Models\UserField;
/*
UserField::getCloudUserFieldsToBox('lead');
print("Экспорт пользовательских полей лидов завершен\r\n");
sleep(2);
UserField::getCloudUserFieldsToBox('contact');
print("Экспорт пользовательских полей контактов завершен\r\n");
sleep(2);
UserField::getCloudUserFieldsToBox('deal');
print("Экспорт пользовательских полей сделок завершен\r\n");
print("Экспорт всех пользовательских полей завершен");
*/