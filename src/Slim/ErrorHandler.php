<?php

declare(strict_types=1);

namespace phpClub\Slim;

use Pagerfanta\Exception\LessThan1CurrentPageException;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Handlers\Error as DefaultErrorHandler;

class ErrorHandler
{
    private $logger;
    private $defaultErrorHandler;
    private $notFoundHandler;

    public function __construct(LoggerInterface $logger, DefaultErrorHandler $defaultErrorHandler, NotFoundHandler $notFoundHandler)
    {
        $this->logger = $logger;
        $this->defaultErrorHandler = $defaultErrorHandler;
        $this->notFoundHandler = $notFoundHandler;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception): ResponseInterface
    {
        if ($exception instanceof OutOfRangeCurrentPageException || $exception instanceof LessThan1CurrentPageException) {
            return ($this->notFoundHandler)($request, $response);
        }

        $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

        return ($this->defaultErrorHandler)($request, $response, $exception);
    }
}
