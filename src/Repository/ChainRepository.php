<?php

declare(strict_types=1);

namespace phpClub\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use phpClub\Entity\Post;
use phpClub\Entity\RefLink;

class ChainRepository extends EntityRepository
{
    /**
     * @return ArrayCollection|Post[]
     */
    public function getChain(int $postId): ArrayCollection
    {
        /** @var RefLink[] $chain */
        $chain = $this->findBy(['post' => $postId], ['reference' => 'ASC']);

        $posts = new ArrayCollection();

        foreach ($chain as $reflink) {
            if (!$posts->contains($reflink->getReference())) {
                $posts->add($reflink->getReference());
            }
        }

        return $posts;
    }
}
