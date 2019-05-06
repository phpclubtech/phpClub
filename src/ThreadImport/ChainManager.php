<?php

declare(strict_types=1);

namespace phpClub\ThreadImport;

use Doctrine\ORM\EntityManagerInterface;
use phpClub\Entity\Post;
use phpClub\Entity\RefLink;
use phpClub\Entity\Thread;
use phpClub\Repository\PostRepository;

class ChainManager
{
    private $entityManager;
    private $postRepository;

    public function __construct(EntityManagerInterface $entityManager, PostRepository $postRepository)
    {
        $this->entityManager = $entityManager;
        $this->postRepository = $postRepository;
    }

    /**
     * @param Thread $thread
     */
    public function insertChain(Thread $thread): void
    {
        /** @var Post $firstPost */
        $firstPost = $thread->getPosts()->first();
        if ($firstPost->isFirstPost() && $firstPost->isOld()) {
            return;
        }

        foreach ($thread->getPosts() as $post) {
            $this->recursiveInsertChain($post);
        }

        $this->entityManager->flush();
    }

    /**
     * @param Post      $forPost
     * @param Post|null $reference
     * @param int       $depth
     */
    private function recursiveInsertChain(Post $forPost, Post $reference = null, int $depth = 0): void
    {
        $reference = $reference ?: $forPost;

        if ($depth === 0) {
            $reflink = new Reflink($forPost, $forPost, $depth);
            $this->entityManager->persist($reflink);
        }

        $references = $this->parseReferences($reference);

        foreach ($references as $reference) {
            /** @var Post|null $reference */
            $reference = $this->postRepository->find($reference);
            if ($reference && !$reference->isFirstPost()) {
                $reflink = new RefLink($forPost, $reference, $depth + 1);
                $this->entityManager->persist($reflink);
                $reflink = new RefLink($reference, $forPost, $depth * -1 - 1);
                $this->entityManager->persist($reflink);
                $this->recursiveInsertChain($forPost, $reference, $depth + 1);
            }
        }
    }

    /**
     * @param Post $post
     *
     * @return array
     */
    private function parseReferences(Post $post): array
    {
        $regexp = '/<a href="[\S]+" class="post-reply-link" data-thread="(\d+)" data-num="(\d+)">/';
        preg_match_all($regexp, $post->getText(), $matches);

        return $matches[2];
    }

    public function removeAllChains(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeQuery('DELETE FROM ref_link');
    }
}
