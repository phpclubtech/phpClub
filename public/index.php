<?php

$di = require_once __DIR__ . '/../src/Bootstrap.php';

use Slim\App;

$application = new App($di);

/* Register application routes */
$application->get('/', 'BoardController:indexAction')->setName('index');
$application->get('/pr/res/{thread:[0-9]+}.html', 'BoardController:threadAction')->setName('thread');
$application->get('/pr/chain/{post:[0-9]+}/', 'BoardController:chainAction')->setName('chain');
$application->get('/search/', 'SearchController:searchAction')->setName('search');

$application->map(['GET', 'POST'], '/login/', 'UsersController:authAction');
$application->map(['GET', 'POST'], '/registration/', 'UsersController:registrationAction');
$application->map(['GET', 'POST'], '/config/', 'UsersController:configureAction');

$application->post('/logout/', 'UsersController:logOutAction');

/* API */
$application->get('/api/board/get/message/{id:[0-9]+}/', 'ApiController:getPost');

$application->run();
