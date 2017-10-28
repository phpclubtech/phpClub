<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\Thread;

use phpClub\Entity\{File, Post};
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

    public function extractFile(Crawler $fileNode, Post $post): File
    {
        $fileXPath = '//a[@class="expand_image"]';
        $fileNode = $fileNode->filterXPath($fileXPath);

        if (!count($fileNode)) {
            throw new \Exception("Unable to parse file, HTML: {$fileNode->html()}");
        }

        list(, $filePath, $width, $height) = preg_split("/','|',|,|\)/", $fileNode->attr('onclick'), -1, PREG_SPLIT_NO_EMPTY);

        if ($this->isOldArhivachThread($filePath)) {
            // Hack for old arhivach threads
            return new File($filePath, str_replace('/img/', '/thumb/', $filePath), $post, 0, 0);
        }

        $thumbXPath = '//div[@class="post_image"]/img';
        $thumbNode = $fileNode->filterXPath($thumbXPath);

        if (!count($thumbNode)) {
            throw new \Exception("Unable to parse thumb, HTML: {$thumbNode->html()}");
        }

        return new File($filePath, $thumbNode->attr('src'), $post, (int) $height, (int) $width);
    }
    
    private function isOldArhivachThread(string $fileName): bool 
    {
        return strpos($fileName, 'abload.de') !== false;
    }
}
