<?php

declare(strict_types=1);

namespace phpClub\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use phpClub\Entity\Thread;

class ThreadRepository extends BaseEntityRepository
{
    /**
     * @param int $page
     * @param int $perPage
     * @return Pagerfanta
     */
    public function getWithLastPosts($page = 1, int $perPage = 10): Pagerfanta
    {
        $dql = 'SELECT t, lp FROM phpClub\Entity\Thread t
                JOIN t.lastPosts lp
                ORDER BY t.id DESC, lp.id ASC';

        $adapter = new DoctrineORMAdapter($this->getEntityManager()->createQuery($dql));

        return (new Pagerfanta($adapter))
            ->setCurrentPage($page)
            ->setMaxPerPage($perPage);
    }
}
