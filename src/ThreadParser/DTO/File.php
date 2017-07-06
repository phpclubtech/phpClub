<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\DTO;

class File
{
    public $width;
    public $height;
    public $fullName;
    public $thumbName;

    public function __construct(
        string $fullName,
        string $thumbName,
        int $width,
        int $height
    ) {
        $this->fullName = $fullName;
        $this->thumbName = $thumbName;
        $this->width = $width;
        $this->height = $height;
    }
}
