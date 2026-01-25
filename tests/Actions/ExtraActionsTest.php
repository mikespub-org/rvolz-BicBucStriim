<?php

use BicBucStriim\Actions\ExtraActions;
use BicBucStriim\Utilities\RequestUtil;
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
        ExtraActions::addRoutes($app);
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
}
