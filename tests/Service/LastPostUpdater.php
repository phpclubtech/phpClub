<?php

declare(strict_types=1);

namespace Tests\Service;

use Doctrine\ORM\EntityManager;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\Repository\ThreadRepository;
use Tests\AbstractTestCase;
use phpClub\Service\LastPostUpdater;

class LastPostUpdaterTest extends AbstractTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var LastPostUpdater
     */
    private $lastPostUpdater;

    /**
     * @var ThreadRepository
     */
    private $threadRepository;

    public function setUp()
    {
        $this->entityManager = $this->getContainer()->get(EntityManager::class);
        $this->threadRepository = $this->getContainer()->get(ThreadRepository::class);
        $this->lastPostUpdater = $this->getContainer()->get(LastPostUpdater::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    public function testLastPosts()
    {
        $threads = $this->prepareThreads();

        $this->lastPostUpdater->updateLastPosts($threads);
        
        $expectedLastPosts = [
            [15, 16],
            [11, 12, 13, 14],
            [6, 8, 9, 10],
            [1, 3, 4, 5],
        ];

        /** @var Thread[] $threadsWithLastPosts */
        $threadsWithLastPosts = $this->threadRepository->getThreadsWithLastPosts();
        
        foreach ($threadsWithLastPosts as $thread) {
            $postIds = $thread->getLastPosts()
                ->map(function (Post $post) { return $post->getId(); })
                ->toArray();
            
            $this->assertEquals($postIds, current($expectedLastPosts));
            next($expectedLastPosts);
        }
    }

    private function prepareThreads(): array
    {
        // Last posts: 1, 3, 4, 5
        $thread1 = $this->createThreadWithPosts(1, 6);
        // Last posts: 6, 8, 9, 10
        $thread2 = $this->createThreadWithPosts(6, 11);
        // Last posts: 11, 12, 13, 14
        $thread3 = $this->createThreadWithPosts(11, 15);
        // Last posts: 15, 16
        $thread4 = $this->createThreadWithPosts(15, 17);

        $this->entityManager->flush();
        $this->entityManager->clear();

        return [$thread1, $thread2, $thread3, $thread4];
    }

    private function createThreadWithPosts(int $fromId, int $toId): Thread
    {
        $thread = $this->createThread($fromId);

        for ($i = $fromId; $i < $toId; $i++) {
            $thread->addPost($this->createPost($i, $thread));
        }
        
        $this->entityManager->persist($thread);
        
        return $thread;
    }

    public function tearDown()
    {
        $this->entityManager->getConnection()->rollBack();
    }
}