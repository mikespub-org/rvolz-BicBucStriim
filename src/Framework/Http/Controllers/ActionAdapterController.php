<?php

namespace BicBucStriim\Framework\Http\Controllers;

use BicBucStriim\Actions\ActionRegistry;
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
        ActionRegistry $registry,
        PsrHttpFactoryInterface $psrFactory,
        HttpFoundationFactoryInterface $httpFoundationFactory,
    ): LaravelResponse {
        // 1. Get the target action from the route defaults.
        $route = $request->route();
        $actionCallable = $route->defaults['action_callable'];
        [$class, $method] = $actionCallable;

        // 2. Convert the Laravel Request to a PSR-7 Request.
        $psrRequest = $psrFactory->createRequest($request);

        // 3. Enforce the group admin guard for gated routes (Admin/Metadata/
        // Extra). Laravel has no native PSR-15 route middleware, so the
        // gatekeeper flag is carried as a route default (action_gated) by
        // ActionServiceProvider. Use the framework-agnostic RoleGuard check and
        // fail closed (deny) with a plain 403. Frameworks without an auth bridge
        // deny everyone here, which is safer than leaving the admin routes
        // unprotected.
        if (!empty($route->defaults['action_gated']) && !RoleGuard::isAllowed($psrRequest)) {
            return new LaravelResponse(RoleGuard::DENIED_MESSAGE, 403);
        }

        // 4. Get the shared instance of the Action class from our registry.
        $actionInstance = $registry->getInstance($class);

        // 5. Initialize the action instance.
        $actionInstance->initialize($psrRequest, null);

        // 6. Execute the action method with route parameters.
        $routeParameters = $route->parameters();
        $psrResponse = $actionInstance->$method(...array_values($routeParameters));

        // 7. Convert the resulting PSR-7 Response back to a Laravel Response.
        return $httpFoundationFactory->createResponse($psrResponse);
    }
}
