<?php

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Middleware\GatekeeperMiddleware;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;

/**
 * @covers \BicBucStriim\Actions\DefaultActions
 * @covers \BicBucStriim\Traits\AppTrait
 * @covers \BicBucStriim\Utilities\RequestUtil
 * @covers \BicBucStriim\Utilities\TestHelper
 * @covers \BicBucStriim\Middleware\GatekeeperMiddleware
 */
class GatekeeperTest extends PHPUnit\Framework\TestCase
{
    /**
     * @deprecated 3.4.0 replaced by using GatekeeperMiddleware instead
     */
    public function testCheckAdmin()
    {
        $expected = true;
        $app = TestHelper::getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');

        $self = new DefaultActions($app->getContainer());
        $callable = [$self, 'check_admin'];
        $result = $callable($request);
        $this->assertEquals($expected, $result);

        $expected = 'You don&#039;t have sufficient access rights.';
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($self->response()));
        $this->assertStringContainsString($expected, (string) $self->response()->getBody());
    }

    /**
     * @deprecated 3.4.0 replaced by using GatekeeperMiddleware instead
     */
    public function testCheckAdminAuth()
    {
        $expected = false;
        $app = TestHelper::getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');
        $userData = [
            'role' => 1,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        $request = $request->withAttribute('auth', $auth);

        $self = new DefaultActions($app->getContainer());
        $callable = [$self, 'check_admin'];
        $result = $callable($request);
        $this->assertEquals($expected, $result);
    }

    public function testGatekeeper()
    {
        $expected = 'You don&#039;t have sufficient access rights.';
        $app = TestHelper::getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');
        $handler = TestHelper::getHandler($app);

        $gatekeeper = new GatekeeperMiddleware($app->getContainer());
        $response = $gatekeeper->process($request, $handler);
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($response));
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    public function testGatekeeperAuth()
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
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($response));
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }
}
