<?php

namespace phpClub\Controller;

use Foolz\SphinxQL\Drivers\Pdo\Connection;
use Pagerfanta\Pagerfanta;
use phpClub\Pagination\PaginationRenderer;
use phpClub\Pagination\SphinxAdapter;
use phpClub\Repository\PostRepository;
use phpClub\Service\Breadcrumbs;
use phpClub\Service\UrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer as View;

class SearchController
{
    private $view;
    private $postRepository;
    private $paginationRenderer;
    private $sphinxConnection;
    private $urlGenerator;

    public function __construct(
        PostRepository $postRepository,
        PaginationRenderer $paginationRenderer,
        View $view,
        Connection $sphinxConnection,
        UrlGenerator $urlGenerator
    ) {
        $this->view = $view;
        $this->postRepository = $postRepository;
        $this->paginationRenderer = $paginationRenderer;
        $this->sphinxConnection = $sphinxConnection;
        $this->urlGenerator = $urlGenerator;
    }

    public function searchAction(Request $request, Response $response): ResponseInterface
    {
        $query = $request->getParam('q');
        $page = $request->getParam('page', 1);

        $posts = (new Pagerfanta(new SphinxAdapter($this->sphinxConnection, $this->postRepository, $query)))
            ->setCurrentPage($page);

        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->addCrumb('Все треды', '/');
        $breadcrumbs->addCrumb("Поиск по запросу \"{$query}\"", $this->urlGenerator->toSearch($query));

        $viewArgs = [
            'query'       => $query,
            'posts'       => $posts,
            'breadcrumbs' => $breadcrumbs->getAllBreadCrumbs(),
            'pagination'  => $this->paginationRenderer->render($posts, $request->getAttribute('route'), $request->getQueryParams()),
        ];

        return $this->view->render($response, '/searchResults.phtml', $viewArgs);
    }
}
