<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use phpClub\BoardClient\ArhivachClient;
use phpClub\BoardClient\DvachClient;
use phpClub\Command\ImportThreadsCommand;
use phpClub\Command\RebuildChainsCommand;
use phpClub\Controller\ApiController;
use phpClub\Controller\BoardController;
use phpClub\Controller\SearchController;
use phpClub\Controller\UsersController;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\Entity\User;
use phpClub\FileStorage\LocalFileStorage;
use phpClub\Pagination\PaginationRenderer;
use phpClub\Repository\ChainRepository;
use phpClub\Repository\PostRepository;
use phpClub\Repository\ThreadRepository;
use phpClub\Service\Authorizer;
use phpClub\Service\UrlGenerator;
use phpClub\Slim\MonologErrorHandler;
use phpClub\Slim\NotFoundHandler;
use phpClub\ThreadImport\ChainManager;
use phpClub\ThreadImport\LastPostUpdater;
use phpClub\ThreadImport\ThreadImporter;
use phpClub\ThreadParser\ArhivachThreadParser;
use phpClub\ThreadParser\DateConverter;
use phpClub\ThreadParser\DvachThreadParser;
use phpClub\ThreadParser\Internal\CloudflareEmailDecoder;
use phpClub\ThreadParser\MarkupConverter;
use phpClub\ThreadParser\MDvachThreadParser;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Slim\Container;
use Slim\Views\PhpRenderer;
use Symfony\Component\Cache\Simple\ArrayCache;

(new Dotenv\Dotenv(__DIR__ . '/../'))->load();

$slimConfig = require __DIR__ . '/../config/settings.php';

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

$di[EntityManagerInterface::class] = function (Container $di) {
    return $di[EntityManager::class];
};

$di[ChainManager::class] = function (Container $di) {
    return new ChainManager($di[EntityManager::class], $di[PostRepository::class]);
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

$di['ArhivachMarkupConverter'] = function () {
    return new MarkupConverter(true);
};

$di['DvachMarkupConverter'] = function () {
    return new MarkupConverter(false);
};

$di[ArhivachThreadParser::class] = function ($di) {
    $tz = new \DateTimeZone('Europe/Moscow');
    $dateConverter = new DateConverter($tz);

    return new ArhivachThreadParser($dateConverter, $di['ArhivachMarkupConverter'], $di[CloudflareEmailDecoder::class]);
};

$di[DvachThreadParser::class] = function ($di) {
    $tz = new \DateTimeZone('Europe/Moscow');
    $dateConverter = new DateConverter($tz);

    return new DvachThreadParser($dateConverter, $di['DvachMarkupConverter'], $di[CloudflareEmailDecoder::class]);
};

$di[MDvachThreadParser::class] = function ($di) {
    $tz = new \DateTimeZone('Europe/Moscow');
    $dateConverter = new DateConverter($tz);

    return new MDvachThreadParser($dateConverter, $di['DvachMarkupConverter'], $di[CloudflareEmailDecoder::class]);
};

$di[CloudflareEmailDecoder::class] = function () {
    return new CloudflareEmailDecoder();
};

$di[ThreadRepository::class] = function (Container $di) {
    return $di->get(EntityManager::class)->getRepository(Thread::class);
};

$di[PostRepository::class] = function (Container $di) {
    return $di->get(EntityManager::class)->getRepository(Post::class);
};

$di[ChainRepository::class] = function (Container $di) {
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
        $di[ChainManager::class],
        $di[CacheInterface::class]
    );
};

$di[ImportThreadsCommand::class] = function (Container $di) {
    return new ImportThreadsCommand(
        $di[ThreadImporter::class],
        $di[DvachClient::class],
        $di[ArhivachClient::class],
        $di[DvachThreadParser::class],
        $di[MDvachThreadParser::class],
        $di[ArhivachThreadParser::class]
    );
};

$di[RebuildChainsCommand::class] = function (Container $di) {
    return new RebuildChainsCommand($di[ChainManager::class], $di[ThreadRepository::class]);
};

$di[Client::class] = function () {
    return new Client([
        'timeout' => 30,
    ]);
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
        'urlGenerator'   => $di->get(UrlGenerator::class),
        'paginator'      => $di->get(PaginationRenderer::class),
        'arhivachClient' => $di->get(ArhivachClient::class),
    ]);
};

$di[PaginationRenderer::class] = function (Container $di): PaginationRenderer {
    return new PaginationRenderer($di->get('router'));
};

$di[Authorizer::class] = function (Container $di): Authorizer {
    return new Authorizer($di->get(EntityManager::class)->getRepository(User::class));
};

$di[CacheInterface::class] = function (): CacheInterface {
    // TODO: fix pagination for file cache
    return new ArrayCache();
};

$di['SphinxConnection'] = function (Container $di) {
    $pdo = new \PDO($di['connections']['sphinx']['dsn']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    return $pdo;
};

$di[LoggerInterface::class] = function (Container $di): LoggerInterface {
    $logger = new Logger($di['logger']['name']);
    $logger->pushProcessor(new UidProcessor());
    $formatter = new LineFormatter();
    $formatter->includeStacktraces(true);
    $rotatingFileHandler = new RotatingFileHandler($di['logger']['path'], 20, $di['logger']['level']);
    $rotatingFileHandler->setFormatter($formatter);
    $logger->pushHandler($rotatingFileHandler);
    $logger->pushHandler(new SlackWebhookHandler(getenv('SLACK_WEBHOOK_URL')));

    return $logger;
};

$di['notFoundHandler'] = function (Container $di): NotFoundHandler {
    return new NotFoundHandler($di->get(PhpRenderer::class));
};

$di['errorHandler'] = function (Container $di): MonologErrorHandler {
    return new MonologErrorHandler($di[LoggerInterface::class], $di[\Slim\Handlers\Error::class]);
};

$di[\Slim\Handlers\Error::class] = function (Container $di) {
    return new \Slim\Handlers\Error($di->get('settings')['displayErrorDetails']);
};

/* Application controllers section */
$di['BoardController'] = function (Container $di): BoardController {
    return new BoardController(
        $di->get(Authorizer::class),
        $di->get(PhpRenderer::class),
        $di->get(CacheInterface::class),
        $di->get(ThreadRepository::class),
        $di->get(ChainRepository::class),
        $di->get(PaginationRenderer::class)
    );
};

$di['SearchController'] = function (Container $di): SearchController {
    return new SearchController(
        $di->get(Authorizer::class),
        $di->get(PostRepository::class),
        $di->get(PaginationRenderer::class),
        $di->get(PhpRenderer::class),
        $di->get('SphinxConnection')
    );
};

$di['UsersController'] = function (Container $di): UsersController {
    return new UsersController($di->get(Authorizer::class), $di->get(PhpRenderer::class));
};

$di['ApiController'] = function (Container $di): ApiController {
    return new ApiController($di->get(PostRepository::class));
};

/* Error handler for altering PHP errors output */
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    if (!(error_reporting() & $errno)) {
        return;
    }

    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

return $di;
