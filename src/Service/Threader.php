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

    public function getThreads()
    {
        //$logged = $this->authorizer->isLoggedIn();

        $threadsQuery = $this->em->createQuery('SELECT t FROM phpClub\Entity\Thread t ORDER BY t.number DESC');
        $threads = $threadsQuery->getArrayResult();

        foreach ($threads as $key => $value) {
            $thread = new Thread();
            $thread->setNumber($value['number']);

            $countQuery = $this->em->createQuery("SELECT COUNT(p) FROM phpClub\Entity\Post p WHERE p.thread = :number");
            $countQuery->setParameter('number', $thread->getNumber());
            $count = $countQuery->getSingleScalarResult();

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
}
