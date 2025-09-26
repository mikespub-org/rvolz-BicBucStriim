<?php

declare(strict_types=1);

namespace BicBucStriim\Framework;

use BicBucStriim\Middleware\CalibreConfigMiddleware;
use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\ActionResolver;
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
            foreach ($routeMap as $name => [$methods, $path, $callable]) {
                $r->addRoute($methods, $path, $callable);
            }
        });

        // 3. DISPATCHING: Dispatch the request to find the matching route.
        $routeInfo = $router->dispatch($request->getMethod(), $request->getUri()->getPath());
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
                $callable = $routeInfo[1];
                $routeParams = $routeInfo[2];
                [$class, $method] = $callable;
                $initialResponse = $psr17Factory->createResponse();
                $response = $this->resolver->resolve($class, $method, $request, $initialResponse, array_values($routeParams));
                break;
        }

        return $response;
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
