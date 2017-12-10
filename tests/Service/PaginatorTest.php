<?php

declare(strict_types=1);

namespace Service;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use phpClub\Service\Paginator;
use Tests\AbstractTestCase;

class PaginatorTest extends AbstractTestCase
{
    /**
     * @var Paginator
     */
    private $paginator;

    public function setUp()
    {
        $this->paginator = $this->getContainer()->get(Paginator::class);
    }
    
    public function testPaginatorIsNotRenderedWhenItemsCountIsLessThanMaxPerPage()
    {
        $pagerfanta = (new Pagerfanta(new ArrayAdapter([1, 2, 3, 4])))->setMaxPerPage(10);
        $html = $this->paginator->render($pagerfanta);
        $this->assertEmpty($html);

        $pagerfanta = (new Pagerfanta(new ArrayAdapter(range(1, 100))))->setMaxPerPage(10);
        $html = $this->paginator->render($pagerfanta);
        $this->assertNotEmpty($html);
    }
}