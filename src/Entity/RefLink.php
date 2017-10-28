<?php
namespace phpClub\Entity;

/**
* @Entity(repositoryClass="phpClub\Repository\RefLinkRepository")
**/
class RefLink
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    private $id;

    /** @ManyToOne(targetEntity="Post") */
    private $post;

    /** @ManyToOne(targetEntity="Post") */
    private $reference;

    /** @Column(type="integer") **/
    private $depth;

    public function getId()
    {
        return $this->id;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    public function getDepth()
    {
        return $this->depth;
    }
}
