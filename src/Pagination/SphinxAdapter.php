<?php

namespace phpClub\Pagination;

use Pagerfanta\Adapter\AdapterInterface;
use phpClub\Repository\PostRepository;
use Foolz\SphinxQL\SphinxQL;
use Foolz\SphinxQL\Drivers\Pdo\Connection;

class SphinxAdapter implements AdapterInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var PostRepository
     */
    protected $postRepository;

    public function __construct(Connection $connection, PostRepository $postRepository, string $query)
    {
        $this->connection = $connection;
        $this->postRepository = $postRepository;
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        $query = $this->query;

        $q = (new SphinxQL($this->connection))->select('COUNT(*)')
            ->from('index_posts')
            ->match('*', $query);

        $result = $q->execute()->fetchAssoc();

        $count = $result['count(*)'];

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $query = $this->query;

        $q = (new SphinxQL($this->connection))->select('*')
            ->from('index_posts')
            ->match('*', $query)
            ->where('is_first_post', 'NOT IN', [1])
            ->orderBy('date', 'DESC')
            ->offset($offset)
            ->limit($length)
            ->option('max_matches', 10000);

        $ids = array_column($q->execute()->fetchAllAssoc(), 'id');

        $posts = $this->postRepository->findBy(['id' => $ids], ['date' => 'DESC']);

        return $posts;
    }
}
