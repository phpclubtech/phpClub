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
     * @param bool $isAbsolute
     * @return string
     */
    public function generateUploadPath(File $file, bool $isAbsolute = false): string
    {
        $root = $isAbsolute ? $this->uploadRoot : '';

        return $root . '/' . $file->getPost()->getThread()->getId() . '/' . $file->getRelativePath();
    }

    /**
     * @param File $file
     * @param bool $isAbsolute
     * @return string
     */
    public function generateThumbUploadPath(File $file, bool $isAbsolute = false): string
    {
        $root = $isAbsolute ? $this->uploadRoot : '';
        
        return $root . '/thumb/' . $this->generateUploadPath($file);
    }
}