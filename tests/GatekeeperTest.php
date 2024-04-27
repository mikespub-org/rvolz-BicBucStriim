<?php

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Middleware\GatekeeperMiddleware;
use BicBucStriim\Utilities\RequestUtil;
use Middlewares\Utils\CallableHandler;
use Slim\Factory\AppFactory;

/**
 * @covers \BicBucStriim\Actions\DefaultActions
 * @covers \BicBucStriim\Traits\AppTrait
 * @covers \BicBucStriim\Utilities\RequestUtil
 * @covers \BicBucStriim\Middleware\GatekeeperMiddleware
 */
class GatekeeperTest extends PHPUnit\Framework\TestCase
{
    protected function getAppWithContainer()
    {
        $container = require dirname(__DIR__) . '/config/container.php';
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $settings = require dirname(__DIR__) . '/config/settings.php';
        // set by LoginMiddleware based on request in normal operation
        $settings['globalSettings']['l10n'] = new \BicBucStriim\Utilities\L10n('en');
        $app->getContainer()->set('globalSettings', $settings['globalSettings']);
        // skip middleware and routes here

        return $app;
    }

    protected function getAuth($request, $userData = null)
    {
        $auth_factory = new \Aura\Auth\AuthFactory($request->getCookieParams(), new \Aura\Auth\Session\NullSession, new \Aura\Auth\Session\NullSegment);
        $auth = $auth_factory->newInstance();
        if (!empty($userData)) {
            $auth->setStatus(\Aura\Auth\Status::VALID);
            $auth->setUserData($userData);
        }

        return $auth;
    }

    protected function getHandler($app, $content = 'Handled!')
    {
        $handler = new CallableHandler(function ($request) use ($app, $content) {
            $response = $app->getResponseFactory()->createResponse();
            $response->getBody()->write($content);
            return $response;
        }, $app->getResponseFactory());

        return $handler;
    }

    public function testCheckAdmin()
    {
        $expected = true;
        $app = $this->getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');

        $self = new DefaultActions($app);
        $self->request($request);
        $callable = [$self, 'check_admin'];
        $result = $callable();
        $this->assertEquals($expected, $result);

        $expected = 'You don&#039;t have sufficient access rights.';
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($self->response()));
        $this->assertStringContainsString($expected, (string) $self->response()->getBody());
    }

    public function testCheckAdminAuth()
    {
        $expected = false;
        $app = $this->getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');
        $userData = [
            'role' => 1,
        ];
        $auth = $this->getAuth($request, $userData);

        $self = new DefaultActions($app);
        $self->request($request);
        $self->auth($auth);
        $callable = [$self, 'check_admin'];
        $result = $callable();
        $this->assertEquals($expected, $result);
    }

    public function testGatekeeper()
    {
        $expected = 'You don&#039;t have sufficient access rights.';
        $app = $this->getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');
        $handler = $this->getHandler($app);

        $gatekeeper = new GatekeeperMiddleware($app);
        $response = $gatekeeper->process($request, $handler);
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($response));
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    public function testGatekeeperAuth()
    {
        $expected = 'Expected!';
        $app = $this->getAppWithContainer();
        $request = RequestUtil::getServerRequest('GET', '/');
        $userData = [
            'role' => 1,
        ];
        $auth = $this->getAuth($request, $userData);
        $request = $request->withAttribute('auth', $auth);
        $handler = $this->getHandler($app, $expected);

        $gatekeeper = new GatekeeperMiddleware($app);
        $response = $gatekeeper->process($request, $handler);
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($response));
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }
}
