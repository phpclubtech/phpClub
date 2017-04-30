<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 2:07 PM
 */

namespace phpClub\Controller;

use Slim\Exception\NotFoundException;
use Slim\Http\Response;
use Slim\Http\Request;
use phpClub\Service\View;
use phpClub\Service\Threader;
use phpClub\Service\Authorizer;

/**
 * Class MainPageController
 *
 * @package phpClub\Controller
 * @author foobar1643 <foobar76239@gmail.com>
 */
class BoardController
{
    /**
     * @var \phpClub\Service\View
     */
    protected $view;

    /**
     * @var \phpClub\Service\Threader
     */
    protected $threader;

    /**
     * @var \phpClub\Service\Authorizer
     */
    protected $authorizer;

    public function __construct(Threader $threader, Authorizer $authorizer, View $view)
    {
        $this->view = $view;

        $this->threader = $threader;

        $this->authorizer = $authorizer;
    }

    public function indexAction(Request $request, Response $response, array $args = []): Response
    {
        return $this->view->renderToResponse(
            $response,
            'board',
            [
                'threads' => $this->threader->getThreads(),
                'logged' => $this->authorizer->isLoggedIn()
            ]
        );
    }

    public function threadAction(Request $request, Response $response, array $args = []): Response
    {
        try {
            $thread = $this->threader->getThread((int)$args['thread']);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($request, $response);
        }

        return $this->view->renderToResponse(
            $response,
            'thread',
            [
                'thread' => $thread, 'logged' => $this->authorizer->isLoggedIn()
            ]
        );
    }
}
