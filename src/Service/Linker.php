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

            $linkRepository = $this->em->getRepository('phpClub\Entity\ArchiveLink');

            if (Validator::validateArchiveLink($post['archive-link'])) {
                $thread = $this->em->getRepository('phpClub\Entity\Thread')->find($post['thread']);

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

    public function removeLink(int $id)
    {
        $archiveLink = $this->em->getRepository('phpClub\Entity\ArchiveLink')->find($id);

        $threadNumber = $archiveLink->getThread()->getNumber();

        if ($archiveLink) {
            $this->em->remove($archiveLink);
            $this->em->flush();

            return $threadNumber;
        }

        return false;
    }
}
