<?php

namespace phpClub\Controller;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use phpClub\Pagination\PaginationRenderer;
use phpClub\Repository\ChainRepository;
use phpClub\Repository\ThreadRepository;
use phpClub\Service\Authorizer;
use phpClub\Service\Breadcrumbs;
use phpClub\Service\UrlGenerator;
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
     * @var ChainRepository
     */
    private $chainRepository;

    /**
     * @var PaginationRenderer
     */
    private $paginationRenderer;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    public function __construct(
        Authorizer $authorizer,
        PhpRenderer $view,
        CacheInterface $cache,
        ThreadRepository $threadRepository,
        ChainRepository $chainRepository,
        PaginationRenderer $paginationRenderer,
        UrlGenerator $urlGenerator
    ) {
        $this->view = $view;
        $this->authorizer = $authorizer;
        $this->cache = $cache;
        $this->threadRepository = $threadRepository;
        $this->chainRepository = $chainRepository;
        $this->paginationRenderer = $paginationRenderer;
        $this->urlGenerator = $urlGenerator;
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $page = $request->getParam('page', 1);

        $threadsQuery = $this->threadRepository->getThreadsWithLastPostsQuery();

        $threads = (new Pagerfanta(new DoctrineORMAdapter($threadsQuery)))
            ->setMaxPerPage(10)
            ->setCurrentPage($page);

        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->addCrumb('Все треды', '/');

        $viewArgs = [
            'threads'     => $threads,
            'logged'      => $this->authorizer->isLoggedIn(),
            'breadcrumbs' => $breadcrumbs->getAllBreadCrumbs(),
            'pagination'  => $this->paginationRenderer->render($threads, $request->getAttribute('route'), $request->getQueryParams()),
        ];

        if ($this->authorizer->isLoggedIn()) {
            return $this->view->render($response, '/board.html', $viewArgs);
        }

        $template = $this->getOrSetCache('/board.phtml', $viewArgs, 'board_index' . $page);

        return $this->renderHtml($response, $template);
    }

    public function threadAction(Request $request, Response $response, array $args): ResponseInterface
    {
        $thread = $this->threadRepository->find($args['thread']);

        $OP = $thread->getPosts()->first();

        if (!$thread) {
            throw new NotFoundException($request, $response);
        }
        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->addCrumb('Все треды', '/');
        $breadcrumbs->addCrumb($OP->getTitle(), $this->urlGenerator->toPostAnchor($OP));

        $viewArgs = [
            'thread'      => $thread,
            'logged'      => $this->authorizer->isLoggedIn(),
            'breadcrumbs' => $breadcrumbs->getAllBreadCrumbs(),
        ];

        if ($this->authorizer->isLoggedIn()) {
            return $this->view->render($response, '/thread.html', $viewArgs);
        }

        $template = $this->getOrSetCache('/thread.phtml', $viewArgs, 'thread_' . $args['thread']);

        return $this->renderHtml($response, $template);
    }

    public function chainAction(Request $request, Response $response, array $args): ResponseInterface
    {
        $postId = (int) $args['post'];
        $chain = $this->chainRepository->getChain($postId);

        if ($chain->isEmpty()) {
            throw new NotFoundException($request, $response);
        }

        $post = $chain->filter(function ($entry) use ($postId) {
            return $entry->getId() == $postId;
        })->first();

        $OP = $post->getThread()->getPosts()->first();

        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->addCrumb('Все треды', '/');
        $breadcrumbs->addCrumb($OP->getTitle(), $this->urlGenerator->toPostAnchor($OP));
        $breadcrumbs->addCrumb("Ответы на пост №{$postId}", $this->urlGenerator->toChain($post));

        return $this->view->render($response, '/chain.phtml', [
            'posts'       => $chain,
            'postId'      => $postId,
            'logged'      => $this->authorizer->isLoggedIn(),
            'breadcrumbs' => $breadcrumbs->getAllBreadCrumbs(),
        ]);
    }

    public function aboutAction(Request $request, Response $response): ResponseInterface
    {
        $viewArgs = [
            'logged'     => $this->authorizer->isLoggedIn(),
        ];

        return $this->view->render($response, '/about.phtml', $viewArgs);
    }

    /**
     * Get html template cache by key or set html cache to cache by key.
     *
     * @param string $template  path to template
     * @param array  $data      array of attr inside template
     * @param string $nameCache name key cache
     *
     * @throws \Exception
     * @throws \Throwable
     *
     * @return mixed string of html template with set attr
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
     * Render template by html string.
     *
     * @param Response $response
     * @param string   $html
     *
     * @return Response
     */
    public function renderHtml(Response $response, string $html): Response
    {
        $response->getBody()->write($html);

        return $response;
    }
}
