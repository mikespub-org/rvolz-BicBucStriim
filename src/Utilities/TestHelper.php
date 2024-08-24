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

    public static function getApp($login = 0)
    {
        $app = require(static::baseDir() . '/config/bootstrap.php');
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
        $app = require(static::baseDir() . '/config/bootstrap.php');
        $settings = $app->getContainer()->get(Settings::class);
        $settings['hasapi'] = $hasapi;
        $settings->must_login = 0;
        $app->getContainer()->set(Settings::class, $settings);
        return $app;
    }

    public static function getAppWithContainer()
    {
        $container = require(static::baseDir() . '/config/container.php');
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $settings = require(static::baseDir() . '/config/settings.php');
        // set by LoginMiddleware based on request in normal operation
        $settings['globalSettings']['l10n'] = new \BicBucStriim\Utilities\L10n('en');
        $app->getContainer()->set(Settings::class, $settings['globalSettings']);
        $app->getContainer()->set(ResponseFactoryInterface::class, fn() => $app->getResponseFactory());
        $app->getContainer()->set(RouteCollectorInterface::class, fn() => $app->getRouteCollector());
        // skip middleware and routes here

        return $app;
    }

    public static function getAuthFactory($request)
    {
        return new \Aura\Auth\AuthFactory($request->getCookieParams(), new \Aura\Auth\Session\NullSession(), new \Aura\Auth\Session\NullSegment());
    }

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
