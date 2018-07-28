<?php

declare(strict_types=1);

namespace phpClub\Slim;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;

class NotFoundHandler
{
    /**
     * @var PhpRenderer
     */
    private $phpRenderer;

    public function __construct(PhpRenderer $phpRenderer)
    {
        $this->phpRenderer = $phpRenderer;
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->phpRenderer
            ->render($response, '/notFound.phtml')
            ->withStatus(404);
    }
}
