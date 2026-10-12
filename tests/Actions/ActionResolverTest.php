<?php

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\ActionResolver;
use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the framework-agnostic action resolver: it fetches the singleton
 * action instance from the registry, initializes it with the request/response,
 * and dispatches the requested method with the route arguments.
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
        $response = $resolver->resolve(DefaultActions::class, 'helloResponse', $request, null, ['Bob']);

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
        $response = $resolver->resolve(DefaultActions::class, 'helloResponse', $request);

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
        $response = $resolver->resolve(DefaultActions::class, 'helloResponse', $request);
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
    }
}
