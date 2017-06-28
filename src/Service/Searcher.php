<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 8:57 PM
 */

namespace phpClub\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;

class Searcher
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function search(string $searchQuery)
    {
        $pdo = new \PDO('mysql:host=127.0.0.1;port=9306');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $posts = new ArrayCollection();
        $ids = array();

        $query = $pdo->prepare("SELECT * FROM index_posts WHERE MATCH (:search) ORDER BY id ASC LIMIT 1000");
        $query->bindValue(':search', $searchQuery);
        $query->execute();

        $results = $query->fetchAll();

        foreach ($results as $result) {
            $ids[]=$result['id'];
        }

        $posts = $this->em->getRepository('phpClub\Entity\Post')->findBy(["post"=>$ids]);

        return $posts;
    }
}
