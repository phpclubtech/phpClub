<?php
namespace App;

use App\Controller;
use App\Validator;
use App\Helper;
use App\Thread;
use App\Post;
use App\File;

class Threader extends Controller
{
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function update()
    {
        $threads = file_get_contents('https://2ch.hk/pr/catalog.json');

        if ($http_response_header[9] != 'Content-Type: application/json') {
            throw new Exception("Invalid catalog file");
        }

        $threads = json_decode($threads);

        if (!$threads) {
            throw new Exception("Failed decoding threads json file");
            
        }

        foreach ($threads->threads as $someThread) {
            if (Validator::validateThreadSubject($someThread->subject)) {
                $json = file_get_contents("https://2ch.hk/pr/res/{$someThread->num}.json");

                if ($http_response_header[9] != 'Content-Type: application/json') {
                    throw new Exception("Invalid thread file");
                }

                $jsonthread = json_decode($json);

                if (!jsonthread) {
                    throw new Exception("Failed decoding thread json file");
    
                }

                $thread = new Thread($this->pdo);
                $thread->number = $jsonthread->current_thread;

                if (!$thread->getThread($jsonthread->current_thread)) {
                    mkdir(__DIR__ . "/../pr/src/$jsonthread->current_thread");
                    mkdir(__DIR__ . "/../pr/thumb/$jsonthread->current_thread");

                    $thread->addThread();
                }

                foreach ($jsonthread->threads['0']->posts as $jsonpost) {
                    $post = new Post($this->pdo);

                    if ($post->getPost($jsonpost->num)) {
                        continue;
                    }

                    $post->thread = $jsonthread->current_thread;
                    $post->post = $jsonpost->num;
                    $post->comment = $jsonpost->comment;
                    $post->date = $jsonpost->date;
                    $post->email = $jsonpost->email;
                    $post->name = $jsonpost->name;
                    $post->subject = $jsonpost->subject;

                    $post->addPost();

                    foreach($jsonpost->files as $jsonfile) {
                        if ($jsonfile->displayname == 'Стикер') {
                            continue;
                        }

                        $file = new File($this->pdo);

                        $file->post = $jsonpost->num;
                        $file->displayname = $jsonfile->displayname;
                        $file->duration = (isset($jsonfile->duration)) ? $jsonfile->duration : '';
                        $file->fullname = $jsonfile->fullname;
                        $file->height = $jsonfile->height;
                        $file->md5 = $jsonfile->md5;
                        $file->name = $jsonfile->name;
                        $file->nsfw = $jsonfile->nsfw;
                        $file->path = $jsonfile->path;
                        $file->size = $jsonfile->size;
                        $file->thumbnail = $jsonfile->thumbnail;
                        $file->tn_height = $jsonfile->tn_height;
                        $file->tn_width = $jsonfile->tn_width;
                        $file->type = $jsonfile->type;
                        $file->width = $jsonfile->width;

                        $file->addFile();
                        
                        $content = file_get_contents("https://2ch.hk{$file->path}");
                        $thumbnail = file_get_contents("https://2ch.hk{$file->thumbnail}");

                        if (!$content or !$thumbnail) {
                            throw new Exception("Invalid files");
                        }

                        file_put_contents(__DIR__ . "/..{$file->path}", $content);
                        file_put_contents(__DIR__ . "/..{$file->thumbnail}", $thumbnail);
                    }
                }

                //just in case
                file_put_contents(__DIR__ . "/../json/{$jsonthread->current_thread}.json", $json);
            }
        }
    }

    public function runThreads()
    {
        //mess
        $thread = new Thread($this->pdo);
        $threads = $thread->getThreads();

        foreach ($threads as $somethread) {

            //mess
            $post = new Post($this->pdo);

            $count = $post->getCountByThread($somethread->number);

            $somethread->posts = new \SplObjectStorage();
            $somethread->posts->attach($post->getPost($somethread->number));
            $somethread->posts->addAll($post->getPostsByThread($somethread->number, 3, $count - 3));

            foreach ($somethread->posts as $threadpost) {
                $file = new File($this->pdo);
                $threadpost->files = $file->getFilesByPost($threadpost->post);
            }
        }

        $this->render('public/board.php', compact('threads'));
    }

    public function runThread()
    {
        $number = $this->getNumberQuery();

        if (!$number) {
            $this->redirect();
        }

        $thread = new Thread($this->pdo);
        $thread->number = $number;

        //mess
        $post = new Post($this->pdo);
        $thread->posts = $post->getPostsByThread($thread->number);

        foreach ($thread->posts as $threadpost) {
            //mess
            $file = new File($this->pdo);
            $threadpost->files = $file->getFilesByPost($threadpost->post);
        }

        $this->render('public/thread.php', compact('thread'));
    }

    public function runChain()
    {
        $number = $this->getChainQuery();

        if (!$number) {
            $this->redirect();
        }

        //mess
        $post = new Post($this->pdo);
        $allPosts = $post->getAllPosts();

        $refmap = Helper::createRefMap($allPosts);
        $chain = Helper::createChain($number, $refmap);

        usort($chain, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        $posts = new \SplObjectStorage();
        
        foreach ($chain as $link) {
            $post = new Post($this->pdo);
            $post = $post->getPost($link);

            if (!$post) {
                continue;
            } 

            //mess
            $file = new File($this->pdo);
            $post->files = $file->getFilesByPost($post->post);

            $posts->attach($post);
        }

        $this->render('public/chain.php', compact('posts'));
    }
}