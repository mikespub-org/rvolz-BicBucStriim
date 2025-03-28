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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Route or group middleware to check if user has admin access
 * Note: based on DefaultActions here instead of DefaultMiddleware to render response
 */
class GatekeeperMiddleware extends DefaultActions implements MiddlewareInterface
{
    /**
     * Check if user has admin access or return error page response
     * @see \BicBucStriim\Actions\DefaultActions::check_admin()
     * @param Request $request The request
     * @param RequestHandler $handler The handler
     * @return Response The response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // @todo remove dependency on $this->requester and $this->responder?
        $this->initialize($request, null);
        if (!$this->requester->isAdmin()) {
            return $this->render('error.twig', [
                'page' => $this->buildPage('error', 0, 0),
                'error' => $this->getMessageString('error_no_access')]);
        }

        return $handler->handle($request);
    }
}
