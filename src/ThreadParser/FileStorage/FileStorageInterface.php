<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\FileStorage;

use phpClub\Entity\FileReference;
use phpClub\ThreadParser\Dto\File as FileDto;

interface FileStorageInterface
{
    /**
     * @param string $path       Absolute path or remote url
     * @param string $directory  Prefix
     * @return string            New path on the storage
     */
    public function put(string $path, string $directory): string;

    /**
     * @param string $path       Absolute path or remote url
     * @param string $directory  Prefix
     * @return bool
     */
    public function isFileExist(string $path, string $directory): bool;
}
