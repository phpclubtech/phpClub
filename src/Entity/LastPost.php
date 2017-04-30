<?php
namespace phpClub\Entity;

/** @Entity @Table(name="lastposts") **/
class LastPost
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
    * @ManyToOne(targetEntity="phpClub\Entity\Thread", inversedBy="posts")
    * @JoinColumn(name="thread", referencedColumnName="number")
    **/
    protected $thread;

    /**
    * @ManyToOne(targetEntity="phpClub\Entity\Post")
    * @JoinColumn(name="post", referencedColumnName="post")
    */
    protected $post;


    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getThread()
    {
        return $this->thread;
    }

    public function setThread(\App\Entities\Thread $thread)
    {
        $this->thread = $thread;

        return $this;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost(\App\Entities\Post $post)
    {
        $this->post = $post;

        return $this;
    }
}
