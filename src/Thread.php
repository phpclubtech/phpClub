<?php
namespace App;

use Doctrine\Common\Collections\ArrayCollection;

/**
* @Entity @Table(name="threads")
**/
class Thread
{

    /** @Id @Column(type="integer") **/
    protected $number;
    
    /** @OneToMany(targetEntity="App\Post", mappedBy="thread") **/
    public $posts;

    /** @OneToMany(targetEntity="App\ArchiveLink", mappedBy="thread") **/
    public $archiveLinks;

    public function __construct() {
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

    public function addPost(\App\Post $post)
    {
        $this->posts[] = $post;

        return $this;
    }

    public function removePost(\App\Post $post)
    {
        $this->posts->removeElement($post);
    }

    public function getPosts()
    {
        return $this->posts;
    }

    public function addArchiveLink(\App\ArchiveLink $archiveLink)
    {
        $this->archiveLinks[] = $archiveLink;

        return $this;
    }

    public function removeArchiveLink(\App\ArchiveLink $archiveLink)
    {
        $this->archiveLinks->removeElement($archiveLink);;
    }

    public function getArchiveLinks()
    {
        return $this->archiveLinks;
    }
}