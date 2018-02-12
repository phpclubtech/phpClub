<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 8:57 PM.
 */

namespace phpClub\Service;

use Doctrine\Common\Collections\ArrayCollection;
use phpClub\Repository\PostRepository;

class Searcher
{
    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function search(string $searchQuery)
    {
        $pdo = new \PDO('mysql:host=127.0.0.1;port=9306');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $posts = new ArrayCollection();
        $ids = [];

        $query = $pdo->prepare('SELECT * FROM index_posts WHERE MATCH (:search) ORDER BY id ASC LIMIT 1000');
        $query->bindValue(':search', $searchQuery);
        $query->execute();

        $results = $query->fetchAll();

        foreach ($results as $result) {
            $ids[] = $result['id'];
        }

        $posts = $this->postRepository->findBy(['id'=>$ids]);

        return $posts;
    }
}
