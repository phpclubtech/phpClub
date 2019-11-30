<?php

declare(strict_types=1);

namespace phpClub\Pagination;

use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Slim\Interfaces\RouteInterface;
use Slim\Router;

class PaginationRenderer
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function render(Pagerfanta $pagerfanta, RouteInterface $route, array $queryParams = []): string
    {
        if ($pagerfanta->count() < $pagerfanta->getMaxPerPage()) {
            return '';
        }

        $generateRoute = function (int $page) use ($queryParams, $route): string {
            $allQueryParams = array_merge($queryParams, compact('page'));
            return $this->router->pathFor($route->getName(), [], $allQueryParams);
        };

        return (new DefaultView())->render($pagerfanta, $generateRoute, [
            'container_template' => '<nav class="pagerfanta">%pages%</nav>',
            'prev_message' => 'Предыдущая',
            'next_message' => 'Следующая',
        ]);
    }
}
