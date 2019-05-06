<?php

namespace phpClub\Controller;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\Pagination\PaginationRenderer;
use phpClub\Repository\ChainRepository;
use phpClub\Repository\ThreadRepository;
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
    private $view;
    private $cache;
    private $threadRepository;
    private $chainRepository;
    private $paginationRenderer;
    private $urlGenerator;

    public function __construct(
        PhpRenderer $view,
        CacheInterface $cache,
        ThreadRepository $threadRepository,
        ChainRepository $chainRepository,
        PaginationRenderer $paginationRenderer,
        UrlGenerator $urlGenerator
    ) {
        $this->view = $view;
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
            'breadcrumbs' => $breadcrumbs->getAllBreadCrumbs(),
            'pagination'  => $this->paginationRenderer->render($threads, $request->getAttribute('route'), $request->getQueryParams()),
        ];

        $template = $this->getOrSetCache('/board.phtml', $viewArgs, 'board_index' . $page);

        return $this->renderHtml($response, $template);
    }

    public function threadAction(Request $request, Response $response, array $args): ResponseInterface
    {
        /** @var Thread|null $thread */
        $thread = $this->threadRepository->find($args['thread']);
        if (!$thread) {
            throw new NotFoundException($request, $response);
        }
        /** @var Post $OP */
        $OP = $thread->getPosts()->first();
        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->addCrumb('Все треды', '/');
        $breadcrumbs->addCrumb($OP->getTitle(), $this->urlGenerator->toPostAnchor($OP));

        $viewArgs = [
            'thread'      => $thread,
            'breadcrumbs' => $breadcrumbs->getAllBreadCrumbs(),
        ];

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

        $post = $chain->filter(function (Post $entry) use ($postId) {
            return $entry->getId() == $postId;
        })->first();

        /** @var Post $OP */
        $OP = $post->getThread()->getPosts()->first();

        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->addCrumb('Все треды', '/');
        $breadcrumbs->addCrumb($OP->getTitle(), $this->urlGenerator->toPostAnchor($OP));
        $breadcrumbs->addCrumb("Ответы на пост №{$postId}", $this->urlGenerator->toChain($post));

        return $this->view->render($response, '/chain.phtml', [
            'posts'       => $chain,
            'postId'      => $postId,
            'breadcrumbs' => $breadcrumbs->getAllBreadCrumbs(),
        ]);
    }

    public function aboutAction(Request $request, Response $response): ResponseInterface
    {
        return $this->view->render($response, '/about.phtml');
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
