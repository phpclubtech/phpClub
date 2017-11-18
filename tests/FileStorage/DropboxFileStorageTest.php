<?php

declare(strict_types=1);

namespace Tests\FileStorage;

use phpClub\Entity\File;
use phpClub\FileStorage\DropboxFileStorage;
use Spatie\Dropbox\Client;
use GuzzleHttp\Psr7\Response;
use Spatie\Dropbox\Exceptions\BadRequest;
use Tests\AbstractTestCase;

class DropboxFileStorageTest extends AbstractTestCase
{
    public function testUpload()
    {
        $dropBoxFileStorage = new DropboxFileStorage($this->getDropboxClientMock());
        
        $file = $this->createFile(1);
        
        $this->assertFalse($dropBoxFileStorage->isFileExist($file->getPath(), '92'));
        
        $newPath = $dropBoxFileStorage->put($file->getPath(), '92');
        
        $this->assertTrue($dropBoxFileStorage->isFileExist($file->getPath(), '92'));
        $this->assertNotEmpty($newPath);
    }

    /**
     * @expectedException \Throwable
     */
    public function testThrowsOnInvalidFilePath()
    {
        $dropBoxFileStorage = new DropboxFileStorage($this->getDropboxClientMock());
        
        $file = new File('non-exists', 'non-exists', $this->createPost(1), 100, 100, 100);

        $dropBoxFileStorage->put($file->getPath(), '92');
    }

    private function getDropboxClientMock(): Client
    {
        return new class() extends Client
        {
            private $files = [];

            public function __construct()
            {

            }

            public function upload(string $path, $contents, $mode = 'add'): array
            {
                $this->files[$path] = $contents;

                return [];
            }

            public function createSharedLinkWithSettings(string $path, array $settings = [])
            {
                return ['url' => uniqid()];
            }

            public function getMetadata(string $path): array
            {
                if (isset($this->files[$path])) {
                    return ['path' => $path];
                }

                throw new BadRequest(new Response());
            }
        };
    }
}