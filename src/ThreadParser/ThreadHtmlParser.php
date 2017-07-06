<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use Symfony\Component\DomCrawler\Crawler;
use phpClub\ThreadParser\DTO\{File, Post};
use phpClub\ThreadParser\Thread\ThreadInterface;

class ThreadHtmlParser
{
    /**
     * @var DateConverter
     */
    private $dateConverter;

    /**
     * @var ThreadInterface
     */
    private $thread;

    public function __construct(DateConverter $dateConverter, ThreadInterface $thread)
    {
        $this->dateConverter = $dateConverter;
        $this->thread        = $thread;
    }

    public function getPosts(string $threadHtml): array
    {
        $threadCrawler = new Crawler($threadHtml);

        $postsXPath = $this->thread->getPostsXPath();
        $posts      = $threadCrawler->filterXPath($postsXPath);

        if (!count($posts)) {
            throw new \Exception('Posts not found');
        }

        return $posts->each(\Closure::fromCallable([$this, 'getPost']));
    }

    public function getPost(Crawler $postNode): Post
    {
        return new Post(
            $this->getId($postNode),
            $this->getTitle($postNode),
            $this->getAuthor($postNode),
            $this->getDate($postNode),
            $this->getText($postNode),
            $this->getFiles($postNode)
        );
    }

    private function getAuthor(Crawler $postNode): string
    {
        $authorXPath = $this->thread->getAuthorXPath();
        $authorNode  = $postNode->filterXPath($authorXPath);

        if (!count($authorNode)) {
            throw new \Exception("Unable to parse post author, HTML: {$postNode->html()}");
        }

        return $authorNode->text();
    }

    private function getDate(Crawler $postNode): \DateTimeImmutable
    {
        $dateXPath = $this->thread->getDateXPath();
        $dateNode  = $postNode->filterXPath($dateXPath);

        if (!count($dateNode)) {
            throw new \Exception("Unable to parse post date, HTML: {$postNode->html()}");
        }

        return $this->dateConverter->toDateTime($dateNode->text());
    }

    private function getId(Crawler $postNode): int
    {
        $idXPath = $this->thread->getIdXPath();
        $idNode  = $postNode->filterXPath($idXPath);

        if (!count($idNode)) {
            throw new \Exception("Unable to parse post id, HTML: {$postNode->html()}");
        }

        $postId = preg_replace('/[^\d]+/', '', $idNode->text());

        return (int) $postId;
    }

    private function getText(Crawler $postNode): string
    {
        $textXPath = $this->thread->getTextXPath();
        $textNode  = $postNode->filterXPath($textXPath);

        if (!count($textNode)) {
            throw new \Exception("Unable to parse post text, HTML: {$postNode->html()}");
        }

        return trim($textNode->html());
    }

    private function getTitle(Crawler $postNode): string
    {
        $titleXPath = $this->thread->getTitleXPath();
        $titleNode  = $postNode->filterXPath($titleXPath);

        if (!count($titleNode)) {
            return '';
        }

        return trim($titleNode->text());
    }

    /**
     * @param Crawler $postNode
     * @return File[]
     */
    private function getFiles(Crawler $postNode): array
    {
        $filesXPath = $this->thread->getFilesXPath();
        $filesNode = $postNode->filterXPath($filesXPath);

        return $filesNode->each(\Closure::fromCallable([$this->thread, 'getFile']));
    }
}
