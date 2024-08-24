<?php

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\ResponseUtil;
use BicBucStriim\Utilities\RouteUtil;
use BicBucStriim\Utilities\TestHelper;
use BicBucStriim\Utilities\ActionsCallableResolver;
use BicBucStriim\Utilities\ActionsWrapperStrategy;
use Slim\Factory\AppFactory;

/**
 * @covers \BicBucStriim\Actions\DefaultActions
 * @covers \BicBucStriim\Traits\AppTrait
 * @covers \BicBucStriim\Utilities\ActionsCallableResolver
 * @covers \BicBucStriim\Utilities\ActionsWrapperStrategy
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

    public function testGetRoutesWithSelf()
    {
        $expected = array_values($this->getExpectedRoutes());
        //$app = AppFactory::create();
        //$self = new DefaultActions($app);
        $container = require dirname(__DIR__) . '/config/container.php';
        $self = new DefaultActions($container);
        // replace '$self' in $expected with actual $self
        array_walk($expected, function (&$value) use ($self) {
            $value[2][0] = $self;
        });
        $routes = DefaultActions::getRoutes($self);
        $this->assertEquals($expected, $routes);
    }

    public function testGetRoutesWithStatic()
    {
        $expected = array_values($this->getExpectedRoutes());
        //$app = AppFactory::create();
        $self = DefaultActions::class;
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
        //$app = AppFactory::create();
        //$self = new DefaultActions($app);
        $app = TestHelper::getAppWithContainer();
        $self = new DefaultActions($app->getContainer());
        $callable = [$self, 'hello'];
        $args = [];
        $result = $callable(...$args);
        $result ??= $self->response();
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($result));
        $this->assertEquals($expected, (string) $self->response()->getBody());
    }

    public function testHelloWithName()
    {
        $expected = 'Hello, name!';
        //$app = AppFactory::create();
        //$self = new DefaultActions($app);
        $app = TestHelper::getAppWithContainer();
        $self = new DefaultActions($app->getContainer());
        $callable = [$self, 'hello'];
        $args = ['name'];
        $result = $callable(...$args);
        $result ??= $self->response();
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($result));
        $this->assertEquals($expected, (string) $self->response()->getBody());
    }

    protected function skipTestHelloViaRouteHandler()
    {
        //$this->markTestSkipped('Using wrap route handler is deprecated');
        $app = AppFactory::create();
        //$self = new DefaultActions($app);
        $container = require dirname(__DIR__) . '/config/container.php';
        $self = new DefaultActions($container);
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

    protected function skipTestHelloViaCallableResolver()
    {
        //$this->markTestSkipped('Using actions callable resolver is deprecated');
        $container = require dirname(__DIR__) . '/config/container.php';
        AppFactory::setContainer($container);
        // Slim 4 framework uses its own CallableResolver if this is a class string, *before* invocation strategy
        $callableResolver = new ActionsCallableResolver($container);
        AppFactory::setCallableResolver($callableResolver);
        $app = AppFactory::create();
        //$callableResolver->setApp($app);
        /**
         * See https://www.slimframework.com/docs/v4/objects/routing.html#route-strategies
         * Changing the default invocation strategy on the RouteCollector component
         * will change it for every route being defined after this change being applied
         */
        $routeCollector = $app->getRouteCollector();
        $routeCollector->setDefaultInvocationStrategy(new ActionsWrapperStrategy());
        DefaultActions::addRoutes($app);

        $expected = 'Hello, world!';
        $request = RequestUtil::getServerRequest('GET', '/');
        $response = $app->handle($request);
        $this->assertEquals($expected, (string) $response->getBody());
    }

    public function testHelloViaAppRequest()
    {
        $container = require dirname(__DIR__) . '/config/container.php';
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        /**
         * See https://www.slimframework.com/docs/v4/objects/routing.html#route-strategies
         * Changing the default invocation strategy on the RouteCollector component
         * will change it for every route being defined after this change being applied
         */
        $routeCollector = $app->getRouteCollector();
        $routeCollector->setDefaultInvocationStrategy(new ActionsWrapperStrategy());
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
}
