<?php

namespace BicBucStriim\Framework\Http\Controllers;

use BicBucStriim\Actions\ActionResolver;
use BicBucStriim\Framework\MiddlewareDispatcher;
use BicBucStriim\Framework\MiddlewareResolver;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response as LaravelResponse;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\PsrHttpFactoryInterface;

/**
 * @suppress PHP0413
 */
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
        // 1. Get the target action callable from the route defaults.
        $route = $request->route();
        $actionCallable = $route->defaults['action_callable'];

        // 2. Convert the Laravel Request to a PSR-7 Request.
        $psrRequest = $psrFactory->createRequest($request);

        // 3. Resolve the route's PSR-15 middleware (group gatekeeper flag +
        // route-level entries, carried as route defaults).
        $middleware = $this->resolveRouteMiddleware($route, $middlewareResolver);

        // 4. Run the callable inside the shared MiddlewareDispatcher via the
        // ActionResolver (accepts either the [class, method] array or a Closure).
        $psrResponse = $this->runActionPipeline($resolver, $actionCallable, $route->parameters(), $middleware, $psrRequest);

        // 5. Convert the resulting PSR-7 Response back to a Laravel Response.
        return $httpFoundationFactory->createResponse($psrResponse);
    }

    /**
     * Resolve the PSR-15 middleware for the current route.
     *
     * Laravel has no native per-route middleware, so ActionServiceProvider carries
     * both the group gatekeeper flag (action_gated, e.g. Admin/Metadata/Extra) and
     * the route-level middleware (action_middleware, e.g. RoleGuard::class on
     * main-author-note) as route defaults. resolveForRoute() is the single
     * spelling of that policy shared with the custom App and Symfony.
     */
    private function resolveRouteMiddleware($route, MiddlewareResolver $middlewareResolver): array
    {
        $gated = !empty($route->defaults['action_gated']);
        $routeMiddleware = $route->defaults['action_middleware'] ?? [];
        return $middlewareResolver->resolveForRoute($gated, $routeMiddleware);
    }

    /**
     * Run the action callable inside the shared MiddlewareDispatcher through a
     * terminal handler, so middleware (e.g. RoleGuard's 403 short-circuit) wraps
     * it exactly as on the custom App and Slim. The dispatcher invokes the handler
     * directly when there is no middleware.
     */
    private function runActionPipeline(ActionResolver $resolver, array|\Closure $callable, array $args, array $middleware, PsrRequest $psrRequest): PsrResponse
    {
        $handler = new class ($resolver, $callable, $args) implements RequestHandlerInterface {
            public function __construct(
                private ActionResolver $resolver,
                private array|\Closure $callable,
                private array $args,
            ) {}

            public function handle(PsrRequest $request): PsrResponse
            {
                return $this->resolver->resolve($this->callable, $request, null, array_values($this->args));
            }
        };
        return new MiddlewareDispatcher($middleware, $handler)->handle($psrRequest);
    }
}
