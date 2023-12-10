<?php

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\ResponseUtil;
use BicBucStriim\Utilities\RouteUtil;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestResponseArgs;

/**
 * @covers \BicBucStriim\Actions\DefaultActions
 * @covers \BicBucStriim\Traits\AppTrait
 * @covers \BicBucStriim\Utilities\RequestUtil
 * @covers \BicBucStriim\Utilities\ResponseUtil
 * @covers \BicBucStriim\Utilities\RouteUtil
 */
class DefaultActionsTest extends PHPUnit\Framework\TestCase
{
    public static function getExpectedRoutes()
    {
        return [
            // 'name' => [method(s), path, ...middleware(s), callable] with '$self' string
            'hello' => ['GET', '/', ['$self', 'hello']],
            'hello_name' => ['GET', '/{name}', ['$self', 'hello']],
        ];
    }

    public function testGetRoutes()
    {
        $expected = $this->getExpectedRoutes();
        $app = AppFactory::create();
        $self = new DefaultActions($app);
        // replace '$self' in $expected with actual $self
        array_walk($expected, function (&$value) use ($self) {
            $value[2][0] = $self;
        });
        $routes = DefaultActions::getRoutes($self);
        $this->assertEquals($expected, $routes);
    }

    public function testAddRoutes()
    {
        $expected = $this->getExpectedRoutes();
        $app = AppFactory::create();
        DefaultActions::addRoutes($app);
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

    public function testHello()
    {
        $expected = 'Hello, world!';
        $app = AppFactory::create();
        $self = new DefaultActions($app);
        $callable = [$self, 'hello'];
        $args = [];
        $callable(...$args);
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($self->response()));
        $this->assertEquals($expected, (string) $self->response()->getBody());
    }

    public function testHelloWithName()
    {
        $expected = 'Hello, name!';
        $app = AppFactory::create();
        $self = new DefaultActions($app);
        $callable = [$self, 'hello'];
        $args = ['name'];
        $callable(...$args);
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($self->response()));
        $this->assertEquals($expected, (string) $self->response()->getBody());
    }

    public function testHelloViaRouteHandler()
    {
        $app = AppFactory::create();
        $self = new DefaultActions($app);
        $callable = [$self, 'hello'];
        $wrapper = RouteUtil::wrapRouteHandler($callable);

        $expected = 'Hello, world!';
        $request = RequestUtil::getServerRequest();
        $response = ResponseUtil::getResponse($app);
        $args = [];
        $response = $wrapper($request, $response, ...$args);
        $this->assertEquals($expected, (string) $response->getBody());

        $expected = 'Hello, name!';
        $request = RequestUtil::getServerRequest();
        $response = ResponseUtil::getResponse($app);
        $args = ['name'];
        $response = $wrapper($request, $response, ...$args);
        $this->assertEquals($expected, (string) $response->getBody());
    }

    public function testHelloViaAppRequest()
    {
        $app = AppFactory::create();
        /**
         * See https://www.slimframework.com/docs/v4/objects/routing.html#route-strategies
         * Changing the default invocation strategy on the RouteCollector component
         * will change it for every route being defined after this change being applied
         */
        $routeCollector = $app->getRouteCollector();
        $routeCollector->setDefaultInvocationStrategy(new RequestResponseArgs());
        DefaultActions::addRoutes($app);

        $expected = 'Hello, world!';
        $request = RequestUtil::getServerRequest('GET', '/');
        $response = $app->handle($request);
        $this->assertEquals($expected, (string) $response->getBody());

        $expected = 'Hello, name!';
        $request = RequestUtil::getServerRequest('GET', '/name');
        $response = $app->handle($request);
        $this->assertEquals($expected, (string) $response->getBody());
    }

    public function testCheckAdmin()
    {
        // @todo add container and auth to app to check admin
        $this->expectExceptionMessage('Call to a member function has() on null');
        $expected = true;
        $app = AppFactory::create();
        $self = new DefaultActions($app);
        $callable = [$self, 'check_admin'];
        $callable();
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($self->response()));
        $this->assertEquals($expected, (string) $self->response()->getBody());
    }
}
