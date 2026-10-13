<?php

/**
 * Adapted from https://github.com/selective-php/basepath
 */

namespace BicBucStriim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use BicBucStriim\Utilities\RequestUtil;

/**
 * Base path middleware.
 *
 * Detects the URL base path (root or subfolder) and exposes it via the
 * neutral RequestUtil::BASE_PATH request attribute. Works with Slim (which
 * also needs App::setBasePath()) and with the framework-agnostic custom App.
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
    public function __construct($app, ?string $phpSapi = null)
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
        // Slim's App exposes setBasePath(); the framework-agnostic custom App
        // does not (it strips the base path from the dispatch path in handle()),
        // so only call it when the host app provides it.
        if (method_exists($this->app, 'setBasePath')) {
            $this->app->setBasePath($basePath);
        }
        // Set the neutral base-path attribute on the request so getPathInfo()
        // (and the custom App's FastRoute dispatch) can strip it consistently.
        $request = $request->withAttribute(RequestUtil::BASE_PATH, $basePath);

        return $handler->handle($request);
    }
}
