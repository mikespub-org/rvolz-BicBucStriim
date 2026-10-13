<?php

declare(strict_types=1);

namespace BicBucStriim\Framework;

use BicBucStriim\Utilities\RequestUtil;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Framework-agnostic admin access guard (PSR-15 middleware).
 *
 * This is the framework-neutral equivalent of the Slim GatekeeperMiddleware for
 * frameworks that do not reuse the Twig-based HTML error page (the custom App,
 * Symfony, Laravel). It applies the same admin check (RequestUtil::isAdmin) and
 * denies non-admin requests with a plain 403 response - without any dependency
 * on DefaultActions or the template engine, both of which are Slim-coupled.
 *
 * It relies on the authenticated identity being present on the request as the
 * 'auth' attribute (as set by the Slim login middleware / an auth bridge).
 * Frameworks without such a bridge therefore fail closed (deny everyone) until
 * auth is bridged, which is safer than leaving admin routes unprotected.
 */
class RoleGuard implements MiddlewareInterface
{
    /** @var string Message returned when access is denied. */
    public const DENIED_MESSAGE = "You don't have sufficient access rights.";

    public function __construct(
        private ?ResponseFactoryInterface $responseFactory = null,
    ) {}

    /**
     * Framework-agnostic admin access check, shared by every non-Slim adapter
     * (the custom App, Symfony, Laravel).
     *
     * Applies the same check as the Slim GatekeeperMiddleware: the request is
     * allowed only if it carries an authenticated admin identity (the 'auth'
     * request attribute, as set by a login/auth bridge). Frameworks without
     * such a bridge therefore fail closed (deny everyone) until auth is
     * bridged, which is safer than leaving admin routes unprotected.
     */
    public static function isAllowed(ServerRequestInterface $request): bool
    {
        $requester = new RequestUtil($request);
        return $requester->isAdmin();
    }

    /**
     * Determine whether a route's middleware list contains a RoleGuard-type guard.
     *
     * The route map stores middleware as framework-agnostic class-strings (e.g.
     * RoleGuard::class), so a route can be protected by an access guard without
     * also being part of a gated action group. Reference adapters (Symfony,
     * Laravel) that do not run a native PSR-15 route pipeline use this to detect
     * the guard and enforce the admin check inline, keeping the map identical
     * across every framework.
     *
     * @param array<int, string|object> $middleware
     */
    public static function hasGuard(array $middleware): bool
    {
        foreach ($middleware as $entry) {
            $class = is_object($entry) ? get_class($entry) : $entry;
            if (is_string($class) && ($class === self::class || is_subclass_of($class, self::class))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build the plain "access denied" response (HTTP 403).
     */
    public function denied(): ResponseInterface
    {
        $factory = $this->responseFactory ?? new Psr17Factory();
        $response = $factory->createResponse(403);
        $response->getBody()->write(self::DENIED_MESSAGE);
        return $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (self::isAllowed($request)) {
            return $handler->handle($request);
        }

        return $this->denied();
    }
}
