<?php

declare(strict_types=1);

namespace phpClub\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
* @Entity(repositoryClass="phpClub\Repository\PostRepository")
**/
class Post
{
    /** @Id @Column(type="integer") **/
    private $id;

    /** @Column(type="text") **/
    private $text;

    /** @Column(type="date") **/
    private $date;

    /** @Column(type="string", nullable=true) **/
    private $email;

    /** @Column(type="boolean") */
    private $isOpPost;
    
    /** @Column(type="boolean") */
    private $isFirstPost;

    /** @Column(type="string") **/
    private $title;

    /** @Column(type="string") **/
    private $author;
    
    /**
     * @OneToMany(targetEntity="File", mappedBy="post", cascade={"all"})
     **/
    private $files;

    /**
     * @ManyToOne(targetEntity="Thread", inversedBy="posts")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private $thread;

    public function __construct(
        $id,
        string $title,
        string $author,
        \DateTimeImmutable $date,
        string $text,
        Thread $thread,
        array $files = [],
        bool $isOpPost = false,
        string $email = null
    ) {
        $this->id = $id;
        $this->text = $text;
        $this->date = $date;
        $this->email = $email;
        $this->title = $title;
        $this->author = $author;
        $this->thread = $thread;
        $this->isOpPost = $isOpPost;
        $this->files = new ArrayCollection($files);
        $this->isFirstPost = $thread->getPosts()->isEmpty() || $id === $thread->getPosts()->first()->getId();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addFile(File $file)
    {
        $this->files->add($file);
    }

    public function addFiles(array $files)
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getThread()
    {
        return $this->thread;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return File[]|ArrayCollection
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function isOpPost(): bool
    {
        return !! $this->isOpPost;
    }

    public function isFirstPost(): bool
    {
        return $this->isFirstPost;
    }
}
