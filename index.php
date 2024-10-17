<?php
ini_set('max_execution_time', 0);
set_time_limit(0);

function preprint($data){
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

require "vendor/autoload.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Esvlad\Bx24copytobox\Models\Database;
$db = new Database();

require "routers.php";