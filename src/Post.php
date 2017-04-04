<?php
namespace App;

use Doctrine\Common\Collections\ArrayCollection;

/**
* @Entity @Table(name="posts")
**/
class Post
{
    /**
    * @ManyToOne(targetEntity="App\Thread", inversedBy="posts")
    * @JoinColumn(name="thread", referencedColumnName="number")
    **/
    protected $thread;

    /** @Id @Column(type="integer") **/
    protected $post;

    /** @Column(type="text") **/
    protected $comment;

    /** @Column(type="string") **/
    protected $date;

    /** @Column(type="string") **/
    protected $email;

    /** @Column(type="string") **/
    protected $name;

    /** @Column(type="string") **/
    protected $subject;

    /** @OneToMany(targetEntity="App\File", mappedBy="post") **/
    public $files;

    public function __construct() {
        $this->files = new ArrayCollection();
    }

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
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost($post)
    {
        $this->post = $post;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function addFile(\App\File $file)
    {
        $this->files[] = $file;

        return $this;
    }

    public function removeFile(\App\File $file)
    {
        $this->files->removeElement($file);
    }

    public function getFiles()
    {
        return $this->files;
    }
    
    public function isOpPost()
    {
        if ($this->thread->getNumber() == $this->post) {
            return true;
        }

        return false;
    }
}