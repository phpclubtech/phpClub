<?php
namespace phpClub\Repository;

use phpClub\Entity\Thread;
use phpClub\Repository\BaseEntityRepository;

class ThreadRepository extends BaseEntityRepository
{
    /**
     * @param int $page
     * @param int $perPage
     * @return Thread[]
     */
    public function getWithLastPosts($page = 1, $perPage = 15): array
    {
        $dql = 'SELECT t, lp FROM phpClub\Entity\Thread t
                JOIN t.lastPosts lp
                ORDER BY t.id DESC, lp.id ASC';

        // TODO: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/pagination.html

        $query = $this->getEntityManager()->createQuery($dql);

        return $query->getResult();
    }
}
