<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 2:05 PM
 */

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use phpClub\Controller\ArchiveLinkController;
use phpClub\Controller\BoardController;
use phpClub\Controller\SearchController;
use phpClub\Controller\UsersController;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\Entity\RefLink;
use phpClub\Entity\Thread;
use phpClub\Entity\User;
use phpClub\Entity\ArchiveLink;
use phpClub\Service\Authorizer;
use phpClub\Service\Linker;
use phpClub\Service\Searcher;
use phpClub\Service\Threader;
use phpClub\ThreadParser\ThreadProvider\DvachApiClient;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer as View;
use Symfony\Component\Cache\Simple\AbstractCache;
use Symfony\Component\Cache\Simple\FilesystemCache;

$slimConfig = [
    'settings' => [
        'displayErrorDetails' => ini_get("display_errors"),
    ],
];

$di = new Container($slimConfig);

/* General services section */
$di['EntityManager'] = function (Container $di): EntityManager{
    $paths     = array(__DIR__ . "/Entity/");
    $isDevMode = false;

    $config = $di->get('config');

    $metaConfig = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);

    $namingStrategy = new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy();
    $metaConfig->setNamingStrategy($namingStrategy);

    $metaConfig->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

    $entityManager = EntityManager::create($config, $metaConfig);

    return $entityManager;
};

$di['config'] = function (): array{
    return parse_ini_file(__DIR__ . '/../config/config.ini');
};

/* Application services section */
$di['DropboxClient'] = function ($di) {
    return new \Spatie\Dropbox\Client($di['config']['dropbox_token']);
};

$di['Guzzle'] = function () {
    return new GuzzleHttp\Client();
};

$di['EventManager'] = function () {
    return new Zend\EventManager\EventManager();
};

$di['DvachApiClient'] = function ($di) {
    return new DvachApiClient($di['Guzzle'], $di['EventManager']);
};

$di['DvachApiClient.cacheable'] = function ($di) {
    return DvachApiClient::createCacheable($di['EventManager']);
};

$di['View'] = function (): View {
    return new View(__DIR__ . '/../templates');
};

$di['Authorizer'] = function (Container $di): Authorizer {
    return new Authorizer($di->get('EntityManager')->getRepository(User::class));
};

$di['Searcher'] = function (Container $di): Searcher {
    return new Searcher($di->get('EntityManager')->getRepository(Post::class));
};

$di['Linker'] = function (Container $di): Linker {
    return new Linker(
        $di->get('EntityManager')->getRepository(ArchiveLink::class),
        $di->get('EntityManager')->getRepository(Thread::class)
    );
};

$di["Cache"] = function (Container $di): AbstractCache {
    return new FilesystemCache();
};

/* Application controllers section */
$di['BoardController'] = function (Container $di): BoardController {
    return new BoardController($di->get('Threader'), $di->get('Authorizer'), $di->get('View'), $di->get('Cache'));
};

$di['SearchController'] = function (Container $di): SearchController {
    return new SearchController($di->get('Searcher'), $di->get('Authorizer'), $di->get('View'));
};

$di['UsersController'] = function (Container $di): UsersController {
    return new UsersController($di->get('Authorizer'), $di->get('View'));
};

$di['ArchiveLinkController'] = function (Container $di): ArchiveLinkController {
    return new ArchiveLinkController($di->get('Authorizer'), $di->get('Linker'));
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
            ->render($response, '/notFound.phtml', [])
            ->withStatus(404);
    };
};

set_error_handler($di->get('PHPErrorHandler'));
