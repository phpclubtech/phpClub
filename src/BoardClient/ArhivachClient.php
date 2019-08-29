<?php

declare(strict_types=1);

namespace phpClub\BoardClient;

use GuzzleHttp\Client;
use phpClub\Entity\Thread;
use phpClub\ThreadParser\ArhivachThreadParser;

class ArhivachClient
{
    private $guzzle;
    private $threadParser;

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
        return array_map(function ($threadUrl) {
            $threadHtml = (string) $this->guzzle->get($threadUrl)->getBody();

            return $this->threadParser->extractThread($threadHtml);
        }, $threadUrls);
    }

    public function generateArchiveLink(): string
    {
        return getenv('ARHIVACH_DOMAIN') . '/ajax/?act=locate_thread&url=';
    }
}
