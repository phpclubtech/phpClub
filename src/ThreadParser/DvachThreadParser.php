<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use phpClub\Entity\{File, Post};
use Symfony\Component\DomCrawler\Crawler;

class DvachThreadParser extends AbstractThreadParser
{
    protected function getPostsXPath(): string
    {
        return '//td[@class="reply"] | //div[starts-with(@id, "post-body-") or @class="oppost"]';
    }

    protected function getAuthorXPath(): string
    {
        return '//span[starts-with(@class,"poster") or @class="ananimas"][normalize-space(string())]
                | //span[@class="name"]/text()
                | //div/a[@class="post-email"]/text()
                | //span[@class="mod"]/text()';
    }

    protected function getDateXPath(): string
    {
        return '//span[contains(@class,"dateTime") or @class="posttime"]/text()';
    }

    protected function getIdXPath(): string
    {
        return '//td/@id | //div/@data-num | //span[@class="reflink"]/a';
    }

    protected function getTextXPath(): string
    {
        return '//blockquote/p | //blockquote[not(p)]';
    }

    protected function getTitleXPath(): string
    {
        return '//span[@class="nameBlock"]/span[@class="subject"]
                | //span[@class="post-title"]';
    }

    protected function getFilesXPath(): string
    {
        return '//div[starts-with(@class, "images")]/figure[starts-with(@class, "image")]
                | //span[starts-with(@id, "exlink_")]';
    }

    /**
     * @param Crawler $fileNode
     * @param Post $post
     * @return File
     * @throws \Exception
     */
    protected function extractFile(Crawler $fileNode, Post $post): File
    {
        list(, $fullName, $thumbName, $width, $height) = $this->extractOnClickJsArgs($fileNode);
        
        return new File(ltrim($fullName, "'"), $thumbName, $post, (int)$height, (int)$width);
    }

    /**
     * @param Crawler $fileNode
     * @return array
     * @throws \Exception
     */
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
