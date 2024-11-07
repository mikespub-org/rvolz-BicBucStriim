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
            'loader_path' => ['GET', '/extra/loader/{path:.*}', ['$self', 'loader']],
            'loader' => ['GET', '/extra/loader', ['$self', 'loader']],
            'extra' => ['GET', '/extra/', ['$self', 'extra']],
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
            $this->assertEquals(true, in_array($routeInfo[1], $patterns));
        }
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testExtraRequestNoAuth(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/extra/');

        $expected = 'You don&#039;t have sufficient access rights.';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testExtraRequest(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/extra/');
        $userData = [
            'role' => 1,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        $request = $request->withAttribute('auth', $auth);

        $expected = '<title>BicBucStriim :: Extra</title>';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testLoaderRequest(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/extra/loader');
        $userData = [
            'role' => 1,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        $request = $request->withAttribute('auth', $auth);

        $expected = '<title>BBS Loader</title>';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testLoaderPathRequest(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/extra/loader/books/0/5');
        $userData = [
            'role' => 1,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        $request = $request->withAttribute('auth', $auth);

        $expected = 'Die Glücksritter';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }
}
