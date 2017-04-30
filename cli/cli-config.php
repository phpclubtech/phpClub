<?php
require_once __DIR__ . '/src/init.php';

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($container['EntityManager']);