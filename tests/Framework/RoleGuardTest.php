<?php

use BicBucStriim\Framework\RoleGuard;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use Middlewares\Utils\CallableHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the framework-agnostic admin access guard used by the custom App.
 *
 * RoleGuard fails closed: without a valid admin identity on the 'auth' request
 * attribute it denies everyone with a 403.
 */
#[CoversClass(RoleGuard::class)]
#[CoversClass(RequestUtil::class)]
class RoleGuardTest extends PHPUnit\Framework\TestCase
{
    public function testDeniesWithoutAuthAttribute(): void
    {
        $guard = new RoleGuard(new Psr17Factory());
        $request = RequestUtil::getServerRequest('GET', '/admin/');
        $handler = new CallableHandler(function ($request) {
            throw new RuntimeException('handler must not be reached');
        });

        $response = $guard->process($request, $handler);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('sufficient access rights', (string) $response->getBody());
    }

    public function testDeniesNonAdminUser(): void
    {
        $guard = new RoleGuard(new Psr17Factory());
        $request = RequestUtil::getServerRequest('GET', '/admin/');
        // role 0 = regular (non-admin) user with a valid identity
        $auth = TestHelper::getAuth($request, ['role' => 0]);
        $request = $request->withAttribute('auth', $auth);
        $handler = new CallableHandler(function ($request) {
            throw new RuntimeException('handler must not be reached');
        });

        $response = $guard->process($request, $handler);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAllowsAdminUser(): void
    {
        $guard = new RoleGuard(new Psr17Factory());
        $request = RequestUtil::getServerRequest('GET', '/admin/');
        // role 1 = admin
        $auth = TestHelper::getAuth($request, ['role' => 1]);
        $request = $request->withAttribute('auth', $auth);

        $handler = new CallableHandler(function ($request) {
            $response = new Psr17Factory()->createResponse();
            $response->getBody()->write('allowed');
            return $response;
        });

        $response = $guard->process($request, $handler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('allowed', (string) $response->getBody());
    }

    public function testHasGuardReturnsFalseForEmptyMiddleware(): void
    {
        $this->assertFalse(RoleGuard::hasGuard([]));
    }

    public function testHasGuardDetectsRoleGuardClassString(): void
    {
        $this->assertTrue(RoleGuard::hasGuard([RoleGuard::class]));
    }

    public function testHasGuardDetectsRoleGuardInstance(): void
    {
        $this->assertTrue(RoleGuard::hasGuard([new RoleGuard(new Psr17Factory())]));
    }

    public function testHasGuardDetectsRoleGuardSubclass(): void
    {
        $factory = new Psr17Factory();
        $subclass = new class ($factory) extends RoleGuard {};
        $this->assertTrue(RoleGuard::hasGuard([$subclass::class]));
    }

    public function testHasGuardIgnoresUnrelatedMiddleware(): void
    {
        $this->assertFalse(RoleGuard::hasGuard(['OtherMiddleware', new stdClass()]));
    }
}
