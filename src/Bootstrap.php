<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 2:05 PM
 */

require(__DIR__ . '/../vendor/autoload.php');

use Slim\Container;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use phpClub\Service\View;
use phpClub\Controller\BoardController;
use phpClub\Service\Threader;
use phpClub\Service\Authorizer;

$slimConfig = [
    'settings' => [
        'displayErrorDetails' => ini_get("display_errors"),
    ],
];

$di = new Container($slimConfig);

$di['EntityManager'] = function (Container $di): EntityManager {
    $paths = array(__DIR__ . "/Entities/");
    $isDevMode = false;

    $config = $di->get('config');

    $metaConfig = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
    $metaConfig->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

    $entityManager = EntityManager::create($config, $metaConfig);

    return $entityManager;
};

$di['config'] = function (): array {
    return parse_ini_file(__DIR__ . '/../config/config.ini');
};

$di['View'] = function (): View {
    return new View(__DIR__ . '/../templates');
};

$di['Threader'] = function (Container $di): Threader {
    return new Threader($di->get('EntityManager'), $di->get('Authorizer'));
};

$di['Authorizer'] = function (Container $di): Authorizer {
    return new Authorizer($di->get('EntityManager'));
};

$di['BoardController'] = function (Container $di): BoardController {
    return new BoardController($di->get('Threader'), $di->get('View'));
};

$di['PHPErrorHandler'] = function (): callable {
    return function (int $errno, string $errstr, string $errfile, int $errline): void {
        if (!(error_reporting() & $errno)) {
            return;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    };
};

set_error_handler($di->get('PHPErrorHandler'));
