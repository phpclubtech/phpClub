<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 10:43 PM
 */

namespace phpClub\Controller;

use Slim\Http\Response;
use Slim\Http\Request;
use phpClub\Service\View;
use phpClub\Service\Authorizer;
use phpClub\Service\Linker;

/**
 * Class ArchiveLinkController
 *
 * @package phpClub\Controller
 * @author foobar1643 <foobar76239@gmail.com>
 */
class ArchiveLinkController
{
    /**
     * @var \phpClub\Service\View
     */
    protected $view;

    /**
     * @var \phpClub\Service\Authorizer
     */
    protected $authorizer;

    /**
     * @var \phpClub\Service\Linker
     */
    protected $linker;

    public function __construct(Authorizer $authorizer, Linker $linker, View $view)
    {
        $this->view = $view;

        $this->authorizer = $authorizer;

        $this->linker = $linker;
    }

    public function addLinkAction(Request $request, Response $response, array $args = []): Response
    {
        if ($this->authorizer->isLoggedIn()) {
            return $response->withRedirect('/');
        }

        $threadNumber = $this->linker->addLink();
        $redirect = ($threadNumber === false) ? '/' : "/pr/res/{$threadNumber}.html";

        return $response->withRedirect($redirect);
    }

    public function removeLinkAction(Request $request, Response $response, array $args = []): Response
    {
        if ($this->authorizer->isLoggedIn()) {
            return $response->withRedirect('/');
        }

        $threadNumber = $this->linker->removeLink();
        $redirect = ($threadNumber === false) ? '/' : "/pr/res/{$threadNumber}.html";

        return $response->withRedirect($redirect);
    }
}
