<?php

declare(strict_types=1);

namespace phpClub\Service;

use phpClub\Entity\{Thread, Post};
use phpClub\BoardClient\ArhivachClient;
use Slim\Interfaces\RouterInterface;

class UrlGenerator
{
    /**
     * @var RouterInterface
     */
    private $router;
    
    /**
     * @var ArhivachClient
     */
    private $arhivachClient;

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

    public function toArhivachThread(Thread $thread): string
    {
        return $this->arhivachClient->generateArchiveLink($thread);
    }

    public function toDvachArchiveThread(Thread $thread): string
    {
        // TODO: implement
        return '';
    }
    
    public function toDvachIcon()
    {
        return '/media/images/2ch.ico';
    }

    public function toArhivachIcon()
    {
        return '/media/images/arhivach.ico';
    }
}