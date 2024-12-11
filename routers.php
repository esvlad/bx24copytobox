<?php

use MiladRahimi\PhpRouter\Router;
use Laminas\Diactoros\Response\JsonResponse;

use Esvlad\Bx24copytobox\Controllers\{
    LeadsController,
    DealsController,
    ContactsController,
    TasksController
};

use Esvlad\Bx24copytobox\Models\Crm;

$router = Router::create();

$router->get('/', function() {
    return 'Home page!';
});

$router->group(['prefix' => '/lead'], function (Router $router) {
    $router->post('/addtobox/{cloud_lead_id}', [LeadsController::class, 'addtobox']);
    $router->get('/synctobox/{box_lead_id}/user/{box_user_id}', [LeadsController::class, 'synchronization']);
});

$router->group(['prefix' => '/deal'], function (Router $router) {
    $router->post('/addtobox/{cloud_deal_id}', [DealsController::class, 'addtobox']);
    $router->get('/synctobox/{box_deal_id}/user/{box_user_id}', [DealsController::class, 'synchronization']);
});

$router->group(['prefix' => '/contact'], function (Router $router) {
    $router->post('/addtobox/{cloud_contact_id}', [ContactsController::class, 'addtobox']);
    $router->get('/synctobox/{box_contact_id}/user/{box_user_id}', [ContactsController::class, 'synchronization']);
    $router->get('/synctobox_address/{box_contact_id}/user/{box_user_id}', [ContactsController::class, 'synchronizationAddress']);
});

$router->get('/task/synctobox/{box_task_id}/user/{box_user_id}', [TasksController::class, 'synchronization']);

try{
    $router->dispatch();
} catch(\Exception $e){
    Crm::setLog(
        ['errors' => $e],
        'errors'
    );
}