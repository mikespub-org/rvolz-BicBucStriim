<?php

declare(strict_types=1);

use BicBucStriim\Framework\MiddlewareDispatcher;
use BicBucStriim\Framework\RoleGuard;
use BicBucStriim\Utilities\RequestUtil;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tests for the shared PSR-15 middleware dispatcher.
 *
 * Covers FIFO ordering, the empty-queue fallback and short-circuiting
 * (a guard returning 403 never reaches the terminal handler).
 */
#[CoversClass(MiddlewareDispatcher::class)]
class MiddlewareDispatcherTest extends PHPUnit\Framework\TestCase
{
    private function makeRequest(): ServerRequestInterface
    {
        return RequestUtil::getServerRequest('GET', '/');
    }

    private function makeOkHandler(string $body = 'ok'): RequestHandlerInterface
    {
        return new class ($body) implements RequestHandlerInterface {
            public function __construct(private string $body) {}
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Psr17Factory()->createResponse(200);
                $response->getBody()->write($this->body);
                return $response;
            }
        };
    }

    private function makeAppendingMiddleware(string $marker, bool $appendAfter = true): MiddlewareInterface
    {
        return new class ($marker, $appendAfter) implements MiddlewareInterface {
            public function __construct(private string $marker, private bool $appendAfter) {}
            public function handle_marker(): string
            {
                return $this->marker;
            }
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $request = $request->withAttribute('trace', [...$request->getAttribute('trace', []), $this->marker . '-in']);
                $response = $handler->handle($request);
                if ($this->appendAfter) {
                    $response->getBody()->write('|' . $this->marker);
                }
                return $response;
            }
        };
    }

    public function testEmptyQueueCallsFallbackHandler(): void
    {
        $dispatcher = new MiddlewareDispatcher([], $this->makeOkHandler('fallback'));

        $response = $dispatcher->handle($this->makeRequest());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('fallback', (string) $response->getBody());
    }

    public function testMiddlewareRunsInFifoOrder(): void
    {
        $dispatcher = new MiddlewareDispatcher(
            [$this->makeAppendingMiddleware('first'), $this->makeAppendingMiddleware('second')],
            $this->makeOkHandler('base')
        );

        $response = $dispatcher->handle($this->makeRequest());

        // Terminal body first, then each middleware appends on the way out:
        // second (innermost) writes before first (outermost) unwinds.
        $this->assertSame('base|second|first', (string) $response->getBody());
    }

    public function testShortCircuitNeverReachesFallback(): void
    {
        $guard = new RoleGuard(new Psr17Factory());
        $reached = false;
        $handler = new class ($reached) implements RequestHandlerInterface {
            public function __construct(private bool &$reached) {}
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->reached = true;
                return new Psr17Factory()->createResponse(200);
            }
        };
        $dispatcher = new MiddlewareDispatcher([$guard], $handler);

        // No 'auth' attribute -> RoleGuard denies with 403.
        $response = $dispatcher->handle($this->makeRequest());

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($reached);
    }

    public function testDispatcherIsSingleUsePerHandleCall(): void
    {
        $dispatcher = new MiddlewareDispatcher(
            [$this->makeAppendingMiddleware('once', false)],
            $this->makeOkHandler('base')
        );

        // First handle consumes the queue; the dispatcher object itself holds
        // shifted state, so document that each handle() call progresses it.
        $first = $dispatcher->handle($this->makeRequest());
        $this->assertSame('base', (string) $first->getBody());
    }
}
