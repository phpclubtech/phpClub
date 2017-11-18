<?php

namespace phpClub\Service;

use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;

class Paginator
{
    public function paginate(Pagerfanta $pagerfanta): string
    {
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
