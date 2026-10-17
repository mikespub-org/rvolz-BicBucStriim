<?php

declare(strict_types=1);

namespace BicBucStriim\Framework;

use BicBucStriim\Middleware\BasePathMiddleware;
use BicBucStriim\Middleware\CalibreConfigMiddleware;
use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\ActionResolver;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\RouteEntry;
use FastRoute\Dispatcher;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A minimal, custom framework application class.
 */
class App implements \Psr\Http\Server\RequestHandlerInterface
{
    private ContainerInterface $container;
    private ActionRegistry $registry;
    private ActionResolver $resolver;
    private MiddlewareResolver $middlewareResolver;

    public function __construct(ContainerInterface $container, ?ActionRegistry $registry = null)
    {
        $this->container = $container;
        // Allow injecting a pre-seeded registry (e.g. tests registering a custom
        // action with a Closure route); otherwise build the default one. The
        // standard actions are still loaded in handle() either way.
        $this->registry = $registry ?? new ActionRegistry($this->container);
        $this->resolver = new ActionResolver($this->registry);
        $this->middlewareResolver = new MiddlewareResolver($this->container);
    }

    /**
     * Run the application: handle the request and emit the response.
     */
    public function run(): void
    {
        // 1. Create a PSR-7 request object from the PHP superglobals.
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $request = $creator->fromGlobals();

        // 2. Build the middleware stack.
        // In a real application, this would be more dynamic.
        $middleware = [
            // Determine the base path first so getPathInfo() and the FastRoute
            // dispatch in handle() work when BBS runs in a subfolder.
            new BasePathMiddleware($this),
            new CalibreConfigMiddleware($this->container),
            // Add other middleware like GatekeeperMiddleware here.
        ];

        // 3. Create the dispatcher. The App class itself is the final handler.
        $dispatcher = new MiddlewareDispatcher($middleware, $this);

        // 4. Handle the request through the middleware pipeline.
        $response = $dispatcher->handle($request);

        // 5. Emit the final response.
        $this->emit($response);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // 2. ROUTING: load all actions into the registry, then build a FastRoute
        // dispatcher from its route map.
        new ActionLoader()->load($this->registry);
        $router = $this->buildRouter($this->registry->getRouteMap());

        // 3. DISPATCHING: match the (base-path-stripped) request path.
        $routeInfo = $router->dispatch($request->getMethod(), $this->dispatchPath($request));
        $psr17Factory = new Psr17Factory();
        $response = null;

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $response = $psr17Factory->createResponse(404);
                $response->getBody()->write('404 Not Found');
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                $response = $psr17Factory->createResponse(405);
                $response->getBody()->write('405 Method Not Allowed');
                break;

            case Dispatcher::FOUND:
                /** @var RouteEntry $route */
                $route = $routeInfo[1];
                $response = $this->dispatchFound($route, $routeInfo[2], $request, $psr17Factory->createResponse());
                break;
        }

        return $response;
    }

    /**
     * Build a FastRoute dispatcher from the registry's route map. Each RouteEntry
     * is stored as the handler so its callable + middleware can be recovered
     * after a successful match.
     *
     * @param array<string, RouteEntry> $routeMap
     */
    private function buildRouter(array $routeMap): Dispatcher
    {
        return \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) use ($routeMap) {
            foreach ($routeMap as $route) {
                $r->addRoute($route->methods, $route->path, $route);
            }
        });
    }

    /**
     * Resolve the path to dispatch against, stripping any detected base path (set
     * by BasePathMiddleware) so routes match when BBS is mounted in a subfolder.
     */
    private function dispatchPath(ServerRequestInterface $request): string
    {
        $basePath = $request->getAttribute(RequestUtil::BASE_PATH, '');
        $path = $request->getUri()->getPath();
        if (!empty($basePath) && str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        }
        return $path;
    }

    /**
     * Dispatch a matched route: resolve its middleware (route-level + the group
     * admin guard for gated routes) via the shared MiddlewareResolver, then run
     * the callable inside the shared MiddlewareDispatcher. The dispatcher invokes
     * the terminal handler directly when there is no middleware, so gated and
     * ungated routes share a single path (mirroring the Laravel and Symfony
     * adapters). ActionResolver accepts either the [class, method] array or a
     * Closure and deconstructs as needed.
     */
    private function dispatchFound(RouteEntry $route, array $routeParams, ServerRequestInterface $request, ResponseInterface $initialResponse): ResponseInterface
    {
        $middleware = $this->middlewareResolver->resolveForRoute((bool) $route->gated, $route->middleware);
        $handler = new class ($this->resolver, $route->callable, $initialResponse, $routeParams) implements \Psr\Http\Server\RequestHandlerInterface {
            public function __construct(
                private ActionResolver $resolver,
                private array|\Closure $callable,
                private ResponseInterface $response,
                private array $args,
            ) {}
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->resolver->resolve($this->callable, $request, $this->response, array_values($this->args));
            }
        };
        return new MiddlewareDispatcher($middleware, $handler)->handle($request);
    }

    /**
     * Emits a PSR-7 response.
     */
    private function emit(ResponseInterface $response): void
    {
        // Send headers
        header(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()));
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
        // Send body
        echo $response->getBody();
    }
}
