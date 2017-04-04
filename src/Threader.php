<?php
namespace App;

use \Doctrine\ORM\EntityManager;

use App\Controller;
use App\Validator;
use App\Helper;
use App\Thread;
use App\Post;
use App\File;

class Threader extends Controller
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function update()
    {
        $threadsHeaders = get_headers('https://2ch.hk/pr/catalog.json', true);

        if ($threadsHeaders['Content-Type'][0] != 'application/json') {
            throw new \Exception("Invalid catalog file");
        }

        $threads = file_get_contents('https://2ch.hk/pr/catalog.json');
        $threads = json_decode($threads);

        if (!$threads) {
            throw new \Exception("Failed decoding threads json file");
            
        }

        foreach ($threads->threads as $someThread) {
            if (Validator::validateThreadSubject($someThread->subject)) {

                $threadHeaders = get_headers("https://2ch.hk/pr/res/{$someThread->num}.json", true);

                if ($threadHeaders['Content-Type'][0] != 'application/json') {
                    throw new \Exception("Invalid thread file");
                }

                $json = file_get_contents("https://2ch.hk/pr/res/{$someThread->num}.json");
                $jsonthread = json_decode($json);

                if (!$jsonthread) {
                    throw new \Exception("Failed decoding thread json file");
    
                }

                $thread = $this->em->getRepository('App\Thread')->find($jsonthread->current_thread);

                if (!$thread) {
                    $thread = new Thread();
                    $thread->setNumber($jsonthread->current_thread);

                    mkdir(__DIR__ . "/../pr/src/$jsonthread->current_thread");
                    mkdir(__DIR__ . "/../pr/thumb/$jsonthread->current_thread");

                    $this->em->persist($thread);
                    $this->em->flush();
                }

                foreach ($jsonthread->threads['0']->posts as $jsonpost) {
                    if ($this->em->getRepository('App\Post')->find($jsonpost->num)) {
                        continue;
                    }
                    
                    $post = new Post();

                    $post->setThread($thread);
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

                        $file->setPost($post);
                        $file->setDisplayname($jsonfile->displayname);
                        $file->setDuration((isset($jsonfile->duration)) ? $jsonfile->duration : null);
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
            $count = $thread->getPosts()->count();
            
            foreach ($thread->getPosts() as $post) {
                if ($post->isOpPost() or $thread->getPosts()->key() >= $count - 3) {
                    $thread->getPosts()->next();
                    continue;
                }

                $thread->getPosts()->removeElement($post);
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

        $thread = $this->em->getRepository('App\Thread')->find($number);

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

        $posts = new \Doctrine\Common\Collections\ArrayCollection();
        
        foreach ($chain as $link) {
            $post = $this->em->getRepository('App\Post')->findOneBy(array('post' => $link));

            if (!$post) {
                continue;
            } 

            $posts->add($post);
        }

        $this->render('public/chain.php', compact('posts'));
    }
}