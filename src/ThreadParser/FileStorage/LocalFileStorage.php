<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\FileStorage;

use phpClub\Entity\File;
use phpClub\ThreadParser\Helper\LocalFileFinder;
use phpClub\ThreadParser\Helper\UploadPathHelper;
use GuzzleHttp\Client as Guzzle;
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
     * @var Guzzle
     */
    private $guzzle;

    /**
     * @param UploadPathHelper $uploadPathHelper
     * @param LocalFileFinder $fileFinder
     * @param Filesystem $filesystem
     * @param Guzzle $guzzle
     */
    public function __construct(
        UploadPathHelper $uploadPathHelper,
        LocalFileFinder $fileFinder,
        Filesystem $filesystem,
        Guzzle $guzzle
    ) {
        $this->uploadPathHelper = $uploadPathHelper;
        $this->filesystem = $filesystem;
        $this->fileFinder = $fileFinder;
        $this->guzzle = $guzzle;
    }

    /**
     * @param File $file
     * @return void
     */
    public function put(File $file)
    {
        $uploadAs = $this->uploadPathHelper->generateUploadPath($file, $absolute = true);
        $thumbUploadAs = $this->uploadPathHelper->generateThumbUploadPath($file, $absolute = true);
        
        if ($file->isRemote()) {
            $this->guzzle->get($file->getRemoteUrl(), ['sink' => $uploadAs]);
            $this->guzzle->get($file->getThumbnailRemoteUrl(), ['sink' => $thumbUploadAs]);
        } else {
            $this->filesystem->copy($this->fileFinder->findAbsolutePathForFile($file), $uploadAs);
            $this->filesystem->copy($this->fileFinder->findThumbAbsolutePathForFile($file), $thumbUploadAs);
        }
    }
}