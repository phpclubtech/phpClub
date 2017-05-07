<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 1:22 PM
 */

require(__DIR__ . '/../src/Bootstrap.php');

use Slim\App;

$application = new App($di);

/* Register application routes */
$application->get('/', 'BoardController:indexAction');
$application->get('/pr/res/{thread:[0-9]+}.html', 'BoardController:threadAction');
$application->get('/pr/chain/{post:[0-9]+}', 'BoardController:chainAction');
$application->get('/search/{searchQuery}', 'SearchController:searchAction');

$application->map(['GET', 'POST'], '/login/', 'UsersController:authAction');
$application->map(['GET', 'POST'], '/registration/', 'UsersController:registrationAction');
$application->map(['GET', 'POST'], '/config/', 'UsersController:configureAction');

$application->post('/logout/', 'UsersController:logOutAction');
$application->post('/addarchivelink/', 'ArchiveLinkController:addLinkAction');
$application->post('/removearchivelink/', 'ArchiveLinkController:removeLinkAction');

$application->run();
