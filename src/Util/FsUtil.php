<?php

declare(strict_types=1);

namespace phpClub\Util;

class FsUtil
{
    /**
     * Safe version of the file_get_contents.
     *
     * @throws \Exception
     */
    public static function getContents(string $path): string
    {
        $contents = file_get_contents($path);
        if (!$contents) {
            throw new \Exception(sprintf('Unable to read file contents, path %s', $path));
        }
        return $contents;
    }
}
