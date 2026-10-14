<?php

namespace BicBucStriim\Framework\Http\Controllers;

use BicBucStriim\Actions\ActionResolver;
use BicBucStriim\Framework\MiddlewareDispatcher;
use BicBucStriim\Framework\MiddlewareResolver;
use BicBucStriim\Framework\RoleGuard;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response as LaravelResponse;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\PsrHttpFactoryInterface;

class ActionAdapterController extends Controller
{
    /**
     * Handle the request and execute the appropriate action.
     */
    public function handle(
        LaravelRequest $request,
        ActionResolver $resolver,
        MiddlewareResolver $middlewareResolver,
        PsrHttpFactoryInterface $psrFactory,
        HttpFoundationFactoryInterface $httpFoundationFactory,
    ): LaravelResponse {
        // 1. Get the target action from the route defaults.
        $route = $request->route();
        $actionCallable = $route->defaults['action_callable'];
        [$class, $method] = $actionCallable;

        // 2. Convert the Laravel Request to a PSR-7 Request.
        $psrRequest = $psrFactory->createRequest($request);

        // 3. Build the PSR-15 middleware pipeline for this route. Laravel has no
        // native per-route middleware, so ActionServiceProvider carries both the
        // group gatekeeper flag (action_gated, e.g. Admin/Metadata/Extra) and the
        // route-level middleware (action_middleware, e.g. RoleGuard::class on
        // main-author-note) as route defaults. Prepend RoleGuard for gated routes
        // and resolve every entry to a concrete PSR-15 instance via the shared,
        // container-aware MiddlewareResolver.
        $gated = !empty($route->defaults['action_gated']);
        $routeMiddleware = $route->defaults['action_middleware'] ?? [];
        if ($gated) {
            array_unshift($routeMiddleware, RoleGuard::class);
        }
        $middleware = $middlewareResolver->resolve($routeMiddleware);

        // 4. Execute the action via the shared ActionResolver inside a terminal
        // request handler, so middleware (e.g. RoleGuard's 403 short-circuit)
        // wraps it exactly as on the custom App and Slim.
        $routeParameters = $route->parameters();
        $handler = new class ($resolver, $class, $method, $routeParameters) implements RequestHandlerInterface {
            public function __construct(
                private ActionResolver $resolver,
                private string $class,
                private string $method,
                private array $args,
            ) {}

            public function handle(PsrRequest $request): PsrResponse
            {
                return $this->resolver->resolve($this->class, $this->method, $request, null, array_values($this->args));
            }
        };
        $psrResponse = (new MiddlewareDispatcher($middleware, $handler))->handle($psrRequest);

        // 5. Convert the resulting PSR-7 Response back to a Laravel Response.
        return $httpFoundationFactory->createResponse($psrResponse);
    }
}
