<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use phpClub\Entity\{File, Post, Thread};
use Slim\Container;

abstract class AbstractTestCase extends TestCase
{
    private $container;
    
    public function createThread($id): Thread
    {
        return new Thread($id);
    }

    public function createPost($id, Thread $thread = null): Post
    {
        return new Post(
            $id,
            'title ' . $id,
            'author ' . $id,
            new \DateTimeImmutable(),
            'text ' . $id,
            $thread ?: $this->createThread($id)
        );
    }

    public function createFile(int $id): File
    {
        return new File(
            __DIR__ . '/FileStorage/1.png',
            __DIR__ . '/FileStorage/2.png',
            $this->createPost($id),
            100,
            200,
            120
        );
    }

    public function getContainer(): Container
    {
        if (!$this->container) {
            $this->container = require_once __DIR__ . '/../src/Bootstrap.php';
        }
        
        return $this->container;
    }
}