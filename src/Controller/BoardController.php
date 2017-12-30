<?php

namespace phpClub\Controller;

use phpClub\Repository\RefLinkRepository;
use phpClub\Repository\ThreadRepository;
use phpClub\Service\Authorizer;
use phpClub\ThreadImport\RefLinkGenerator;
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

    /**
     * @var RefLinkGenerator
     */
    private $refLinkManager;
    
    /**
     * @var RefLinkRepository
     */
    private $refLinkRepository;

    public function __construct(
        Authorizer $authorizer,
        PhpRenderer $view,
        CacheInterface $cache,
        ThreadRepository $threadRepository,
        RefLinkGenerator $refLinkManager,
        RefLinkRepository $refLinkRepository
    ) {
        $this->view = $view;
        $this->authorizer = $authorizer;
        $this->cache = $cache;
        $this->threadRepository = $threadRepository;
        $this->refLinkManager = $refLinkManager;
        $this->refLinkRepository = $refLinkRepository;
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $page = $request->getParam('page', 1);

        $viewArgs = [
            'threads' => $this->threadRepository->getThreadsWithLastPosts($page),
            'logged' => $this->authorizer->isLoggedIn(),
        ];

        if ($this->authorizer->isLoggedIn()) {
            return $this->view->render($response, '/board.html', $viewArgs);
        }

        $template = $this->getOrSetCache('/board.phtml', $viewArgs, 'board_index' .  $page);

        return $this->renderHtml($response, $template);
    }

    public function threadAction(Request $request, Response $response, array $args): ResponseInterface
    {
        $thread = $this->threadRepository->find($args['thread']);

        if (!$thread) {
            throw new NotFoundException($request, $response);
        }

        $viewArgs = [
            'thread' => $thread,
            'logged' => $this->authorizer->isLoggedIn(),
        ];

        if ($this->authorizer->isLoggedIn()) {
            return $this->view->render($response, '/thread.html', $viewArgs);
        }

        $template = $this->getOrSetCache('/thread.phtml', $viewArgs, 'thread_' . $args['thread']);

        return $this->renderHtml($response, $template);
    }

    public function chainAction(Request $request, Response $response, array $args): ResponseInterface
    {
        $chain = $this->refLinkRepository->getChain((int) $args['post']);

        if ($chain->isEmpty()) {
            throw new NotFoundException($request, $response);
        }

        return $this->view->render($response, '/chain.phtml', [
            'posts' => $chain,
            'logged' => $this->authorizer->isLoggedIn(),
        ]);
    }

    /**
     * Get html template cache by key or set html cache to cache by key
     * 
     * @param  string $template   path to template
     * @param  array $data        array of attr inside template
     * @param  string $nameCache  name key cache
     * @return mixed              string of html template with set attr
     * @throws \Exception
     * @throws \Throwable
     */
    public function getOrSetCache($template, array $data, string $nameCache)
    {
        $cache = $this->cache->get($nameCache);

        if (!$cache) {
            $cache = $this->view->fetch($template, $data);
            $this->cache->set($nameCache, $cache);
        }

        return $cache;
    }

    /**
     * Render template by html string
     *
     * @param Response $response
     * @param string $html
     * @return Response
     */
    public function renderHtml(Response $response, string $html): Response
    {
        $response->getBody()->write($html);

        return $response;
    }
}
