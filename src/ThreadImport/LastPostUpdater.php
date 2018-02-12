<?php

declare(strict_types=1);

namespace phpClub\ThreadImport;

use Doctrine\DBAL\Connection;
use phpClub\Entity\Thread;

class LastPostUpdater
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function updateLastPosts(array $threads): void
    {
        assert(count($threads));

        $threadIds = array_map(function (Thread $thread) {
            return $thread->getId();
        }, $threads);

        $deleteLastPostsSql = 'DELETE FROM last_post WHERE thread_id IN (?)';
        $this->connection->executeQuery($deleteLastPostsSql, [$threadIds], [Connection::PARAM_INT_ARRAY]);

        // Select first and 3 last posts for each thread, insert into last_post
        $insertLastPostsSql = 'INSERT INTO last_post (thread_id, post_id)
                               SELECT p.thread_id, p.id FROM post p
                               JOIN post p2 ON p.thread_id = p2.thread_id AND p.id <= p2.id AND p.thread_id IN (?)
                               GROUP BY p.thread_id, p.id
                               HAVING COUNT(*) <= 3 OR p.thread_id = p.id';

        $this->connection->executeQuery($insertLastPostsSql, [$threadIds], [Connection::PARAM_INT_ARRAY]);
    }
}
