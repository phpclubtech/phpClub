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
use phpClub\Repository\ArchiveLinkRepository;
use phpClub\Repository\ThreadRepository;

class Linker
{
    public function __construct(ArchiveLinkRepository $archiveLinkRepository, ThreadRepository $threadRepository)
    {
        $this->archiveLinkRepository = $archiveLinkRepository;
        $this->threadRepository = $threadRepository;
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

            $linkRepository = $this->archiveLinkRepository;

            if (Validator::validateArchiveLink($post['archive-link'])) {
                $thread = $this->threadRepository->find($post['thread']);

                if ($thread) {
                    if (!$linkRepository->findOneBy(['link' => $post['archive-link']])) {
                        $archiveLink = new ArchiveLink();
                        $archiveLink->setThread($thread);
                        $archiveLink->setLink($post['archive-link']);

                        $this->archiveLinkRepository($archiveLink);
                        $this->archiveLinkRepository();

                        return $thread->getNumber();
                    }
                }
            }
        }

        return false;
    }

    public function removeLink(int $id)
    {
        $archiveLink = $this->$this->archiveLinkRepository->find($id);

        $threadNumber = $archiveLink->getThread()->getNumber();

        if ($archiveLink) {
            $this->archiveLinkRepository->remove($archiveLink);
            $this->archiveLinkRepository->flush();

            return $threadNumber;
        }

        return false;
    }
}
