<?php
namespace phpClub\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/** 
* @Entity @Table(name="threads")
* @Entity(repositoryClass="phpClub\Repository\ThreadRepository")
**/
class Thread
{

    /** @Id @Column(type="integer") **/
    protected $number;
    
    /** @OneToMany(targetEntity="phpClub\Entity\Post", mappedBy="thread") **/
    public $posts;

    /** @OneToMany(targetEntity="phpClub\Entity\ArchiveLink", mappedBy="thread") **/
    public $archiveLinks;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->archiveLinks = new ArrayCollection();
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    public function addPost(Post $post)
    {
        $this->posts[] = $post;

        return $this;
    }

    public function removePost(Post $post)
    {
        $this->posts->removeElement($post);
    }

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
    }

    public function getArchiveLinks()
    {
        return $this->archiveLinks;
    }
}
