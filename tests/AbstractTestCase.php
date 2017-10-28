<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use phpClub\Entity\{File, Post, Thread};

abstract class AbstractTestCase extends TestCase
{
    public function createThread($id): Thread
    {
        return new Thread($id);
    }

    public function createPost($id): Post
    {
        return new Post(
            $id,
            'title ' . $id,
            'author ' . $id,
            new \DateTimeImmutable(),
            'text ' . $id,
            $this->createThread($id)
        );
    }

    public function createFile($id): File
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
}