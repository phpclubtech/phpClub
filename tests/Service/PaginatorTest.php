<?php

declare(strict_types=1);

namespace Service;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use phpClub\Service\PaginationRenderer;
use Slim\Route;
use Slim\Router;
use Symfony\Component\DomCrawler\Crawler;
use Tests\AbstractTestCase;

class PaginatorTest extends AbstractTestCase
{
    /**
     * @var PaginationRenderer
     */
    private $paginationRenderer;

    /**
     * @var Router
     */
    private $router;

    public function setUp()
    {
        $this->router = $this->getContainer()->get('router');
        $this->router->map(['GET'], '/', function () {})->setName('index');
        $this->router->map(['GET'], '/search', function () {})->setName('search');

        $this->paginationRenderer = new PaginationRenderer($this->router);
    }

    public function testPaginationIsNotRenderedWhenItemsCountIsLessThanMaxPerPage()
    {
        $route = (new Route(['GET'], '/', function () {}))->setName('index');

        $pagerfanta = (new Pagerfanta(new ArrayAdapter([1, 2, 3, 4])))->setMaxPerPage(10);
        $html = $this->paginationRenderer->render($pagerfanta, $route);
        $this->assertEmpty($html);

        $pagerfanta = (new Pagerfanta(new ArrayAdapter(range(1, 100))))->setMaxPerPage(10);
        $html = $this->paginationRenderer->render($pagerfanta, $route);
        $this->assertNotEmpty($html);
    }

    public function testPaginatorGeneratesLinksWithCorrectPathAndQueryString()
    {
        $route = $this->router->getNamedRoute('search');

        $pagerfanta = (new Pagerfanta(new ArrayAdapter(range(1, 100))))->setMaxPerPage(10);
        $html = $this->paginationRenderer->render($pagerfanta, $route, ['q' => 'Foo']);

        $crawler = new Crawler($html);
        $hrefs = $crawler->filterXPath('//a')->extract(['href']);

        foreach ($hrefs as $href) {
            $queryString = parse_url($href, PHP_URL_QUERY);
            parse_str($queryString, $queryParamsArray);
            $this->assertCount(2, $queryParamsArray);
        }
    }
}
