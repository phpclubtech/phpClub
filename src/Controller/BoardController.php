<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 2:07 PM
 */

namespace phpClub\Controller;

use phpClub\Service\Authorizer;
use phpClub\Service\Threader;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer as View;
use Symfony\Component\Cache\Simple\AbstractCache;

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

    public function __construct(Threader $threader, Authorizer $authorizer, View $view, AbstractCache $cache)
    {
        $this->view = $view;

        $this->threader = $threader;

        $this->authorizer = $authorizer;

        $this->cache = $cache;
    }

    public function indexAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        $template = $this->getOrSetCache('/board.phtml', ['threads' => $this->threader->getThreads(), 'logged' => $this->authorizer->isLoggedIn()], 'bord_index' . $this->authorizer->isLoggedIn());

        return $this->renderHtml($response, $template);
    }

    public function threadAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        try {
            $thread = $this->threader->getThread((int) $args['thread']);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($request, $response);
        }

        $template = $this->getOrSetCache('/thread.phtml', ['thread' => $thread, 'logged' => $this->authorizer->isLoggedIn()], 'thread_' . (int) $args['thread'] . '_' . $this->authorizer->isLoggedIn());

        return $this->renderHtml($response, $template);
    }

    public function chainAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        try {
            $chain = $this->threader->getChain((int) $args['post']);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($request, $response);
        }

        $template = $this->getOrSetCache('/chain.phtml', ['posts' => $chain, 'logged' => $this->authorizer->isLoggedIn()], 'chain' . (int) $args['post'] . '_' . $this->authorizer->isLoggedIn());

        return $this->renderHtml($response, $template);
    }
    /**
     * [getOrSetCache get html template cache by key or set html cache to cache by key]
     * @param  [string] $template   [path to timplate]
     * @param  [array] $data       [array of attr inside template]
     * @param  [stirng] $name_cache [name key cache]
     * @return [string]             [string of html template with set attr]
     */
    public function getOrSetCache($template, $data, $name_cache)
    {
        $cache = $this->cache->get($name_cache);
        if (!$cache) {
            $cache = $this->view->fetch(
                $template,
                $data
            );
            $this->cache->set($name_cache, $cache);
        }
        return $cache;
    }
    /**
     * [renderHtml Render template by html string]
     * @param  [Response] $response [action response varible]
     * @param  [string] $html     [html string]
     * @return [Response]           [render templatate]
     */
    public function renderHtml($response, $html)
    {
        $response->getBody()->write($html);

        return $response;
    }
}
