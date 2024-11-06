<?php

use BicBucStriim\Actions\ApiActions;
use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ApiActions::class)]
#[CoversClass(DefaultActions::class)]
#[CoversClass(TestHelper::class)]
class ApiActionsTest extends PHPUnit\Framework\TestCase
{
    // @todo update when route count changes
    public const ROUTE_COUNT = 60;

    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testApiHomeRequest(): void
    {
        $app = TestHelper::getAppWithApi();
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = '<title>BicBucStriim - SwaggerUI</title>';
        $request = RequestUtil::getServerRequest('GET', '/api/');
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testApiRoutesRequest(): void
    {
        $app = TestHelper::getAppWithApi();
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = self::ROUTE_COUNT;
        $request = RequestUtil::getServerRequest('GET', '/api/routes');
        $response = $app->handle($request);
        $result = json_decode((string) $response->getBody(), true);
        $this->assertCount($expected, $result['routes']);
        $expected = './api/openapi.json';
        $this->assertArrayHasKey($expected, $result['routes']);
    }

    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testOpenApiRequest(): void
    {
        $app = TestHelper::getAppWithApi();
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = self::ROUTE_COUNT;
        $request = RequestUtil::getServerRequest('GET', '/api/openapi.json');
        $response = $app->handle($request);
        $result = json_decode((string) $response->getBody(), true);
        $this->assertCount($expected, $result['paths']);
        $expected = '/api/openapi.json';
        $this->assertArrayHasKey($expected, $result['paths']);
    }

    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testCorsOptionsRequest(): void
    {
        $app = TestHelper::getAppWithApi();
        $this->assertEquals(\Slim\App::class, $app::class);

        $origin = '*';
        $expected = [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, Accept, Origin, Authorization',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
            'Vary' => 'Origin',
        ];
        $request = RequestUtil::getServerRequest('OPTIONS', '/');
        $response = $app->handle($request);
        foreach ($expected as $header => $line) {
            $this->assertEquals($line, $response->getHeaderLine($header));
        }

        $request = RequestUtil::getServerRequest('OPTIONS', '/api/');
        $response = $app->handle($request);
        foreach ($expected as $header => $line) {
            $this->assertEquals($line, $response->getHeaderLine($header));
        }
    }

    /**
     * Requesting main page with header 'Accept: application/json'
     * should return the template variables as json object instead
     * of the actual templated page output
     */
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testMainRequestWithHeader(): void
    {
        $app = TestHelper::getAppWithApi();
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = ['page', 'books', 'stats', 'outdated'];
        $origin = 'https://remote.host';
        $request = RequestUtil::getServerRequest('GET', '/')->withHeader('Accept', 'application/json')->withHeader('Origin', $origin);
        $response = $app->handle($request);
        $result = json_decode((string) $response->getBody(), true);
        $this->assertEquals($expected, array_keys($result));
        // Add Allow-Origin + Allow-Credentials to response for non-preflighted requests
        $expected = [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Vary' => 'Origin',
        ];
        foreach ($expected as $header => $line) {
            $this->assertEquals($line, $response->getHeaderLine($header));
        }
    }

    /**
     * Requesting main page with header 'Accept: application/json'
     * but without hasapi should return normal templated page output
     */
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testMainRequestWithoutHasApi(): void
    {
        $app = TestHelper::getAppWithApi(false);
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = '<title>BicBucStriim :: Most recent</title>';
        $request = RequestUtil::getServerRequest('GET', '/')->withHeader('Accept', 'application/json');
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }
}
