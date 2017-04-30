<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 2:07 PM
 */

namespace phpClub\Controller;

use Slim\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use phpClub\Service\View;
use phpClub\Service\Threader;

/**
 * Class MainPageController
 *
 * @package phpClub\Controller
 * @author foobar1643 <foobar76239@gmail.com>
 */
class BoardController extends BaseController
{
    protected $view;

    protected $threader;

    public function __construct(Threader $threader, View $view)
    {
        $this->view = $view;

        $this->threader = $threader;
    }

    public function indexAction(Request $request, Response $response, array $args = []): Response
    {
        return $this->view->renderToResponse($response, 'board', ['threads' => $this->threader->getThreads()]);
    }

    public function threadAction(Request $request, Response $response, array $args = []): Response
    {
        try {
            $thread = $this->threader->getThread((int)$args['thread']);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($request, $response);
        }

        return $this->view->renderToResponse($response, 'thread', ['thread' => $thread]);
    }
}
