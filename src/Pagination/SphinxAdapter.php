<?php

namespace phpClub\Pagination;

use Pagerfanta\Adapter\AdapterInterface;
use phpClub\Repository\PostRepository;

class SphinxAdapter implements AdapterInterface
{
    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var PostRepository
     */
    protected $postRepository;

    public function __construct(\PDO $pdo, PostRepository $postRepository, string $query)
    {
        $this->pdo = $pdo;
        $this->postRepository = $postRepository;
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        $pdo = $this->pdo;

        $query = $this->query;

        $q = $pdo->prepare('SELECT COUNT(*) FROM index_posts WHERE MATCH (:search)');
        $q->bindValue(':search', $query);
        $q->execute();

        $count = $q->fetchColumn();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $query = $this->query;

        $q = $this->pdo->prepare('SELECT * FROM index_posts WHERE MATCH (:search) AND is_first_post != 1 ORDER BY date DESC LIMIT :offset, :length OPTION max_matches=100000');
        $q->bindValue(':search', $query);
        $q->bindValue(':length', $length, \PDO::PARAM_INT);
        $q->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $q->execute();

        $ids = array_column($q->fetchAll(), 'id');

        $posts = $this->postRepository->findBy(['id' => $ids]);

        return $posts;
    }
}
