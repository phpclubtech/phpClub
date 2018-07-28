<?php

$di = require_once __DIR__ . '/../src/Bootstrap.php';

use Slim\App;

$app = new App($di);
require_once __DIR__ . '/../config/routes.php';
$app->run();
