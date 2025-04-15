<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\AppData\Settings;
use Middlewares\Utils\CallableHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteCollectorInterface;

class TestHelper
{
    protected static function baseDir()
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Get Slim app with bootstrap = for full actions
     * @param mixed $login
     * @return mixed
     */
    public static function getApp($login = 0)
    {
        $app = require static::baseDir() . '/config/bootstrap.php';
        $settings = $app->getContainer()->get(Settings::class);
        // will be overridden by own config middleware
        $settings->must_login = $login;
        $app->getContainer()->set(Settings::class, $settings);
        return $app;
    }

    /**
     * Make hasapi configurable via environment variable
     */
    public static function getAppWithApi($hasapi = true)
    {
        // we need to set this before bootstrap to get api routes
        putenv("BBS_HAS_API=$hasapi");
        $app = require static::baseDir() . '/config/bootstrap.php';
        $settings = $app->getContainer()->get(Settings::class);
        $settings['hasapi'] = $hasapi;
        $settings->must_login = 0;
        $app->getContainer()->set(Settings::class, $settings);
        return $app;
    }

    /**
     * Get Slim app with container = for middleware & basic actions without full bootstrap
     * @return \Slim\App<\DI\Container>
     */
    public static function getAppWithContainer()
    {
        $container = require static::baseDir() . '/config/container.php';
        AppFactory::setContainer($container);
        /** @var \Slim\App<\DI\Container> $app */
        $app = AppFactory::create();
        $settings = require static::baseDir() . '/config/settings.php';
        // set by LoginMiddleware based on request in normal operation
        $settings['globalSettings']['l10n'] = new \BicBucStriim\Utilities\L10n('en');
        $app->getContainer()->set(Settings::class, $settings['globalSettings']);
        $app->getContainer()->set(ResponseFactoryInterface::class, fn() => $app->getResponseFactory());
        $app->getContainer()->set(RouteCollectorInterface::class, fn() => $app->getRouteCollector());
        // skip middleware and routes here

        return $app;
    }

    /**
     * Get auth factory with null session & cookies = for resume service and auth below
     * @param mixed $request
     * @return \Aura\Auth\AuthFactory
     */
    public static function getAuthFactory($request)
    {
        return new \Aura\Auth\AuthFactory($request->getCookieParams(), new \Aura\Auth\Session\NullSession(), new \Aura\Auth\Session\NullSegment());
    }

    /**
     * Get auth tracker = for requests with auth
     * @param mixed $request
     * @param mixed $userData
     * @return \Aura\Auth\Auth
     */
    public static function getAuth($request, $userData = null)
    {
        $authFactory = static::getAuthFactory($request);
        $auth = $authFactory->newInstance();
        if (!empty($userData)) {
            $auth->setStatus(\Aura\Auth\Status::VALID);
            $auth->setUserName($userData['username'] ?? 'admin');
            $auth->setUserData($userData);
            $auth->setFirstActive(time() - 60);
            $auth->setLastActive(time());
        }

        return $auth;
    }

    /**
     * Get server request with auth = for actions with gatekeeper
     * @param mixed $method
     * @param mixed $uri
     * @param mixed $params
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function getAuthRequest($method = null, $uri = null, $params = null)
    {
        $request = RequestUtil::getServerRequest($method, $uri, $params);
        //$request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');
        $userData = [
            'id' => 1,
            'role' => 1,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        $request = $request->withAttribute('auth', $auth);
        return $request;
    }

    /**
     * Get request handler = for use in middleware tests
     * @param mixed $app
     * @param mixed $content
     * @return CallableHandler
     */
    public static function getHandler($app, $content = 'Handled!')
    {
        $handler = new CallableHandler(function ($request) use ($app, $content) {
            $response = $app->getResponseFactory()->createResponse();
            $response->getBody()->write((string) $content);
            return $response;
        }, $app->getResponseFactory());

        return $handler;
    }
}
