<?php

/** @var \Slim\App $app */

/* Register application routes */
$app->get('/', 'BoardController:indexAction')->setName('index');
$app->get('/pr/res/{thread:[0-9]+}.html', 'BoardController:threadAction')->setName('thread');
$app->get('/pr/chain/{post:[0-9]+}/', 'BoardController:chainAction')->setName('chain');
$app->get('/search/', 'SearchController:searchAction')->setName('search');

$app->map(['GET', 'POST'], '/login/', 'UsersController:authAction');
$app->map(['GET', 'POST'], '/registration/', 'UsersController:registrationAction');
$app->map(['GET', 'POST'], '/config/', 'UsersController:configureAction');

$app->post('/logout/', 'UsersController:logOutAction');

/* API */
$app->get('/api/board/get/message/{id:[0-9]+}/', 'ApiController:getPost');
