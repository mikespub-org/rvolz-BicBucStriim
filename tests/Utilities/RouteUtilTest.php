<?php

use BicBucStriim\Framework\RoleGuard;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\RouteUtil;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\Factory\AppFactory;

/**
 * Direct unit tests for the framework-agnostic RouteUtil parsing helpers.
 *
 * `parseRouteTuple()` is the single source of truth for turning a route
 * definition tuple
 *
 *     name => [method(s), path, ...middleware(s), callable]
 *
 * into its parts. It is consumed both by the Slim router (mapSlim4Routes) and
 * by the framework-agnostic ActionRegistry used by the custom App, Symfony and
 * Laravel adapters, so it is exercised on every framework.
 *
 * `addRoutesFromMap()` is the production Slim registration path (config/
 * routes.php); the per-action testAddRoutes() tests already cover it end-to-end
 * via a single-class registry, so here we add a focused smoke test for the
 * method-expansion / naming / flat-path contract.
 */
#[CoversClass(RouteUtil::class)]
class RouteUtilTest extends PHPUnit\Framework\TestCase
{
    public function testParseRouteTupleMinimalHasNoMiddleware(): void
    {
        $callable = static fn() => null;
        $parsed = RouteUtil::parseRouteTuple(['GET', '/books/', $callable]);

        $this->assertSame('GET', $parsed['methods']);
        $this->assertSame('/books/', $parsed['path']);
        $this->assertSame($callable, $parsed['callable']);
        $this->assertSame([], $parsed['middleware']);
    }

    public function testParseRouteTupleWithSingleMiddleware(): void
    {
        $callable = static fn() => null;
        $mw = 'Some\\Middleware';
        $parsed = RouteUtil::parseRouteTuple(['GET', '/authors/{id}/notes/', $mw, $callable]);

        $this->assertSame('GET', $parsed['methods']);
        $this->assertSame('/authors/{id}/notes/', $parsed['path']);
        $this->assertSame($callable, $parsed['callable']);
        $this->assertSame([$mw], $parsed['middleware']);
    }

    public function testParseRouteTupleWithMultipleMiddlewareKeepsOrder(): void
    {
        $callable = static fn() => null;
        $mw1 = 'First\\Middleware';
        $mw2 = 'Second\\Middleware';
        $parsed = RouteUtil::parseRouteTuple(['POST', '/x/', $mw1, $mw2, $callable]);

        $this->assertSame('POST', $parsed['methods']);
        $this->assertSame([$mw1, $mw2], $parsed['middleware']);
    }

    public function testParseRouteTupleMethodsMayBeAnArray(): void
    {
        $callable = static fn() => null;
        $methods = ['GET', 'POST'];
        $parsed = RouteUtil::parseRouteTuple([$methods, '/x/', $callable]);

        $this->assertSame($methods, $parsed['methods']);
        $this->assertSame('/x/', $parsed['path']);
        $this->assertSame([], $parsed['middleware']);
    }

    public function testParseRouteTupleMiddlewareMayBeObjects(): void
    {
        $callable = static fn() => null;
        $mw = new class {};
        $parsed = RouteUtil::parseRouteTuple(['GET', '/x/', $mw, $callable]);

        $this->assertSame([$mw], $parsed['middleware']);
    }

    public function testParseRouteTupleTooShortThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RouteUtil::parseRouteTuple(['GET', '/x/']);
    }

    public function testAddRoutesFromMapExpandsGetToHeadAndSetsNames(): void
    {
        $callable = static fn() => null;
        $routeMap = [
            'get-hello' => [
                'methods' => 'GET',
                'path' => '/hello/',
                'callable' => $callable,
                'middleware' => [],
                'gated' => false,
            ],
            'post-thing' => [
                'methods' => 'POST',
                'path' => '/things/',
                'callable' => $callable,
                'middleware' => [],
                'gated' => false,
            ],
        ];

        $app = \Slim\Factory\AppFactory::create();
        RouteUtil::addRoutesFromMap($app, $routeMap);

        $routes = $app->getRouteCollector()->getRoutes();
        $this->assertCount(2, $routes);

        // Slim keys routes by identifier, not name — index by name ourselves.
        $byName = [];
        foreach ($routes as $route) {
            $byName[$route->getName()] = $route;
        }

        // GET expands to GET + HEAD (historical Slim behavior); POST stays POST.
        $this->assertArrayHasKey('get-hello', $byName);
        $this->assertArrayHasKey('post-thing', $byName);
        $this->assertSame(['GET', 'HEAD'], $byName['get-hello']->getMethods());
        $this->assertSame(['POST'], $byName['post-thing']->getMethods());

        // Paths are registered flat (already prefixed in the map).
        $this->assertSame('/hello/', $byName['get-hello']->getPattern());
        $this->assertSame('/things/', $byName['post-thing']->getPattern());
    }

    /**
     * A route whose middleware slot carries the framework-agnostic
     * RoleGuard::class must have the injected $gatekeeper attached in its
     * place (so Slim keeps its Twig 403 page). We prove the swap by passing a
     * sentinel gatekeeper that short-circuits with a known body: if the swap
     * happened, the sentinel runs and the route handler never does.
     */
    public function testAddRoutesFromMapSwapsRoleGuardClassForGatekeeper(): void
    {
        $callable = static fn() => 'HANDLER-RAN';
        $routeMap = [
            'guarded' => [
                'methods' => 'GET',
                'path' => '/guarded/',
                'callable' => $callable,
                'middleware' => [RoleGuard::class],
                'gated' => false,
            ],
        ];

        $app = AppFactory::create();
        RouteUtil::addRoutesFromMap($app, $routeMap, $this->sentinelGatekeeper());

        $request = RequestUtil::getServerRequest('GET', '/guarded/');
        $response = $app->handle($request);

        $this->assertSame('GATEKEEPER-RAN', (string) $response->getBody());
    }

    /**
     * A gated route (gated => true) must have the $gatekeeper attached even
     * with an empty per-route middleware list. Again proven functionally: the
     * sentinel gatekeeper short-circuits before the handler.
     */
    public function testAddRoutesFromMapAttachesGatekeeperToGatedRoute(): void
    {
        $callable = static fn() => 'HANDLER-RAN';
        $routeMap = [
            'gated' => [
                'methods' => 'GET',
                'path' => '/gated/',
                'callable' => $callable,
                'middleware' => [],
                'gated' => true,
            ],
        ];

        $app = AppFactory::create();
        RouteUtil::addRoutesFromMap($app, $routeMap, $this->sentinelGatekeeper());

        $request = RequestUtil::getServerRequest('GET', '/gated/');
        $response = $app->handle($request);

        $this->assertSame('GATEKEEPER-RAN', (string) $response->getBody());
    }

    /**
     * Control case: with no gatekeeper and no RoleGuard middleware, the route
     * handler must run — proving middleware is not attached spuriously.
     */
    public function testAddRoutesFromMapRunsHandlerWithoutGatekeeper(): void
    {
        $factory = new Psr17Factory();
        $callable = function () use ($factory) {
            $response = $factory->createResponse();
            $response->getBody()->write('HANDLER-RAN');
            return $response;
        };
        $routeMap = [
            'open' => [
                'methods' => 'GET',
                'path' => '/open/',
                'callable' => $callable,
                'middleware' => [],
                'gated' => false,
            ],
        ];

        $app = AppFactory::create();
        RouteUtil::addRoutesFromMap($app, $routeMap);

        $request = RequestUtil::getServerRequest('GET', '/open/');
        $response = $app->handle($request);

        $this->assertSame('HANDLER-RAN', (string) $response->getBody());
    }

    /**
     * Build a PSR-15 middleware that short-circuits with a sentinel body,
     * never calling the wrapped handler. Used to observe (functionally) that a
     * given route middleware was actually attached and runs.
     */
    private function sentinelGatekeeper(): MiddlewareInterface
    {
        $factory = new Psr17Factory();
        return new class ($factory) implements MiddlewareInterface {
            public function __construct(private Psr17Factory $factory) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $this->factory->createResponse();
                $response->getBody()->write('GATEKEEPER-RAN');
                return $response;
            }
        };
    }
}
