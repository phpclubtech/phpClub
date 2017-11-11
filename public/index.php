<?php

$di = require_once __DIR__ . '/../src/Bootstrap.php';

use Slim\App;

$application = new App($di);

/* Register application routes */
$application->get('/', 'BoardController:indexAction');
$application->get('/pr/res/{thread:[0-9]+}.html', 'BoardController:threadAction')->setName('thread');
$application->get('/pr/chain/{post:[0-9]+}', 'BoardController:chainAction')->setName('chain');
$application->get('/search/{searchQuery}', 'SearchController:searchAction');

$application->map(['GET', 'POST'], '/login/', 'UsersController:authAction');
$application->map(['GET', 'POST'], '/registration/', 'UsersController:registrationAction');
$application->map(['GET', 'POST'], '/config/', 'UsersController:configureAction');

$application->post('/logout/', 'UsersController:logOutAction');
$application->post('/addarchivelink/', 'ArchiveLinkController:addLinkAction');
$application->get('/removearchivelink/{archiveLinkID:[0-9]+}', 'ArchiveLinkController:removeLinkAction');

$application->run();
