<?php
require_once __DIR__ . '/../src/Bootstrap.php';

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($di[\Doctrine\ORM\EntityManager::class]);
