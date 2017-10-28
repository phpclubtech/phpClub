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
    
    /** @OneToMany(targetEntity="Post", mappedBy="thread", cascade={"all"}) **/
    private $posts;

    /** @OneToMany(targetEntity="ArchiveLink", mappedBy="thread") **/
    private $archiveLinks;

    /**
     * @param $id
     * @param Post[] $posts
     * @param ArchiveLink[] $archiveLinks
     */
    public function __construct($id, array $posts = [], array $archiveLinks = [])
    {
        $this->id = $id;
        $this->posts = new ArrayCollection($posts);
        $this->archiveLinks = new ArrayCollection($archiveLinks);
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

    public function addArchiveLink(ArchiveLink $archiveLink)
    {
        $this->archiveLinks[] = $archiveLink;

        return $this;
    }

    public function removeArchiveLink(ArchiveLink $archiveLink)
    {
        $this->archiveLinks->removeElement($archiveLink);
        
        return $this;
    }

    public function getArchiveLinks()
    {
        return $this->archiveLinks;
    }
}
