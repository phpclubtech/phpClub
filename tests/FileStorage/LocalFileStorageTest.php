<?php

declare(strict_types=1);

namespace Tests\FileStorage;

use org\bovigo\vfs\vfsStream;
use phpClub\Entity\File;
use phpClub\FileStorage\LocalFileStorage;
use Symfony\Component\Filesystem\Filesystem;
use Tests\AbstractTestCase;

class LocalFileStorageTest extends AbstractTestCase
{
    /**
     * @var LocalFileStorage
     */
    private $fileStorage;

    public function setUp()
    {
        $testDirectory = vfsStream::setup();
        $this->fileStorage = new LocalFileStorage(new Filesystem(), $testDirectory->url());
    }
    
    public function testFileStorage()
    {
        $file = $this->createFile(1);

        $this->assertFalse($this->fileStorage->isFileExist($file->getPath(), '92'));
        
        $newPath = $this->fileStorage->put($file->getPath(), '92');
        
        $this->assertTrue($this->fileStorage->isFileExist($file->getPath(), '92'));
        $this->assertNotEmpty($newPath);
    }

    /**
     * @expectedException \Throwable
     */
    public function testThrowsOnInvalidFilePath()
    {
        $file = new File('not-exists', 'not-exists', $this->createPost(1), 100, 100, 100);
        
        $this->fileStorage->put($file->getPath(), '92');
    }
}
