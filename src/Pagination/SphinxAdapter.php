<?php

declare(strict_types=1);

namespace phpClub\Pagination;

use Foolz\SphinxQL\Drivers\Pdo\Connection;
use Foolz\SphinxQL\SphinxQL;
use Pagerfanta\Adapter\AdapterInterface;
use phpClub\Repository\PostRepository;

class SphinxAdapter implements AdapterInterface
{
    private const MAX_SEARCH_COUNT = 10_000;
    private Connection $connection;
    private string $query;
    private PostRepository $postRepository;

    public function __construct(Connection $connection, PostRepository $postRepository, string $query)
    {
        $this->connection = $connection;
        $this->postRepository = $postRepository;
        $this->query = $query;
    }

    public function getNbResults()
    {
        $query = $this->query;

        $q = (new SphinxQL($this->connection))->select('COUNT(*)')
            ->from(['index_posts'])
            ->match('*', $query)
            ->where('is_first_post', 'NOT IN', [1])
            ->option('max_matches', self::MAX_SEARCH_COUNT);

        $result = $q->execute()->fetchAssoc();

        $count = min($result['count(*)'], self::MAX_SEARCH_COUNT);

        return $count;
    }

    public function getSlice($offset, $length)
    {
        $query = $this->query;

        $q = (new SphinxQL($this->connection))->select('*')
            ->from(['index_posts'])
            ->match('*', $query)
            ->where('is_first_post', 'NOT IN', [1])
            ->orderBy('date', 'DESC')
            ->offset($offset)
            ->limit($length)
            ->option('max_matches', self::MAX_SEARCH_COUNT);

        $ids = array_column($q->execute()->fetchAllAssoc(), 'id');

        $posts = $this->postRepository->findBy(['id' => $ids], ['date' => 'DESC']);

        return $posts;
    }
}
