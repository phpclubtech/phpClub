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
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer as View;
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
     * @var \Slim\Views\PhpRenderer
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

    public function indexAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        return $this->view->render(
            $response,
            '/board.phtml',
            [
                'threads' => $this->threader->getThreads(),
                'logged' => $this->authorizer->isLoggedIn()
            ]
        );
    }

    public function threadAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        try {
            $thread = $this->threader->getThread((int)$args['thread']);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($request, $response);
        }

        return $this->view->render(
            $response,
            '/thread.phtml',
            [
                'thread' => $thread, 'logged' => $this->authorizer->isLoggedIn()
            ]
        );
    }

    public function chainAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        try {
            $chain = $this->threader->getChain((int)$args['post']);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($request, $response);
        }

        return $this->view->render(
            $response,
            '/chain.phtml',
            [
                'posts' => $chain, 'logged' => $this->authorizer->isLoggedIn()
            ]
        );
    }
}
