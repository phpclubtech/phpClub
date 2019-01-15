<?php

declare(strict_types=1);

namespace phpClub\BoardClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use phpClub\Entity\Thread;
use phpClub\ThreadParser\ArhivachThreadParser;

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
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $password;

    public function __construct(
        Client $guzzle,
        ArhivachThreadParser $threadParser,
        string $email,
        string $password
    ) {
        $this->guzzle = $guzzle;
        $this->threadParser = $threadParser;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * @param string[] $threadUrls
     *
     * @return Thread[]
     */
    public function getPhpThreads(array $threadUrls = null): array
    {
        return array_map(function ($threadUrl) {
            $threadHtml = (string) $this->guzzle->get($threadUrl)->getBody();

            return $this->threadParser->extractThread($threadHtml);
        }, $threadUrls);
    }

    /**
     * @param Thread $thread
     *
     * @return bool
     */
    public function isThreadArchived(Thread $thread): bool
    {
        $url = $this->generateArchiveLink($thread);

        try {
            $this->guzzle->get($url);
        } catch (ClientException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function generateArchiveLink(): string
    {
        return getenv('ARHIVACH_DOMAIN') . '/ajax/?act=locate_thread&url=';
    }

    public function archive(Thread $thread): void
    {
        // login using email + password, save cookie
        // send POST request to arhivach/add
        // check response is ok
    }
}
