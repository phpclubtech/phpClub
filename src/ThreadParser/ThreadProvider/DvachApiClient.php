<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\ThreadProvider;

use phpClub\Entity\{File, Post, Thread};
use GuzzleHttp\{Client, HandlerStack};
use Doctrine\Common\Cache\FilesystemCache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use phpClub\ThreadParser\Event;
use Zend\EventManager\EventManagerInterface;

class DvachApiClient
{
    const BASE_URL = 'https://2ch.hk';
    const THREAD_CATALOG_URL = 'https://2ch.hk/pr/catalog.json';

    // Fixes 2ch.hk API poor naming
    const POST_AUTHOR = 'name';
    const POST_NUMBER = 'num';
    const POST_TITLE = 'subject';
    const POST_TEXT = 'comment';
    const THREAD_TITLE = 'subject';
    const THREAD_NUMBER = 'num';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @param Client $client
     * @param EventManagerInterface $eventManager
     */
    public function __construct(Client $client, EventManagerInterface $eventManager)
    {
        $this->client = $client;
        $this->eventManager = $eventManager;
    }

    /**
     * @param EventManagerInterface $eventManager
     * @param int $ttl TTL in seconds
     * @return DvachApiClient
     */
    public static function createCacheable(EventManagerInterface $eventManager, int $ttl = 3600): self
    {
        $stack = HandlerStack::create();

        $stack->push(
            new CacheMiddleware(
                new GreedyCacheStrategy(
                    new DoctrineCacheStorage(
                        new FilesystemCache('/tmp/')
                    ),
                    $ttl
                )
            )
        );

        return new self(new Client(['handler' => $stack]), $eventManager);
    }

    /**
     * @return Thread[]
     */
    public function getAlivePhpThreads(): array
    {
        $responseBody = $this->client->get(self::THREAD_CATALOG_URL)->getBody();
        $responseJson = \GuzzleHttp\json_decode($responseBody, $assoc = true);
        $threads = $responseJson['threads'];

        $phpThreadsArray = array_filter($threads, [$this, 'looksLikePhpThread']);
        $phpThreads = array_map([$this, 'extractThread'], $phpThreadsArray);

        return $phpThreads;
    }

    /**
     * @param array $threadArray
     * @return bool
     */
    private function looksLikePhpThread(array $threadArray): bool
    {
        return !! preg_match('/Клуб.*PHP/ui', $threadArray[self::THREAD_TITLE]);
    }

    /**
     * @param array $phpThread
     * @return Thread
     * @throws \Exception
     */
    private function extractThread(array $phpThread): Thread
    {
        $threadId = $phpThread[self::THREAD_NUMBER];
        $threadUrl = "https://2ch.hk/pr/res/{$threadId}.json";

        $responseBody = $this->client->get($threadUrl)->getBody();
        $responseJson = \GuzzleHttp\json_decode($responseBody, $assoc = true);

        $postsArray = $responseJson['threads'][0]['posts'] ?? null;

        if ($postsArray === null) {
            throw new \Exception("2ch.hk API has changed, path threads[0].posts is not exists");
        }
        
        $thread = new Thread($threadId);

        foreach ($postsArray as $postArray) {
            $thread->addPost($this->extractPost($postArray, $thread));
        }

        return $thread;
    }

    /**
     * @param array $postArray
     * @param Thread $thread
     * @return Post
     */
    private function extractPost(array $postArray, Thread $thread): Post
    {
        $post = new Post(
            $postArray[self::POST_NUMBER],
            $postArray[self::POST_TITLE],
            $postArray[self::POST_AUTHOR],
            (new \DateTimeImmutable())->setTimestamp($postArray['timestamp']),
            $postArray[self::POST_TEXT],
            $thread
        );

        foreach ($postArray['files'] as $fileArray) {
            $file = $this->extractFile($fileArray, $post);
            $this->eventManager->trigger(Event::FILE_EXTRACTED, $file);
            $post->addFile($file);
        }

        return $post;
    }

    /**
     * @param array $fileArray
     * @param Post $post
     * @return File
     */
    private function extractFile(array $fileArray, Post $post): File
    {
        return File::create(
            self::BASE_URL . $fileArray['path'],
            self::BASE_URL . $fileArray['thumbnail'],
            $fileArray['width'],
            $fileArray['height'],
            $post,
            $fileArray['size']
        );
    }
}