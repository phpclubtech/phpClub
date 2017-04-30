<?php
namespace App;

use \Doctrine\ORM\EntityManager;

class Searcher extends Controller
{
    protected $em;
    protected $authorizer;

    public function __construct(EntityManager $em, Authorizer $authorizer)
    {
        $this->em = $em;
        $this->authorizer = $authorizer;
    }

    public function search()
    {
        $logged = $this->authorizer->isLoggedIn();

        $pdo = new \PDO('mysql:host=127.0.0.1;port=9306');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $posts = new \Doctrine\Common\Collections\ArrayCollection();

        $search = $this->GetSearchQuery();

        if ($search) {
            $query = $pdo->prepare("SELECT * FROM index_posts WHERE MATCH (:search) ORDER BY id ASC");
            $query->bindValue(':search', $search);
            $query->execute();

            $results = $query->fetchAll();

            foreach ($results as $result) {
                $post = $this->em->getRepository('App\Entities\Post')->find($result['id']);

                $posts->add($post);
            }
        }

        $this->render('public/search.php', compact('logged', 'posts'));
    }
}