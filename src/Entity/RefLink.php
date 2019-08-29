<?php

namespace phpClub\Entity;

/**
 * @Entity(repositoryClass="phpClub\Repository\ChainRepository")
 **/
class RefLink
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    private $id;

    /** @ManyToOne(targetEntity="Post", inversedBy="replies") */
    private $post;

    /** @ManyToOne(targetEntity="Post") */
    private $reference;

    /** @Column(type="integer") **/
    private $depth;

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
