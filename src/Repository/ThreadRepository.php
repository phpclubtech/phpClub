<?php
namespace phpClub\Repository;

use phpClub\Repository\BaseEntityRepository;

class ThreadRepository extends BaseEntityRepository
{

    public function getPostCount()
    {
        $em = $this->getEntityManager();
        return $em->createQuery('SELECT COUNT(p) as post_count, t.number
				FROM phpClub\Entity\Thread t  JOIN phpClub\Entity\Post p
				WHERE p.thread = t.number
				GROUP BY t.number
				ORDER BY t.number DESC');
    }

}
