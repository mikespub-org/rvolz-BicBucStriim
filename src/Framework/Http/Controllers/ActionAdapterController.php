<?php

namespace BicBucStriim\Framework\Http\Controllers;

use BicBucStriim\Actions\ActionRegistry;
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

        // 2. Get the shared instance of the Action class from our registry.
        $actionInstance = $registry->getInstance($class);

        // 3. Convert the Laravel Request to a PSR-7 Request.
        $psrRequest = $psrFactory->createRequest($request);

        // 4. Initialize the action instance.
        $actionInstance->initialize($psrRequest, null);

        // 5. Execute the action method with route parameters.
        $routeParameters = $route->parameters();
        $psrResponse = $actionInstance->$method(...array_values($routeParameters));

        // 6. Convert the resulting PSR-7 Response back to a Laravel Response.
        return $httpFoundationFactory->createResponse($psrResponse);
    }
}
