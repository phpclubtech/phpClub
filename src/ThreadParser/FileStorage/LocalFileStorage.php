<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\FileStorage;

use phpClub\Entity\File;
use phpClub\ThreadParser\Helper\LocalFileFinder;
use phpClub\ThreadParser\Helper\UploadPathHelper;
use Symfony\Component\Filesystem\Filesystem;

class LocalFileStorage implements FileStorageInterface
{
    /**
     * @var UploadPathHelper
     */
    private $uploadPathHelper;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LocalFileFinder
     */
    private $fileFinder;

    /**
     * @param UploadPathHelper $uploadPathHelper
     * @param LocalFileFinder $fileFinder
     * @param Filesystem $filesystem
     */
    public function __construct(
        UploadPathHelper $uploadPathHelper,
        LocalFileFinder $fileFinder,
        Filesystem $filesystem
    ) {
        $this->uploadPathHelper = $uploadPathHelper;
        $this->filesystem = $filesystem;
        $this->fileFinder = $fileFinder;
    }

    /**
     * @param File $file
     * @return void
     */
    public function put(File $file)
    {
        $uploadAs = $this->uploadPathHelper->generateAbsolutePath($file);
        $thumbUploadAs = $this->uploadPathHelper->generateAbsoluteThumbPath($file);
        
        $this->filesystem->copy(
            $file->getRemoteUrl() ?: $this->fileFinder->findAbsolutePath($file),
            $uploadAs
        );

        $this->filesystem->copy(
            $file->getThumbnailRemoteUrl() ?: $this->fileFinder->findThumbAbsolutePath($file),
            $thumbUploadAs
        );
    }
}