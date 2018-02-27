<?php

namespace phpClub\Pagination;

use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Slim\Route;
use Slim\Router;

class PaginationRenderer
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function render(Pagerfanta $pagerfanta, Route $route, array $queryParams = []): string
    {
        if ($pagerfanta->count() < $pagerfanta->getMaxPerPage()) {
            return '';
        }

        $view = new DefaultView();

        $generateRoute = function (int $page) use ($queryParams, $route): string {
            return $this->router->pathFor($route->getName(), [], array_merge($queryParams, compact('page')));
        };

        return $view->render($pagerfanta, $generateRoute, [
            'container_template' => '<nav class="pagerfanta">%pages%</nav>',
            'previous_message'   => 'Предыдущая',
            'next_message'       => 'Следующая',
        ]);
    }
}
