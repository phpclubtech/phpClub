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
$application->get('/search/{searchQuery}', 'SearchController:searchAction');
$application->get('/login/', 'UsersController:displayAuthAction');
$application->get('/registration/', 'UsersController:displayRegistrationAction');
$application->get('/config/', 'UsersController:displayConfigureAction');

$application->post('/login/', 'UsersController:preformAuthAction');
$application->post('/registration/', 'UsersController:preformRegistrationAction');
$application->post('/config/', 'UsersController:preformConfigureAction');
$application->post('/logout/', 'UsersController:preformLogOutAction');
$application->post('/addarchivelink/', 'ArchiveLinkController:addLinkAction');
$application->post('/removearchivelink/', 'ArchiveLinkController:removeLinkAction');

$application->run();
