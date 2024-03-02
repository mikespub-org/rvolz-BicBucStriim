<?php

use BicBucStriim\Utilities\RequestUtil;

/**
 * @todo test with/without login required + caching
 * @covers \BicBucStriim\Middleware\BasePathDetector
 * @covers \BicBucStriim\Middleware\BasePathMiddleware
 * @covers \BicBucStriim\Middleware\CachingMiddleware
 * @covers \BicBucStriim\Middleware\CalibreConfigMiddleware
 * @covers \BicBucStriim\Middleware\LoginMiddleware
 * @covers \BicBucStriim\Middleware\OwnConfigMiddleware
 */
class MiddlewareTest extends PHPUnit\Framework\TestCase
{
    public static function getExpectedRoutes()
    {
        return [
            // 'name' => [
            //    input, output (= text or size),
            //    method(s), path, ...middleware(s), callable with '$self' string
            //],
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
        ];
    }

    public function getApp($login = 0)
    {
        global $langen;
        require('config/langs.php');
        $app = require(dirname(__DIR__) . '/config/bootstrap.php');
        $globalSettings = $app->getContainer()->get('globalSettings');
        $globalSettings[LOGIN_REQUIRED] = $login;
        $app->getContainer()->set('globalSettings', $globalSettings);
        return $app;
    }

    /**
     * @runInSeparateProcess
     */
    public function testAppMainRequest()
    {
        $app = $this->getApp();
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
            $app = $this->getApp();

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
            }
        }
    }
}
