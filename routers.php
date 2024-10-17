<?php

use MiladRahimi\PhpRouter\Router;
use Laminas\Diactoros\Response\JsonResponse;

use Esvlad\Bx24copytobox\Controllers\{
    DealsController,
    TasksController
};

$router = Router::create();

$router->get('/', function() {
    return 'Home page!';
});


$router->group(['prefix' => '/deal'], function (Router $router) {
    $router->post('/add/cloud_id/{cloud_id}', [DealsController::class, 'addtobox']);
    $router->get('/sync/cloud_id/{box_id}', [DealsController::class, 'synchronizationDeal']);
    $router->get('/sync/contact/{box_id}', [DealsController::class, 'synchronizationContact']);
});

$router->get('/task/{box_id}/sync', [TasksController::class, 'synchronizationTask']);

$router->dispatch();