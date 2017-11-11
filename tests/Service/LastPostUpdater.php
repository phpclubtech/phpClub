<?php

declare(strict_types=1);

namespace Tests\Service;

use Doctrine\ORM\EntityManager;
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
        $this->entityManager = $this->getContainer()->get('EntityManager');
        $this->threadRepository = $this->getContainer()->get('ThreadRepository');
        $this->lastPostUpdater = $this->getContainer()->get('LastPostUpdater');
        $this->entityManager->getConnection()->beginTransaction();
        parent::setUp();
    }

    public function testLastPosts()
    {
        $threads = $this->prepareThreads();

        $this->lastPostUpdater->updateLastPosts($threads);

        $threadsWithLastPosts = $this->threadRepository->getWithLastPosts();

        list($thread4, $thread3, $thread2, $thread1) = $threadsWithLastPosts;
        
        $this->assertHasLastPosts($thread4, [15, 16]);
        $this->assertHasLastPosts($thread3, [11, 12, 13, 14]);
        $this->assertHasLastPosts($thread2, [6, 8, 9, 10]);
        $this->assertHasLastPosts($thread1, [1, 3, 4, 5]);
    }

    private function assertHasLastPosts(Thread $thread, array $expectedLastPosts): void
    {
        foreach ($thread->getLastPosts() as $lastPost) {
            $this->assertEquals(current($expectedLastPosts), $lastPost->getId());
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