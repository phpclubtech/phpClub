<?php
namespace App;

use App\ActiveRecord;

class File extends ActiveRecord
{
    protected $pdo;

    public $post;
    public $displayname;
    public $duration;
    public $fullname;
    public $height;
    public $md5;
    public $name;
    public $nsfw;
    public $path;
    public $size;
    public $thumbnail;
    public $tn_height;
    public $tn_width;
    public $type;
    public $width;

    public function addFile()
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("INSERT INTO files (
                post,
                displayname,
                duration,
                fullname,
                height,
                md5,
                name,
                nsfw,
                path,
                size,
                thumbnail,
                tn_height,
                tn_width,
                type,
                width
            ) VALUES (
                :post,
                :displayname,
                :duration,
                :fullname,
                :height,
                :md5,
                :name,
                :nsfw,
                :path,
                :size,
                :thumbnail,
                :tn_height,
                :tn_width,
                :type,
                :width
            )"
        );

        $query->execute(array(
            ':post' => $this->post,
            ':displayname' => $this->displayname,
            ':duration' => $this->duration,
            ':fullname' => $this->fullname,
            ':height' => $this->height,
            ':md5' => $this->md5,
            ':name' => $this->name,
            ':nsfw' => $this->nsfw,
            ':path' => $this->path,
            ':size' => $this->size,
            ':thumbnail' => $this->thumbnail,
            ':tn_height' => $this->tn_height,
            ':tn_width' => $this->tn_width,
            ':type' => $this->type,
            ':width' => $this->width
        ));
    }

    public function getFilesByPost($number)
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("SELECT * FROM files WHERE post=:number");
        $query->bindValue(':number', $number);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        $files = new \SplObjectStorage();

        foreach ($results as $result) {
            $file = new File($pdo);

            $file->post = $result['post'];
            $file->displayname = $result['displayname'];
            $file->duration = $result['duration'];
            $file->fullname = $result['fullname'];
            $file->height = $result['height'];
            $file->md5 = $result['md5'];
            $file->name = $result['name'];
            $file->nsfw = $result['nsfw'];
            $file->path = $result['path'];
            $file->size = $result['size'];
            $file->thumbnail = $result['thumbnail'];
            $file->tn_height = $result['tn_height'];
            $file->tn_width = $result['tn_width'];
            $file->type = $result['type'];
            $file->width = $result['width'];

            $files->attach($file);
        }

        return $files;
    }
}