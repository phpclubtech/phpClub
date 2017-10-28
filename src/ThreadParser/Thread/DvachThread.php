<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\Thread;

use phpClub\Entity\File;
use phpClub\Entity\Post;
use Symfony\Component\DomCrawler\Crawler;

class DvachThread implements ThreadInterface
{
    public function getPostsXPath(): string
    {
        return '//td[@class="reply"] | //div[starts-with(@id, "post-body-") or @class="oppost"]';
    }

    public function getAuthorXPath(): string
    {
        return '//span[starts-with(@class,"poster") or @class="ananimas"][normalize-space(string())]
                | //span[@class="name"]/text()
                | //div/a[@class="post-email"]/text()';
    }

    public function getDateXPath(): string
    {
        return '//span[contains(@class,"dateTime") or @class="posttime"]/text()';
    }

    public function getIdXPath(): string
    {
        return '//td/@id | //div/@data-num | //span[@class="reflink"]/a';
    }

    public function getTextXPath(): string
    {
        return '//blockquote/p | //blockquote[not(p)]';
    }

    public function getTitleXPath(): string
    {
        return '//span[@class="nameBlock"]/span[@class="subject"]
                | //span[@class="post-title"]';
    }

    public function getFilesXPath(): string
    {
        return '//div[starts-with(@class, "images")]/figure[starts-with(@class, "image")]
                | //span[starts-with(@id, "exlink_")]';
    }

    public function extractFile(Crawler $fileNode, Post $post): File
    {
        list(, $fullName, $thumbName, $width, $height) = $this->extractOnClickJsArgs($fileNode);
        
        return new File($fullName, $thumbName, $post, (int) $height, (int) $width);
    }

    private function extractOnClickJsArgs(Crawler $fileNode): array
    {
        $argsXPath = '//div[@class="image-link"]/a/@onclick | //a[@name="expandfunc"]/@onclick';
        $argsNode  = $fileNode->filterXPath($argsXPath);

        if (!count($argsNode)) {
            throw new \Exception("Unable to find expand params, HTML: {$fileNode->html()}");
        }

        $params = $argsNode->text();

        return preg_split("/','|',|,|\)/", $params, -1, PREG_SPLIT_NO_EMPTY);
    }
}
