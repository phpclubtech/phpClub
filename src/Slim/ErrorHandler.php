<?php

declare(strict_types=1);

namespace phpClub\Slim;

use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Exception\NotValidMaxPerPageException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Handlers\Error as DefaultErrorHandler;

class ErrorHandler
{
    private LoggerInterface $logger;
    private DefaultErrorHandler $defaultErrorHandler;
    private NotFoundHandler $notFoundHandler;

    public function __construct(LoggerInterface $logger, DefaultErrorHandler $defaultErrorHandler, NotFoundHandler $notFoundHandler)
    {
        $this->logger = $logger;
        $this->defaultErrorHandler = $defaultErrorHandler;
        $this->notFoundHandler = $notFoundHandler;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception): ResponseInterface
    {
        if ($exception instanceof NotValidCurrentPageException || $exception instanceof NotValidMaxPerPageException) {
            return ($this->notFoundHandler)($request, $response);
        }

        $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

        return ($this->defaultErrorHandler)($request, $response, $exception);
    }
}
