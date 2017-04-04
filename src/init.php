<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Migrations;

use App\ActiveRecord;
use App\Thread;
use App\Post;
use App\File;

use App\Threader;
use App\Router;

$container = new Container();

$container['EntityManager'] = function () {
    $paths = array(__DIR__ . "/");
    $isDevMode = false;

    $config = parse_ini_file(__DIR__ . '/config.ini');

    $metaConfig = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
    $metaConfig->setAutoGenerateProxyClasses(\Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_ALWAYS);

    $entityManager = EntityManager::create($config, $metaConfig);

    return $entityManager;
};

$container['Threader'] = function ($c) {
    return new Threader($c['EntityManager']);
};

$container['Router'] = function ($c) {
    return new Router($c['Threader']);
};