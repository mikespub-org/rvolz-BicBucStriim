<?php

declare(strict_types=1);

namespace BicBucStriim\Tests\Framework;

use BicBucStriim\Framework\App;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\AppData\Settings;
use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Utilities\L10n;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(App::class)]
class AppTest extends TestCase
{
    protected TestHelper $helper;
    protected string $testDbPath;

    public function setUp(): void
    {
        $this->testDbPath = dirname(__DIR__) . '/fixtures/lib2';
    }

    public function tearDown(): void
    {
        // ...
    }

    /**
     * Creates a DI container configured for testing.
     */
    private function getTestContainer(): \Psr\Container\ContainerInterface
    {
        $container = include dirname(__DIR__, 2) . '/config/container.php';

        // Override settings to point to our test database
        $settings = new Settings([
            'calibre_dir' => $this->testDbPath,
            'lang' => 'en',
            'l10n' => new L10n('en'),
            'sep' => ' - ',
            'version' => 'TEST',
        ]);
        $calibre = new Calibre($this->testDbPath . '/metadata.db');

        $container->set(Settings::class, $settings);
        $container->set(Calibre::class, $calibre);

        return $container;
    }

    /**
     * Test a successful route match and action execution.
     */
    public function testHandleFoundRoute(): void
    {
        $container = $this->getTestContainer();
        $app = new App($container);

        // Create a request for the home page
        $request = RequestUtil::getServerRequest('GET', '/');

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<h2>Most recent</h2>', (string) $response->getBody());
    }

    /**
     * Test the response for a route that does not exist.
     */
    public function testHandleNotFoundRoute(): void
    {
        $container = $this->getTestContainer();
        $app = new App($container);

        $request = RequestUtil::getServerRequest('GET', '/a/non/existent/route');

        $response = $app->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('404 Not Found', (string) $response->getBody());
    }

    /**
     * Test the response for a route with an invalid HTTP method.
     */
    public function testHandleMethodNotAllowed(): void
    {
        $container = $this->getTestContainer();
        $app = new App($container);

        // The home page route '/' only accepts GET, so POST should fail.
        $request = RequestUtil::getServerRequest('POST', '/');
        $response = $app->handle($request);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertStringContainsString('405 Method Not Allowed', (string) $response->getBody());
    }

    /**
     * Test that a gated (admin) route is denied when no admin identity is
     * present on the request. This exercises the RoleGuard middleware branch of
     * App::handle() (route['gated'] -> unshift guard -> MiddlewareDispatcher)
     * which is unreachable from the public routes tested above.
     */
    public function testHandleGatedRouteDenied(): void
    {
        $container = $this->getTestContainer();
        $app = new App($container);

        // /admin/ is gated: AdminActions requires the admin role.
        $request = RequestUtil::getServerRequest('GET', '/admin/');

        $response = $app->handle($request);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('sufficient access rights', (string) $response->getBody());
    }

    /**
     * Test that a gated (admin) route is allowed and dispatched to the action
     * when a valid admin identity is present on the request 'auth' attribute.
     */
    public function testHandleGatedRouteAllowed(): void
    {
        $container = $this->getTestContainer();
        $app = new App($container);

        $request = RequestUtil::getServerRequest('GET', '/admin/');
        $auth = TestHelper::getAuth($request, ['role' => 1]);
        $request = $request->withAttribute('auth', $auth);

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that a route carrying a route-specific middleware class-string
     * (main-author-note -> /authors/{id}/notes/, middleware RoleGuard::class)
     * is denied when no admin identity is present. This exercises the
     * App::handle() route-middleware resolution path (resolve() string branch
     * -> RoleGuard fallback), which is distinct from the group-gated
     * resolveForRoute() path above.
     */
    public function testHandleRouteMiddlewareDenied(): void
    {
        $container = $this->getTestContainer();
        $app = new App($container);

        $request = RequestUtil::getServerRequest('GET', '/authors/1/notes/');

        $response = $app->handle($request);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('sufficient access rights', (string) $response->getBody());
    }

    /**
     * Test that the same route-specific-middleware route is allowed and
     * dispatched to the action when a valid admin identity is present.
     */
    public function testHandleRouteMiddlewareAllowed(): void
    {
        $container = $this->getTestContainer();
        $app = new App($container);

        $request = RequestUtil::getServerRequest('GET', '/authors/1/notes/');
        $auth = TestHelper::getAuth($request, ['role' => 1]);
        $request = $request->withAttribute('auth', $auth);

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * A Closure route (context-free handler, no action instance) must dispatch
     * end-to-end on the custom App: the registry carries it as a RouteEntry
     * whose callable is a Closure, and ActionResolver invokes it directly.
     */
    public function testHandleClosureRoute(): void
    {
        $container = $this->getTestContainer();
        $registry = new \BicBucStriim\Actions\ActionRegistry($container);
        $registry->register(ClosureTestAction::class);
        $app = new App($container, $registry);

        $request = RequestUtil::getServerRequest('GET', '/closure/ping');
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('CLOSURE-E2E', (string) $response->getBody());
    }

    /**
     * Control case: array-shaped routes on the same registry still dispatch to
     * the action (proving the closure seam didn't disturb the normal path).
     */
    public function testHandleArrayRouteStillWorksWithInjectedRegistry(): void
    {
        $container = $this->getTestContainer();
        $registry = new \BicBucStriim\Actions\ActionRegistry($container);
        $registry->register(ClosureTestAction::class);
        $app = new App($container, $registry);

        // The standard 'hello' route (DefaultActions) is loaded by ActionLoader
        // into the injected registry too; it must still resolve normally.
        $request = RequestUtil::getServerRequest('GET', '/');
        $response = $app->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Most recent', (string) $response->getBody());
    }
}

/**
 * Minimal action exposing a single Closure route, used to verify that
 * context-free Closure handlers dispatch through the custom App (and, by
 * extension, the other framework adapters) just like array-shaped handlers.
 */
class ClosureTestAction extends \BicBucStriim\Actions\DefaultActions
{
    public const PREFIX = '/closure';

    public static function getRoutes($self)
    {
        return [
            // name => [method(s), path, ...middleware(s), callable]
            'closure-ping' => ['GET', '/ping', function ($request, $response) {
                $response->getBody()->write('CLOSURE-E2E');
                return $response;
            }],
        ];
    }
}
