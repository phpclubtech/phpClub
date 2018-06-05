<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use Symfony\Component\DomCrawler\Crawler;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\ThreadParser\ThreadParseException;
use phpClub\Util\DOMUtil;

/**
 * Parses threads from m2-ch.ru website.
 */
class MDvachThreadParser extends AbstractThreadParser
{
    protected function getPostsXPath(): string
    {
        return '//div[@class="body"]/div[@class="thread" or @class="reply"]';
    }

    protected function getAuthorXPath(): string
    {
        // TODO: now it catches only tripcode and sage, what about normal name? 
        return '//span[@class="sage"]';
    }

    protected function getTripCodeXPath(): string 
    {
        return '//span[@class="postertrip"]';
    }

    protected function getDateXPath(): string
    {
        return '//div[@class="pst_bar"]/time';
    }

    protected function getTitleXPath(): string
    {
        return '//div[@class="pst_bar"]/b[@class="ft"] | //div[@class="pst_bar"]/strong[@class="ft"]';
    }

    protected function getIdXPath(): string
    {
        return './div/@id';
    }

    protected function getTextXPath(): string
    {
        return '//div[@class="pst"]';
    }

    protected function getFilesXPath(): string
    {
        return '//a[@class="il"]';
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

    protected function extractDate(Crawler $postNode): \DateTimeInterface
    {
        $dateXPath = $this->getDateXPath();
        $dateNode = $postNode->filterXPath($dateXPath);

        if (!count($dateNode)) {
            throw new ThreadParseException("Unable to find post date node");
        }

        // Date doesn't contain year so we have to guess it by post id 
        $postId = $postNode->attr('id');
        if (!$postId) {
            throw new ThreadParseException("Cannot read post id while parsing date");
        }

        if ($postId >= 272705 && $postId <= 289749) {
            $year = 2013;
        } else {
            throw new ThreadParseException("m2-ch parser doesn't know the year for post id '$postId'");
        }        

        $rawDate = $dateNode->text();

        return $this->dateConverter->parseMDvachDate($rawDate, $year);
    }

    protected function extractFile(Crawler $fileNode): File
    {
        /* $fileNode contains:

        <a class="il" href="http://2ch.wf/pr/src/1367507520375.png">
            <div class="thumb inreply" style="background-image: url('pr/thumb/1367507520375s.gif');">
                <div class="img_size">33 Кб, 500x500</div>
            </div>
        </a>

        For video: 

        <a class="il" href="http://youtu.be/giC3-LnnV4c">
            <div class="thumb video yt" style="background-image: url('pr/thumb/yougiC3-LnnV4c.jpg');">
                <div class="img_size">Youtube</div>
            </div>
        </a>

        */
        $originalUrl = $fileNode->attr('href');
        if (!$originalUrl) {
            throw new ThreadParseException("Cannot find URL in file node");
        }

        $thumbStyle = $fileNode->filter('div.thumb')->attr('style');
        if (!$thumbStyle) {
            throw new ThreadParseException("Cannot find style attr of thumb node inside file node");
        }

        if (!preg_match("/background-image:\s*url\('([^']*)'\)/", $thumbStyle, $m2)) {
            throw new ThreadParseException("Cannot find thumbnail URL");
        }

       $thumbPath = $m2[1];        

        if ($fileNode->filter('.video.yt')->count() > 0) {
            // Is an Youtube video - what to do?
            $file = new File;
            $file->setPath($originalUrl);
            $file->setThumbPath($thumbPath);

            return $file;
        }

        $path = parse_url($originalUrl, PHP_URL_PATH);

        $sizeText = $fileNode->filter('div.img_size')->text();
        if (!$sizeText) {
            throw new ThreadParseException("Cannot find size node inside file node");
        }

        if (!preg_match("/(\d+)x(\d+)/", $sizeText, $m)) {
            throw new ThreadParseException("Cannot find image size in string '$sizeText'");
        }

        $thumbPath = $m2[1];

        $file = new File;
        $file->setPath($path);
        $file->setThumbPath($thumbPath);
        $file->setWidth(intval($m[1]));
        $file->setHeight(intval($m[2]));

        return $file;
    }    
}
