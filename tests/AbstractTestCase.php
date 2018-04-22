<?php

declare(strict_types=1);

namespace Tests;

use Doctrine\ORM\EntityManager;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\ThreadImport\ChainManager;
use phpClub\ThreadImport\LastPostUpdater;
use phpClub\ThreadImport\ThreadImporter;
use phpClub\ThreadParser\DvachThreadParser;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Slim\Container;
use Tests\FileStorage\FileStorageMock;

abstract class AbstractTestCase extends TestCase
{
    private static $container;

    public function createThread($id): Thread
    {
        return new Thread($id);
    }

    public function createPost($id, Thread $thread = null): Post
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
            self::$container = require_once __DIR__ . '/../src/Bootstrap.php';
        }

        return self::$container;
    }

    public function importThreadToDb(string $pathToHtml)
    {
        /** @var DvachThreadParser $parser */
        $parser = $this->getContainer()->get(DvachThreadParser::class);
        $thread = $parser->extractThread(file_get_contents($pathToHtml));

        $importer = new ThreadImporter(
            new FileStorageMock(),
            $this->getContainer()->get(EntityManager::class),
            $this->createMock(LastPostUpdater::class),
            $this->createMock(ChainManager::class),
            $this->createMock(CacheInterface::class)
        );

        $importer->import([$thread]);
    }
}
