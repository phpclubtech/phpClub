<?php

declare(strict_types=1);

namespace phpClub\FileStorage;

use Symfony\Component\Filesystem\Filesystem;

class LocalFileStorage implements FileStorageInterface
{
    private Filesystem $filesystem;
    private string $uploadRoot;

    public function __construct(Filesystem $filesystem, string $uploadRoot)
    {
        $this->filesystem = $filesystem;
        $this->uploadRoot = $uploadRoot;
    }

    public function put(string $path, string $directory): string
    {
        $relativePath = sprintf("/%s/%s", $directory, basename($path));

        if (!$this->isFileExist($path, $directory)) {
            $this->filesystem->copy($path, $this->uploadRoot . $relativePath);
        }

        return $relativePath;
    }

    public function isFileExist(string $path, string $directory): bool
    {
        return $this->filesystem->exists(sprintf("%s/%s/%s", $this->uploadRoot, $directory, basename($path)));
    }

    public function getFileSize(string $path): int
    {
        return (int) (filesize($this->uploadRoot . $path) / 1_024);
    }
}
