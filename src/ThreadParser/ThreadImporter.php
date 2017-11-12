<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Evenement\EventEmitterTrait;
use phpClub\Entity\Thread;
use phpClub\Service\LastPostUpdater;
use phpClub\ThreadParser\FileStorage\FileStorageInterface;

class ThreadImporter
{
    use EventEmitterTrait;

    const EVENT_THREAD_PERSISTED = 'event.thread.persisted';

    /**
     * @var FileStorageInterface
     */
    private $fileStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LastPostUpdater
     */
    private $lastPostUpdater;

    public function __construct(
        FileStorageInterface $fileStorage,
        EntityManagerInterface $entityManager,
        LastPostUpdater $lastPostUpdater
    ) {
        $this->fileStorage = $fileStorage;
        $this->entityManager = $entityManager;
        $this->lastPostUpdater = $lastPostUpdater;
    }

    /**
     * @param Thread[] $threads
     */
    public function import(array $threads): void
    {
        $this->cascadeRemoveThreads($threads);

        foreach ($threads as $thread) {
            $this->saveFilesFromThread($thread);
            $this->entityManager->persist($thread);
            $this->emit(self::EVENT_THREAD_PERSISTED, [$thread]);
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->lastPostUpdater->updateLastPosts($threads);
        // TODO: recalculate chains
    }

    /**
     * @param Thread[] $threads
     * @return void
     */
    private function cascadeRemoveThreads(array $threads): void
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();

        $threadIds = array_map(function (Thread $thread) {
            return $thread->getId();
        }, $threads);

        $connection->executeQuery('DELETE FROM thread WHERE id IN (?)',
            [$threadIds],
            [Connection::PARAM_STR_ARRAY]
        );
    }

    private function saveFilesFromThread(Thread $thread): void
    {
        foreach ($thread->getPosts() as $post) {
            foreach ($post->getFiles() as $file) {
                $file->updatePaths(
                    $this->fileStorage->put($file->getPath(), (string) $thread->getId()),
                    $this->fileStorage->put($file->getThumbPath(), $thread->getId() . '/thumb')
                );
            }
        }
    }
}