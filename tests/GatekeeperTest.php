<?php

use BicBucStriim\Middleware\GatekeeperMiddleware;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DefaultActions::class)]
#[CoversClass(\BicBucStriim\Traits\AppTrait::class)]
#[CoversClass(RequestUtil::class)]
#[CoversClass(TestHelper::class)]
#[CoversClass(GatekeeperMiddleware::class)]
class GatekeeperTest extends PHPUnit\Framework\TestCase
{
    public function testGatekeeper(): void
    {
        $expected = 'You don&#039;t have sufficient access rights.';
        $app = TestHelper::getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');
        $handler = TestHelper::getHandler($app);

        $gatekeeper = new GatekeeperMiddleware($app->getContainer());
        $response = $gatekeeper->process($request, $handler);
        $this->assertEquals(\Nyholm\Psr7\Response::class, $response::class);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    public function testGatekeeperAuth(): void
    {
        $expected = 'Expected!';
        $app = TestHelper::getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');
        $userData = [
            'role' => 1,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        $request = $request->withAttribute('auth', $auth);
        $handler = TestHelper::getHandler($app, $expected);

        $gatekeeper = new GatekeeperMiddleware($app->getContainer());
        $response = $gatekeeper->process($request, $handler);
        $this->assertEquals(\Nyholm\Psr7\Response::class, $response::class);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }
}
