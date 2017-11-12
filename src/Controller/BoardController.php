<?php

namespace phpClub\Controller;

use phpClub\Repository\ThreadRepository;
use phpClub\Service\Authorizer;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;

class BoardController
{
    /**
     * @var Authorizer
     */
    private $authorizer;

    /**
     * @var PhpRenderer
     */
    private $view;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var ThreadRepository
     */
    private $threadRepository;

    public function __construct(
        Authorizer $authorizer,
        PhpRenderer $view,
        CacheInterface $cache,
        ThreadRepository $threadRepository
    ) {
        $this->view = $view;
        $this->authorizer = $authorizer;
        $this->cache = $cache;
        $this->threadRepository = $threadRepository;
    }

    public function indexAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        $template = $this->getOrSetCache('/board.phtml', [
            'threads' => $this->threadRepository->getWithLastPosts($request->getParam('page', 1)),
            'logged' => $this->authorizer->isLoggedIn()
        ], 'bord_index' . ($this->authorizer->isLoggedIn() ? $_COOKIE['token'] : false));

        return $this->renderHtml($response, $template);
    }

    public function threadAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        $thread = $this->threadRepository->find($args['thread']);

        if (!$thread) {
            throw new NotFoundException($request, $response);
        }

        $template = $this->getOrSetCache('/thread.phtml', [
            'thread' => $thread,
            'logged' => $this->authorizer->isLoggedIn()],
            'thread_' . (int) $args['thread'] .'_' . ($this->authorizer->isLoggedIn() ? $_COOKIE['token'] : false)
        );

        return $this->renderHtml($response, $template);
    }

    public function chainAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        try {
            $chain = $this->threader->getChain((int) $args['post']);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($request, $response);
        }

        $template = $this->getOrSetCache('/chain.phtml', ['posts' => $chain, 'logged' => $this->authorizer->isLoggedIn()], 'chain' . (int) $args['post'] .'_' . ($this->authorizer->isLoggedIn() ? $_COOKIE['token'] : false));

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
