<?php

declare(strict_types=1);

namespace phpClub\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity(repositoryClass="phpClub\Repository\ThreadRepository")
 **/
class Thread
{
    /** @Id @Column(type="integer") **/
    private int $id;

    /**
     * @var Post[]|Collection
     *
     * @OneToMany(targetEntity="Post", mappedBy="thread", cascade={"all"})
     **/
    private $posts;

    /**
     * @var Post[]|Collection
     * @ManyToMany(targetEntity="Post")
     * @JoinTable(name="last_post",
     *      joinColumns={@JoinColumn(name="thread_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="post_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $lastPosts;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->posts = new ArrayCollection();
        $this->lastPosts = new ArrayCollection();
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
     * @return Post[]|Collection
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @return Post[]|Collection
     */
    public function getLastPosts()
    {
        return $this->lastPosts;
    }
}
