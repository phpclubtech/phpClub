<?php

namespace phpClub\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="phpClub\Repository\ThreadRepository")
 **/
class Thread
{
    /** @Id @Column(type="integer") **/
    private $id;

    /**
     * @OneToMany(targetEntity="Post", mappedBy="thread", cascade={"all"})
     **/
    private $posts;

    /**
     * @var Post[]
     * @ManyToMany(targetEntity="Post")
     * @JoinTable(name="last_post",
     *      joinColumns={@JoinColumn(name="thread_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="post_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $lastPosts;

    /**
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->posts = new ArrayCollection();
        $this->lastPosts = new ArrayCollection();
    }

    public function setPosts(array $posts = []): self
    {
        $this->posts = $posts;
    }

    public function addPost(Post $post)
    {
        $this->posts->add($post);
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Post[]|ArrayCollection
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @return Post[]|ArrayCollection
     */
    public function getLastPosts()
    {
        return $this->lastPosts;
    }
}
