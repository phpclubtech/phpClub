<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use Doctrine\ORM\EntityManagerInterface;
use Evenement\EventEmitterTrait;
use phpClub\Entity\Thread;
use phpClub\Entity\{Post, File};
use phpClub\Repository\ThreadRepository;
use phpClub\ThreadParser\FileStorage\FileStorageInterface;
use phpClub\ThreadParser\Dto\{Post as PostDto, File as FileDto};

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
     * @var ThreadRepository
     */
    private $threadRepository;

    public function __construct(
        FileStorageInterface $fileStorage,
        EntityManagerInterface $entityManager,
        ThreadRepository $threadRepository
    ) {
        $this->fileStorage = $fileStorage;
        $this->entityManager = $entityManager;
        $this->threadRepository = $threadRepository;
    }

    /**
     * @param Thread[] $threads
     */
    public function import(array $threads): void
    {
        $this->cascadeRemoveThreads($threads);

        $this->entityManager->flush();

        foreach ($threads as $thread) {
            $this->saveFilesFromThread($thread);
            $this->entityManager->persist($thread);
            $this->emit(self::EVENT_THREAD_PERSISTED, [$thread]);
        }

        // TODO: recalculate 3 last posts
        // TODO: recalculate chains

        $this->entityManager->flush();
    }

    /**
     * @param Thread[] $threads
     * @return void
     */
    private function cascadeRemoveThreads(array $threads): void
    {
        foreach ($threads as $thread) {
            $reference = $this->entityManager->getReference(Thread::class, $thread->getId());
            $this->entityManager->remove($reference);
        }
    }

    private function saveFilesFromThread(Thread $thread): void
    {
        foreach ($thread->getPosts() as $post) {
            foreach ($post->getFiles() as $file) {
                if ($this->fileStorage->isFileExist($file->getPath(), $thread->getId())) {
                    continue;
                }

                $file->updatePaths(
                    $this->fileStorage->put($file->getPath(), $thread->getId()),
                    $this->fileStorage->put($file->getThumbPath(), $thread->getId() . '/thumb')
                );
            }
        }
    }
}