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
     * The client-provided file name
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

    public function __construct(
        string $path,
        string $thumbPath,
        Post $post,
        int $height,
        int $width,
        int $size = null,
        string $clientName = null
    ) {
        $this->path = $path;
        $this->thumbPath = $thumbPath;
        $this->post = $post;
        $this->height = $height;
        $this->width = $width;
        $this->size = $size;
        $this->clientName = $clientName;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getSize(): int
    {
        return $this->size ?: 0;
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
}
