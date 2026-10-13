<?php

declare(strict_types=1);

namespace BicBucStriim\Framework;

use BicBucStriim\Middleware\BasePathMiddleware;
use BicBucStriim\Middleware\CalibreConfigMiddleware;
use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\ActionResolver;
use BicBucStriim\Utilities\RequestUtil;
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

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->registry = new ActionRegistry($this->container);
        $this->resolver = new ActionResolver($this->registry);
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
        // 2. ROUTING: Use our ActionRegistry to get a map of all application routes.
        $loader = new ActionLoader();
        $loader->load($this->registry);
        $routeMap = $this->registry->getRouteMap();

        // Create a router and add all the routes from our registry.
        $router = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) use ($routeMap) {
            foreach ($routeMap as $name => $route) {
                // FastRoute stores our route definition as the handler so we can
                // recover the callable + middleware after a successful match.
                $r->addRoute($route['methods'], $route['path'], $route);
            }
        });

        // 3. DISPATCHING: Strip any detected base path (set by BasePathMiddleware)
        // so routes match when BBS is mounted in a subfolder, then dispatch.
        $basePath = $request->getAttribute(RequestUtil::BASE_PATH, '');
        $dispatchPath = $request->getUri()->getPath();
        if (!empty($basePath) && str_starts_with($dispatchPath, $basePath . '/')) {
            $dispatchPath = substr($dispatchPath, strlen($basePath));
        }
        $routeInfo = $router->dispatch($request->getMethod(), $dispatchPath);
        $response = null;
        $psr17Factory = new Psr17Factory();

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
                /** @var array{methods: array<string>|string, path: string, callable: array{0: class-string, 1: string}, middleware: array<int, mixed>, gated: bool} $route */
                $route = $routeInfo[1];
                $routeParams = $routeInfo[2];
                [$class, $method] = $route['callable'];
                $initialResponse = $psr17Factory->createResponse();

                // Run the route's middleware (route-level middleware + the group
                // admin guard for gated routes) before dispatching to the action.
                $middleware = $route['middleware'];
                if ($route['gated']) {
                    array_unshift($middleware, $this->getRoleGuard());
                }
                if (!empty($middleware)) {
                    $resolver = $this->resolver;
                    $handler = new class ($resolver, $class, $method, $initialResponse, $routeParams) implements \Psr\Http\Server\RequestHandlerInterface {
                        public function __construct(
                            private ActionResolver $resolver,
                            private string $class,
                            private string $method,
                            private ResponseInterface $response,
                            private array $args,
                        ) {}
                        public function handle(ServerRequestInterface $request): ResponseInterface
                        {
                            return $this->resolver->resolve($this->class, $this->method, $request, $this->response, array_values($this->args));
                        }
                    };
                    $dispatcher = new MiddlewareDispatcher($middleware, $handler);
                    $response = $dispatcher->handle($request);
                } else {
                    $response = $this->resolver->resolve($class, $method, $request, $initialResponse, array_values($routeParams));
                }
                break;
        }

        return $response;
    }

    /**
     * Get the admin access guard middleware used for gated routes.
     *
     * For the custom App we use the framework-agnostic RoleGuard (a plain 403
     * response) rather than the Slim GatekeeperMiddleware, which is coupled to
     * DefaultActions and the Twig template engine (see src/Middleware). The
     * guard relies on the authenticated identity being present on the request
     * as the 'auth' attribute (set by a login/auth bridge); without one it
     * fails closed (denies everyone), which is safer than leaving the admin
     * routes unprotected.
     */
    private function getRoleGuard(): \Psr\Http\Server\MiddlewareInterface
    {
        return new RoleGuard(new Psr17Factory());
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
