<?php

use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;

/**
 * @covers \BicBucStriim\Actions\ApiActions
 * @covers \BicBucStriim\Actions\DefaultActions
 * @covers \BicBucStriim\Utilities\TestHelper
 */
class ApiActionsTest extends PHPUnit\Framework\TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testApiHomeRequest()
    {
        $app = TestHelper::getAppWithApi();
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
        $app = TestHelper::getAppWithApi();
        $this->assertEquals(\Slim\App::class, $app::class);

        // @todo update when route count changes
        $expected = 57;
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
        $app = TestHelper::getAppWithApi();
        $this->assertEquals(\Slim\App::class, $app::class);

        // @todo update when route count changes
        $expected = 57;
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
        $app = TestHelper::getAppWithApi();
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
        $app = TestHelper::getAppWithApi(false);
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = '<title>BicBucStriim :: Most recent</title>';
        $request = RequestUtil::getServerRequest('GET', '/')->withHeader('Accept', 'application/json');
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }
}
