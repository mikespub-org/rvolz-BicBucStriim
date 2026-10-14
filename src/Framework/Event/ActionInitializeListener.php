<?php

declare(strict_types=1);

namespace BicBucStriim\Framework\Event;

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Framework\MiddlewareDispatcher;
use BicBucStriim\Framework\MiddlewareResolver;
use BicBucStriim\Framework\RoleGuard;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\PsrHttpFactoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Listens to the kernel.controller event to run our Action classes inside a
 * PSR-15 middleware pipeline. This replicates the behaviour of the Slim
 * ActionsWrapperStrategy and the custom App's dispatch.
 *
 * When a route carries middleware (the group admin guard for gated routes,
 * plus any route-level middleware such as RoleGuard::class), the action is
 * executed inside the shared MiddlewareDispatcher and the resulting response is
 * set on the event, which short-circuits Symfony's own controller invocation
 * (HttpKernel::handleRaw checks hasResponse() after dispatching kernel.controller).
 * When there is no middleware, the action is initialized and left for Symfony to
 * invoke as usual (the PsrResponseListener then converts the PSR-7 result).
 */
#[AsEventListener(event: KernelEvents::CONTROLLER, method: 'onKernelController')]
class ActionInitializeListener
{
    public function __construct(
        private PsrHttpFactoryInterface $psrHttpFactory,
        private MiddlewareResolver $middlewareResolver,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Only handle our Action classes; leave every other controller alone.
        if (!is_array($controller) || !$controller[0] instanceof DefaultActions) {
            return;
        }

        /** @var DefaultActions $actionInstance */
        [$actionInstance, $method] = $controller;
        $request = $event->getRequest();

        // Convert the Symfony Request to a PSR-7 Request.
        $psrRequest = $this->psrHttpFactory->createRequest($request);

        // Build the middleware list for this route. Symfony has no native
        // per-route middleware, so ActionRouteLoader carries both the group
        // gatekeeper flag (_bbs_gated, e.g. Admin/Metadata/Extra) and the
        // route-level middleware (_bbs_middleware, e.g. RoleGuard::class on
        // main-author-note) as route defaults. Prepend RoleGuard for gated
        // routes and resolve every entry to a concrete PSR-15 instance via the
        // shared, container-aware MiddlewareResolver.
        $gated = $request->attributes->getBoolean('_bbs_gated');
        $routeMiddleware = $request->attributes->get('_bbs_middleware', []);
        if ($gated) {
            array_unshift($routeMiddleware, RoleGuard::class);
        }
        $middleware = $this->middlewareResolver->resolve($routeMiddleware);

        if (empty($middleware)) {
            // No middleware for this route: initialize the action and let Symfony
            // invoke the controller itself. The PsrResponseListener (kernel.view)
            // converts the action's PSR-7 response into a Symfony Response.
            $actionInstance->initialize($psrRequest, null);
            return;
        }

        // Middleware present: run the action inside a PSR-15 pipeline and take
        // over the response via setResponse(), so the middleware (e.g. RoleGuard's
        // 403 short-circuit) wraps it exactly as on the custom App and Laravel.
        // The terminal handler initializes the action and calls the method with
        // the route parameters — the same positional-args convention the custom
        // App and Laravel use (action methods take simple scalar route params).
        $routeParams = array_values($request->attributes->get('_route_params', []));
        $handler = new class ($actionInstance, $method, $routeParams) implements RequestHandlerInterface {
            public function __construct(
                private DefaultActions $action,
                private string $method,
                private array $args,
            ) {}

            public function handle(PsrRequest $request): PsrResponse
            {
                $this->action->initialize($request, null);
                return $this->action->{$this->method}(...$this->args);
            }
        };
        $psrResponse = (new MiddlewareDispatcher($middleware, $handler))->handle($psrRequest);

        // Convert the PSR-7 response to a native Symfony Response and short-
        // circuit the kernel. (kernel.view is not dispatched on this path, so we
        // convert here rather than relying on the PsrResponseListener.)
        $event->setResponse($this->httpFoundationFactory->createResponse($psrResponse));
    }
}
