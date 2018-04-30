<?php

declare(strict_types=1);

namespace phpClub\Entity;

/**
 * @Entity(repositoryClass="phpClub\Repository\FileRepository")
 **/
class File
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    private $id;

    /**
     * @var string
     * @Column(type="string")
     */
    private $path;

    /**
     * @var string
     * @Column(type="string")
     */
    private $thumbPath;

    /** @Column(type="integer", nullable=true) **/
    private $size;

    /** @Column(type="integer", nullable=true) **/
    private $width;

    /** @Column(type="integer", nullable=true) **/
    private $height;

    /**
     * The client-provided file name.
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    private $clientName;

    /**
     * @var Post
     * @ManyToOne(targetEntity="Post", inversedBy="files")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private $post;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function hasSize(): bool
    {
        return (bool) $this->size;
    }

    public function getSize(): int
    {
        return 0;

        //return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getThumbPath(): string
    {
        return $this->thumbPath;
    }

    public function getName(): string
    {
        return $this->clientName ?: basename($this->path);
    }

    public function updatePaths(string $path, string $thumbPath): void
    {
        $this->path = $path;
        $this->thumbPath = $thumbPath;
    }

    public function setPath(string $path): File
    {
        $this->path = $path;

        return $this;
    }

    public function setThumbPath(string $thumbPath): File
    {
        $this->thumbPath = $thumbPath;

        return $this;
    }

    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    public function setClientName(string $clientName): File
    {
        $this->clientName = $clientName;

        return $this;
    }

    public function setPost(Post $post): File
    {
        $this->post = $post;

        return $this;
    }
}
