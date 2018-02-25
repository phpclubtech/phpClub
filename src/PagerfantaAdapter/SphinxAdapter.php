<?php
namespace phpClub\PagerfantaAdapter;

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

        $q = $pdo->prepare("SELECT * FROM index_posts WHERE MATCH (:search) ORDER BY id ASC LIMIT 1000");
        $q->bindValue(':search', $query);
        $q->execute();

        $results = $q->fetchAll();

        return count($results);
    }
    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $pdo = $this->pdo;

        $query = $this->query;

        $ids = [];

        $q = $pdo->prepare("SELECT * FROM index_posts WHERE MATCH (:search) ORDER BY id ASC LIMIT {$offset},{$length}");
        $q->bindValue(':search', $query);
        $q->execute();

        $results = $q->fetchAll();

        foreach ($results as $result) {
            $ids[] = $result['id'];
        }

        $posts = $this->postRepository->findBy(['id'=>$ids]);

        return $posts;
    }
}