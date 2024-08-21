<?php

use MiladRahimi\PhpRouter\Router;
use Laminas\Diactoros\Response\JsonResponse;

use Esvlad\Bx24copytobox\Controllers\{
    Leads,
};

$router = Router::create();

$router->get('/', function() {
    return 'Home page!';
});


$router->group(['prefix' => '/lead'], function (Router $router) {
    $router->get('/add/{cloud_id}', [Leads::class, 'add']);
    $router->get('/update/{cloud_id}', [Leads::class, 'update']);
});

$router->dispatch();