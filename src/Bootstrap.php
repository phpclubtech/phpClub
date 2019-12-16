<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Dotenv\Dotenv;
use Foolz\SphinxQL\Drivers\Pdo\Connection;
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
use phpClub\Entity\Post;
use phpClub\Entity\RefLink;
use phpClub\Entity\Thread;
use phpClub\FileStorage\LocalFileStorage;
use phpClub\Pagination\PaginationRenderer;
use phpClub\Repository\ChainRepository;
use phpClub\Repository\PostRepository;
use phpClub\Repository\ThreadRepository;
use phpClub\Service\UrlGenerator;
use phpClub\Slim\ErrorHandler;
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
use phpClub\Util\Environment;
use Psr\Log\LoggerInterface;
use Slim\Container;
use Slim\Handlers\Error;
use Slim\Views\PhpRenderer;
use Symfony\Component\Filesystem\Filesystem;

(new Dotenv(__DIR__ . '/../'))->load();

$slimConfig = require __DIR__ . '/../config/settings.php';

$di = new Container($slimConfig);

$di[EntityManager::class] = function (Container $di): EntityManager {
    $paths = [__DIR__ . '/Entity/'];
    $isDevMode = false;
    $config = Environment::isTest() ? $di['connections']['mysql_test'] : $di['connections']['mysql'];
    $cache = Environment::isProd() ? new PhpFileCache(sys_get_temp_dir()) : new ArrayCache();
    $proxyDir = sys_get_temp_dir();
    $metaConfig = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache);
    $metaConfig->setNamingStrategy(new UnderscoreNamingStrategy());
    $metaConfig->setProxyDir($proxyDir);
    $metaConfig->setAutoGenerateProxyClasses(true);

    return EntityManager::create($config, $metaConfig);
};

$di[EntityManagerInterface::class] = fn (Container $di) => $di[EntityManager::class];

$di[ChainManager::class] = fn (Container $di) => new ChainManager($di[EntityManager::class], $di[PostRepository::class]);

$di[LastPostUpdater::class] = fn (Container $di) => new LastPostUpdater($di[EntityManager::class]->getConnection());

$di[ArhivachClient::class] = fn (Container $di) => new ArhivachClient(
    $di[Client::class],
    $di[ArhivachThreadParser::class]
);

$di['ArhivachMarkupConverter'] = fn () => new MarkupConverter(true);

$di['DvachMarkupConverter'] = fn () => new MarkupConverter(false);

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

$di[CloudflareEmailDecoder::class] = fn () => new CloudflareEmailDecoder();

$di[ThreadRepository::class] = fn (Container $di) => $di->get(EntityManager::class)->getRepository(Thread::class);

$di[PostRepository::class] = fn (Container $di) => $di->get(EntityManager::class)->getRepository(Post::class);

$di[ChainRepository::class] = fn (Container $di) => $di->get(EntityManager::class)->getRepository(RefLink::class);

$di[LocalFileStorage::class] = fn () => new LocalFileStorage(new Filesystem(), __DIR__ . '/../public');

$di[ThreadImporter::class] = fn (Container $di) => new ThreadImporter(
    $di[$di['settings']['fileStorage']],
    $di[EntityManager::class],
    $di[LastPostUpdater::class],
    $di[ChainManager::class]
);

$di[ImportThreadsCommand::class] = fn (Container $di) => new ImportThreadsCommand(
    $di[ThreadImporter::class],
    $di[DvachClient::class],
    $di[ArhivachClient::class],
    $di[DvachThreadParser::class],
    $di[MDvachThreadParser::class],
    $di[ArhivachThreadParser::class]
);

$di[RebuildChainsCommand::class] = fn (Container $di) => new RebuildChainsCommand($di[ChainManager::class], $di[ThreadRepository::class]);

$di[Client::class] = fn () => new Client([
    'timeout' => 30,
]);

$di[DvachClient::class] = fn ($di) => new DvachClient($di[Client::class]);

$di[UrlGenerator::class] = fn (Container $di) => new UrlGenerator($di->get('router'), $di[ArhivachClient::class]);

$di[PhpRenderer::class] = fn (Container $di): PhpRenderer => new PhpRenderer(__DIR__ . '/../templates', [
    // Shared variables
    'urlGenerator' => $di->get(UrlGenerator::class),
    'paginator' => $di->get(PaginationRenderer::class),
    'arhivachClient' => $di->get(ArhivachClient::class),
]);

$di[PaginationRenderer::class] = fn (Container $di): PaginationRenderer => new PaginationRenderer($di->get('router'));

$di['SphinxConnection'] = function (Container $di) {
    $connection = new Connection();
    $config = parse_url($di['connections']['sphinx']['dsn']);
    if (!$config) {
        throw new Exception('Invalid Sphinx configuration');
    }
    $connection->setParams($config);

    return $connection;
};

$di[LoggerInterface::class] = function (Container $di): LoggerInterface {
    $logger = new Logger($di['logger']['name']);
    $logger->pushProcessor(new UidProcessor());
    $formatter = new LineFormatter();
    $formatter->includeStacktraces(true);
    $rotatingFileHandler = new RotatingFileHandler($di['logger']['path'], 20, $di['logger']['level']);
    $rotatingFileHandler->setFormatter($formatter);
    $logger->pushHandler($rotatingFileHandler);
    if (Environment::isProd()) {
        $url = getenv('SLACK_WEBHOOK_URL');
        if (!$url) {
            throw new Exception('Invalid SLACK_WEBHOOK_URL');
        }
        $logger->pushHandler(new SlackWebhookHandler($url));
    }

    return $logger;
};

$di['notFoundHandler'] = fn (Container $di): NotFoundHandler => new NotFoundHandler($di->get(PhpRenderer::class));

$di['errorHandler'] = fn (Container $di): ErrorHandler => new ErrorHandler($di[LoggerInterface::class], $di[Error::class], $di['notFoundHandler']);

$di[Error::class] = fn (Container $di) => new Error($di->get('settings')['displayErrorDetails']);

/* Application controllers section */
$di[BoardController::class] = fn (Container $di): BoardController => new BoardController(
    $di->get(PhpRenderer::class),
    $di->get(ThreadRepository::class),
    $di->get(ChainRepository::class),
    $di->get(PaginationRenderer::class),
    $di->get(UrlGenerator::class)
);

$di[SearchController::class] = fn (Container $di): SearchController => new SearchController(
    $di->get(PostRepository::class),
    $di->get(PaginationRenderer::class),
    $di->get(PhpRenderer::class),
    $di->get('SphinxConnection'),
    $di->get(UrlGenerator::class)
);

$di[ApiController::class] = fn (Container $di): ApiController => new ApiController($di->get(PostRepository::class));

/* Error handler for altering PHP errors output */
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    if (!(error_reporting() & $errno)) {
        return;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

return $di;
