<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\ThreadProvider;

use phpClub\ThreadParser\Helper\DateConverter;
use phpClub\Entity\{File, Post, Thread};
use phpClub\ThreadParser\Thread\ThreadInterface;
use Symfony\Component\DomCrawler\Crawler;

class ThreadHtmlParser
{
    /**
     * @var ThreadInterface
     */
    private $thread;

    /**
     * @var DateConverter
     */
    private $dateConverter;

    /**
     * @param ThreadInterface $thread
     * @param DateConverter $dateConverter
     */
    public function __construct(ThreadInterface $thread, DateConverter $dateConverter)
    {
        $this->thread        = $thread;
        $this->dateConverter = $dateConverter;
    }

    /**
     * @param string $threadsDir
     * @return Thread[]
     * @throws \Exception
     */
    public function parseAllThreads(string $threadsDir): array
    {
        $threadHtmlPaths = glob($threadsDir . '/*/*.htm*');

        if (!$threadHtmlPaths) {
            throw new \Exception('No threads found in ' . $threadsDir);
        }

        return array_map(function ($threadHtmlPath) {
            return $this->extractThread(file_get_contents($threadHtmlPath), dirname($threadHtmlPath));
        }, $threadHtmlPaths);
    }

    /**
     * @param string $threadHtml
     * @param string $threadPath
     * @return Thread
     * @throws \Exception
     */
    public function extractThread(string $threadHtml, string $threadPath = ''): Thread
    {
        $threadCrawler = new Crawler($threadHtml);

        $postsXPath = $this->thread->getPostsXPath();

        $firstPostXPath = $threadCrawler->filterXPath($postsXPath . '[1]');
        $thread = new Thread($this->extractId($firstPostXPath));

        $postNodes = $threadCrawler->filterXPath($postsXPath);

        if (!count($postNodes)) {
            throw new \Exception('Posts not found');
        }

        $extractPost = function (Crawler $postNode) use ($thread, $threadPath) {
            $post = new Post(
                $this->extractId($postNode),
                $this->extractTitle($postNode),
                $this->extractAuthor($postNode),
                $this->extractDate($postNode),
                $this->extractText($postNode),
                $thread
            );
            
            if (!$this->isThreadWithMissedFiles($thread)) {
                $post->addFiles($this->extractFiles($postNode, $post, $threadPath));
            }
            
            $thread->addPost($post);
        };

        $postNodes->each($extractPost);

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
     * @param string $threadPath
     * @return File[]
     */
    private function extractFiles(Crawler $postNode, Post $post, string $threadPath): array
    {
        $filesXPath = $this->thread->getFilesXPath();
        $fileNodes = $postNode->filterXPath($filesXPath);

        $extractFile = function (Crawler $fileNode) use ($post, $threadPath) {
            $file = $this->thread->extractFile($fileNode, $post);

            if ($threadPath && !filter_var($file->getPath(), FILTER_VALIDATE_URL)) {
                $file->updatePaths(
                    $threadPath . '/' . basename($file->getPath()),
                    $threadPath . '/' . basename($file->getThumbPath())
                );
            }

            return $file;
        };

        return $fileNodes->each($extractFile);
    }

    private function isThreadWithMissedFiles(Thread $thread): bool
    {
        // 345388 - Thread #15 (Google cache)
        $threadsWithMissedFiles = ['345388'];
        
        return in_array($thread->getId(), $threadsWithMissedFiles);
    }
}
