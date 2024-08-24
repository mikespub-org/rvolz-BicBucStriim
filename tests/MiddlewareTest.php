<?php

use BicBucStriim\AppData\Settings;
use BicBucStriim\Middleware\LoginMiddleware;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;

/**
 * @todo test with/without login required + caching
 * @covers \BicBucStriim\Middleware\BasePathDetector
 * @covers \BicBucStriim\Middleware\BasePathMiddleware
 * @covers \BicBucStriim\Middleware\CachingMiddleware
 * @covers \BicBucStriim\Middleware\CalibreConfigMiddleware
 * @covers \BicBucStriim\Middleware\DefaultMiddleware
 * @covers \BicBucStriim\Middleware\LoginMiddleware
 * @covers \BicBucStriim\Middleware\OwnConfigMiddleware
 * @covers \BicBucStriim\Session\SessionFactory
 * @covers \BicBucStriim\Session\Session
 * @covers \BicBucStriim\Traits\AppTrait
 */
class MiddlewareTest extends PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        ini_set('session.gc_maxlifetime', 3600);
    }

    public static function getExpectedRoutes()
    {
        return [
            // 'name' => [
            //    input, output (= text, size or header(s)),
            //    method(s), path, ...middleware(s), callable with '$self' string
            //],
            // for html output specify the expected content text
            'show_login' => [
                [], '<title>BicBucStriim :: Login</title>',
                'GET', '/login/', ['$self', 'show_login'],
            ],
            'perform_login' => [
                [], 'hello',
                'POST', '/login/', ['$self', 'perform_login'],
            ],
            'logout' => [
                [], '<title>BicBucStriim :: Logout</title>',
                'GET', '/logout/', ['$self', 'logout'],
            ],
            'title' => [
                ['id' => 7], '<title>BicBucStriim :: Book Details</title>',
                'GET', '/titles/{id}/', ['$self', 'title'],
            ],
            // for file output specify the expected content size
            'cover' => [
                ['id' => 7], 168310,
                'GET', '/titles/{id}/cover/', ['$self', 'cover'],
            ],
            'book' => [
                ['id' => 7, 'file' => 'The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin.epub'], 10198,
                'GET', '/titles/{id}/file/{file}', ['$self', 'book'],
            ],
            'kindle' => [
                ['id' => 7, 'file' => 'The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin.epub'], 'hello',
                'POST', '/titles/{id}/kindle/{file}', ['$self', 'kindle'],
            ],
            'thumbnail' => [
                ['id' => 7], 51232,
                'GET', '/titles/{id}/thumbnail/', ['$self', 'thumbnail'],
            ],
            // temporary routes for the tailwind templates (= based on the v2.x frontend)
            'thumbnail2' => [
                ['id' => 7], 51232,
                'GET', '/static/titlethumbs/{id}/', ['$self', 'thumbnail'],
            ],
            // for redirect etc. specify the expected header(s)
            'admin' => [
                [], ['Location' => './login/'],
                'GET', '/admin/', ['$self', 'admin'],
            ],
            'opds' => [
                [], '<title>BicBucStriim Root Catalog</title>',
                'GET', '/opds/', ['$self', 'opdsRoot'],
            ],
        ];
    }

    /**
     * @runInSeparateProcess
     */
    public function testAppMainRequest()
    {
        $app = TestHelper::getApp();
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = '<title>BicBucStriim :: Most recent</title>';
        $request = RequestUtil::getServerRequest('GET', '/');
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    /**
     * @dataProvider getExpectedRoutes
     * @runInSeparateProcess
     * @depends testAppMainRequest
     */
    public function testAppGetRequest($input, $output, $methods, $pattern, ...$args)
    {
        $this->assertGreaterThan(0, count($args));
        if (is_string($methods) && $methods == 'GET') {
            $app = TestHelper::getApp();

            foreach ($input as $name => $value) {
                $pattern = str_replace('{' . $name . '}', (string) $value, $pattern);
            }
            $expected = $output;
            $request = RequestUtil::getServerRequest('GET', $pattern);
            $response = $app->handle($request);
            if (is_string($expected)) {
                $this->assertStringContainsString($expected, (string) $response->getBody());
            } elseif (is_numeric($expected)) {
                $this->assertEquals($expected, $response->getHeaderLine('Content-Length'));
                $this->assertEquals($expected, strlen((string) $response->getBody()));
            } elseif (is_array($expected)) {
                foreach ($expected as $header => $line) {
                    $this->assertEquals($line, $response->getHeaderLine($header));
                }
            }
        }
    }

    /**
     * @dataProvider getExpectedRoutes
     */
    public function testMustLoginNoAuth($input, $output, $methods, $pattern, ...$args)
    {
        $this->assertGreaterThan(0, count($args));
        if (is_string($methods) && $methods == 'GET') {
            $app = TestHelper::getAppWithContainer();
            $settings = $app->getContainer()->get(Settings::class);
            $settings->must_login = 1;
            $app->getContainer()->set(Settings::class, $settings);

            foreach ($input as $name => $value) {
                $pattern = str_replace('{' . $name . '}', (string) $value, $pattern);
            }
            $request = RequestUtil::getServerRequest('GET', $pattern);
            // Expect the handler to process if authorized
            if (is_string($output)) {
                $expected = $output;
            } elseif (is_numeric($output)) {
                $expected = (string) $output;
            } elseif (is_array($output)) {
                $expected = json_encode($output);
            }
            $handler = TestHelper::getHandler($app, $expected);

            // Expect to be redirected here, except for login and static resources
            $expected = ['Location' => '/usr/local/bin/login/'];
            $noRedirect = ['/login/', '/cover/', '/thumbnail/'];
            foreach ($noRedirect as $skip) {
                if (str_contains($pattern, $skip)) {
                    $expected = (string) $output;
                    break;
                }
            }
            // Expect to get authentication error for /opds
            if (str_contains($pattern, '/opds')) {
                $expected = ['WWW-Authenticate' => 'Basic realm="BicBucStriim"'];
            }

            $middleware = new LoginMiddleware($app->getContainer(), $settings['appname'], []);
            $response = $middleware->process($request, $handler);
            $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($response));
            if (is_string($expected)) {
                $this->assertStringContainsString($expected, (string) $response->getBody());
            } elseif (is_array($expected)) {
                foreach ($expected as $header => $line) {
                    $this->assertEquals($line, $response->getHeaderLine($header));
                }
            }
        }
    }

    /**
     * @dataProvider getExpectedRoutes
     */
    public function testMustLoginWithAuth($input, $output, $methods, $pattern, ...$args)
    {
        $this->assertGreaterThan(0, count($args));
        if (is_string($methods) && $methods == 'GET') {
            $app = TestHelper::getAppWithContainer();
            $settings = $app->getContainer()->get(Settings::class);
            $settings->must_login = 1;
            $app->getContainer()->set(Settings::class, $settings);

            foreach ($input as $name => $value) {
                $pattern = str_replace('{' . $name . '}', (string) $value, $pattern);
            }
            $request = RequestUtil::getServerRequest('GET', $pattern);
            // Expect the handler to process when authorized
            if (is_string($output)) {
                $expected = $output;
            } elseif (is_numeric($output)) {
                $expected = (string) $output;
            } elseif (is_array($output)) {
                $expected = json_encode($output);
            }
            $handler = TestHelper::getHandler($app, $expected);

            // Build mock LoginMiddleware with is_authorized() == true
            $middleware = $this->getMockBuilder(LoginMiddleware::class)
                ->setConstructorArgs([$app->getContainer(), $settings['appname'], []])
                ->onlyMethods(['is_authorized'])
                ->getMock();
            // Not expects($this->once()) because this will not be called for static resources
            $middleware->expects($this->any())->method('is_authorized')->willReturn(true);

            $response = $middleware->process($request, $handler);
            $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($response));
            $this->assertStringContainsString($expected, (string) $response->getBody());
        }
    }

    public function testIsAuthorizedWithAuthValid()
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->must_login = 1;
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/admin/');
        // Make sure userData is considered "valid"
        $userData = [
            'id' => 1,
            'role' => 1,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        // Set resume service for session
        $app->getContainer()->set('resume_service', TestHelper::getAuthFactory($request)->newResumeService());

        // Build mock LoginMiddleware with makeAuthTracker == $auth
        $middleware = $this->getMockBuilder(LoginMiddleware::class)
            ->setConstructorArgs([$app->getContainer(), $settings['appname'], []])
            ->onlyMethods(['makeAuthTracker'])
            ->getMock();
        $middleware->expects($this->once())->method('makeAuthTracker')->willReturn($auth);

        $expected = 'Expected!';
        $handler = TestHelper::getHandler($app, $expected);

        $response = $middleware->process($request, $handler);
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($response));
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    public function testIsAuthorizedWithAuthInvalid()
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->must_login = 1;
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/admin/');
        // Make sure userData is considered "invalid" = missing id and role
        $userData = [
            'invalid' => true,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        // Set resume service for session
        $app->getContainer()->set('resume_service', TestHelper::getAuthFactory($request)->newResumeService());

        // Build mock LoginMiddleware with makeAuthTracker == $auth
        $middleware = $this->getMockBuilder(LoginMiddleware::class)
            ->setConstructorArgs([$app->getContainer(), $settings['appname'], []])
            ->onlyMethods(['makeAuthTracker'])
            ->getMock();
        $middleware->expects($this->once())->method('makeAuthTracker')->willReturn($auth);

        $expected = 'Handled!';
        $handler = TestHelper::getHandler($app, $expected);

        // Expect to be redirected here
        $expected = ['Location' => '/usr/local/bin/login/'];
        $response = $middleware->process($request, $handler);
        $this->assertEquals(\Nyholm\Psr7\Response::class, get_class($response));
        foreach ($expected as $header => $line) {
            $this->assertEquals($line, $response->getHeaderLine($header));
        }
    }

    public function testIsAuthorizedWithPhpAuthTrue()
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->must_login = 1;
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/admin/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');
        $middleware = new LoginMiddleware($app->getContainer(), $settings['appname'], []);
        $middleware->request($request);

        // test protected method using closure bind & call or use reflection
        $isAuthorized = function ($request) {
            /** @var LoginMiddleware $this */
            return $this->is_authorized($request);
        };
        $result = $isAuthorized->call($middleware, $request);
        $this->assertTrue($result);
        unset($_SESSION[\Aura\Auth\Auth::class]);
    }

    public function testIsAuthorizedWithPhpAuthFalse()
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->must_login = 1;
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/admin/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'wrong');
        $middleware = new LoginMiddleware($app->getContainer(), $settings['appname'], []);
        $middleware->request($request);

        // test protected method using closure bind & call or use reflection
        $isAuthorized = function ($request) {
            /** @var LoginMiddleware $this */
            return $this->is_authorized($request);
        };
        $result = $isAuthorized->call($middleware, $request);
        $this->assertFalse($result);
        unset($_SESSION[\Aura\Auth\Auth::class]);
    }

    public function testIsAuthorizedWithHttpAuthTrue()
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->must_login = 1;
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/admin/');
        $request = $request->withHeader('Authorization', 'Basic ' . base64_encode('admin:admin'));
        $middleware = new LoginMiddleware($app->getContainer(), $settings['appname'], []);
        $middleware->request($request);

        // test protected method using closure bind & call or use reflection
        $isAuthorized = function ($request) {
            /** @var LoginMiddleware $this */
            return $this->is_authorized($request);
        };
        $result = $isAuthorized->call($middleware, $request);
        $this->assertTrue($result);
        unset($_SESSION[\Aura\Auth\Auth::class]);
    }

    public function testIsAuthorizedWithHttpAuthFalse()
    {
        $app = TestHelper::getAppWithContainer();
        $settings = $app->getContainer()->get(Settings::class);
        $settings->must_login = 1;
        $app->getContainer()->set(Settings::class, $settings);

        $request = RequestUtil::getServerRequest('GET', '/admin/');
        $request = $request->withHeader('Authorization', 'Basic ' . base64_encode('admin:wrong'));
        $middleware = new LoginMiddleware($app->getContainer(), $settings['appname'], []);
        $middleware->request($request);

        // test protected method using closure bind & call or use reflection
        $isAuthorized = function ($request) {
            /** @var LoginMiddleware $this */
            return $this->is_authorized($request);
        };
        $result = $isAuthorized->call($middleware, $request);
        $this->assertFalse($result);
        unset($_SESSION[\Aura\Auth\Auth::class]);
    }
}
