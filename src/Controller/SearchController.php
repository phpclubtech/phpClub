<?php

namespace phpClub\Controller;

use phpClub\Service\Authorizer;
use phpClub\Service\Searcher;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer as View;
use Slim\Views\PhpRenderer;

class SearchController
{
    /**
     * @var PhpRenderer
     */
    protected $view;

    /**
     * @var Searcher
     */
    protected $searcher;

    /**
     * @var Authorizer
     */
    protected $authorizer;

    public function __construct(Searcher $searcher, Authorizer $authorizer, View $view)
    {
        $this->view = $view;
        $this->searcher = $searcher;
        $this->authorizer = $authorizer;
    }

    public function searchAction(Request $request, Response $response, array $args = []): ResponseInterface
    {
        $query = $request->getParam('q');

        return $this->view->render($response, '/searchResults.phtml', [
            'logged' => $this->authorizer->isLoggedIn(),
            'posts'  => $this->searcher->search($query),
        ]);
    }
}
