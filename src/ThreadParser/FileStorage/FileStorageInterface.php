<?php

namespace phpClub\ThreadParser\FileStorage;

use phpClub\Entity\File;

interface FileStorageInterface
{
    /**
     * @param File $file
     * @return void
     */
    public function put(File $file);
}
