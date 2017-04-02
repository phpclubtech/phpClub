<?php
namespace App;

/**
* @Entity @Table(name="posts")
**/
class Post
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @Column(type="integer") **/
    protected $thread;

    /** @Column(type="integer") **/
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

    public $files;

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

    public function isOpPost()
    {
        if ($this->thread == $this->post) {
            return true;
        }

        return false;
    }
}