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

$application->run();
