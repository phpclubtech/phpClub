<?php

declare(strict_types=1);

namespace phpClub\Repository;

use Doctrine\ORM\EntityRepository;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class BaseEntityRepository extends EntityRepository
{
    public function flush()
    {
        $this->getEntityManager()->flush();
    }

    public function persist($entity)
    {
        $this->getEntityManager()->persist($entity);
    }

    public function remove($entity)
    {
    	$this->getEntityManager()->remove($entity);
    }

    public function paginate($queryOrQueryBuilder, int $page, int $perPage): Pagerfanta
    {
        $adapter = new DoctrineORMAdapter($queryOrQueryBuilder);

        return (new Pagerfanta($adapter))
            ->setCurrentPage($page)
            ->setMaxPerPage($perPage);
    }
}
