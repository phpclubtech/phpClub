<?php

declare(strict_types=1);

namespace phpClub\ThreadImport;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use phpClub\Entity\File;
use phpClub\Entity\Thread;
use phpClub\FileStorage\FileStorageInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class ThreadImporter
{
    private FileStorageInterface $fileStorage;
    private EntityManagerInterface $entityManager;
    private LastPostUpdater $lastPostUpdater;
    private ChainManager $chainManager;
    private Logger $logger;

    public function __construct(
        FileStorageInterface $fileStorage,
        EntityManagerInterface $entityManager,
        LastPostUpdater $lastPostUpdater,
        ChainManager $chainManager,
        Logger $logger
    ) {
        $this->fileStorage = $fileStorage;
        $this->entityManager = $entityManager;
        $this->lastPostUpdater = $lastPostUpdater;
        $this->chainManager = $chainManager;
        $this->logger = $logger;
    }

    public function import(
        array $threads, 
        callable $onThreadImported = null,
        bool $deleteImages = false
    ): void {
        $this->entityManager->beginTransaction();
        $this->cascadeRemoveThreads($threads);

        foreach ($threads as $thread) {
            if ($deleteImages) {
                $this->deleteFilesFromThread($thread);
            } else {
                $this->saveFilesFromThread($thread);    
            }
            
            $this->entityManager->persist($thread);
            $this->chainManager->insertChain($thread);
            if ($onThreadImported) {
                $onThreadImported($thread);
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->lastPostUpdater->updateLastPosts($threads);
        $this->entityManager->commit();
    }

    /**
     * @param Thread[] $threads
     */
    private function cascadeRemoveThreads(array $threads): void
    {
        $connection = $this->entityManager->getConnection();

        $threadIds = array_map(fn (Thread $thread) => $thread->getId(), $threads);

        $connection->executeQuery('DELETE FROM thread WHERE id IN (?)',
            [$threadIds],
            [Connection::PARAM_STR_ARRAY]
        );
    }

    private function deleteFilesFromThread(Thread $thread): void
    {
        foreach ($thread->getPosts() as $post) {
            $post->removeAllFiles();
        }
    }

    private function saveFilesFromThread(Thread $thread): void
    {
        foreach ($thread->getPosts() as $post) {
            foreach ($post->getFiles() as $file) {
                try {
                    $file->updatePaths(
                        $this->fileStorage->put($file->getPath(), (string) $thread->getId()),
                        $this->fileStorage->put($file->getThumbPath(), $thread->getId() . '/thumb')
                    );
                    $this->updateFileSize($file);
                } catch (IOException $e) {
                    // Unable to download, skip
                    $this->logger->warn(sprintf(
                        "Error saving image %s: %s",
                        $file->getPath(),
                        $e->getMessage()
                    ));
                }
            }
        }
    }

    private function updateFileSize(File $file): void
    {
        if (!$file->hasSize()) {
            $file->setSize($this->fileStorage->getFileSize($file->getPath()));
        }
    }
}
