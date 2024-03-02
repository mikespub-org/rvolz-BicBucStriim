<?php

use BicBucStriim\Actions\MainActions;
use BicBucStriim\Utilities\RequestUtil;
use Slim\Factory\AppFactory;

/**
 * @covers \BicBucStriim\Actions\MainActions
 */
class MainActionsTest extends PHPUnit\Framework\TestCase
{
    public static function getExpectedRoutes()
    {
        return [
            // 'name' => [
            //    input, output (= text or size),
            //    method(s), path, ...middleware(s), callable with '$self' string
            //],
            'main' => [
                [], '<h2>Most recent</h2>',
                'GET', '/', ['$self', 'main'],
            ],
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
            'authorNotes' => [
                ['id' => 5], 'You don&#039;t have sufficient access rights.',
                'GET', '/authors/{id}/notes/', ['$self', 'check_admin'], ['$self', 'authorNotes'],
            ],
            //'authorNotesEdit' => [
            //    ['id' => 5], 'hello',
            //    'POST', '/authors/{id}/notes/', ['$self', 'check_admin'], ['$self', 'authorNotesEdit']
            //],
            'authorDetailsSlice' => [
                ['id' => 5, 'page' => 0], '<title>BicBucStriim :: Author Details</title>',
                'GET', '/authors/{id}/{page}/', ['$self', 'authorDetailsSlice'],
            ],
            'authorsSlice' => [
                ['page' => 0], '<title>BicBucStriim :: Authors</title>',
                'GET', '/authorslist/{page}/', ['$self', 'authorsSlice'],
            ],
            'globalSearch' => [
                [], '<title>BicBucStriim :: Search</title>',
                'GET', '/search/', ['$self', 'globalSearch'],
            ],
            'seriesDetailsSlice' => [
                ['id' => 1, 'page' => 0], '<title>BicBucStriim :: Series Details</title>',
                'GET', '/series/{id}/{page}/', ['$self', 'seriesDetailsSlice'],
            ],
            'seriesSlice' => [
                ['page' => 0], '<title>BicBucStriim :: Series</title>',
                'GET', '/serieslist/{page}/', ['$self', 'seriesSlice'],
            ],
            'tagDetailsSlice' => [
                ['id' => 3, 'page' => 0], '<title>BicBucStriim :: Tag Details</title>',
                'GET', '/tags/{id}/{page}/', ['$self', 'tagDetailsSlice'],
            ],
            'tagsSlice' => [
                ['page' => 0], '<title>BicBucStriim :: Tags</title>',
                'GET', '/tagslist/{page}/', ['$self', 'tagsSlice'],
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
            'titlesSlice' => [
                ['page' => 0], '<title>BicBucStriim :: Books</title>',
                'GET', '/titleslist/{page}/', ['$self', 'titlesSlice'],
            ],
            // temporary routes for the tailwind templates (= based on the v2.x frontend)
            'authorsSlice2' => [
                [], '<title>BicBucStriim :: Authors</title>',
                'GET', '/authors/', ['$self', 'authorsSlice'],
            ],
            'authorDetailsSlice2' => [
                ['id' => 5], '<title>BicBucStriim :: Author Details</title>',
                'GET', '/authors/{id}/', ['$self', 'authorDetailsSlice'],
            ],
            'seriesSlice2' => [
                [], '<title>BicBucStriim :: Series</title>',
                'GET', '/series/', ['$self', 'seriesSlice'],
            ],
            'seriesDetailsSlice2' => [
                ['id' => 1], '<title>BicBucStriim :: Series Details</title>',
                'GET', '/series/{id}/', ['$self', 'seriesDetailsSlice'],
            ],
            'tagsSlice2' => [
                [], '<title>BicBucStriim :: Tags</title>',
                'GET', '/tags/', ['$self', 'tagsSlice'],
            ],
            'tagDetailsSlice2' => [
                ['id' => 3], '<title>BicBucStriim :: Tag Details</title>',
                'GET', '/tags/{id}/', ['$self', 'tagDetailsSlice'],
            ],
            'titlesSlice2' => [
                [], '<title>BicBucStriim :: Books</title>',
                'GET', '/titles/', ['$self', 'titlesSlice'],
            ],
            'cover2' => [
                ['id' => 7], 168310,
                'GET', '/static/covers/{id}/', ['$self', 'cover'],
            ],
            'thumbnail2' => [
                ['id' => 7], 51232,
                'GET', '/static/titlethumbs/{id}/', ['$self', 'thumbnail'],
            ],
        ];
    }

    public function testAddRoutes()
    {
        $expected = $this->getExpectedRoutes();
        $app = AppFactory::create();
        MainActions::addRoutes($app);
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
     * @runInSeparateProcess
     */
    public function testAppBootstrap()
    {
        $app = require(dirname(__DIR__) . '/config/bootstrap.php');
        $this->assertEquals(\Slim\App::class, $app::class);
    }

    public function getApp()
    {
        global $langen;
        require('config/langs.php');
        $app = require(dirname(__DIR__) . '/config/bootstrap.php');
        $globalSettings = $app->getContainer()->get('globalSettings');
        $globalSettings[LOGIN_REQUIRED] = 0;
        $app->getContainer()->set('globalSettings', $globalSettings);
        return $app;
    }

    /**
     * @runInSeparateProcess
     * @depends testAppBootstrap
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
     * @depends testAppBootstrap
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
