<?php
namespace App;

use App\ActiveRecord;

class Post extends ActiveRecord
{
    protected $pdo;

    public $thread;
    public $post;
    public $comment;
    public $date;
    public $email;
    public $name;
    public $subject;

    public $files;

    public function addPost()
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("INSERT INTO posts (
                thread,
                post,
                comment,
                date,
                email,
                name,
                subject
            )
             VALUES (
                :thread,
                :post,
                :comment,
                :date,
                :email,
                :name,
                :subject
                
            )
        ");

        $query->execute(array(
            ':thread' => $this->thread,
            ':post' => $this->post,
            ':comment' => $this->comment,
            ':date' => $this->date,
            ':email' => $this->email,
            ':name' => $this->name,
            ':subject' => $this->subject
        ));
    }

    public function getPost($number)
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("SELECT * FROM posts WHERE post=:post");
        $query->bindValue(':post', $number);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return false;
        }

        $post = new Post($pdo);
        
        $post->thread = $result['thread'];
        $post->post = $result['post'];
        $post->comment = $result['comment'];
        $post->date = $result['date'];
        $post->email = $result['email'];
        $post->name = $result['name'];
        $post->subject = $result['subject'];

        return $post;
    }

    public function getPostsByThread($number, $limit = 2147483647, $offset = 0)
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("SELECT * FROM posts WHERE thread=:number LIMIT :limit OFFSET :offset");
        $query->bindValue(':number', $number);
        $query->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
        $query->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        $posts = new \SplObjectStorage();

        foreach ($results as $result) {
            $post = new Post($pdo);

            $post->thread = $result['thread'];
            $post->post = $result['post'];
            $post->comment = $result['comment'];
            $post->date = $result['date'];
            $post->email = $result['email'];
            $post->name = $result['name'];
            $post->subject = $result['subject'];

            $posts->attach($post);
        }

        return $posts;
    }

    public function getAllPosts()
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("SELECT * FROM posts");
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        $posts = new \SplObjectStorage();

        foreach ($results as $result) {
            $post = new Post($pdo);

            $post->thread = $result['thread'];
            $post->post = $result['post'];
            $post->comment = $result['comment'];
            $post->date = $result['date'];
            $post->email = $result['email'];
            $post->name = $result['name'];
            $post->subject = $result['subject'];

            $posts->attach($post);
        }

        return $posts;
    }

    public function getCountByThread($number)
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE thread=:number");
        $query->bindValue(':number', $number);
        $query->execute();

        $result = $query->fetchColumn();

        return $result;

    }

    public function isOpPost()
    {
        if ($this->thread == $this->post) {
            return true;
        }

        return false;
    }
}