<?php

namespace phpClub\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use phpClub\Entity\RefLink;

class ChainRepository extends BaseEntityRepository
{
    /**
     * @param int $postId
     *
     * @return ArrayCollection
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
