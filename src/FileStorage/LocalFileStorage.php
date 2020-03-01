<?php

declare(strict_types=1);

namespace phpClub\FileStorage;

use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

class LocalFileStorage implements FileStorageInterface
{
    private Filesystem $filesystem;
    private Client $guzzle;
    private string $uploadRoot;

    public function __construct(Filesystem $filesystem, Client $guzzle, string $uploadRoot)
    {
        $this->filesystem = $filesystem;
        $this->guzzle = $guzzle;
        $this->uploadRoot = $uploadRoot;
    }

    public function put(string $path, string $directory): string
    {
        $relativePath = sprintf("/%s/%s", $directory, basename($path));
        $targetPath = $this->uploadRoot . $relativePath;

        if (!$this->isFileExist($path, $directory)) {
            if ($this->doesLookLikeUrl($path)) {
                /** @var resource $content */
                $content = $this->guzzle->get($path)->getBody();
                $this->filesystem->dumpFile($targetPath, $content);
            } else {
                $this->filesystem->copy($path, $targetPath);
            }
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

    private function doesLookLikeUrl(string $path): bool 
    {
        return preg_match("!^[a-z0-9_\-]://!ui", $path) > 0;
    }
}
