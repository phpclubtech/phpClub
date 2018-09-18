<?php

declare(strict_types=1);

namespace phpClub\Slim;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Handlers\Error as DefaultErrorHandler;
use phpClub\Slim\NotFoundHandler;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;

/**
 * PSR-3 decorator for default Slim error handler.
 *
 * @see https://akrabat.com/logging-errors-in-slim-3/
 */
class ErrorHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DefaultErrorHandler
     */
    private $defaultErrorHandler;

    /**
     * @var NotFoundHandler
     */
    private $notFoundHandler;

    public function __construct(LoggerInterface $logger, DefaultErrorHandler $defaultErrorHandler, NotFoundHandler $notFoundHandler)
    {
        $this->logger = $logger;
        $this->defaultErrorHandler = $defaultErrorHandler;
        $this->notFoundHandler = $notFoundHandler;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception): ResponseInterface
    {
        if ($exception instanceof OutOfRangeCurrentPageException) {
            return ($this->notFoundHandler)($request, $response);
        } else {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            return ($this->defaultErrorHandler)($request, $response, $exception);
        }
    }
}
