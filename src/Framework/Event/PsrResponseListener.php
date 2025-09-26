<?php

declare(strict_types=1);

namespace BicBucStriim\Framework\Event;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;

/**
 * Listens to the kernel.view event to convert PSR-7 responses
 * from our actions into Symfony HttpFoundation responses.
 */
#[AsEventListener(event: KernelEvents::VIEW, method: 'onKernelView')]
class PsrResponseListener
{
    public function __construct(
        private HttpFoundationFactoryInterface $httpFoundationFactory,
    ) {}

    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof PsrResponse) {
            $symfonyResponse = $this->httpFoundationFactory->createResponse($controllerResult);
            $event->setResponse($symfonyResponse);
        }
    }
}
