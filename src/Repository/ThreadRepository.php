<?php

declare(strict_types=1);

namespace phpClub\Repository;

use Doctrine\ORM\Query;

class ThreadRepository extends BaseEntityRepository
{
    /**
     * @return Query
     */
    public function getThreadsWithLastPostsQuery(): Query
    {
        $dql = 'SELECT t, lp FROM phpClub\Entity\Thread t
                JOIN t.lastPosts lp
                ORDER BY t.id DESC, lp.id ASC';

        return $this->getEntityManager()->createQuery($dql);
    }
}
