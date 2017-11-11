<?php

declare(strict_types=1);

namespace Tests\Service;

use Tests\AbstractTestCase;
use phpClub\Service\UrlGenerator;
use Slim\Router;

class UrlGeneratorTest extends AbstractTestCase
{
    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    public function setUp()
    {
        $this->urlGenerator = new UrlGenerator($this->createRouterWithRoutes());
    }

    private function createRouterWithRoutes()
    {
        $router = new Router();

        $router->map(['GET'], '/thread/{thread}', null)->setName('thread');
        $router->map(['GET'], '/chain/{id}', null)->setName('chain');
        
        return $router;
    }

    public function testToThread()
    {
        $this->assertNotEmpty($this->urlGenerator->toThread($this->createThread(1)));
    }

    public function testToPostAnchor()
    {
        $this->assertNotEmpty($this->urlGenerator->toPostAnchor($this->createPost(1)));
    }

    public function testToChain()
    {
        $this->assertNotEmpty($this->urlGenerator->toChain($this->createPost(3)));
    }
}