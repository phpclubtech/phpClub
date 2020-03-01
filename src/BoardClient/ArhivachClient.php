<?php

declare(strict_types=1);

namespace phpClub\BoardClient;

use GuzzleHttp\Client;
use LogicException;
use phpClub\Entity\Thread;
use phpClub\ThreadParser\ArhivachThreadParser;

class ArhivachClient
{
    private Client $guzzle;
    private ArhivachThreadParser $threadParser;

    public function __construct(
        Client $guzzle,
        ArhivachThreadParser $threadParser
    ) {
        $this->guzzle = $guzzle;
        $this->threadParser = $threadParser;
    }

    /**
     * @param string[] $threadUrls
     *
     * @return Thread[]
     */
    public function getPhpThreads(array $threadUrls = []): array
    {
        $htmls = $this->downloadThreads($threadUrls);
        $threads = [];
        foreach ($htmls as $html) {
            $threads[] = $this->threadParser->extractThread($html);
        }

        return $threads;
    }

    /**
     * Returns an array [thread id => thread HTML]
     */
    public function downloadThreads(array $urls): array
    {
        $htmls = [];
        foreach ($urls as $url) {
            $threadId = $this->getThreadIdFromUrl($url);
            if (array_key_exists($threadId, $htmls)) {
                throw new LogicException("Thread key $threadId is not unique");
            }

            $htmls[$threadId] = (string) $this->guzzle->get($url)->getBody();
        }

        return $htmls;
    }

    public function getThreadIdFromUrl(string $url): string 
    {
        // E.g. /threads/12345.html
        $path = parse_url($url, PHP_URL_PATH);
        // Remove directories. Don't use pathinfo as it is platform-dependent
        $path = preg_replace("!^(.*/)?([^/]+)/?$!u", '$2', $path);
        // Remove extension
        $path = preg_replace("!\.\w+$!u", '', $path);
        // Replace unsafe characters
        $path = preg_replace("![^a-zA-Z0-9_.,\-]+!u", '_', $path);

        if ($path === '') {
            throw new LogicException("Cannot generate safe thread name from URL $url");
        }

        return $path;
    }

    public function generateArchiveLink(): string
    {
        return getenv('ARHIVACH_DOMAIN') . '/ajax/?act=locate_thread&url=';
    }
}
