<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 2:05 PM
 */

require(__DIR__ . '/../vendor/autoload.php');

use Slim\Container;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use phpClub\Controller\BoardController;
use phpClub\Controller\SearchController;
use phpClub\Controller\UsersController;
use phpClub\Controller\ArchiveLinkController;
use phpClub\Service\View;
use phpClub\Service\Threader;
use phpClub\Service\Authorizer;
use phpClub\Service\Searcher;
use phpClub\Service\Linker;

$slimConfig = [
    'settings' => [
        'displayErrorDetails' => ini_get("display_errors"),
    ],
];

$di = new Container($slimConfig);

/* General services section */
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


/* Application services section */
$di['View'] = function (): View {
    return new View(__DIR__ . '/../templates');
};

$di['Threader'] = function (Container $di): Threader {
    return new Threader($di->get('EntityManager'), $di->get('Authorizer'));
};

$di['Authorizer'] = function (Container $di): Authorizer {
    return new Authorizer($di->get('EntityManager'));
};

$di['Searcher'] = function (Container $di): Searcher {
    return new Searcher($di->get('EntityManager'));
};

$di['Linker'] = function (Container $di): Linker {
    return new Linker($di->get('EntityManager'));
};


/* Application controllers section */
$di['BoardController'] = function (Container $di): BoardController {
    return new BoardController($di->get('Threader'), $di->get('Authorizer'), $di->get('View'));
};

$di['SearchController'] = function (Container $di): SearchController {
    return new SearchController($di->get('Searcher'), $di->get('View'));
};

$di['UsersController'] = function (Container $di): UsersController {
    return new UsersController($di->get('Authorizer'), $di->get('View'));
};

$di['ArchiveLinkController'] = function (Container $di): ArchiveLinkController {
    return new ArchiveLinkController($di->get('Authorizer'), $di->get('Linker'), $di->get('View'));
};


/* Error handler for altering PHP errors output */
$di['PHPErrorHandler'] = function () {
    return function (int $errno, string $errstr, string $errfile, int $errline) {
        if (!(error_reporting() & $errno)) {
            return;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    };
};

$di['notFoundHandler'] = function (Container $di) {
    return function (Request $request, Response $response) use ($di) {
        return $di->get('View')
            ->renderToResponse($response, 'notFound')
            ->withStatus(404);
    };
};

set_error_handler($di->get('PHPErrorHandler'));
