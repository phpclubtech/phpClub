<?php

declare(strict_types=1);

namespace phpClub\ThreadImport;

use Doctrine\ORM\EntityManagerInterface;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\Repository\PostRepository;
use phpClub\Repository\ThreadRepository;

class ChainManager
{
    private EntityManagerInterface $entityManager;
    private PostRepository $postRepository;
    private ThreadRepository $threadRepository;

    public function __construct(EntityManagerInterface $entityManager, PostRepository $postRepository, ThreadRepository $threadRepository)
    {
        $this->entityManager = $entityManager;
        $this->postRepository = $postRepository;
        $this->threadRepository = $threadRepository;
    }

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

    private function recursiveInsertChain(Post $forPost, Post $reference = null, int $depth = 0): void
    {
        $query = 'INSERT INTO ref_link (post_id, reference_id, depth) VALUES (?, ?, ?)';

        $reference = $reference ?: $forPost;
        $connection = $this->entityManager->getConnection();
        if ($depth === 0) {
            $connection->executeQuery($query, [$forPost->getId(), $forPost->getId(), $depth]);
        }

        $references = $this->parseReferences($reference);

        foreach ($references as $reference) {
            /** @var Post|null $reference */
            $reference = $this->postRepository->find($reference);
            if ($reference && !$reference->isFirstPost()) {
                $connection->executeQuery($query, [$forPost->getId(), $reference->getId(), $depth + 1]);
                $connection->executeQuery($query, [$reference->getId(), $forPost->getId(), $depth * -1 - 1]);
                $this->recursiveInsertChain($forPost, $reference, $depth + 1);
            }
        }
    }

    private function parseReferences(Post $post): array
    {
        $regexp = '/<a href="[\S]+" class="post-reply-link" data-thread="(\d+)" data-num="(\d+)">/';
        preg_match_all($regexp, $post->getText(), $matches);

        return $matches[2];
    }

    private function removeAllChains(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeQuery('DELETE FROM ref_link');
    }

    public function rebuildAllChains(): void
    {
        $this->entityManager->getConnection()->transactional(function () {
            $this->removeAllChains();
            foreach ($this->threadRepository->findAllChunks() as $thread) {
                $this->insertChain($thread);
            }
        });
    }
}
