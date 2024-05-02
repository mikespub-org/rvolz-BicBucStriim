<?php

use BicBucStriim\Actions\OpdsActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use Slim\Factory\AppFactory;

/**
 * @covers \BicBucStriim\Actions\OpdsActions
 * @covers \BicBucStriim\Actions\DefaultActions
 * @covers \BicBucStriim\Utilities\TestHelper
 */
class OpdsActionsTest extends PHPUnit\Framework\TestCase
{
    public static function getExpectedRoutes()
    {
        return [
            // 'name' => [
            //    input, output (= text, size or header(s)),
            //    method(s), path, ...middleware(s), callable with '$self' string
            //],
            // for html output specify the expected content text
            'opdsRoot' => [
                [], '<title>BicBucStriim Root Catalog</title>',
                'GET', '/opds/', ['$self', 'opdsRoot'],
            ],
            'opdsNewest' => [
                [], '<title>By Newest</title>',
                'GET', '/opds/newest/', ['$self', 'opdsNewest'],
            ],
            'opdsByTitle' => [
                ['page' => 0], '<title>By Title</title>',
                'GET', '/opds/titleslist/{page}/', ['$self', 'opdsByTitle'],
            ],
            'opdsByAuthorInitial' => [
                [], '<title>By Author</title>',
                'GET', '/opds/authorslist/', ['$self', 'opdsByAuthorInitial'],
            ],
            'opdsByAuthorNamesForInitial' => [
                ['initial' => 'R'], '<title>All Authors for &quot;R&quot;</title>',
                'GET', '/opds/authorslist/{initial}/', ['$self', 'opdsByAuthorNamesForInitial'],
            ],
            'opdsByAuthor' => [
                ['initial' => 'R', 'id' => 9, 'page' => 0], '<title>All books by &quot;Rainer Maria Rilke&quot;</title>',
                'GET', '/opds/authorslist/{initial}/{id}/{page}/', ['$self', 'opdsByAuthor'],
            ],
            'opdsByTagInitial' => [
                [], '<title>By Tag</title>',
                'GET', '/opds/tagslist/', ['$self', 'opdsByTagInitial'],
            ],
            'opdsByTagNamesForInitial' => [
                ['initial' => 'A'], '<title>All tags for &quot;A&quot;</title>',
                'GET', '/opds/tagslist/{initial}/', ['$self', 'opdsByTagNamesForInitial'],
            ],
            'opdsByTag' => [
                ['initial' => 'A', 'id' => 21, 'page' => 0], '<title>All books for tag &quot;Architecture&quot;</title>',
                'GET', '/opds/tagslist/{initial}/{id}/{page}/', ['$self', 'opdsByTag'],
            ],
            'opdsBySeriesInitial' => [
                [], '<title>By Series</title>',
                'GET', '/opds/serieslist/', ['$self', 'opdsBySeriesInitial'],
            ],
            'opdsBySeriesNamesForInitial' => [
                ['initial' => 'S'], '<title>All series for &quot;S&quot;</title>',
                'GET', '/opds/serieslist/{initial}/', ['$self', 'opdsBySeriesNamesForInitial'],
            ],
            'opdsBySeries' => [
                ['initial' => 'S', 'id' => 1, 'page' => 0], '<title>All books for series &quot;Serie Grimmelshausen&quot;</title>',
                'GET', '/opds/serieslist/{initial}/{id}/{page}/', ['$self', 'opdsBySeries'],
            ],
            'opdsSearchDescriptor' => [
                [], '<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">',
                'GET', '/opds/opensearch.xml', ['$self', 'opdsSearchDescriptor'],
            ],
            'opdsBySearch' => [
                ['page' => 0, 'search' => 'der'], '<title>By Search: der</title>',
                'GET', '/opds/searchlist/{page}/', ['$self', 'opdsBySearch'],
            ],
            // for redirect etc. specify the expected header(s)
            'opdsLogout' => [
                [], ['WWW-Authenticate' => 'Basic realm="BicBucStriim"'],
                'GET', '/opds/logout/', ['$self', 'opdsLogout'],
            ],
        ];
    }

    public function testAddRoutes()
    {
        $expected = $this->getExpectedRoutes();
        $app = AppFactory::create();
        OpdsActions::addRoutes($app);
        $routeCollector = $app->getRouteCollector();
        $routes = $routeCollector->getRoutes();
        $this->assertEquals(count($expected), count($routes));
        $patterns = [];
        foreach ($routes as $route) {
            $patterns[] = $route->getPattern();
        }
        // path is now shifted to index 3
        foreach ($expected as $routeInfo) {
            $this->assertEquals(true, in_array($routeInfo[3], $patterns));
        }
    }

    /**
     * @dataProvider getExpectedRoutes
     * @runInSeparateProcess
     */
    public function testAppGetRequest($input, $output, $methods, $pattern, ...$args)
    {
        $this->assertGreaterThan(0, count($args));
        if (is_string($methods) && $methods == 'GET') {
            $app = TestHelper::getApp();

            $seen = [];
            foreach ($input as $name => $value) {
                if (str_contains($pattern, '{' . $name . '}')) {
                    $seen[$name] = 1;
                }
                $pattern = str_replace('{' . $name . '}', (string) $value, $pattern);
            }
            $expected = $output;
            $request = RequestUtil::getServerRequest('GET', $pattern);
            $params = array_diff_key($input, $seen);
            if (!empty($params)) {
                $request = $request->withQueryParams($params);
            }
            $response = $app->handle($request);
            if (is_string($expected)) {
                $this->assertStringContainsString($expected, (string) $response->getBody());
            } elseif (is_numeric($expected)) {
                $this->assertEquals($expected, $response->getHeaderLine('Content-Length'));
                $this->assertEquals($expected, strlen((string) $response->getBody()));
            } elseif (is_array($expected)) {
                foreach ($expected as $header => $line) {
                    if ($header == 'Status') {
                        $this->assertEquals($line, $response->getStatus());
                        continue;
                    }
                    $this->assertEquals($line, $response->getHeaderLine($header));
                }
            }
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testOpdsSearchMissing()
    {
        $app = TestHelper::getApp();

        $expected = 400;
        $request = RequestUtil::getServerRequest('GET', '/opds/searchlist/0/');
        $response = $app->handle($request);
        $this->assertEquals($expected, $response->getStatusCode());
    }
}
