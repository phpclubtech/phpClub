<?php
namespace App\Entities;

/** @Entity @Table(name="archivelinks") **/
class ArchiveLink
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
    * @ManyToOne(targetEntity="App\Entities\Thread", inversedBy="posts")
    * @JoinColumn(name="thread", referencedColumnName="number")
    **/
    protected $thread;

    /** @Column(type="string") **/
    protected $link;

    public function getId()
    {
        return $this->id;
    }

    public function getThread()
    {
        return $this->thread;
    }

    public function setThread($thread)
    {
        $this->thread = $thread;

        return $this;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }
}