<?php

declare(strict_types=1);

namespace phpClub\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;
use phpClub\Entity\Thread;

class ThreadRepository extends BaseEntityRepository
{
    /**
     * @param int $page
     * @param int $perPage
     * @return Thread[]|iterable
     */
    public function getWithLastPosts($page = 1, int $perPage = 10): iterable
    {
        $dql = 'SELECT t, lp FROM phpClub\Entity\Thread t
                JOIN t.lastPosts lp
                ORDER BY t.id DESC, lp.id ASC';

        $query = $this->getEntityManager()->createQuery($dql)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return new Paginator($query);
    }
}
