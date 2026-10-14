<?php

declare(strict_types=1);

namespace BicBucStriim\Framework\Event;

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Framework\MiddlewareDispatcher;
use BicBucStriim\Framework\MiddlewareResolver;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\PsrHttpFactoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
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
 * @suppress PHP0413
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

        // Context-free Closure route: no action instance, but still run through
        // the shared PSR-15 pipeline (see dispatchClosure()).
        if ($controller instanceof \Closure) {
            $this->dispatchClosure($event, $controller);
            return;
        }

        // Only handle our Action classes; leave every other controller alone.
        if (!is_array($controller) || !$controller[0] instanceof DefaultActions) {
            return;
        }

        $this->dispatchAction($event, $controller);
    }

    /**
     * Resolve the PSR-15 middleware for the current route.
     *
     * Symfony has no native per-route middleware, so ActionRouteLoader carries
     * both the group gatekeeper flag (_bbs_gated, e.g. Admin/Metadata/Extra) and
     * the route-level middleware (_bbs_middleware, e.g. RoleGuard::class on
     * main-author-note) as route defaults. resolveForRoute() is the single,
     * container-aware spelling of the gated+route policy shared with the custom
     * App and Laravel adapters.
     */
    private function resolveMiddleware(Request $request): array
    {
        $gated = $request->attributes->getBoolean('_bbs_gated');
        $routeMiddleware = $request->attributes->get('_bbs_middleware', []);
        return $this->middlewareResolver->resolveForRoute($gated, $routeMiddleware);
    }

    /**
     * Run a terminal handler inside the shared PSR-15 pipeline with a PSR-7
     * request, then bridge the PSR-7 response back to a native Symfony response
     * via setResponse() — short-circuiting Symfony's own controller invocation.
     *
     * @param array<int, mixed> $middleware already resolved via resolveMiddleware()
     */
    private function runInPipeline(ControllerEvent $event, array $middleware, RequestHandlerInterface $handler): void
    {
        $psrRequest = $this->psrHttpFactory->createRequest($event->getRequest());
        $psrResponse = new MiddlewareDispatcher($middleware, $handler)->handle($psrRequest);
        $event->setResponse($this->httpFoundationFactory->createResponse($psrResponse));
    }

    /**
     * Dispatch a context-free Closure route. There is no action instance to
     * initialize, but the closure still runs through the shared PSR-15 middleware
     * pipeline (with a PSR-7 request) so route/group middleware applies and the
     * closure behaves exactly as it does on the custom App and Laravel.
     */
    private function dispatchClosure(ControllerEvent $event, \Closure $callable): void
    {
        $request = $event->getRequest();
        $routeParams = array_values($request->attributes->get('_route_params', []));
        $handler = new class ($callable, $routeParams) implements RequestHandlerInterface {
            public function __construct(
                private \Closure $callable,
                private array $args,
            ) {}

            public function handle(PsrRequest $request): PsrResponse
            {
                return ($this->callable)($request, null, ...$this->args);
            }
        };
        $this->runInPipeline($event, $this->resolveMiddleware($request), $handler);
    }

    /**
     * Dispatch an Action-class route. With no middleware, the action is only
     * initialized and left for Symfony to invoke as usual (the PsrResponseListener
     * then converts the PSR-7 result). With middleware, it runs inside the shared
     * pipeline so the middleware (e.g. RoleGuard's 403 short-circuit) wraps it
     * exactly as on the custom App and Laravel.
     */
    private function dispatchAction(ControllerEvent $event, array $controller): void
    {
        /** @var DefaultActions $actionInstance */
        [$actionInstance, $method] = $controller;
        $request = $event->getRequest();
        $psrRequest = $this->psrHttpFactory->createRequest($request);
        $middleware = $this->resolveMiddleware($request);

        if (empty($middleware)) {
            // No middleware for this route: initialize the action and let Symfony
            // invoke the controller itself. The PsrResponseListener (kernel.view)
            // converts the action's PSR-7 response into a Symfony Response.
            $actionInstance->initialize($psrRequest, null);
            return;
        }

        // Middleware present: run the action inside a PSR-15 pipeline and take
        // over the response via setResponse(). The terminal handler initializes
        // the action and calls the method with the route parameters — the same
        // positional-args convention the custom App and Laravel use.
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
        $this->runInPipeline($event, $middleware, $handler);
    }
}
