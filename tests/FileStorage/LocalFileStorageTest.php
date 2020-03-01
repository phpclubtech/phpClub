<?php

declare(strict_types=1);

namespace Tests\FileStorage;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Tests\AbstractTestCase;
use org\bovigo\vfs\vfsStream;
use phpClub\Entity\File;
use phpClub\FileStorage\LocalFileStorage;

class LocalFileStorageTest extends AbstractTestCase
{
    private LocalFileStorage $fileStorage;

    public function setUp(): void
    {
        $testDirectory = vfsStream::setup();
        $this->fileStorage = new LocalFileStorage(
            new Filesystem(), 
            $this->mockGuzzle(), // Should not be called
            $testDirectory->url()
        );
    }

    private function mockGuzzle(): Client
    {
        $mockHandler = new MockHandler([]);
        $stack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $stack]);
        return $client;
    }

    public function testFileStorage()
    {
        $file = $this->createFile(1);

        $this->assertFalse($this->fileStorage->isFileExist($file->getPath(), '92'));

        $newPath = $this->fileStorage->put($file->getPath(), '92');

        $this->assertTrue($this->fileStorage->isFileExist($file->getPath(), '92'));
        $this->assertNotEmpty($newPath);
    }

    public function testThrowsOnInvalidFilePath()
    {
        $this->expectException(FileNotFoundException::class);

        $file = (new File())
            ->setPath('not-exists')
            ->setThumbPath('not-exists')
            ->setHeight(100)
            ->setWidth(200)
            ->setPost($this->createPost(1));

        $this->fileStorage->put($file->getPath(), '92');
    }
}
