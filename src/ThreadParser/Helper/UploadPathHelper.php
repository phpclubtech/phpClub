<?php

declare(strict_types = 1);

namespace phpClub\ThreadParser\Helper;

use phpClub\Entity\File;

class UploadPathHelper
{
    /**
     * @var string
     */
    private $uploadRoot;

    /**
     * @param string $uploadRoot
     */
    public function __construct(string $uploadRoot)
    {
        $this->uploadRoot = rtrim($uploadRoot, '/');
    }

    /**
     * @param File $file
     * @return string
     */
    public function generateRelativePath(File $file): string
    {
        return '/' . $file->getPost()->getThread()->getId() . '/' . $file->getRelativePath();
    }

    /**
     * @param File $file
     * @return string
     */
    public function generateAbsolutePath(File $file): string
    {
        return $this->uploadRoot . $this->generateRelativePath($file);
    }

    /**
     * @param File $file
     * @return string
     */
    public function generateRelativeThumbPath(File $file): string
    {
        return '/thumb' . $this->generateRelativePath($file);
    }

    /**
     * @param File $file
     * @return string
     */
    public function generateAbsoluteThumbPath(File $file): string
    {
        return $this->uploadRoot . $this->generateRelativeThumbPath($file);
    }
}