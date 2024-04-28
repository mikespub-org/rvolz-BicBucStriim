<?php
/**
 * Adapted from https://github.com/selective-php/basepath
 */

namespace BicBucStriim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Slim 4 Base path middleware.
 */
final class BasePathMiddleware implements MiddlewareInterface
{
    /**
     * @var \Slim\App|object The slim app
     */
    private $app;

    /**
     * @var string|null
     */
    private $phpSapi;

    /**
     * The constructor.
     *
     * @param \Slim\App|object $app The app
     * @param string|null $phpSapi The PHP_SAPI value
     */
    public function __construct($app, string $phpSapi = null)
    {
        $this->app = $app;
        $this->phpSapi = $phpSapi;
    }

    /**
     * Invoke middleware.
     *
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The handler
     *
     * @return ResponseInterface The response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $detector = new BasePathDetector($request->getServerParams(), $this->phpSapi);

        $basePath = $detector->getBasePath();
        $this->app->setBasePath($basePath);
        // set attribute on request for caching middleware
        $request = $request->withAttribute(RouteContext::BASE_PATH, $basePath);

        return $handler->handle($request);
    }
}
