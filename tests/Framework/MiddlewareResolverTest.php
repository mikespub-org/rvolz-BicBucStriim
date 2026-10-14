<?php

use BicBucStriim\Framework\MiddlewareResolver;
use BicBucStriim\Framework\RoleGuard;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tests for the shared, framework-agnostic middleware resolver.
 *
 * The resolver turns a route's abstract middleware entries (class-strings or
 * objects) into concrete PSR-15 instances. It supports optional container-based
 * dependency injection, a RoleGuard special case (needs a PSR-17 factory), and a
 * no-argument-constructor fallback, and defensively skips anything that is not
 * valid PSR-15 middleware.
 */
#[CoversClass(MiddlewareResolver::class)]
class MiddlewareResolverTest extends PHPUnit\Framework\TestCase
{
    /** A middleware with a no-argument constructor. */
    private function makeNoArgMiddlewareClass(): string
    {
        return new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        }::class;
    }

    /** Minimal PSR-11 container stub (mirrors ContainerAdapterTest). */
    private function makePsr11Container(array $entries = []): Psr\Container\ContainerInterface
    {
        return new class ($entries) implements Psr\Container\ContainerInterface {
            private array $entries;

            public function __construct(array $entries)
            {
                $this->entries = $entries;
            }

            public function get(string $id): mixed
            {
                if (!array_key_exists($id, $this->entries)) {
                    throw new class extends \RuntimeException implements Psr\Container\NotFoundExceptionInterface {};
                }
                return $this->entries[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->entries);
            }
        };
    }

    public function testObjectMiddlewarePassesThrough(): void
    {
        $instance = new RoleGuard(new Psr17Factory());
        $resolver = new MiddlewareResolver();

        $resolved = $resolver->resolve([$instance]);

        $this->assertCount(1, $resolved);
        $this->assertSame($instance, $resolved[0]);
    }

    public function testRoleGuardClassStringUsesResponseFactory(): void
    {
        $resolver = new MiddlewareResolver(null, new Psr17Factory());

        $resolved = $resolver->resolve([RoleGuard::class]);

        $this->assertCount(1, $resolved);
        $this->assertInstanceOf(RoleGuard::class, $resolved[0]);
    }

    public function testClassStringFallsBackToNoArgConstructor(): void
    {
        $class = $this->makeNoArgMiddlewareClass();
        $resolver = new MiddlewareResolver();

        $resolved = $resolver->resolve([$class]);

        $this->assertCount(1, $resolved);
        $this->assertInstanceOf($class, $resolved[0]);
    }

    public function testClassStringResolvesViaContainerWhenAvailable(): void
    {
        // A container-provided instance must win over the no-arg fallback, even
        // for RoleGuard: this proves the optional DI path is preferred.
        $provided = new RoleGuard(new Psr17Factory());
        $container = $this->makePsr11Container([RoleGuard::class => $provided]);
        $resolver = new MiddlewareResolver($container);

        $resolved = $resolver->resolve([RoleGuard::class]);

        $this->assertCount(1, $resolved);
        $this->assertSame($provided, $resolved[0], 'container-provided instance should win');
    }

    public function testNonMiddlewareObjectIsSkipped(): void
    {
        $resolver = new MiddlewareResolver();

        $resolved = $resolver->resolve([new \stdClass()]);

        $this->assertSame([], $resolved);
    }

    public function testUnknownClassStringIsSkipped(): void
    {
        $resolver = new MiddlewareResolver();

        $resolved = $resolver->resolve(['BicBucStriim\\Does\\Not\\Exist']);

        $this->assertSame([], $resolved);
    }

    public function testMixedEntriesResolveAndSkip(): void
    {
        $guard = new RoleGuard(new Psr17Factory());
        $noArg = $this->makeNoArgMiddlewareClass();
        $resolver = new MiddlewareResolver();

        $resolved = $resolver->resolve([$guard, $noArg, new \stdClass()]);

        $this->assertCount(2, $resolved);
        $this->assertSame($guard, $resolved[0]);
        $this->assertInstanceOf($noArg, $resolved[1]);
    }

    public function testEmptyListResolvesToEmptyArray(): void
    {
        $resolver = new MiddlewareResolver();
        $this->assertSame([], $resolver->resolve([]));
    }
}
