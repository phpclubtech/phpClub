<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Bootstrap.php';

use phpClub\ThreadParser\Command\ImportThreadsCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new ImportThreadsCommand($di->get('ThreadImporter'), $di->get('DvachApiClient')));
$application->run();
