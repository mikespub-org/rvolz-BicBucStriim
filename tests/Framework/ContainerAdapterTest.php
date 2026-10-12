<?php

use BicBucStriim\Framework\ContainerAdapter;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the PSR-11 container wrapper used by the framework-agnostic core.
 *
 * The adapter adds a non-standard set() method (for testability and dynamic
 * service replacement) on top of any PSR-11 container, plus a PHP-DI specific
 * factory-resolution behaviour (Closure definitions are invoked).
 */
#[CoversClass(ContainerAdapter::class)]
class ContainerAdapterTest extends PHPUnit\Framework\TestCase
{
    /**
     * Minimal PSR-11 container implementation that does NOT expose set().
     */
    private function makePsr11Container(array $entries = []): Psr\Container\ContainerInterface
    {
        return new class ($entries) implements Psr\Container\ContainerInterface {
            /** @var array<string, mixed> */
            private array $entries;

            public function __construct(array $entries)
            {
                $this->entries = $entries;
            }

            public function get(string $id): mixed
            {
                if (!array_key_exists($id, $this->entries)) {
                    // psr/container v2 ships no concrete NotFoundException, so provide
                    // a minimal implementation of the interface.
                    throw new class extends \RuntimeException implements \Psr\Container\NotFoundExceptionInterface {};
                }
                return $this->entries[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->entries);
            }
        };
    }

    public function testGetDelegatesToWrappedContainer(): void
    {
        $adapter = new ContainerAdapter($this->makePsr11Container(['foo' => 'bar']));
        $this->assertSame('bar', $adapter->get('foo'));
    }

    public function testHasDelegatesToWrappedContainer(): void
    {
        $adapter = new ContainerAdapter($this->makePsr11Container(['foo' => 'bar']));
        $this->assertTrue($adapter->has('foo'));
        $this->assertFalse($adapter->has('missing'));
    }

    public function testSetOverridesAndAddsEntries(): void
    {
        $adapter = new ContainerAdapter($this->makePsr11Container(['foo' => 'bar']));
        $adapter->set('foo', 'overridden');
        $adapter->set('baz', 'quux');
        $this->assertSame('overridden', $adapter->get('foo'));
        $this->assertSame('quux', $adapter->get('baz'));
        $this->assertTrue($adapter->has('baz'));
    }

    public function testSetReturnsVoid(): void
    {
        // set() is intentionally non-chainable: it returns void, so we just call
        // it and confirm the value was applied via get().
        $adapter = new ContainerAdapter($this->makePsr11Container());
        $adapter->set('foo', 'bar');
        $this->assertSame('bar', $adapter->get('foo'));
    }

    public function testSetTakesPrecedenceOverWrappedContainer(): void
    {
        $adapter = new ContainerAdapter($this->makePsr11Container(['foo' => 'original']));
        $adapter->set('foo', 'override');
        // Even though the wrapped container still holds 'original', the local
        // override must win.
        $this->assertSame('override', $adapter->get('foo'));
    }

    public function testGetFallsBackToWrappedContainerForUnknownIds(): void
    {
        $adapter = new ContainerAdapter($this->makePsr11Container(['foo' => 'bar']));
        $adapter->set('baz', 'quux');
        $this->assertSame('bar', $adapter->get('foo'));
    }

    public function testSetClosureIsResolvedViaContainerCallAndMemoized(): void
    {
        // The Closure->call() factory resolution applies to values set at runtime
        // via set() (that's how AppTrait::setSettings and tests inject factories).
        // It's only triggered when the wrapped container exposes a call() method.
        $calls = 0;
        $inner = $this->makePsr11Container();
        $callable = new class ($inner) implements Psr\Container\ContainerInterface {
            public function __construct(private Psr\Container\ContainerInterface $container) {}

            public function call(callable $callable)
            {
                // Emulate PHP-DI: invoke the definition closure.
                return $callable();
            }

            public function get(string $id): mixed
            {
                return $this->container->get($id);
            }

            public function has(string $id): bool
            {
                return $this->container->has($id);
            }
        };

        $adapter = new ContainerAdapter($callable);
        $adapter->set('svc', function () use (&$calls) {
            $calls++;
            return 'resolved';
        });

        // First get() resolves the factory through call()...
        $first = $adapter->get('svc');
        // ...and memoizes it: a second get() returns the cached value without
        // invoking the factory again (proven by $calls staying at 1).
        $second = $adapter->get('svc');
        $this->assertSame('resolved', $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }

    public function testClosureInWrappedContainerIsReturnedAsIs(): void
    {
        // Closures coming from the *wrapped* container are NOT resolved by the
        // adapter (only runtime set() values are) -- this documents that boundary.
        $closure = function () {
            return 'resolved';
        };
        $adapter = new ContainerAdapter($this->makePsr11Container(['svc' => $closure]));
        $this->assertSame($closure, $adapter->get('svc'));
    }
}
