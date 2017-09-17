<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\FileStorage;

use phpClub\Entity\File;
use phpClub\ThreadParser\Helper\LocalFileFinder;
use Symfony\Component\Filesystem\Filesystem;

class LocalFileStorage implements FileStorageInterface
{
    /**
     * @var string
     */
    private $uploadRoot;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LocalFileFinder
     */
    private $fileFinder;

    /**
     * @param Filesystem $filesystem
     * @param LocalFileFinder $fileFinder
     * @param string $uploadRoot
     */
    public function __construct(Filesystem $filesystem, LocalFileFinder $fileFinder, string $uploadRoot)
    {
        $this->filesystem = $filesystem;
        $this->fileFinder = $fileFinder;
        $this->uploadRoot = $uploadRoot;
    }

    /**
     * @param File $file
     * @return void
     */
    public function put(File $file)
    {
        $saveAs = $this->uploadRoot . '/' . $file->getRelativePath();
        
        if (!$this->filesystem->exists($saveAs)) {
            $this->filesystem->copy(
                $file->getRemoteUrl() ?: $this->fileFinder->findAbsolutePath($file),
                $saveAs
            );
        }

        $thumbSaveAs = $this->uploadRoot . '/' . $file->getThumbnailRelativePath();
        
        if (!$this->filesystem->exists($thumbSaveAs)) {
            $this->filesystem->copy(
                $file->getThumbnailRemoteUrl() ?: $this->fileFinder->findThumbAbsolutePath($file),
                $thumbSaveAs
            );
        }
    }
}