<?php
namespace phpClub\Entity;

/**
* @Entity @Table(name="refmap")
**/
class RefLink
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
    * @ManyToOne(targetEntity="phpClub\Entity\Post")
    * @JoinColumn(name="post", referencedColumnName="post")
    */
    protected $post;

    /**
    * @ManyToOne(targetEntity="phpClub\Entity\Post")
    * @JoinColumn(name="reference", referencedColumnName="post")
    */
    protected $reference;

    /** @Column(type="integer") **/
    protected $depth;

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
