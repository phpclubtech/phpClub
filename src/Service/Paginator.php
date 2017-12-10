<?php

namespace phpClub\Service;

use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;

class Paginator
{
    public function render(Pagerfanta $pagerfanta): string
    {
        if ($pagerfanta->count() < $pagerfanta->getMaxPerPage()) {
            return '';
        }

        $view = new DefaultView();
        
        return $view->render($pagerfanta, [$this, 'generateRoute'], [
            'container_template' => '<nav class="pagerfanta">%pages%</nav>',
        ]);
    }

    public function generateRoute(int $page): string
    {
         return '/?page=' . $page;
    }
}
