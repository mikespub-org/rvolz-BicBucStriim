<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Middleware;

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Framework\RoleGuard;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Route or group middleware to check if user has admin access.
 *
 * The admin decision itself is delegated to the framework-agnostic
 * \BicBucStriim\Framework\RoleGuard (shared by every adapter), so the policy
 * has a single source of truth. This middleware only adds the Slim/Twig
 * behavior: on denial it renders the HTML error page, for which it lazily
 * composes a DefaultActions helper instead of inheriting from it (keeping the
 * middleware focused on access control rather than being a DefaultActions).
 */
class GatekeeperMiddleware implements MiddlewareInterface
{
    /** @var ContainerInterface */
    private $container;
    /** @var ?DefaultActions lazily built to render the Twig error page */
    private $actions = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Check if user has admin access or return error page response
     * @see \BicBucStriim\Actions\DefaultActions::check_admin()
     * @param Request $request The request
     * @param RequestHandler $handler The handler
     * @return Response The response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Shared, framework-agnostic admin policy. The decision needs only the
        // request (the authenticated 'auth' attribute), so no DefaultActions
        // initialization is required on the allowed path.
        if (RoleGuard::isAllowed($request)) {
            return $handler->handle($request);
        }

        // Denied: lazily build + initialize a DefaultActions helper to render
        // the Twig error page (only needed on this path).
        $actions = $this->actions ??= new DefaultActions($this->container);
        $actions->initialize($request, null);
        return $actions->render('error.twig', [
            'page' => $actions->buildPage('error', 0, 0),
            'error' => $actions->getMessageString('error_no_access')]);
    }
}
