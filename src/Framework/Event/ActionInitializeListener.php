<?php

declare(strict_types=1);

namespace BicBucStriim\Framework\Event;

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Framework\RoleGuard;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\PsrHttpFactoryInterface;

/**
 * Listens to the kernel.controller event to initialize our Action classes.
 * This replicates the behavior of the Slim ActionsWrapperStrategy.
 */
#[AsEventListener(event: KernelEvents::CONTROLLER, method: 'onKernelController')]
class ActionInitializeListener
{
    public function __construct(
        private PsrHttpFactoryInterface $psrHttpFactory,
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Check if the controller is one of our Action classes
        if (is_array($controller) && $controller[0] instanceof DefaultActions) {
            /** @var DefaultActions $actionInstance */
            $actionInstance = $controller[0];

            // Convert Symfony Request to a PSR-7 Request
            $psrRequest = $this->psrHttpFactory->createRequest($event->getRequest());

            // Enforce the group admin guard for gated routes (Admin/Metadata/
            // Extra). Symfony has no native per-route middleware, so the
            // gatekeeper flag is carried as a route default (_bbs_gated) by
            // ActionRouteLoader. Use the framework-agnostic RoleGuard check and
            // fail closed (deny) with an idiomatic 403. Frameworks without an
            // auth bridge deny everyone here, which is safer than leaving the
            // admin routes unprotected.
            if ($event->getRequest()->attributes->getBoolean('_bbs_gated') && !RoleGuard::isAllowed($psrRequest)) {
                throw new AccessDeniedHttpException(RoleGuard::DENIED_MESSAGE);
            }

            $actionInstance->initialize($psrRequest, null);
        }
    }
}
