<?php

declare(strict_types=1);

namespace phpClub\BoardClient;

use GuzzleHttp\Client;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;

class DvachClient
{
    /** Return an array of thread URLs (links to JSON files), [threadId => URL] */
    const RETURN_URLS = 'RETURN_URLS';
    
    /** Return an array of JSON files, [threadId => JSON] */
    const RETURN_BODIES = 'RETURN_BODIES';

    /** Parse threads and return an array of Thread objects [threadId => Thread] */
    const RETURN_THREADS = 'RETURN_THREADS';

    // Fixes 2ch.hk API poor naming
    private const POST_AUTHOR = 'name';
    private const POST_TITLE = 'subject';
    private const POST_TEXT = 'comment';
    private const THREAD_TITLE = 'subject';

    private Client $guzzle;

    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @param string $returnType One of constants self::RETURN_*, determines 
     *                           what will be returned.
     * @return Thread[]|string[]
     */
    public function getAlivePhpThreads(string $returnType = self::RETURN_THREADS): array {
        $responseBody = (string) $this->guzzle->get('https://2ch.hk/pr/catalog.json')->getBody();
        $responseJson = \GuzzleHttp\json_decode($responseBody, $assoc = true);
        $threads = $responseJson['threads'];
        $phpThreadsArray = array_filter($threads, [$this, 'looksLikePhpThread']);

        $result = [];

        foreach ($phpThreadsArray as $threadArray) {
            $threadId = (int) $threadArray['num'];
            $url = $this->makeThreadUrl($threadArray);

            switch ($returnType) {
                case self::RETURN_URLS:
                    $data = $url;
                    break;

                case self::RETURN_BODIES:                    
                    $data = $this->downloadThread($url);
                    break;

                case self::RETURN_THREADS:
                    $jsonString = $this->downloadThread($url);
                    $data = $this->extractThread($threadId, $jsonString);
                    break;

                default:
                    throw new \InvalidArgumentException("Invalid return type $returnType");
            }

            $result[$threadId] = $data;
        }

        return $result;
    }

    private function looksLikePhpThread(array $threadArray): bool
    {
        return (bool) preg_match('/Клуб.*PHP/ui', $threadArray[self::THREAD_TITLE]);
    }

    private function makeThreadUrl(array $phpThread): string 
    {
        $threadId = (int) $phpThread['num'];
        return "https://2ch.hk/pr/res/{$threadId}.json";
    }

    /**
     * Returns a string containing JSON for given thread URL 
     */
    private function downloadThread(string $url): string 
    {
        $responseBody = (string) $this->guzzle->get($url)->getBody();
        return $responseBody;
    }

    /**
     * @throws \Exception
     */
    private function extractThread(
        int $threadId, 
        string $threadJson
    ): Thread {
        $responseJson = \GuzzleHttp\json_decode($threadJson, $assoc = true);

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
