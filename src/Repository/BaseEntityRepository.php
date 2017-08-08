<?php
namespace phpClub\Repository;

use Doctrine\ORM\EntityRepository;

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
}
