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
        return File::create("img{$id}.png", "img-thumb{$id}.png", 100, 200, $this->createPost($id));
    }
}