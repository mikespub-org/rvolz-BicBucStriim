<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\AppData\Settings;
use Middlewares\Utils\CallableHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteCollectorInterface;

final class FixtureDatabaseException extends \RuntimeException {}

class TestHelper
{
    protected static function baseDir()
    {
        return dirname(__DIR__, 2);
    }

    protected static function useDataPath($app, ?string $dataPath): void
    {
        if ($dataPath !== null) {
            $app->getContainer()->set(
                BicBucStriim::class,
                new BicBucStriim($dataPath)
            );
        }
    }

    public static function copyFixtureDatabase(): string
    {
        $directory = sys_get_temp_dir() . '/bbs-fixture-' . bin2hex(random_bytes(6));
        mkdir($directory);
        $databasePath = $directory . '/data.db';
        $databaseCopied = copy(static::baseDir() . '/tests/fixtures/data2.db', $databasePath);
        $schemaCopied = copy(static::baseDir() . '/data/schema.sql', $directory . '/schema.sql');
        if (!$databaseCopied || !$schemaCopied) {
            static::removeFixtureDatabase($databasePath);
            throw new FixtureDatabaseException('Unable to copy the test database fixture.');
        }
        return $databasePath;
    }

    public static function removeFixtureDatabase(string $databasePath): void
    {
        $directory = dirname($databasePath);
        if (basename($databasePath) !== 'data.db' || !str_starts_with(basename($directory), 'bbs-fixture-')) {
            throw new \InvalidArgumentException('Only fixture database copies can be removed.');
        }

        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $entry) {
                if ($entry->isDir()) {
                    rmdir($entry->getPathname());
                } else {
                    unlink($entry->getPathname());
                }
            }
            rmdir($directory);
        }
    }

    /**
     * Get Slim app with bootstrap = for full actions
     * @param mixed $login
     * @param ?string $dataPath optional path to an isolated, initialized BBS database
     * @return mixed
     */
    public static function getApp($login = 0, ?string $dataPath = null)
    {
        $app = require static::baseDir() . '/config/bootstrap.php';
        static::useDataPath($app, $dataPath);
        $settings = $app->getContainer()->get(Settings::class);
        // will be overridden by own config middleware
        $settings->must_login = $login;
        $app->getContainer()->set(Settings::class, $settings);
        return $app;
    }

    /**
     * Make hasapi configurable via environment variable.
     * @param ?string $dataPath optional path to an isolated, initialized BBS database
     */
    public static function getAppWithApi($hasapi = true, ?string $dataPath = null)
    {
        // we need to set this before bootstrap to get api routes
        putenv("BBS_HAS_API=$hasapi");
        $app = require static::baseDir() . '/config/bootstrap.php';
        static::useDataPath($app, $dataPath);
        $settings = $app->getContainer()->get(Settings::class);
        $settings['hasapi'] = $hasapi;
        $settings->must_login = 0;
        $app->getContainer()->set(Settings::class, $settings);
        return $app;
    }

    /**
     * Get Slim app with container = for middleware & basic actions without full bootstrap
     * @param ?string $dataPath if given, the Slim app is built against this BBS DB
     *      instead of the live data/data.db (read-only, so tests can run against a
     *      sandbox without mutating the checked-in library).
     * @return \Slim\App<\DI\Container>
     */
    public static function getAppWithContainer($dataPath = null)
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

        static::useDataPath($app, $dataPath);

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

    /**
     * Build a sentinel PSR-7 response with a 'HANDLER-RAN' body.
     *
     * Usable as an array-shaped route handler [TestHelper::class, 'respondHandler']
     * in tests that actually dispatch to the handler, mirroring the production
     * [ActionClass::class, 'method'] shape carried by RouteEntry::$callable.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function respondHandler(): \Psr\Http\Message\ResponseInterface
    {
        $response = new \Nyholm\Psr7\Factory\Psr17Factory()->createResponse();
        $response->getBody()->write('HANDLER-RAN');
        return $response;
    }
}
