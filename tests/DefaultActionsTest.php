<?php

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\ResponseUtil;
use BicBucStriim\Utilities\RouteUtil;
use BicBucStriim\Utilities\TestHelper;
use BicBucStriim\Utilities\ActionsWrapperStrategy;
use Slim\Factory\AppFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DefaultActions::class)]
#[CoversClass(\BicBucStriim\Traits\AppTrait::class)]
#[CoversClass(ActionsWrapperStrategy::class)]
#[CoversClass(RequestUtil::class)]
#[CoversClass(ResponseUtil::class)]
#[CoversClass(RouteUtil::class)]
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

    public function testGetRoutesWithSelf(): void
    {
        $expected = array_values($this->getExpectedRoutes());
        $container = require dirname(__DIR__) . '/config/container.php';
        $self = new DefaultActions($container);
        // replace '$self' in $expected with actual $self
        array_walk($expected, function (&$value) use ($self) {
            $value[2][0] = $self;
        });
        $routes = DefaultActions::getRoutes($self);
        $this->assertEquals($expected, $routes);
    }

    public function testGetRoutesWithStatic(): void
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

    public function testAddRoutes(): void
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

    public function testHello(): void
    {
        $expected = 'Hello, world!';
        $app = TestHelper::getAppWithContainer();
        $self = new DefaultActions($app->getContainer());
        $self->initialize(null, null);
        $callable = $self->hello(...);
        $args = [];
        $result = $callable(...$args);
        $this->assertEquals(\Nyholm\Psr7\Response::class, $result::class);
        $this->assertEquals($expected, (string) $result->getBody());
    }

    public function testHelloWithName(): void
    {
        $expected = 'Hello, name!';
        $app = TestHelper::getAppWithContainer();
        $self = new DefaultActions($app->getContainer());
        $self->initialize(null, null);
        $callable = $self->hello(...);
        $args = ['name'];
        $result = $callable(...$args);
        $this->assertEquals(\Nyholm\Psr7\Response::class, $result::class);
        $this->assertEquals($expected, (string) $result->getBody());
    }

    public function testHelloViaAppRequest(): void
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
