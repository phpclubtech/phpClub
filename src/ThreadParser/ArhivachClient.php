<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use GuzzleHttp\Client;
use phpClub\Entity\Thread;

class ArhivachClient
{
    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var ArhivachThreadParser
     */
    private $threadParser;

    /**
     * @param Client $guzzle
     * @param ArhivachThreadParser $threadParser
     */
    public function __construct(Client $guzzle, ArhivachThreadParser $threadParser)
    {
        $this->guzzle = $guzzle;
        $this->threadParser = $threadParser;
    }

    /**
     * @param string $threadUrl
     * @return Thread
     */
    public function extractThread(string $threadUrl): Thread
    {
        $threadHtml = (string) $this->guzzle->get($threadUrl)->getBody();
        $thread = $this->threadParser->extractThread($threadHtml);
        
        return $thread;
    }
}