<?php

declare(strict_types=1);

namespace phpClub\Slim;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Handlers\Error as DefaultErrorHandler;

/**
 * PSR-3 decorator for default Slim error handler
 *
 * @see https://akrabat.com/logging-errors-in-slim-3/
 */
class MonologErrorHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DefaultErrorHandler
     */
    private $defaultErrorHandler;

    public function __construct(LoggerInterface $logger, DefaultErrorHandler $defaultErrorHandler)
    {
        $this->logger = $logger;
        $this->defaultErrorHandler = $defaultErrorHandler;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception): ResponseInterface
    {
        $this->logger->error($exception->getMessage(), ['exception' => $exception]);

        return ($this->defaultErrorHandler)($request, $response, $exception);
    }
}
