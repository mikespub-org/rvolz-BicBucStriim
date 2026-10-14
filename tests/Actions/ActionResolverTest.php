<?php

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\ActionResolver;
use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;

/**
 * Tests for the framework-agnostic action resolver: it accepts a route callable
 * ([ActionClass::class, 'methodName'] array OR a Closure). The array form is
 * resolved through the registry: the singleton action instance is fetched and
 * initialize()d with the request/response, then the named method is called with
 * the route arguments. A Closure is invoked directly (no action instance, so no
 * initialize()).
 */
#[CoversClass(ActionResolver::class)]
#[CoversClass(ActionRegistry::class)]
#[CoversClass(DefaultActions::class)]
class ActionResolverTest extends PHPUnit\Framework\TestCase
{
    public function testResolveDispatchesMethodWithArgs(): void
    {
        $app = TestHelper::getAppWithContainer();
        $registry = new ActionRegistry($app->getContainer());
        $resolver = new ActionResolver($registry);

        $request = RequestUtil::getServerRequest('GET', '/');
        // DefaultActions::helloResponse($name) -> 'Hello, <name>!'
        $response = $resolver->resolve([DefaultActions::class, 'helloResponse'], $request, null, ['Bob']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Hello, Bob!', (string) $response->getBody());
    }

    public function testResolveDefaultsArgsWhenEmpty(): void
    {
        $app = TestHelper::getAppWithContainer();
        $registry = new ActionRegistry($app->getContainer());
        $resolver = new ActionResolver($registry);

        $request = RequestUtil::getServerRequest('GET', '/');
        // No args -> helloResponse() defaults to 'world'.
        $response = $resolver->resolve([DefaultActions::class, 'helloResponse'], $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Hello, world!', (string) $response->getBody());
    }

    public function testResolveReturnsMethodReturnValue(): void
    {
        $app = TestHelper::getAppWithContainer();
        $registry = new ActionRegistry($app->getContainer());
        $resolver = new ActionResolver($registry);

        $request = RequestUtil::getServerRequest('GET', '/');
        // helloResponse returns a PSR-7 response object.
        $response = $resolver->resolve([DefaultActions::class, 'helloResponse'], $request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testResolveInvokesClosureWithRequestResponseAndArgs(): void
    {
        $app = TestHelper::getAppWithContainer();
        $registry = new ActionRegistry($app->getContainer());
        $resolver = new ActionResolver($registry);

        $request = RequestUtil::getServerRequest('GET', '/');
        $response = new Psr17Factory()->createResponse();
        $captured = [];
        $closure = function ($req, $res, ...$args) use (&$captured) {
            $captured = [$req, $res, $args];
            $out = new Psr17Factory()->createResponse();
            $out->getBody()->write('CLOSURE-RAN');
            return $out;
        };

        $result = $resolver->resolve($closure, $request, $response, ['a', 'b']);

        // The closure receives the request, response and positional args as-is.
        $this->assertSame($request, $captured[0]);
        $this->assertSame($response, $captured[1]);
        $this->assertSame(['a', 'b'], $captured[2]);
        $this->assertSame('CLOSURE-RAN', (string) $result->getBody());
    }

    public function testResolveClosureIsNotInitializedAsAction(): void
    {
        $app = TestHelper::getAppWithContainer();
        $registry = new ActionRegistry($app->getContainer());
        $resolver = new ActionResolver($registry);

        $request = RequestUtil::getServerRequest('GET', '/');
        // A closure must be invoked directly with no registry/action involvement.
        $closure = fn() => 'plain-string-result';

        $this->assertSame('plain-string-result', $resolver->resolve($closure, $request));
    }
}
