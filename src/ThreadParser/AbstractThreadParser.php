<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\ThreadParser\Exception\ThreadParseException;
use phpClub\ThreadParser\Internal\CloudflareEmailDecoder;
use phpClub\Util\DOMUtil;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractThreadParser
{
    /**
     * @var DateConverter
     */
    protected $dateConverter;

    /**
     * @var MarkupConverter
     */
    protected $markupConverter;

    /**
     * @var CloudflareEmailDecoder
     */
    private $cloudflareEmailDecoder;

    public function __construct(DateConverter $dateConverter, MarkupConverter $markupConverter, CloudflareEmailDecoder $cloudflareEmailDecoder)
    {
        $this->markupConverter = $markupConverter;
        $this->dateConverter = $dateConverter;
        $this->cloudflareEmailDecoder = $cloudflareEmailDecoder;
    }

    abstract protected function getPostsXPath(): string;

    abstract protected function getIdXPath(): string;

    abstract protected function getTitleXPath(): string;

    abstract protected function getAuthorXPath(): string;

    abstract protected function getTripCodeXPath(): string;

    abstract protected function getDateXPath(): string;

    abstract protected function getTextXPath(): string;

    abstract protected function getFilesXPath(): string;

    abstract protected function extractFile(Crawler $fileNode): File;

    /**
     * @param string $threadHtml
     * @param string $threadPath
     *
     * @return Thread
     */
    public function extractThread(string $threadHtml, string $threadPath = ''): Thread
    {
        $hasCloudflareEmails = $this->cloudflareEmailDecoder->hasCloudflareEmails($threadHtml);
        $threadCrawler = new Crawler($threadHtml);

        $postsXPath = $this->getPostsXPath();
        $postNodes = $threadCrawler->filterXPath($postsXPath);

        if (!count($postNodes)) {
            throw new ThreadParseException('Post nodes not found');
        }

        $firstPost = $postNodes->first();

        $thread = new Thread($this->extractId($firstPost));

        $extractPost = function (Crawler $postNode) use ($thread, $threadPath, $hasCloudflareEmails) {
            try {
                $post = $this->extractSinglePost($postNode, $thread, $threadPath, $hasCloudflareEmails);
            } catch (ThreadParseException $e) {
                // Add details if an exception is thrown
                $html = DOMUtil::getOuterHtml($postNode->getNode(0));

                $details = sprintf(
                    "%s: %s\nPost HTML: \n%s...",
                    get_class($e),
                    $e->getMessage(),
                    mb_substr($html, 0, 2000)
                );

                throw new ThreadParseException($details, 0, $e);
            }

            $post->setThread($thread);
            $thread->addPost($post);
        };

        $postNodes->each($extractPost);

        $this->assertThatPostIdsAreUnique($thread);

        return $thread;
    }

    private function extractSinglePost(
        Crawler $postNode,
        Thread $thread,
        string $threadPath,
        bool $hasCloudflareEmails): Post
    {
        if ($hasCloudflareEmails) {
            $postNode = $this->cloudflareEmailDecoder->restoreCloudflareEmails($postNode);
        }

        $post = (new Post($this->extractId($postNode)))
            ->setTitle($this->extractTitle($postNode))
            ->setAuthor($this->extractAuthor($postNode))
            ->setDate($this->extractDate($postNode))
            ->setText($this->extractText($postNode));

        if (!$this->isThreadWithMissedFiles($thread)) {
            $files = $this->extractFiles($postNode, $threadPath);
            foreach ($files as $file) {
                $post->addFile($file);
            }
        }

        return $post;
    }

    protected function assertThatPostIdsAreUnique(Thread $thread): void
    {
        $ids = [];
        $posts = $thread->getPosts();

        foreach ($posts as $post) {
            $id = $post->getId();

            if (array_key_exists($id, $ids)) {
                throw new ThreadParseException("In thread {$thread->getId()} there is more than one post with id {$id}");
            }

            $ids[$id] = true;
        }
    }

    /**
     * @param Crawler $postNode
     *
     * @return int
     */
    protected function extractId(Crawler $postNode): int
    {
        $idXPath = $this->getIdXPath();
        $idNode = $postNode->filterXPath($idXPath);

        if (!count($idNode)) {
            throw new ThreadParseException('Unable to parse post id');
        }

        $postId = preg_replace('/[^\d]+/', '', $idNode->text());

        return (int) $postId;
    }

    /**
     * @param Crawler $postNode
     *
     * @return string
     */
    protected function extractTitle(Crawler $postNode): string
    {
        $titleXPath = $this->getTitleXPath();
        $titleNode = $postNode->filterXPath($titleXPath);

        if (!count($titleNode)) {
            return '';
        }

        return trim($titleNode->text());
    }

    /**
     * @param Crawler $postNode
     *
     * @return string
     */
    protected function extractAuthor(Crawler $postNode): string
    {
        $authorXPath = $this->getAuthorXPath();
        $authorNode = $postNode->filterXPath($authorXPath);

        if (!count($authorNode)) {
            throw new ThreadParseException('Unable to parse post author');
        }

        $author = trim($authorNode->text());

        $tripXPath = $this->getTripCodeXPath();
        $tripNode = $postNode->filterXPath($tripXPath);
        $trip = trim(DOMUtil::getTextFromCrawler($tripNode));

        // As currently we don't distinguish between names and trip codes,
        // parse both and return first non-empty value
        return $author !== '' ? $author : $trip;
    }

    /**
     * @param Crawler $postNode
     *
     * @return \DateTimeImmutable
     */
    protected function extractDate(Crawler $postNode): \DateTimeInterface
    {
        $dateXPath = $this->getDateXPath();
        $dateNode = $postNode->filterXPath($dateXPath);

        if (!count($dateNode)) {
            throw new ThreadParseException('Unable to parse post date');
        }

        return $this->dateConverter->toDateTime($dateNode->text());
    }

    /**
     * @param Crawler $postNode
     *
     * @return string
     */
    protected function extractText(Crawler $postNode): string
    {
        $textXPath = $this->getTextXPath();
        $blockquoteNode = $postNode->filterXPath($textXPath);

        if (!count($blockquoteNode)) {
            throw new ThreadParseException('Unable to parse post text');
        }

        // $textNode is an iterable
        $blockquoteDomNode = $blockquoteNode->getNode(0);
        $this->markupConverter->transformChildren($blockquoteDomNode);

        return trim($blockquoteNode->html());
    }

    /**
     * @param Crawler $postNode
     * @param string  $threadPath
     *
     * @return File[]
     */
    protected function extractFiles(Crawler $postNode, string $threadPath): array
    {
        $filesXPath = $this->getFilesXPath();
        $fileNodes = $postNode->filterXPath($filesXPath);

        $extractFile = function (Crawler $fileNode) use ($threadPath) {
            $file = $this->extractFile($fileNode);

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

    protected function isThreadWithMissedFiles(Thread $thread): bool
    {
        // 345388 - Thread #15 (Google cache)
        $threadsWithMissedFiles = ['345388'];

        return in_array($thread->getId(), $threadsWithMissedFiles, $strict = true);
    }
}
