<?php
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '100M');

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

use Esvlad\Bx24copytobox\Models\Deal;

//Deal::setDealsDB();
//Deal::tranferListDeals();
//Deal::removeDuplicates();
//Deal::transferElementsDeal();
//Deal::updateCloudDealsToBox();
//Deal::getContactIdByDeal();
//Deal::hasContactIdByDeal();
//Deal::hasContactIdsByDeal();
//Deal::setObservers();
//Deal::exportDate();
//Deal::replacement();
//Deal::replacementDataBase();
//Deal::replacementDealIdInFields();
//Deal::setBoxDealBiz();
//Deal::hasBoxFolderClient();
Deal::compareDealBoxID();

print("Заполнение Базы данных сделок завершен!");