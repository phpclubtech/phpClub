<?php

declare(strict_types=1);

namespace phpClub\Service;

use phpClub\BoardClient\ArhivachClient;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use Slim\Interfaces\RouterInterface;

class UrlGenerator
{
    private RouterInterface $router;
    private ArhivachClient $arhivachClient;

    public function __construct(RouterInterface $router, ArhivachClient $arhivachClient)
    {
        $this->router = $router;
        $this->arhivachClient = $arhivachClient;
    }

    public function toThread(Thread $thread): string
    {
        return $this->router->pathFor('thread', ['thread' => $thread->getId()]);
    }

    public function toPostAnchor(Post $post): string
    {
        return $this->router->pathFor('thread', ['thread' => $post->getThread()->getId()]) . '#' . $post->getId();
    }

    public function toChain(Post $post): string
    {
        return $this->router->pathFor('chain', ['post' => $post->getId()]);
    }

    public function toSearch(string $query): string
    {
        return $this->router->pathFor('search') . "?q={$query}";
    }

    public function toArhivachThread(): string
    {
        return $this->arhivachClient->generateArchiveLink();
    }
}
