<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use phpClub\Entity\{File, Post};
use Symfony\Component\DomCrawler\Crawler;

class ArhivachThreadParser extends AbstractThreadParser
{
    protected function getPostsXPath(): string
    {
        return '//div[@class="post"]';
    }

    protected function getAuthorXPath(): string
    {
        return '//span[@class="poster_name"] | //span[@class="poster_trip"]';
    }

    protected function getDateXPath(): string
    {
        return '//span[@class="post_time"]';
    }

    protected function getIdXPath(): string
    {
        return '//span[@class="post_id"]';
    }

    protected function getTextXPath(): string
    {
        return '//div[@class="post_comment_body"]';
    }

    protected function getTitleXPath(): string
    {
        return '//*[@class="post_subject"]';
    }

    protected function getFilesXPath(): string
    {
        return '//span[@class="post_comment"]/div[@class="post_image_block"]';
    }

    /**
     * @param Crawler $postNode
     * @return string
     * @throws \Exception
     */
    protected function extractAuthor(Crawler $postNode): string
    {
        $authorXPath = $this->getAuthorXPath();
        $authorNode  = $postNode->filterXPath($authorXPath);

        if (!count($authorNode)) {
            return '';
        }

        return $authorNode->text();
    }

    /**
     * @param Crawler $fileNode
     * @param Post $post
     * @return File
     * @throws \Exception
     */
    protected function extractFile(Crawler $fileNode, Post $post): File
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

    /**
     * @param string $fileName
     * @return bool
     */
    private function isOldArhivachThread(string $fileName): bool 
    {
        return strpos($fileName, 'abload.de') !== false;
    }
}
