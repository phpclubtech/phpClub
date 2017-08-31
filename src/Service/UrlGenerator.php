<?php

declare(strict_types=1);

namespace phpClub\Service;

use phpClub\Entity\{Thread, Post, File};
use Slim\Interfaces\RouterInterface;

class UrlGenerator
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function toThread(Thread $thread): string
    {
        return $this->router->pathFor('thread', ['id' => $thread->getId()]);
    }

    public function toFile(File $file): string
    {
        return $file->isRemote() ? $file->getRemoteUrl() : $file->getRelativePath();
    }

    public function toThumbFile(File $file): string
    {
        return $file->isRemote() ? $file->getThumbnailRemoteUrl() : $file->getThumbnailRelativePath();
    }

    public function toPostAnchor(Post $post): string
    {
        return $this->router->pathFor('thread', ['id' => $post->getThread()->getId()]) . '.html#' . $post->getId();
    }

    public function toChain(Post $post): string
    {
        return $this->router->pathFor('chain', ['id' => $post->getId()]);
    }
}