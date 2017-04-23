<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Migrations;

use App\Threader;
use App\Authorizer;
use App\ArchiveLinkController;
use App\Router;

$container = new Container();

$container['EntityManager'] = function () {
    $paths = array(__DIR__ . "/");
    $isDevMode = false;

    $config = parse_ini_file(__DIR__ . '/config.ini');

    $metaConfig = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
    $metaConfig->setAutoGenerateProxyClasses(\Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

    $entityManager = EntityManager::create($config, $metaConfig);

    return $entityManager;
};


$container['Authorizer'] = function ($c) {
    return new Authorizer($c['EntityManager']);
};

$container['Threader'] = function ($c) {
    return new Threader($c['EntityManager'], $c['Authorizer']);
};

$container['ArchiveLinkController'] = function ($c) {
    return new ArchiveLinkController($c['EntityManager'], $c['Authorizer']);
};

$container['Router'] = function ($c) {
    return new Router($c['Threader'], $c['Authorizer'], $c['ArchiveLinkController']);
};