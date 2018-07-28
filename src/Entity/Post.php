<?php

declare(strict_types=1);

namespace phpClub\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @Entity(repositoryClass="phpClub\Repository\PostRepository")
 **/
class Post
{
    /** @Id @Column(type="integer") **/
    private $id;

    /**
     * Contains HTML code.
     *
     * @Column(type="text")
     */
    private $text;

    /**
     * @var \DateTimeImmutable
     *
     * @Column(type="datetime_immutable")
     */
    private $date;

    /** @Column(type="string", nullable=true) **/
    private $email;

    /** @Column(type="boolean") */
    private $isOpPost = false;

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

    /**
     * @var ArrayCollection|RefLink[]
     *
     * @OneToMany(targetEntity="RefLink", mappedBy="post");
     * @OrderBy({"reference" = "ASC"})
     */
    private $replies;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->files = new ArrayCollection();
        $this->replies = new ArrayCollection();
    }

    public function setThread(Thread $thread): self
    {
        $this->thread = $thread;
        $this->isFirstPost = $thread->getPosts()->isEmpty() || $this->id === $thread->getPosts()->first()->getId();

        return $this;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function addFile(File $file): void
    {
        $this->files->add($file);
        $file->setPost($this);
    }

    public function addFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
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

    /**
     * Returns post body as HTML code.
     */
    public function getText()
    {
        return $this->text;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
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

    /**
     * @return RefLink[]|ArrayCollection
     */
    public function getReplies()
    {
        $repliesDepth = -1;

        $criteria = Criteria::create();

        $criteria->where(Criteria::expr()->eq('depth', $repliesDepth));

        return $this->replies->matching($criteria);
    }

    public function isOpPost(): bool
    {
        return $this->isOpPost;
    }

    public function setIsOpPost(bool $isOpPost): self
    {
        $this->isOpPost = $isOpPost;

        return $this;
    }

    public function isFirstPost(): bool
    {
        return $this->isFirstPost;
    }

    public function isOld(): bool
    {
        // Chains supported only for threads 80+
        return $this->id < 825576;
    }
}
