<?php

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\AdminActions;
use BicBucStriim\Actions\ApiActions;
use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Framework\ContainerAdapter;
use BicBucStriim\Utilities\RouteEntry;
use BicBucStriim\Utilities\RouteUtil;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the framework-agnostic route registry consumed by the Symfony,
 * Laravel and custom-App adapters (and mirrored by the Slim RouteUtil path).
 *
 * Covers register()/dedup, prefix handling (explicit vs class PREFIX fallback),
 * route-map building (getRoutes + un-prefixed getExtraRoutes), the 'gated'
 * policy flag, the "skip classes without getRoutes()" branch, caching, and
 * getInstance() container wrapping.
 */
#[CoversClass(ActionRegistry::class)]
#[CoversClass(DefaultActions::class)]
#[CoversClass(AdminActions::class)]
#[CoversClass(ApiActions::class)]
#[CoversClass(ContainerAdapter::class)]
#[CoversClass(RouteUtil::class)]
class ActionRegistryTest extends PHPUnit\Framework\TestCase
{
    public function testRegisterUsesExplicitPrefix(): void
    {
        $registry = new ActionRegistry();
        $registry->register(DefaultActions::class, '/api');
        $this->assertSame([DefaultActions::class], $registry->getActionClasses());

        $map = $registry->getRouteMap();
        $this->assertSame('/api/', $map['hello']->path);
        $this->assertSame('/api/{name}', $map['hello-name']->path);
    }

    public function testRegisterFallsBackToClassPrefixConstant(): void
    {
        $registry = new ActionRegistry();
        // No explicit prefix -> AdminActions::PREFIX ('/admin') is used.
        $registry->register(AdminActions::class);

        $map = $registry->getRouteMap();
        $this->assertSame('/admin/', $map['admin-home']->path);
    }

    public function testRegisterWithNullPrefixClassKeepsRootPaths(): void
    {
        $registry = new ActionRegistry();
        // DefaultActions::PREFIX is null and no explicit prefix given -> root.
        $registry->register(DefaultActions::class);

        $map = $registry->getRouteMap();
        $this->assertSame('/', $map['hello']->path);
        $this->assertSame('/{name}', $map['hello-name']->path);
    }

    public function testRegisterDeduplicates(): void
    {
        $registry = new ActionRegistry();
        $registry->register(DefaultActions::class, '/api');
        // Registering the same class again (different prefix) is ignored.
        $registry->register(DefaultActions::class, '/other');

        $this->assertSame([DefaultActions::class], $registry->getActionClasses());
        // The first prefix ('/api') sticks.
        $this->assertSame('/api/', $registry->getRouteMap()['hello']->path);
    }

    public function testRouteMapEntryShape(): void
    {
        $registry = new ActionRegistry();
        $registry->register(DefaultActions::class, '/api');

        $entry = $registry->getRouteMap()['hello'];
        $this->assertInstanceOf(RouteEntry::class, $entry);
        $this->assertSame('hello', $entry->name);
        $this->assertSame('GET', $entry->methods);
        $this->assertSame('/api/', $entry->path);
        $this->assertSame([DefaultActions::class, 'hello'], $entry->callable);
        $this->assertSame([], $entry->middleware);
        $this->assertFalse($entry->gated);
    }

    public function testGatedFlagTrueForAdminClass(): void
    {
        $registry = new ActionRegistry();
        $registry->register(AdminActions::class, '/admin');

        $map = $registry->getRouteMap();
        // AdminActions::ROLES contains 'admin' -> group gatekeeper applies.
        $this->assertTrue($map['admin-home']->gated);
        $this->assertTrue($map['admin-users']->gated);
    }

    public function testGatedFlagFalseForPublicClass(): void
    {
        $registry = new ActionRegistry();
        $registry->register(DefaultActions::class, '/api');

        $map = $registry->getRouteMap();
        $this->assertFalse($map['hello']->gated);
    }


    public function testClassesWithoutGetRoutesAreSkipped(): void
    {
        $registry = new ActionRegistry();
        // stdClass has neither PREFIX nor getRoutes() -> skipped, empty map.
        $registry->register(\stdClass::class);
        $this->assertSame([\stdClass::class], $registry->getActionClasses());
        $this->assertSame([], $registry->getRouteMap());
    }

    public function testRouteMapIsCached(): void
    {
        $registry = new ActionRegistry();
        $registry->register(DefaultActions::class, '/api');

        // Two calls return equal maps (loaded once, cached internally).
        $this->assertEquals($registry->getRouteMap(), $registry->getRouteMap());
    }

    public function testEmptyRegistryHasEmptyRouteMap(): void
    {
        $registry = new ActionRegistry();
        $this->assertSame([], $registry->getRouteMap());
    }

    public function testGetInstanceReturnsActionWrappedWithAdapter(): void
    {
        // Use the real test container so the adapter wraps a PSR-11 container.
        $container = require dirname(__DIR__, 2) . '/config/container.php';
        $registry = new ActionRegistry($container);
        $registry->register(DefaultActions::class, '/api');

        $instance = $registry->getInstance(DefaultActions::class);
        $this->assertInstanceOf(DefaultActions::class, $instance);
        // The action's container must be our ContainerAdapter (provides set()).
        $reflection = new \ReflectionProperty(DefaultActions::class, 'container');
        $this->assertInstanceOf(ContainerAdapter::class, $reflection->getValue($instance));
    }

    public function testGetInstanceIsMemoized(): void
    {
        $container = require dirname(__DIR__, 2) . '/config/container.php';
        $registry = new ActionRegistry($container);
        $registry->register(DefaultActions::class, '/api');

        $this->assertSame($registry->getInstance(DefaultActions::class), $registry->getInstance(DefaultActions::class));
    }

    public function testExtraRoutesAreNotPrefixed(): void
    {
        $registry = new ActionRegistry();
        // ApiActions mounts under /api but its CORS preflight extra route must
        // stay at the application root (un-prefixed).
        $registry->register(ApiActions::class, '/api');

        $map = $registry->getRouteMap();
        // Regular routes are prefixed.
        $this->assertSame('/api/', $map['api-home']->path);
        $this->assertSame('/api/routes', $map['api-routes']->path);
        // Extra route is un-prefixed and never gated.
        $this->assertArrayHasKey('cors-options', $map);
        $this->assertSame('/{routes:.*}', $map['cors-options']->path);
        $this->assertSame('OPTIONS', $map['cors-options']->methods);
        $this->assertFalse($map['cors-options']->gated);
    }
}
