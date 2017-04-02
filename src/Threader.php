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
    protected $em;

    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    public function update()
    {
        $threads = file_get_contents('https://2ch.hk/pr/catalog.json');

        if ($http_response_header[9] != 'Content-Type: application/json') {
            throw new \Exception("Invalid catalog file");
        }

        $threads = json_decode($threads);

        if (!$threads) {
            throw new \Exception("Failed decoding threads json file");
            
        }

        foreach ($threads->threads as $someThread) {
            if (Validator::validateThreadSubject($someThread->subject)) {
                $json = file_get_contents("https://2ch.hk/pr/res/{$someThread->num}.json");

                if ($http_response_header[9] != 'Content-Type: application/json') {
                    throw new \Exception("Invalid thread file");
                }

                $jsonthread = json_decode($json);

                if (!jsonthread) {
                    throw new \Exception("Failed decoding thread json file");
    
                }

                $thread = new Thread();
                $thread->setNumber($jsonthread->current_thread);

                if (!$this->em->getRepository('App\Thread')->findOneBy(array('number' => $jsonthread->current_thread))) {
                    mkdir(__DIR__ . "/../pr/src/$jsonthread->current_thread");
                    mkdir(__DIR__ . "/../pr/thumb/$jsonthread->current_thread");

                    $this->em->persist($thread);
                    $this->em->flush();
                }

                foreach ($jsonthread->threads['0']->posts as $jsonpost) {
                    if ($this->em->getRepository('App\Post')->findOneBy(array('post' => $jsonpost->num))) {
                        continue;
                    }
                    
                    $post = new Post();

                    $post->setThread($jsonthread->current_thread);
                    $post->setPost($jsonpost->num);
                    $post->setComment($jsonpost->comment);
                    $post->setDate($jsonpost->date);
                    $post->setEmail($jsonpost->email);
                    $post->setName($jsonpost->name);
                    $post->setSubject($jsonpost->subject);

                    $this->em->persist($post);
                    $this->em->flush();

                    foreach($jsonpost->files as $jsonfile) {
                        if ($jsonfile->displayname == 'Стикер') {
                            continue;
                        }

                        $file = new File();

                        $file->setPost($jsonpost->num);
                        $file->setDisplayname($jsonfile->displayname);
                        $file->setDuration((isset($jsonfile->duration)) ? $jsonfile->duration : '');
                        $file->setFullname($jsonfile->fullname);
                        $file->setHeight($jsonfile->height);
                        $file->setMd5($jsonfile->md5);
                        $file->setName($jsonfile->name);
                        $file->setNsfw($jsonfile->nsfw);
                        $file->setPath($jsonfile->path);
                        $file->setSize($jsonfile->size);
                        $file->setThumbnail($jsonfile->thumbnail);
                        $file->setTn_height($jsonfile->tn_height);
                        $file->setTn_width($jsonfile->tn_width);
                        $file->setType($jsonfile->type);
                        $file->setWidth($jsonfile->width);

                        $this->em->persist($file);
                        $this->em->flush();
                        
                        $content = file_get_contents("https://2ch.hk{$file->getPath()}");
                        $thumbnail = file_get_contents("https://2ch.hk{$file->getThumbnail()}");

                        if (!$content or !$thumbnail) {
                            throw new \Exception("Invalid files");
                        }

                        file_put_contents(__DIR__ . "/..{$file->getPath()}", $content);
                        file_put_contents(__DIR__ . "/..{$file->getThumbnail()}", $thumbnail);
                    }
                }

                //just in case
                file_put_contents(__DIR__ . "/../json/{$jsonthread->current_thread}.json", $json);
            }
        }
    }

    public function runThreads()
    {
        $threads = $this->em->getRepository('App\Thread')->findAll();

        foreach ($threads as $thread) {
            $countQuery = $this->em->createQuery("SELECT COUNT(p) FROM App\Post p WHERE p.thread = :number");
            $countQuery->setParameter('number', $thread->getNumber());
            $count = $countQuery->getSingleScalarResult();

            $opPost = $this->em->getRepository('App\Post')->findOneBy(array('post' => $thread->getNumber()));
            $posts = $this->em->getRepository('App\Post')->findBy(array('thread' => $thread->getNumber()), array(), 3, $count - 3);

            array_unshift($posts, $opPost);

            $thread->posts = $posts;

            foreach ($thread->posts as $post) {
                $post->files = $this->em->getRepository('App\File')->findBy(array('post' => $post->getPost()));
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

        $thread = new Thread();
        $thread->setNumber($number);

        $thread->posts = $this->em->getRepository('App\Post')->findBy(array('thread' => $thread->getNumber()));

        foreach ($thread->posts as $post) {
            $post->files = $this->em->getRepository('App\File')->findBy(array('post' => $post->getPost()));
        }

        $this->render('public/thread.php', compact('thread'));
    }

    public function runChain()
    {
        $number = $this->getChainQuery();

        if (!$number) {
            $this->redirect();
        }

        $allPosts = $this->em->getRepository('App\Post')->findAll();

        $refmap = Helper::createRefMap($allPosts);
        $chain = Helper::createChain($number, $refmap);

        usort($chain, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        $posts = array();
        
        foreach ($chain as $link) {
            $post = $this->em->getRepository('App\Post')->findOneBy(array('post' => $link));

            if (!$post) {
                continue;
            } 

            $post->files = $this->em->getRepository('App\File')->findBy(array('post' => $post->getPost()));

            $posts[] = $post;
        }

        $this->render('public/chain.php', compact('posts'));
    }
}