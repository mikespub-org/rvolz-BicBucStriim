<?php

declare(strict_types=1);

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\AppData\Settings;
use BicBucStriim\Middleware\CalibreConfigMiddleware;
use BicBucStriim\Middleware\OwnConfigMiddleware;
use BicBucStriim\Session\SessionFactory;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the boot-time config middlewares plus the Session cookie helpers.
 *
 * CalibreConfigMiddleware redirects to /admin/configuration/ unless the
 * Calibre library is usable (or the request targets login/config pages).
 * OwnConfigMiddleware maps the BBS config DB status to OK/OOPS/OLD. Session
 * covers getCookie/setCookie and the local/auth segment names.
 */
#[CoversClass(CalibreConfigMiddleware::class)]
#[CoversClass(OwnConfigMiddleware::class)]
#[CoversClass(\BicBucStriim\Session\Session::class)]
#[CoversClass(SessionFactory::class)]
class ConfigMiddlewareTest extends PHPUnit\Framework\TestCase
{
    public function testCalibreLoginPageAlwaysPasses(): void
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->calibre_dir = '/nonexistent-calibre-dir';
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/login/');
        $handler = TestHelper::getHandler($app, 'LOGIN');
        $middleware = new CalibreConfigMiddleware($app->getContainer());

        $response = $middleware->process($request, $handler);
        $this->assertStringContainsString('LOGIN', (string) $response->getBody());
    }

    public function testCalibreMissingDirRedirectsToConfiguration(): void
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->calibre_dir = '';
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/titles/');
        $handler = TestHelper::getHandler($app, 'HANDLED');
        $middleware = new CalibreConfigMiddleware($app->getContainer());

        $response = $middleware->process($request, $handler);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('/admin/configuration/', $response->getHeaderLine('Location'));
    }

    public function testCalibreConfigPagePassesWithoutRedirect(): void
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->calibre_dir = '';
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/admin/configuration/');
        $handler = TestHelper::getHandler($app, 'CONFIG');
        $middleware = new CalibreConfigMiddleware($app->getContainer());

        $response = $middleware->process($request, $handler);
        $this->assertStringContainsString('CONFIG', (string) $response->getBody());
    }

    public function testCalibreInvalidDirRedirects(): void
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->calibre_dir = '/nonexistent-calibre-dir';
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/titles/');
        $handler = TestHelper::getHandler($app, 'HANDLED');
        $middleware = new CalibreConfigMiddleware($app->getContainer());

        $response = $middleware->process($request, $handler);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testCalibreValidDirPasses(): void
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->calibre_dir = dirname(__DIR__) . '/fixtures/lib2';
        $app->getContainer()->set(Settings::class, $settings);
        $app->getContainer()->set(
            \BicBucStriim\Calibre\Calibre::class,
            new \BicBucStriim\Calibre\Calibre($settings->calibre_dir . '/metadata.db')
        );

        $request = RequestUtil::getServerRequest('GET', '/titles/');
        $handler = TestHelper::getHandler($app, 'HANDLED');
        $middleware = new CalibreConfigMiddleware($app->getContainer());

        $response = $middleware->process($request, $handler);
        $this->assertStringContainsString('HANDLED', (string) $response->getBody());
    }

    public function testOwnConfigMissingDbReturns500(): void
    {
        // Sandbox app with a fresh, empty DB file that never existed:
        // dbOk() is false and OwnConfigMiddleware must answer 500.
        $dir = sys_get_temp_dir() . '/bbs-nodb-' . uniqid();
        mkdir($dir);
        $app = TestHelper::getAppWithContainer($dir . '/data.db');

        $request = RequestUtil::getServerRequest('GET', '/');
        $handler = TestHelper::getHandler($app, 'HANDLED');
        $middleware = new OwnConfigMiddleware($app->getContainer(), Settings::getKnownConfigs());

        $response = $middleware->process($request, $handler);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('installcheck.php', (string) $response->getBody());
    }

    public function testOwnConfigOldDbReturns500(): void
    {
        // Sandbox app with a fixture-booted DB whose stored db_version is
        // downgraded to an old version, so OwnConfigMiddleware must answer 500
        // with 'Old configuration database'.
        $databasePath = TestHelper::copyFixtureDatabase();
        try {
            $app = TestHelper::getAppWithContainer($databasePath);
            $bbs = $app->getContainer()->get(BicBucStriim::class);
            // Downgrade the stored db_version so OwnConfigMiddleware sees STATUS_OLD.
            $bbs->mydb->exec("UPDATE config SET val = '1' WHERE name = 'db_version'");

            $request = RequestUtil::getServerRequest('GET', '/');
            $handler = TestHelper::getHandler($app, 'HANDLED');
            $middleware = new OwnConfigMiddleware($app->getContainer(), Settings::getKnownConfigs());

            $response = $middleware->process($request, $handler);
            $this->assertSame(500, $response->getStatusCode());
            $this->assertStringContainsString('Old configuration database', (string) $response->getBody());
        } finally {
            unset($response, $middleware, $handler, $request, $bbs, $app);
            TestHelper::removeFixtureDatabase($databasePath);
        }
    }

    public function testOwnConfigOkPasses(): void
    {
        $databasePath = TestHelper::copyFixtureDatabase();
        try {
            $app = TestHelper::getAppWithContainer($databasePath);
            $request = RequestUtil::getServerRequest('GET', '/');
            $handler = TestHelper::getHandler($app, 'HANDLED');
            $middleware = new OwnConfigMiddleware($app->getContainer(), Settings::getKnownConfigs());

            $response = $middleware->process($request, $handler);
            $this->assertStringContainsString('HANDLED', (string) $response->getBody());
        } finally {
            unset($response, $middleware, $handler, $request, $app);
            TestHelper::removeFixtureDatabase($databasePath);
        }
    }
}
