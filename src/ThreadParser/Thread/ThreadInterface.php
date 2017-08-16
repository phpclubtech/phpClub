<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\Thread;

use phpClub\ThreadParser\DTO\File;
use Symfony\Component\DomCrawler\Crawler;

interface ThreadInterface
{
    public function getPostsXPath(): string;

    public function getIdXPath(): string;

    public function getTitleXPath(): string;

    public function getAuthorXPath(): string;

    public function getDateXPath(): string;

    public function getTextXPath(): string;

    public function getFilesXPath(): string;

    public function getFile(Crawler $fileNode): File;
}
