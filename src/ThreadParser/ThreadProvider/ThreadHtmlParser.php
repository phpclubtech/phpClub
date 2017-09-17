<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\ThreadProvider;

use phpClub\ThreadParser\Event;
use phpClub\ThreadParser\Helper\DateConverter;
use phpClub\Entity\{File, Post, Thread};
use phpClub\ThreadParser\Thread\ThreadInterface;
use Symfony\Component\DomCrawler\Crawler;
use Zend\EventManager\EventManagerInterface;

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

    /**
     * @param DateConverter $dateConverter
     * @param ThreadInterface $thread
     */
    public function __construct(DateConverter $dateConverter, ThreadInterface $thread)
    {
        $this->dateConverter = $dateConverter;
        $this->thread        = $thread;
    }

    /**
     * @param string $threadsFolder
     * @return Thread[]
     * @throws \Exception
     */
    public function parseAllThreads(string $threadsFolder)
    {
        $threadHtmls = glob(rtrim($threadsFolder, '/') . '/*.html');

        if (!$threadHtmls) {
            throw new \Exception('No threads found in ' . $threadsFolder);
        }

        return array_map([$this, 'extractThread'], $threadHtmls);
    }

    /**
     * @param string $threadHtml
     * @return Thread
     * @throws \Exception
     */
    public function extractThread(string $threadHtml): Thread
    {
        $threadCrawler = new Crawler($threadHtml);

        $postsXPath = $this->thread->getPostsXPath();
        
        $firstPostXPath = $threadCrawler->filterXPath($postsXPath . '[1]');
        $thread = new Thread($this->extractId($firstPostXPath));

        $postNodes = $threadCrawler->filterXPath($postsXPath);

        if (!count($postNodes)) {
            throw new \Exception('Posts not found');
        }

        // We need to use each() because foreach on Crawler will iterate over DomElements
        $postNodes->each(function (Crawler $postNode) use ($thread) {
            $post = new Post(
                $this->extractId($postNode),
                $this->extractTitle($postNode),
                $this->extractAuthor($postNode),
                $this->extractDate($postNode),
                $this->extractText($postNode),
                $thread
            );
            $post->addFiles($this->extractFiles($postNode, $post));
            $thread->addPost($post);
        });

        return $thread;
    }

    /**
     * @param Crawler $postNode
     * @return int
     * @throws \Exception
     */
    private function extractId(Crawler $postNode): int
    {
        $idXPath = $this->thread->getIdXPath();
        $idNode  = $postNode->filterXPath($idXPath);

        if (!count($idNode)) {
            throw new \Exception("Unable to parse post id, HTML: {$postNode->html()}");
        }

        $postId = preg_replace('/[^\d]+/', '', $idNode->text());

        return (int) $postId;
    }

    /**
     * @param Crawler $postNode
     * @return string
     */
    private function extractTitle(Crawler $postNode): string
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
     * @return string
     * @throws \Exception
     */
    private function extractAuthor(Crawler $postNode): string
    {
        $authorXPath = $this->thread->getAuthorXPath();
        $authorNode  = $postNode->filterXPath($authorXPath);

        if (!count($authorNode)) {
            throw new \Exception("Unable to parse post author, HTML: {$postNode->html()}");
        }

        return $authorNode->text();
    }

    /**
     * @param Crawler $postNode
     * @return \DateTimeImmutable
     * @throws \Exception
     */
    private function extractDate(Crawler $postNode): \DateTimeImmutable
    {
        $dateXPath = $this->thread->getDateXPath();
        $dateNode  = $postNode->filterXPath($dateXPath);

        if (!count($dateNode)) {
            throw new \Exception("Unable to parse post date, HTML: {$postNode->html()}");
        }

        return $this->dateConverter->toDateTime($dateNode->text());
    }

    /**
     * @param Crawler $postNode
     * @return string
     * @throws \Exception
     */
    private function extractText(Crawler $postNode): string
    {
        $textXPath = $this->thread->getTextXPath();
        $textNode  = $postNode->filterXPath($textXPath);

        if (!count($textNode)) {
            throw new \Exception("Unable to parse post text, HTML: {$postNode->html()}");
        }

        return trim($textNode->html());
    }

    /**
     * @param Crawler $postNode
     * @param Post $post
     * @return File[]
     */
    private function extractFiles(Crawler $postNode, Post $post): array
    {
        $filesXPath = $this->thread->getFilesXPath();
        $fileNodes = $postNode->filterXPath($filesXPath);

        return $fileNodes->each(function (Crawler $fileNode) use ($post) {
            return $this->thread->extractFile($fileNode, $post);
        });
    }
}
