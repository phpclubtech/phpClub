<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Cache\FilesystemCache as DoctrineCache;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use phpClub\BoardClient\ArhivachClient;
use phpClub\BoardClient\DvachClient;
use phpClub\Command\ImportThreadsCommand;
use phpClub\Controller\BoardController;
use phpClub\Controller\SearchController;
use phpClub\Controller\UsersController;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\Entity\User;
use phpClub\FileStorage\LocalFileStorage;
use phpClub\Pagination\PaginationRenderer;
use phpClub\Repository\PostRepository;
use phpClub\Repository\RefLinkRepository;
use phpClub\Repository\ThreadRepository;
use phpClub\Service\Authorizer;
use phpClub\Service\UrlGenerator;
use phpClub\ThreadImport\LastPostUpdater;
use phpClub\ThreadImport\RefLinkGenerator;
use phpClub\ThreadImport\ThreadImporter;
use phpClub\ThreadParser\ArhivachThreadParser;
use phpClub\ThreadParser\DateConverter;
use phpClub\ThreadParser\DvachThreadParser;
use Psr\SimpleCache\CacheInterface;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\FilesystemCache;

(new Dotenv\Dotenv(__DIR__ . '/../'))->load();

$slimConfig = [
    'settings' => [
        'displayErrorDetails' => getenv('APP_ENV') !== 'prod',
        'fileStorage'         => LocalFileStorage::class,
    ],
    'connections' => [
        'mysql' => [
            'driver'   => 'pdo_mysql',
            'charset'  => 'utf8',
            'host'     => getenv('DB_HOST'),
            'user'     => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'dbname'   => getenv('DB_NAME'),
        ],
        'mysql_test' => [
            'driver'   => 'pdo_mysql',
            'charset'  => 'utf8',
            'host'     => getenv('DB_HOST'),
            'user'     => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'dbname'   => getenv('TEST_DB_NAME'),
        ],
    ],
];

$di = new Container($slimConfig);

$di[EntityManager::class] = function (Container $di): EntityManager {
    $paths = [__DIR__ . '/Entity/'];
    $isDevMode = false;

    $config = getenv('APP_ENV') === 'test' ? $di['connections']['mysql_test'] : $di['connections']['mysql'];

    $metaConfig = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);

    $namingStrategy = new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy();
    $metaConfig->setNamingStrategy($namingStrategy);

    $metaConfig->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

    $entityManager = EntityManager::create($config, $metaConfig);

    return $entityManager;
};

$di[\Doctrine\ORM\EntityManagerInterface::class] = function (Container $di) {
    return $di[EntityManager::class];
};

$di[RefLinkGenerator::class] = function (Container $di) {
    return new RefLinkGenerator($di[EntityManager::class]);
};

$di[LastPostUpdater::class] = function (Container $di) {
    return new LastPostUpdater($di[EntityManager::class]->getConnection());
};

$di[ArhivachClient::class] = function (Container $di) {
    return new ArhivachClient(
        $di[Client::class],
        $di[ArhivachThreadParser::class],
        getenv('ARHIVACH_EMAIL'),
        getenv('ARHIVACH_PASSWORD')
    );
};

$di[ArhivachThreadParser::class] = function ($di) {
    return new ArhivachThreadParser($di[DateConverter::class]);
};

$di[DvachThreadParser::class] = function ($di) {
    return new DvachThreadParser($di[DateConverter::class]);
};

$di[DateConverter::class] = function () {
    return new DateConverter();
};

$di[ThreadRepository::class] = function (Container $di) {
    return $di->get(EntityManager::class)->getRepository(Thread::class);
};

$di[PostRepository::class] = function (Container $di) {
    return $di->get(EntityManager::class)->getRepository(Post::class);
};

$di[RefLinkRepository::class] = function (Container $di) {
    return $di->get(EntityManager::class)->getRepository(\phpClub\Entity\RefLink::class);
};

$di[LocalFileStorage::class] = function () {
    return new LocalFileStorage(new Symfony\Component\Filesystem\Filesystem(), __DIR__ . '/../public');
};

$di[ThreadImporter::class] = function (Container $di) {
    return new ThreadImporter(
        $di[$di['settings']['fileStorage']],
        $di[EntityManager::class],
        $di[LastPostUpdater::class],
        $di[RefLinkGenerator::class],
        $di[CacheInterface::class]
    );
};

$di[ImportThreadsCommand::class] = function (Container $di) {
    return new ImportThreadsCommand(
        $di[ThreadImporter::class],
        $di[DvachClient::class],
        $di[ArhivachClient::class],
        $di[DvachThreadParser::class]
    );
};

$di[Client::class] = function () {
    return new Client();
};

$di['Guzzle.cacheable'] = function () {
    $ttl = 3600;
    $stack = HandlerStack::create();
    $cacheStorage = new DoctrineCacheStorage(new DoctrineCache('/tmp/'));
    $stack->push(new CacheMiddleware(new GreedyCacheStrategy($cacheStorage, $ttl)));

    return new Client(['handler' => $stack]);
};

$di[DvachClient::class] = function ($di) {
    return new DvachClient($di[Client::class]);
};

$di[UrlGenerator::class] = function (Container $di) {
    return new UrlGenerator($di->get('router'), $di[ArhivachClient::class]);
};

$di[PhpRenderer::class] = function (Container $di): PhpRenderer {
    return new PhpRenderer(__DIR__ . '/../templates', [
        // Shared variables
        'urlGenerator' => $di->get(UrlGenerator::class),
        'paginator'    => $di->get(PaginationRenderer::class),
    ]);
};

$di[PaginationRenderer::class] = function (Container $di): PaginationRenderer {
    return new PaginationRenderer($di->get('router'));
};

$di[Authorizer::class] = function (Container $di): Authorizer {
    return new Authorizer($di->get(EntityManager::class)->getRepository(User::class));
};

$di[CacheInterface::class] = function (): CacheInterface {
    return getenv('APP_ENV') === 'prod' ? new FilesystemCache() : new ArrayCache();
};

/* Application controllers section */
$di['BoardController'] = function (Container $di): BoardController {
    return new BoardController(
        $di->get(Authorizer::class),
        $di->get(PhpRenderer::class),
        $di->get(CacheInterface::class),
        $di->get(ThreadRepository::class),
        $di->get(RefLinkGenerator::class),
        $di->get(RefLinkRepository::class),
        $di->get(PaginationRenderer::class)
    );
};

$di['SearchController'] = function (Container $di): SearchController {
    return new SearchController(
        $di->get(Authorizer::class),
        $di->get(PostRepository::class),
        $di->get(PaginationRenderer::class),
        $di->get(PhpRenderer::class)
    );
};

$di['UsersController'] = function (Container $di): UsersController {
    return new UsersController($di->get(Authorizer::class), $di->get(PhpRenderer::class));
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
        return $di->get(PhpRenderer::class)
            ->render($response, '/notFound.phtml', [])
            ->withStatus(404);
    };
};

set_error_handler($di->get('PHPErrorHandler'));

return $di;
