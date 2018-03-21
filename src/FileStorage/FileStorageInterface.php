<?php

declare(strict_types=1);

namespace phpClub\FileStorage;

interface FileStorageInterface
{
    /**
     * @param string $path      Absolute path or remote url
     * @param string $directory Prefix
     *
     * @return string New path on the storage (public link)
     */
    public function put(string $path, string $directory): string;

    /**
     * @param string $path      Absolute path or remote url
     * @param string $directory Prefix
     *
     * @return bool
     */
    public function isFileExist(string $path, string $directory): bool;

    /**
     * @param string $path
     *
     * @return int
     */
    public function getFileSize(string $path): int;
}
