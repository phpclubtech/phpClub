<?php
namespace App;

use \Doctrine\ORM\EntityManager;

use App\Controller;
use App\Authorizer;
use App\Validator;
use App\Entities\ArchiveLink;

class ArchiveLinkController extends Controller
{
    protected $em;
    protected $authorizer;

    public function __construct(EntityManager $em, Authorizer $authorizer)
    {
        $this->em = $em;
        $this->authorizer = $authorizer;
    }

    public function addLink()
    {
        $logged = $this->authorizer->isLoggedIn();

        if (!$logged) {
            $this->redirect();

            die();
        }

        $post = array();

        if ($_SERVER['REQUSET_METHOD'] = 'POST') {
            $post['thread'] = (isset($_POST['thread']) and is_scalar($_POST['thread'])) ? $_POST['thread'] : '';
            $post['archive-link'] = (isset($_POST['archive-link']) and is_scalar($_POST['archive-link'])) ? $_POST['archive-link'] : '';

            $post['archive-link'] = trim($post['archive-link']);

            if (Validator::validateArchiveLink($post['archive-link'])) {
                $thread = $this->em->getRepository('App\Entities\Thread')->find($post['thread']);

                if ($thread) {
                    if (!$this->em->getRepository('App\Entities\ArchiveLink')->findOneBy(['link' => $post['archive-link']])) {
                        $archiveLink = new ArchiveLink();
                        $archiveLink->setThread($thread);
                        $archiveLink->setLink($post['archive-link']);

                        $this->em->persist($archiveLink);
                        $this->em->flush();

                        $this->redirect("/pr/res/{$thread->getNumber()}.html");

                        die();
                    }
                }
            }
        }

        $this->redirect();

        die();
    }

    public function removeLink() {
        $logged = $this->authorizer->isLoggedIn();

        if (!$logged) {
            $this->redirect();

            die();
        }

        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);

        $matches = array();

        if (preg_match('/\d+/', $path, $matches)) {
            $archiveLink = $this->em->getRepository('App\Entities\ArchiveLink')->find($matches[0]);

            $threadNumber = $archiveLink->getThread()->getNumber();

            if ($archiveLink) {
                $this->em->remove($archiveLink);
                $this->em->flush();

                $this->redirect("/pr/res/{$threadNumber}.html");

                die();
            }
        }

        $this->redirect();

        die();
    }
}