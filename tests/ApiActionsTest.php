<?php

use BicBucStriim\Utilities\RequestUtil;

/**
 * @covers \BicBucStriim\Actions\ApiActions
 * @covers \BicBucStriim\Actions\DefaultActions
 */
class ApiActionsTest extends PHPUnit\Framework\TestCase
{
    /**
     * Make hasapi configurable via environment variable
     */
    public function getApp($hasapi = true)
    {
        // we need to set this before bootstrap to get api routes
        putenv("BBS_HAS_API=$hasapi");
        $app = require(dirname(__DIR__) . '/config/bootstrap.php');
        $settings = $app->getContainer()->get('globalSettings');
        $settings->must_login = 0;
        $app->getContainer()->set('globalSettings', $settings);
        return $app;
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
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
     * @preserveGlobalState disabled
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
     * @preserveGlobalState disabled
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
     * @preserveGlobalState disabled
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
     * @preserveGlobalState disabled
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
