<?php

declare(strict_types=1);

namespace BicBucStriim\Framework;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A PSR-15 middleware dispatcher.
 * It processes a queue of middleware and delegates to a final request handler.
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /**
     * @param MiddlewareInterface[] $queue The middleware queue.
     * @param RequestHandlerInterface $fallbackHandler The final handler to call.
     */
    public function __construct(
        private array $queue,
        private RequestHandlerInterface $fallbackHandler,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->queue)) {
            // If the queue is empty, call the final handler.
            return $this->fallbackHandler->handle($request);
        }

        // Get the next middleware in the queue.
        $middleware = array_shift($this->queue);

        // Process it, passing the rest of the dispatcher as the next handler.
        return $middleware->process($request, $this);
    }
}
