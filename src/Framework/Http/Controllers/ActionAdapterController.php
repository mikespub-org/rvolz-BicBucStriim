<?php

namespace BicBucStriim\Framework\Http\Controllers;

use BicBucStriim\Actions\ActionResolver;
use BicBucStriim\Framework\RoleGuard;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response as LaravelResponse;
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
        PsrHttpFactoryInterface $psrFactory,
        HttpFoundationFactoryInterface $httpFoundationFactory,
    ): LaravelResponse {
        // 1. Get the target action from the route defaults.
        $route = $request->route();
        $actionCallable = $route->defaults['action_callable'];
        [$class, $method] = $actionCallable;

        // 2. Convert the Laravel Request to a PSR-7 Request.
        $psrRequest = $psrFactory->createRequest($request);

        // 3. Enforce admin access control. Laravel has no native PSR-15 route
        // middleware, so ActionServiceProvider carries both the group gatekeeper
        // flag (action_gated, e.g. Admin/Metadata/Extra) and the route-level
        // middleware (action_middleware, e.g. RoleGuard::class on main-author-note)
        // as route defaults. Use the framework-agnostic RoleGuard check and fail
        // closed (deny) with a plain 403. Frameworks without an auth bridge deny
        // everyone here, which is safer than leaving the routes unprotected.
        $gated = !empty($route->defaults['action_gated']);
        $routeMiddleware = $route->defaults['action_middleware'] ?? [];
        if (($gated || RoleGuard::hasGuard($routeMiddleware)) && !RoleGuard::isAllowed($psrRequest)) {
            return new LaravelResponse(RoleGuard::DENIED_MESSAGE, 403);
        }

        // 4. Execute the action via the shared ActionResolver — it looks up the
        // singleton instance, initializes it with the PSR-7 request, and calls
        // the method with the route parameters. Keeps the action lifecycle in
        // one place instead of re-inlining it here.
        $routeParameters = $route->parameters();
        $psrResponse = $resolver->resolve($class, $method, $psrRequest, null, array_values($routeParameters));

        // 7. Convert the resulting PSR-7 Response back to a Laravel Response.
        return $httpFoundationFactory->createResponse($psrResponse);
    }
}
