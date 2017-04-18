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
        $threadsHeaders = get_headers(Helper::getCatalogUrl(), true);

        if ($threadsHeaders['Content-Type'][0] != 'application/json') {
            throw new \Exception("Invalid catalog file");
        }

        $threads = file_get_contents(Helper::getCatalogUrl());
        $threads = json_decode($threads);

        if (!$threads) {
            throw new \Exception("Failed decoding threads json file");
            
        }

        foreach ($threads->threads as $someThread) {
            if (Validator::validateThreadSubject($someThread->subject)) {

                $threadHeaders = get_headers(Helper::getThreadUrl($someThread->num), true);

                if ($threadHeaders['Content-Type'][0] != 'application/json') {
                    throw new \Exception("Invalid thread file");
                }

                $json = file_get_contents(Helper::getThreadUrl($someThread->num));
                $jsonthread = json_decode($json);

                if (!$jsonthread) {
                    throw new \Exception("Failed decoding thread json file");
    
                }

                $thread = $this->em->getRepository('App\Thread')->find($jsonthread->current_thread);

                if (!$thread) {
                    $thread = new Thread();
                    $thread->setNumber($jsonthread->current_thread);

                    mkdir(Helper::getSrcDirectoryPath());
                    mkdir(Helper::getThumbDirectoryPath());

                    $this->em->persist($thread);
                    $this->em->flush();
                }

                foreach ($jsonthread->threads['0']->posts as $jsonpost) {
                    if ($this->em->getRepository('App\Post')->find($jsonpost->num)) {
                        continue;
                    }
                    
                    $post = new Post();
                    $post->setThread($thread);
                    $post->fillData($jsonpost);

                    $this->em->persist($post);
                    $this->em->flush();

                    foreach($jsonpost->files as $jsonfile) {
                        if ($jsonfile->displayname == 'Стикер') {
                            continue;
                        }

                        $file = new File();
                        $file->setPost($post);
                        $file->fillData($jsonfile);

                        $this->em->persist($file);
                        $this->em->flush();
                        
                        $content = file_get_contents(Helper::getSrcUrl($file->getPath()));
                        $thumbnail = file_get_contents(Helper::getThumbUrl($file->getThumbnail()));

                        if (!$content or !$thumbnail) {
                            throw new \Exception("Invalid files");
                        }

                        file_put_contents(Helper::getSrcPath($file->getPath()), $content);
                        file_put_contents(Helper::getThumbPath($file->getThumbnail()), $thumbnail);
                    }
                }

                //just in case
                file_put_contents(Helper::getJsonPath($jsonthread->current_thread), $json);
            }
        }
    }

    public function runThreads()
    {
        $threadsQuery = $this->em->createQuery('SELECT t FROM App\Thread t');
        $threads = $threadsQuery->getArrayResult();

        foreach ($threads as $key => $value) {
            $thread = new Thread();
            $thread->setNumber($value['number']);

            $countQuery = $this->em->createQuery("SELECT COUNT(p) FROM App\Post p WHERE p.thread = :number");
            $countQuery->setParameter('number', $thread->getNumber());
            $count = $countQuery->getSingleScalarResult();

            $opPost = $this->em->getRepository('App\Post')->findOneBy(array('post' => $thread->getNumber()));
            $posts = $this->em->getRepository('App\Post')->findBy(array('thread' => $thread->getNumber()), array(), 3, $count - 3);

            $thread->addPost($opPost);

            foreach ($posts as $post) {
                $thread->addPost($post);
            }

            $threads[$key] = $thread;
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