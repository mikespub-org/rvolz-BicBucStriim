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
            'l10n' => new L10n($en),
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
}
