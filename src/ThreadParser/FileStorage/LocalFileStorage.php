<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\FileStorage;

use Symfony\Component\Filesystem\Filesystem;

class LocalFileStorage implements FileStorageInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $uploadRoot;

    /**
     * @param Filesystem $filesystem
     * @param string $uploadRoot
     */
    public function __construct(Filesystem $filesystem, string $uploadRoot)
    {
        $this->filesystem = $filesystem;
        $this->uploadRoot = $uploadRoot;
    }

    /**
     * @param string $path
     * @param string $directory
     * @return string
     */
    public function put(string $path, string $directory): string
    {
        $relativePath = '/' . $directory . '/' . basename($path);

        if (!$this->isFileExist($path, $directory)) {
            $this->filesystem->copy($path, $this->uploadRoot . $relativePath);
        }

        return $relativePath;
    }

    /**
     * @param string $path
     * @param string $directory
     * @return bool
     */
    public function isFileExist(string $path, string $directory): bool
    {
        return $this->filesystem->exists($this->uploadRoot . '/' . $directory . '/' . basename($path));
    }
}