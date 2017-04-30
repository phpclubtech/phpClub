<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 10:46 PM
 */

namespace phpClub\Service;

use Doctrine\ORM\EntityManager;
use phpClub\Entity\ArchiveLink;

class Linker
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function addLink()
    {
        $post = [];

        if ($_SERVER['REQUEST_METHOD'] = 'POST') {
            $post['thread'] = (isset($_POST['thread']) and is_scalar($_POST['thread'])) ? $_POST['thread'] : '';
            $post['archive-link'] = (isset($_POST['archive-link']) and is_scalar($_POST['archive-link']))
                ? $_POST['archive-link']
                : '';

            $post['archive-link'] = trim($post['archive-link']);
            $linkRepository = $this->em->getRepository('phpClub\Entity\Thread');

            if (Validator::validateArchiveLink($post['archive-link'])) {
                $thread = $linkRepository->find($post['thread']);

                if ($thread) {
                    if (!$linkRepository->findOneBy(['link' => $post['archive-link']])) {
                        $archiveLink = new ArchiveLink();
                        $archiveLink->setThread($thread);
                        $archiveLink->setLink($post['archive-link']);

                        $this->em->persist($archiveLink);
                        $this->em->flush();

                        return $thread->getNumber();
                    }
                }
            }
        }

        return false;
    }

    public function removeLink()
    {
        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);

        if (preg_match('/\d+/', $path, $matches)) {
            $archiveLink = $this->em->getRepository('phpClub\Entity\ArchiveLink')->find($matches[0]);

            $threadNumber = $archiveLink->getThread()->getNumber();

            if ($archiveLink) {
                $this->em->remove($archiveLink);
                $this->em->flush();

                return $threadNumber;
            }
        }

        return false;
    }
}
