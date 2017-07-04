<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 5:47 PM
 */

namespace phpClub\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use phpClub\Entity\Thread;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\Entity\LastPost;
use Symfony\Component\Cache\Simple\FilesystemCache;

class Threader
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \phpClub\Service\Authorizer
     */
    protected $authorizer;

    public function __construct(EntityManager $em, Authorizer $authorizer)
    {
        $this->em = $em;

        $this->authorizer = $authorizer;
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

                $thread = $this->em->getRepository('phpClub\Entity\Thread')->find($jsonthread->current_thread);

                if (!$thread) {
                    $thread = new Thread();
                    $thread->setNumber($jsonthread->current_thread);

                    mkdir(Helper::getSrcDirectoryPath($jsonthread->current_thread));
                    mkdir(Helper::getThumbDirectoryPath($jsonthread->current_thread));

                    $this->em->persist($thread);
                    $this->em->flush();
                }

                foreach ($jsonthread->threads['0']->posts as $jsonpost) {
                    if ($this->em->getRepository('phpClub\Entity\Post')->find($jsonpost->num)) {
                        continue;
                    }

                    $post = new Post();
                    $post->setThread($thread);
                    $post->fillData($jsonpost);

                    $this->em->persist($post);
                    $this->em->flush();


                    $limit = 3;

                    $lastPosts = $this->em->getRepository('phpClub\Entity\LastPost')
                        ->findBy(['thread' => $thread->getNumber()]);

                    if (count($lastPosts) < $limit) {
                        $lastPost = new LastPost();
                        $lastPost->setThread($thread);
                        $lastPost->setPost($post);

                        $this->em->persist($lastPost);
                        $this->em->flush();
                    } else {
                        foreach ($lastPosts as $key => $lastPost) {
                            if ($key == $limit - 1) {
                                $lastPosts[$key]->setPost($post);
                            } else {
                                $lastPosts[$key]->setPost($lastPosts[$key + 1]->getPost());
                            }

                            $this->em->flush();
                        }
                    }

                    Helper::insertChain($this->em, $post, $post);

                    foreach ($jsonpost->files as $jsonfile) {
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
        $cache = new FilesystemCache();
        $cache->clear();
    }

    public function getThreads()
    {
        //$logged = $this->authorizer->isLoggedIn();

        $threadsQuery = $this->em->createQuery('SELECT COUNT(p) as post_count, t.number FROM phpClub\Entity\Thread t  JOIN phpClub\Entity\Post p WHERE p.thread = t.number ORDER BY t.number DESC');
        $threads = $threadsQuery->getArrayResult();

        foreach ($threads as $key => $value) {
            $thread = new Thread();
            $thread->setNumber($value['number']);
            
            $count = $value["post_count"];
            
            $opPost = $this->em
                ->getRepository('phpClub\Entity\Post')
                ->findOneBy(['post' => $thread->getNumber()]);
            $lastPosts = $this->em
                ->getRepository('phpClub\Entity\LastPost')
                ->findBy(['thread' => $thread->getNumber()]);

            $thread->addPost($opPost);

            foreach ($lastPosts as $lastPost) {
                $thread->addPost($lastPost->getPost());
            }

            $threads[$key] = $thread;
        }

        return $threads;
    }

    public function getThread(int $number)
    {
        $thread = $this->em->getRepository('phpClub\Entity\Thread')->find($number);
        
        if ($thread === null) {
            throw new \InvalidArgumentException("Thread with number {$number} does not exist in the system.");
        }

        return $thread;
    }

    public function getChain(int $number)
    {
        // $logged = $this->authorizer->isLoggedIn();

        // $number = $this->getChainQuery();

        /*if (!$number) {
            $this->redirect();
        }*/

        $chain = $this->em->getRepository('phpClub\Entity\RefLink')
            ->findBy(['post' => $number], ['reference' => "ASC"]);

        if ($chain === null) {
            throw new \InvalidArgumentException("Chain with number {$number} does not exist in the system.");
        }

        $posts = new ArrayCollection();

        foreach ($chain as $reflink) {
            if (!$posts->contains($reflink->getReference())) {
                $posts->add($reflink->getReference());
            }
        }

        return $posts;
    }
}
