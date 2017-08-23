<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\Helper;

use phpClub\Entity\File;

/**
 * Searches files in old threads archive by name and returns absolute path
 */
class LocalFileFinder
{
    /**
     * @var string
     */
    private $oldThreadsRoot;

    public function __construct(string $oldThreadsRoot)
    {
        assert(is_dir($oldThreadsRoot));

        $this->oldThreadsRoot = $oldThreadsRoot;
    }

    public function findAbsolutePath(File $file): string
    {
        assert(!$file->isRemote());

        return '';
    }

    public function findThumbAbsolutePath(File $file): string
    {
        assert(!$file->isRemote());

        return '';
    }
}