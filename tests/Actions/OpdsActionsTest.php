<?php

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Actions\OpdsActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use Slim\Factory\AppFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(OpdsActions::class)]
#[CoversClass(DefaultActions::class)]
#[CoversClass(TestHelper::class)]
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
            'opds-home' => [
                [], '<title>BicBucStriim Root Catalog</title>',
                'GET', '/opds/', ['$self', 'opdsRoot'],
            ],
            'opds-newest' => [
                [], '<title>By Newest</title>',
                'GET', '/opds/newest/', ['$self', 'opdsNewest'],
            ],
            'opds-title-page' => [
                ['page' => 0], '<title>By Title</title>',
                'GET', '/opds/titleslist/{page}/', ['$self', 'opdsByTitle'],
            ],
            'opds-author-initials' => [
                [], '<title>By Author</title>',
                'GET', '/opds/authorslist/', ['$self', 'opdsByAuthorInitial'],
            ],
            'opds-author-names' => [
                ['initial' => 'R'], '<title>All Authors for &quot;R&quot;</title>',
                'GET', '/opds/authorslist/{initial}/', ['$self', 'opdsByAuthorNamesForInitial'],
            ],
            'opds-author-page' => [
                ['initial' => 'R', 'id' => 9, 'page' => 0], '<title>All books by &quot;Rainer Maria Rilke&quot;</title>',
                'GET', '/opds/authorslist/{initial}/{id}/{page}/', ['$self', 'opdsByAuthor'],
            ],
            'opds-tag-initials' => [
                [], '<title>By Tag</title>',
                'GET', '/opds/tagslist/', ['$self', 'opdsByTagInitial'],
            ],
            'opds-tag-names' => [
                ['initial' => 'A'], '<title>All tags for &quot;A&quot;</title>',
                'GET', '/opds/tagslist/{initial}/', ['$self', 'opdsByTagNamesForInitial'],
            ],
            'opds-tag-page' => [
                ['initial' => 'A', 'id' => 21, 'page' => 0], '<title>All books for tag &quot;Architecture&quot;</title>',
                'GET', '/opds/tagslist/{initial}/{id}/{page}/', ['$self', 'opdsByTag'],
            ],
            'opds-series-initials' => [
                [], '<title>By Series</title>',
                'GET', '/opds/serieslist/', ['$self', 'opdsBySeriesInitial'],
            ],
            'opds-series-names' => [
                ['initial' => 'S'], '<title>All series for &quot;S&quot;</title>',
                'GET', '/opds/serieslist/{initial}/', ['$self', 'opdsBySeriesNamesForInitial'],
            ],
            'opds-series-page' => [
                ['initial' => 'S', 'id' => 1, 'page' => 0], '<title>All books for series &quot;Serie Grimmelshausen&quot;</title>',
                'GET', '/opds/serieslist/{initial}/{id}/{page}/', ['$self', 'opdsBySeries'],
            ],
            'opds-opensearch' => [
                [], '<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">',
                'GET', '/opds/opensearch.xml', ['$self', 'opdsSearchDescriptor'],
            ],
            'opds-search-page' => [
                ['page' => 0, 'search' => 'der'], '<title>By Search: der</title>',
                'GET', '/opds/searchlist/{page}/', ['$self', 'opdsBySearch'],
            ],
            // for redirect etc. specify the expected header(s)
            'opds-logout' => [
                [], ['WWW-Authenticate' => 'Basic realm="BicBucStriim"'],
                'GET', '/opds/logout/', ['$self', 'opdsLogout'],
            ],
        ];
    }

    public function testAddRoutes(): void
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getExpectedRoutes')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetRequest($input, $output, $methods, $pattern, ...$args): void
    {
        $this->assertGreaterThan(0, count($args));
        if (is_string($methods) && $methods == 'GET') {
            $app = TestHelper::getApp();

            $seen = [];
            foreach ($input as $name => $value) {
                if (str_contains((string) $pattern, '{' . $name . '}')) {
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

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testOpdsSearchMissing(): void
    {
        $app = TestHelper::getApp();

        $expected = 400;
        $request = RequestUtil::getServerRequest('GET', '/opds/searchlist/0/');
        $response = $app->handle($request);
        $this->assertEquals($expected, $response->getStatusCode());
    }
}
