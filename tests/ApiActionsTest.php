<?php

use BicBucStriim\Utilities\RequestUtil;
use Slim\Factory\AppFactory;

/**
 * @covers \BicBucStriim\Actions\ApiActions
 */
class ApiActionsTest extends PHPUnit\Framework\TestCase
{
    /**
     * Make hasapi configurable via environment variable
     */
    public function getApp($hasapi = true)
    {
        global $langen;
        require('config/langs.php');
        // we need to set this before bootstrap to get api routes
        $_ENV['BICBUCSTRIIM_HAS_API'] = $hasapi;
        $app = require(dirname(__DIR__) . '/config/bootstrap.php');
        $globalSettings = $app->getContainer()->get('globalSettings');
        $globalSettings[LOGIN_REQUIRED] = 0;
        $app->getContainer()->set('globalSettings', $globalSettings);
        return $app;
    }

    /**
     * @runInSeparateProcess
     */
    public function testApiHomeRequest()
    {
        $app = $this->getApp();
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = '<title>BicBucStriim - SwaggerUI</title>';
        $request = RequestUtil::getServerRequest('GET', '/api/');
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    /**
     * @runInSeparateProcess
     */
    public function testApiRoutesRequest()
    {
        $app = $this->getApp();
        $this->assertEquals(\Slim\App::class, $app::class);

        // @todo update when route count changes
        $expected = 56;
        $request = RequestUtil::getServerRequest('GET', '/api/routes');
        $response = $app->handle($request);
        $result = json_decode((string) $response->getBody(), true);
        $this->assertCount($expected, $result['routes']);
        $expected = './api/openapi.json';
        $this->assertArrayHasKey($expected, $result['routes']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testOpenApiRequest()
    {
        $app = $this->getApp();
        $this->assertEquals(\Slim\App::class, $app::class);

        // @todo update when route count changes
        $expected = 56;
        $request = RequestUtil::getServerRequest('GET', '/api/openapi.json');
        $response = $app->handle($request);
        $result = json_decode((string) $response->getBody(), true);
        $this->assertCount($expected, $result['paths']);
        $expected = '/api/openapi.json';
        $this->assertArrayHasKey($expected, $result['paths']);
    }

    /**
     * Requesting main page with header 'Accept: application/json'
     * should return the template variables as json object instead
     * of the actual templated page output
     * @runInSeparateProcess
     */
    public function testMainRequestWithHeader()
    {
        $app = $this->getApp();
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = ['page', 'books', 'stats'];
        $request = RequestUtil::getServerRequest('GET', '/')->withHeader('Accept', 'application/json');
        $response = $app->handle($request);
        $result = json_decode((string) $response->getBody(), true);
        $this->assertEquals($expected, array_keys($result));
    }

    /**
     * Requesting main page with header 'Accept: application/json'
     * but without hasapi should return normal templated page output
     * @runInSeparateProcess
     */
    public function testMainRequestWithoutHasApi()
    {
        $app = $this->getApp(false);
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = '<title>BicBucStriim :: Most recent</title>';
        $request = RequestUtil::getServerRequest('GET', '/')->withHeader('Accept', 'application/json');
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }
}
