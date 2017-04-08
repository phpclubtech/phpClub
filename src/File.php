<?php
namespace App;

/**
* @Entity @Table(name="files")
**/
class File
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
    * @ManyToOne(targetEntity="App\Post", inversedBy="files")
    * @JoinColumn(name="post", referencedColumnName="post")
    **/
    protected $post;

    /** @Column(type="string") **/
    protected $displayname;

    /** @Column(type="time", nullable=true) **/
    protected $duration;

    /** @Column(type="string") **/
    protected $fullname;

    /** @Column(type="integer") **/
    protected $height;

    /** @Column(type="string") **/
    protected $md5;

    /** @Column(type="string") **/
    protected $name;

    /** @Column(type="integer") **/
    protected $nsfw;

    /** @Column(type="string") **/
    protected $path;

    /** @Column(type="integer") **/
    protected $size;

    /** @Column(type="string") **/
    protected $thumbnail;

    /** @Column(type="integer") **/
    protected $tn_height;

    /** @Column(type="integer") **/
    protected $tn_width;

    /** @Column(type="integer") **/
    protected $type;

    /** @Column(type="integer") **/
    protected $width;

    public function fillData($json)
    {
        $allowed = [
            'displayname',
            'duration',
            'fullname',
            'height',
            'md5',
            'name',
            'nsfw',
            'path',
            'size',
            'thumbnail',
            'tn_height',
            'tn_width',
            'type',
            'width'
        ];

        foreach ($allowed as $field) {
            if (property_exists($json, $field)) {
                $this->$field = $json->$field;
            }
        }
    }
    
    public function getPost()
    {
        return $this->post;
    }

    public function setPost($post)
    {
        $this->post = $post;
    }

    public function getDisplayname()
    {
        return $this->displayname;
    }

    public function setDisplayname($displayname)
    {
        $this->displayname = $displayname;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getFullname()
    {
        return $this->fullname;
    }

    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function getMd5()
    {
        return $this->md5;
    }

    public function setMd5($md5)
    {
        $this->md5 = $md5;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getNsfw()
    {
        return $this->nsfw;
    }

    public function setNsfw($nsfw)
    {
        $this->nsfw = $nsfw;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    public function getTn_height()
    {
        return $this->tn_height;
    }

    public function setTn_height($tn_height)
    {
        $this->tn_height = $tn_height;
    }

    public function getTn_width()
    {
        return $this->tn_width;
    }

    public function setTn_width($tn_width)
    {
        $this->tn_width = $tn_width;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }
}