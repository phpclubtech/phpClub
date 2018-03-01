<?php

namespace phpClub\Controller;

use Pagerfanta\Pagerfanta;
use phpClub\Pagination\PaginationRenderer;
use phpClub\Pagination\SphinxAdapter;
use phpClub\Repository\PostRepository;
use phpClub\Service\Authorizer;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;
use Slim\Views\PhpRenderer as View;

class SearchController
{
    /**
     * @var PhpRenderer
     */
    protected $view;

    /**
     * @var Authorizer
     */
    protected $authorizer;

    /**
     * @var PostRepository
     */
    private $postRepository;

    /**
     * @var PaginationRenderer
     */
    private $paginationRenderer;

    /**
     * @var \PDO
     */
    private $sphinxConnection;

    public function __construct(
        Authorizer $authorizer,
        PostRepository $postRepository,
        PaginationRenderer $paginationRenderer,
        View $view,
        \PDO $sphinxConnection
    ) {
        $this->view = $view;
        $this->authorizer = $authorizer;
        $this->postRepository = $postRepository;
        $this->paginationRenderer = $paginationRenderer;
        $this->sphinxConnection = $sphinxConnection;
    }

    public function searchAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        $query = $request->getParam('q');
        $page = $request->getParam('page', 1);

        $posts = (new Pagerfanta(new SphinxAdapter($this->sphinxConnection, $this->postRepository, $query)))
            ->setCurrentPage($page);

        $viewArgs = [
            'posts'      => $posts,
            'logged'     => $this->authorizer->isLoggedIn(),
            'pagination' => $this->paginationRenderer->render($posts, $request->getAttribute('route'), $request->getQueryParams()),
        ];

        return $this->view->render($response, '/searchResults.phtml', $viewArgs);
    }
}
