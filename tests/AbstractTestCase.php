<?php

declare(strict_types=1);

namespace Tests;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\Container;
use Tests\FileStorage\FileStorageMock;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\ThreadImport\ChainManager;
use phpClub\ThreadImport\LastPostUpdater;
use phpClub\ThreadImport\ThreadImporter;
use phpClub\ThreadParser\DvachThreadParser;
use phpClub\Util\FsUtil;

abstract class AbstractTestCase extends TestCase
{
    private static ?Container $container = null;

    public function createThread($id): Thread
    {
        return new Thread($id);
    }

    public function createPost(int $id, Thread $thread = null): Post
    {
        return (new Post($id))
            ->setTitle('title ' . $id)
            ->setAuthor('author ' . $id)
            ->setDate(new \DateTimeImmutable())
            ->setText('text ' . $id)
            ->setThread($thread ?: $this->createThread($id));
    }

    public function createFile(int $id): File
    {
        return (new File())
            ->setPath(__DIR__ . '/FileStorage/1.png')
            ->setThumbPath(__DIR__ . '/FileStorage/2.png')
            ->setHeight(100)
            ->setWidth(200)
            ->setPost($this->createPost($id));
    }

    public function getContainer(): Container
    {
        if (!self::$container) {
            self::$container = require __DIR__ . '/../src/Bootstrap.php';
        }

        return self::$container;
    }

    public function importThreadToDb(string $pathToHtml)
    {
        /** @var DvachThreadParser $parser */
        $parser = $this->getContainer()->get(DvachThreadParser::class);
        $thread = $parser->extractThread(FsUtil::getContents($pathToHtml));
        /** @var LastPostUpdater $lastPostUpdater */
        $lastPostUpdater = $this->createMock(LastPostUpdater::class);
        /** @var ChainManager $chainManager */
        $chainManager = $this->createMock(ChainManager::class);

        $importer = new ThreadImporter(
            new FileStorageMock(),
            $this->getContainer()->get(EntityManager::class),
            $lastPostUpdater,
            $chainManager,
            $this->getContainer()->get(LoggerInterface::class)
        );

        // Do not import images to speed up tests
        $importer->import([$thread], null, true);
    }
}
