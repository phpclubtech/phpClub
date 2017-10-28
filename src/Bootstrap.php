<?php

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use phpClub\Controller\ArchiveLinkController;
use phpClub\Controller\BoardController;
use phpClub\Controller\SearchController;
use phpClub\Controller\UsersController;
use phpClub\Entity\{Post, Thread, User};
use phpClub\Entity\ArchiveLink;
use phpClub\Service\Authorizer;
use phpClub\Service\Linker;
use phpClub\Service\Searcher;
use phpClub\ThreadParser\Command\ImportThreadsCommand;
use phpClub\ThreadParser\FileStorage\{DropboxFileStorage, LocalFileStorage};
use phpClub\ThreadParser\ThreadImporter;
use phpClub\ThreadParser\ThreadProvider\DvachApiClient;
use Slim\Container;
use Slim\Http\{Request, Response};
use Slim\Views\PhpRenderer as View;
use Symfony\Component\Cache\Simple\AbstractCache;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Doctrine\Common\Cache\FilesystemCache as DoctrineCache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use GuzzleHttp\{HandlerStack, Client};
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

$slimConfig = [
    'settings' => [
        'displayErrorDetails' => ini_get("display_errors"),
    ],
];

$di = new Container($slimConfig);

/* General services section */
$di['EntityManager'] = function (Container $di): EntityManager {
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

$di['config'] = function (): array {
    return parse_ini_file(__DIR__ . '/../config/config.ini');
};

/* Application services section */
$di['DropboxClient'] = function ($di) {
    return new \Spatie\Dropbox\Client($di['config']['dropbox_token']);
};

$di['DropboxFileStorage'] = function ($di) {
    return new DropboxFileStorage($di['DropboxClient']);
};

$di['ThreadRepository'] = function (Container $di) {
    return $di->get('EntityManager')->getRepository(Thread::class);
};

$di['LocalFileStorage'] = function (Container $di) {
    return new LocalFileStorage(new Symfony\Component\Filesystem\Filesystem(), __DIR__ . '/../public');
};

$di['ThreadImporter'] = function (Container $di) {
    // TODO: use file_storage from config
    return new ThreadImporter($di['LocalFileStorage'], $di['EntityManager'], $di['ThreadRepository']);
};

$di['ImportThreadsCommand'] = function (Container $di) {
    return new ImportThreadsCommand($di['ThreadImporter'], $di['DvachApiClient']);
};

$di['Guzzle'] = function () {
    return new Client();
};

$di['Guzzle.cacheable'] = function () {
    $ttl = 3600;
    $stack = HandlerStack::create();
    $cacheStorage = new DoctrineCacheStorage(new DoctrineCache('/tmp/'));
    $stack->push(new CacheMiddleware(new GreedyCacheStrategy($cacheStorage, $ttl)));

    return new Client(['handler' => $stack]);
};

$di['DvachApiClient'] = function ($di) {
    return new DvachApiClient($di['Guzzle.cacheable']);
};

$di['UrlGenerator'] = function (Container $di) {
    return new \phpClub\Service\UrlGenerator($di->get('router'));
};

$di['View'] = function (Container $di): View {
    return new View(__DIR__ . '/../templates', [
        // Shared variables
        'urlGenerator' => $di['UrlGenerator'],
    ]);
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
    return new BoardController($di->get('Authorizer'), $di->get('View'), $di->get('Cache'), $di);
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
