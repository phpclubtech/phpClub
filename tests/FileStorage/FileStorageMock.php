<?php

declare(strict_types=1);

namespace Tests\FileStorage;

use phpClub\FileStorage\FileStorageInterface;

class FileStorageMock implements FileStorageInterface
{
    public function put(string $path, string $directory): string
    {
        return __DIR__ . '/1.png';
    }

    public function isFileExist(string $path, string $directory): bool
    {
        return false;
    }

    public function getFileSize(string $path): int
    {
        return (int) (filesize(__DIR__ . '/1.png') / 1024);
    }
}
