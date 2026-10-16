<?php

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\ExtraActions;
use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\AppData\Settings;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\RouteUtil;
use BicBucStriim\Utilities\TestHelper;
use Slim\Factory\AppFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExtraActions::class)]
class ExtraActionsTest extends TestCase
{
    public static function getExpectedRoutes()
    {
        return [
            // 'name' => [method(s), path, ...middleware(s), callable] with '$self' string
            'extra-loader-path' => ['GET', '/loader/{path:.*}', ['$self', 'loader']],
            'extra-loader' => ['GET', '/loader', ['$self', 'loader']],
            'extra' => ['GET', '/', ['$self', 'extra']],
        ];
    }

    public function testAddRoutes(): void
    {
        $expected = $this->getExpectedRoutes();
        $app = AppFactory::create();
        // Register only this action class, then drive the production helper
        // (addRoutesFromMap) from the single-source-of-truth route map.
        $registry = new ActionRegistry();
        $registry->register(ExtraActions::class);
        RouteUtil::addRoutesFromMap($app, $registry->getRouteMap());
        $routeCollector = $app->getRouteCollector();
        $routes = $routeCollector->getRoutes();
        $this->assertEquals(count($expected), count($routes));
        $patterns = [];
        foreach ($routes as $route) {
            $patterns[] = $route->getPattern();
        }
        foreach ($expected as $routeInfo) {
            $path = ExtraActions::PREFIX . $routeInfo[1];
            $this->assertEquals(true, in_array($path, $patterns));
        }
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testExtraRequestNoAuth(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/extra/');

        $expected = 302;
        $response = $app->handle($request);
        $this->assertEquals($expected, $response->getStatusCode());
        $expected = ['./login/'];
        $this->assertEquals($expected, $response->getHeader('Location'));
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testExtraRequest(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/extra/');

        $expected = '<title>BicBucStriim :: Extra</title>';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testLoaderRequest(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/extra/loader');

        $expected = '<title>BBS Loader</title>';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testLoaderPathRequest(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/extra/loader/books/0/5');

        $expected = 'Die Glücksritter';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    public static function getMetadataCallbacks(): array
    {
        return [
            'author' => ['setAuthorInfo', 'author', 'author', 'name', 'link', 7],
            'series' => ['setSeriesInfo', 'series', 'series', 'name', 'link', 1],
            'book' => ['setBookInfo', 'title', 'book', 'title', 'uri', 1],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getMetadataCallbacks')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testMetadataCallbacksAddLinks(
        string $callback,
        string $calibreMethod,
        string $bbsMethod,
        string $nameField,
        string $linkField,
        int $id
    ): void {
        $databasePath = TestHelper::copyFixtureDatabase();
        $url = 'https://example.org/item';
        $source = 'Metadata source';
        try {
            $app = TestHelper::getApp(0, $databasePath);
            $settings = $app->getContainer()->get(Settings::class);
            $settings[Settings::CALIBRE_DIR] = dirname(__DIR__, 2) . '/tests/fixtures/lib2';
            $app->getContainer()->set(Settings::class, $settings);
            $action = new ExtraActions($app->getContainer());
            $record = $action->calibre()->$calibreMethod($id);
            $this->assertNotNull($record);
            $name = $record->{$nameField};
            $bbs = $app->getContainer()->get(BicBucStriim::class);
            $entity = $bbs->$bbsMethod($id, $name);
            $this->assertSame([], $entity->getLinks());

            $info = (object) ['source' => $source, $linkField => $url];

            $this->assertTrue($action->$callback($id, $info));
            $links = $bbs->$bbsMethod($id, $name)->getLinks();
            $this->assertCount(1, $links);
            $this->assertSame($url, $links[0]->url);
            $this->assertSame($source . ' Link', $links[0]->label);
        } finally {
            unset($entity, $bbs, $action, $settings, $app, $record);
            TestHelper::removeFixtureDatabase($databasePath);
        }
    }
}
