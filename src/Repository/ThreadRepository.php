<?php

declare(strict_types=1);

namespace phpClub\Repository;

use Pagerfanta\Pagerfanta;

class ThreadRepository extends BaseEntityRepository
{
    /**
     * @param int $page
     * @param int $perPage
     *
     * @return Pagerfanta
     */
    public function getThreadsWithLastPosts(int $page = 1, int $perPage = 10): Pagerfanta
    {
        $dql = 'SELECT t, lp FROM phpClub\Entity\Thread t
                JOIN t.lastPosts lp
                ORDER BY t.id DESC, lp.id ASC';

        return $this->paginate($this->getEntityManager()->createQuery($dql), $page, $perPage);
    }
}
