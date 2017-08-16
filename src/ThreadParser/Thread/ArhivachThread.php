<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\Thread;

use phpClub\ThreadParser\DTO\File;
use Symfony\Component\DomCrawler\Crawler;

class ArhivachThread implements ThreadInterface
{
    public function getPostsXPath(): string
    {
        return '//div[@class="post"]';
    }

    public function getAuthorXPath(): string
    {
        return '//span[@class="poster_name"] | //span[@class="poster_trip"]';
    }

    public function getDateXPath(): string
    {
        return '//span[@class="post_time"]';
    }

    public function getIdXPath(): string
    {
        return '//span[@class="post_id"]';
    }

    public function getTextXPath(): string
    {
        return '//div[@class="post_comment_body"]';
    }

    public function getTitleXPath(): string
    {
        return '//*[@class="post_subject"]';
    }

    public function getFilesXPath(): string
    {
        return '//span[@class="post_comment"]/div[@class="post_image_block"]';
    }

    public function getFile(Crawler $fileNode): File
    {
        $fileXPath = '//a[@class="expand_image"]';
        $fileNode = $fileNode->filterXPath($fileXPath);

        if (!count($fileNode)) {
            throw new \Exception("Unable to parse file, HTML: {$fileNode->html()}");
        }

        $onClickArgs = preg_split("/','|',|,|\)/", $fileNode->attr('onclick'), -1, PREG_SPLIT_NO_EMPTY);

        // Hack for old arhivach threads
        if (strpos($onClickArgs[1], 'abload.de') !== false) {
            return new File($onClickArgs[1], str_replace('/img/', '/thumb/', $onClickArgs[1]), 0, 0);
        }

        list(, $fullName, $width, $height) = $onClickArgs;

        $thumbXPath = '//div[@class="post_image"]/img';
        $thumbNode = $fileNode->filterXPath($thumbXPath);

        if (!count($thumbNode)) {
            throw new \Exception("Unable to parse thumb, HTML: {$thumbNode->html()}");
        }

        $thumbName = $thumbNode->attr('src');

        return new File($fullName, $thumbName, (int) $width, (int) $height);
    }
}
