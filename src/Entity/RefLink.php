<?php

declare(strict_types=1);

namespace phpClub\Entity;

/**
 * @Entity(repositoryClass="phpClub\Repository\ChainRepository")
 **/
class RefLink
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    private ?int $id = null;

    /**
     * @ManyToOne(targetEntity="Post", inversedBy="replies")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Post $post;

    /** 
     * @ManyToOne(targetEntity="Post")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Post $reference;

    /** @Column(type="integer") **/
    private int $depth;

    public function __construct(Post $post, Post $reference, int $depth)
    {
        $this->post = $post;
        $this->reference = $reference;
        $this->depth = $depth;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function getDepth()
    {
        return $this->depth;
    }
}
