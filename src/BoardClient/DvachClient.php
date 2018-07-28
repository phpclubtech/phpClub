<?php

declare(strict_types=1);

namespace phpClub\BoardClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use function GuzzleHttp\Psr7\str;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use Symfony\Component\DomCrawler\Crawler;

class DvachClient
{
    // Fixes 2ch.hk API poor naming
    private const POST_AUTHOR = 'name';
    private const POST_TITLE = 'subject';
    private const POST_TEXT = 'comment';
    private const THREAD_TITLE = 'subject';

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @param Client $guzzle
     */
    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @return Thread[]
     */
    public function getAlivePhpThreads(): array
    {
        $responseBody = $this->guzzle->get('https://2ch.hk/pr/catalog.json')->getBody();
        $responseJson = \GuzzleHttp\json_decode($responseBody, $assoc = true);
        $threads = $responseJson['threads'];

        $phpThreadsArray = array_filter($threads, [$this, 'looksLikePhpThread']);

        return array_map([$this, 'extractThread'], $phpThreadsArray);
    }

    /**
     * @param array $threadArray
     *
     * @return bool
     */
    private function looksLikePhpThread(array $threadArray): bool
    {
        return (bool) preg_match('/Клуб.*PHP/ui', $threadArray[self::THREAD_TITLE]);
    }

    /**
     * @param array $phpThread
     *
     * @throws \Exception
     *
     * @return Thread
     */
    private function extractThread(array $phpThread): Thread
    {
        $threadId = (int) $phpThread['num'];

        $responseBody = $this->guzzle->get("https://2ch.hk/pr/res/{$threadId}.json")->getBody();
        $responseJson = \GuzzleHttp\json_decode($responseBody, $assoc = true);

        $postsArray = $responseJson['threads'][0]['posts'] ?? null;

        if ($postsArray === null) {
            throw new \Exception('2ch.hk API has changed, path threads[0].posts is not exists');
        }

        $thread = new Thread($threadId);

        foreach ($postsArray as $postArray) {
            $post = $this->extractPost($postArray);
            $post->setThread($thread);
            $thread->addPost($post);
        }

        return $thread;
    }

    /**
     * @param array $postArray
     *
     * @return Post
     */
    private function extractPost(array $postArray): Post
    {
        $post = new Post($postArray['num']);
        $post->setTitle($postArray[self::POST_TITLE])
            ->setAuthor($postArray[self::POST_AUTHOR])
            ->setDate((new \DateTimeImmutable())->setTimestamp($postArray['timestamp']))
            ->setText($postArray[self::POST_TEXT]);

        foreach ($postArray['files'] as $fileArray) {
            $file = $this->extractFile($fileArray);
            $file->setPost($post);
            $post->addFile($file);
        }

        return $post;
    }

    /**
     * @param array $fileArray
     *
     * @return File
     */
    private function extractFile(array $fileArray): File
    {
        return (new File())
            ->setPath('https://2ch.hk' . $fileArray['path'])
            ->setThumbPath('https://2ch.hk' . $fileArray['thumbnail'])
            ->setHeight($fileArray['height'])
            ->setWidth($fileArray['width'])
            ->setClientName($fileArray['fullname'] ?? $fileArray['name']);
    }
}
