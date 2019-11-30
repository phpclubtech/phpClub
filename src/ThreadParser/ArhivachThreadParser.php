<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use phpClub\Entity\File;
use phpClub\ThreadParser\Exception\ThreadParseException;
use phpClub\Util\DOMUtil;
use Symfony\Component\DomCrawler\Crawler;

class ArhivachThreadParser extends AbstractThreadParser
{
    protected function getPostsXPath(): string
    {
        return '//div[@class="post"]';
    }

    protected function getAuthorXPath(): string
    {
        return '//span[@class="poster_name"]';
    }

    protected function getTripCodeXPath(): string
    {
        return '//span[@class="poster_trip"]';
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

    protected function extractAuthor(Crawler $postNode): string
    {
        $authorXPath = $this->getAuthorXPath();
        $authorNode = $postNode->filterXPath($authorXPath);
        $author = trim(DOMUtil::getTextFromCrawler($authorNode));

        $tripXPath = $this->getTripCodeXPath();
        $tripNode = $postNode->filterXPath($tripXPath);
        $trip = trim(DOMUtil::getTextFromCrawler($tripNode));

        // Author can be missing
        return $author !== '' ? $author : $trip;
    }

    protected function extractFile(Crawler $fileNode): File
    {
        $fileXPath = '//a[@class="expand_image"]';
        $imgNode = $fileNode->filterXPath($fileXPath);

        if (!count($imgNode)) {
            throw new ThreadParseException("Unable to parse image node, HTML: {$imgNode->html()}");
        }

        [, $filePath, $width, $height] = preg_split("/','|',|,|\)/", $imgNode->attr('onclick'), -1, PREG_SPLIT_NO_EMPTY);

        if ($this->isOldArhivachThread($filePath)) {
            // Hack for old arhivach threads
            [$width, $height] = getimagesize($filePath);

            return (new File())
                ->setPath($filePath)
                ->setThumbPath(str_replace('/img/', '/thumb/', $filePath))
                ->setHeight($height)
                ->setWidth($width);
        }

        $thumbXPath = '//div[@class="post_image"]/img';
        $thumbNode = $imgNode->filterXPath($thumbXPath);

        if (!count($thumbNode)) {
            throw new ThreadParseException("Unable to parse thumb, HTML: {$thumbNode->html()}");
        }

        $clientNameNode = $fileNode->filterXPath('//a[@class="img_filename"]');
        $clientName = count($clientNameNode) ? $clientNameNode->text() : null;

        return (new File())
            ->setPath($filePath)
            ->setThumbPath($thumbNode->attr('src'))
            ->setHeight((int) $height)
            ->setWidth((int) $width)
            ->setClientName($clientName);
    }

    private function isOldArhivachThread(string $fileName): bool
    {
        return strpos($fileName, 'abload.de') !== false;
    }
}
