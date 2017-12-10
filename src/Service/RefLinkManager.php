<?php

declare(strict_types=1);

namespace phpClub\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use phpClub\Entity\Post;
use phpClub\Entity\RefLink;
use phpClub\Entity\Thread;

class RefLinkManager
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Thread $thread
     */
    public function insertChain(Thread $thread): void
    {
        foreach ($thread->getPosts() as $post) {
            $this->recursiveInsertChain($post);
        }

        $this->em->flush();
    }

    /**
     * @param Post $forPost
     * @param Post|null $reference
     * @param int $depth
     */
    private function recursiveInsertChain(Post $forPost, Post $reference = null, int $depth = 0): void
    {
        $reference = $reference ?: $forPost;

        if ($depth === 0) {
            $reflink = new Reflink($forPost, $forPost, $depth);
            $this->em->persist($reflink);
        }
        $references = $this->parseReferences($reference);
        foreach ($references as $r) {
            /** @var Post $r */
            $r = $this->em->getRepository(Post::class)->find($r);
            if ($r) {
                $reflink = new RefLink($forPost, $r, $depth + 1);
                $this->em->persist($reflink);
                $reflink = new RefLink($r, $forPost, $depth * -1 - 1);
                $this->em->persist($reflink);
                $this->recursiveInsertChain($forPost, $r, $depth + 1);
            }
        }
    }

    /**
     * @param Post $post
     * @return array
     */
    private function parseReferences(Post $post): array
    {
        $regexp = '/<a href="[\S]+" class="post-reply-link" data-thread="(\d+)" data-num="(\d+)">/';
        preg_match_all($regexp, $post->getText(), $matches);

        return $matches[2];
    }

    /**
     * @param int $postId
     * @return ArrayCollection
     */
    public function getChain(int $postId): ArrayCollection
    {
        /** @var RefLink[] $chain */
        $chain = $this->em->getRepository(RefLink::class)->findBy(['post' => $postId], ['reference' => 'ASC']);

        $posts = new ArrayCollection();

        foreach ($chain as $reflink) {
            if (!$posts->contains($reflink->getReference())) {
                $posts->add($reflink->getReference());
            }
        }

        return $posts;
    }
}