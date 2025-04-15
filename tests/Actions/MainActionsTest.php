<?php

use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Actions\MainActions;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use BicBucStriim\Utilities\Mailer;
use Slim\Factory\AppFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MainActions::class)]
#[CoversClass(DefaultActions::class)]
#[CoversClass(TestHelper::class)]
#[CoversClass(Mailer::class)]
class MainActionsTest extends PHPUnit\Framework\TestCase
{
    public static function getExpectedRoutes()
    {
        return [
            // 'name' => [
            //    input, output (= text, size or header(s)),
            //    method(s), path, ...middleware(s), callable with '$self' string
            //],
            // for html output specify the expected content text
            'main-home' => [
                [], '<h2>Most recent</h2>',
                'GET', '/', ['$self', 'main'],
            ],
            'main-login' => [
                [], '<title>BicBucStriim :: Login</title>',
                'GET', '/login/', ['$self', 'show_login'],
            ],
            'main-login-post' => [
                [], 'hello',
                'POST', '/login/', ['$self', 'perform_login'],
            ],
            'main-logout' => [
                [], '<title>BicBucStriim :: Logout</title>',
                'GET', '/logout/', ['$self', 'logout'],
            ],
            'main-author-note' => [
                ['id' => 5], 'You don&#039;t have sufficient access rights.',
                'GET', '/authors/{id}/notes/', '$gatekeeper', ['$self', 'authorNotes'],
            ],
            'main-author-v1' => [
                ['id' => 5, 'page' => 0], '<title>BicBucStriim :: Author Details</title>',
                'GET', '/authors/{id}/{page}/', ['$self', 'authorDetailsSlice'],
            ],
            'main-authors-v1' => [
                ['page' => 0], '<title>BicBucStriim :: Authors</title>',
                'GET', '/authorslist/{page}/', ['$self', 'authorsSlice'],
            ],
            'main-search' => [
                [], '<title>BicBucStriim :: Search</title>',
                'GET', '/search/', ['$self', 'globalSearch'],
            ],
            'main-serie-v1' => [
                ['id' => 1, 'page' => 0], '<title>BicBucStriim :: Series Details</title>',
                'GET', '/series/{id}/{page}/', ['$self', 'seriesDetailsSlice'],
            ],
            'main-series-v1' => [
                ['page' => 0], '<title>BicBucStriim :: Series</title>',
                'GET', '/serieslist/{page}/', ['$self', 'seriesSlice'],
            ],
            'main-tag-v1' => [
                ['id' => 3, 'page' => 0], '<title>BicBucStriim :: Tag Details</title>',
                'GET', '/tags/{id}/{page}/', ['$self', 'tagDetailsSlice'],
            ],
            'main-tags-v1' => [
                ['page' => 0], '<title>BicBucStriim :: Tags</title>',
                'GET', '/tagslist/{page}/', ['$self', 'tagsSlice'],
            ],
            'main-title' => [
                ['id' => 7], '<title>BicBucStriim :: Book Details</title>',
                'GET', '/titles/{id}/', ['$self', 'title'],
            ],
            // for file output specify the expected content size
            'main-cover-v1' => [
                ['id' => 7], 168310,
                'GET', '/titles/{id}/cover/', ['$self', 'cover'],
            ],
            'main-book' => [
                ['id' => 7, 'file' => 'The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin.epub'], 10198,
                'GET', '/titles/{id}/file/{file}', ['$self', 'book'],
            ],
            'main-kindle' => [
                ['id' => 7, 'file' => 'The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin.epub'], 'hello',
                'POST', '/titles/{id}/kindle/{file}', ['$self', 'kindle'],
            ],
            'main-thumbnail-v1' => [
                ['id' => 7], 51232,
                'GET', '/titles/{id}/thumbnail/', ['$self', 'thumbnail'],
            ],
            'main-titles-v1' => [
                ['page' => 0], '<title>BicBucStriim :: Books</title>',
                'GET', '/titleslist/{page}/', ['$self', 'titlesSlice'],
            ],
            // temporary routes for the tailwind templates (= based on the v2.x frontend)
            'main-authors-v2' => [
                [], '<title>BicBucStriim :: Authors</title>',
                'GET', '/authors/', ['$self', 'authorsSlice'],
            ],
            'main-author-v2' => [
                ['id' => 5], '<title>BicBucStriim :: Author Details</title>',
                'GET', '/authors/{id}/', ['$self', 'authorDetailsSlice'],
            ],
            'main-series-v2' => [
                [], '<title>BicBucStriim :: Series</title>',
                'GET', '/series/', ['$self', 'seriesSlice'],
            ],
            'main-serie-v2' => [
                ['id' => 1], '<title>BicBucStriim :: Series Details</title>',
                'GET', '/series/{id}/', ['$self', 'seriesDetailsSlice'],
            ],
            'main-tags-v2' => [
                [], '<title>BicBucStriim :: Tags</title>',
                'GET', '/tags/', ['$self', 'tagsSlice'],
            ],
            'main-tag-v2' => [
                ['id' => 3], '<title>BicBucStriim :: Tag Details</title>',
                'GET', '/tags/{id}/', ['$self', 'tagDetailsSlice'],
            ],
            'main-titles-v2' => [
                [], '<title>BicBucStriim :: Books</title>',
                'GET', '/titles/', ['$self', 'titlesSlice'],
            ],
            'main-cover-v2' => [
                ['id' => 7], 168310,
                'GET', '/static/covers/{id}/', ['$self', 'cover'],
            ],
            'main-thumbnail-v2' => [
                ['id' => 7], 51232,
                'GET', '/static/titlethumbs/{id}/', ['$self', 'thumbnail'],
            ],
            'params-scope-type' => [
                ['scope' => 'authors', 'type' => 'initials'], '"data": [',
                'GET', '/params/{scope}/{type}/', ['$self', 'getTailwindParams'],
            ],
            // for redirect etc. specify the expected header(s)
        ];
    }

    public function testAddRoutes(): void
    {
        $expected = $this->getExpectedRoutes();
        $app = AppFactory::create();
        MainActions::addRoutes($app);
        $routeCollector = $app->getRouteCollector();
        $routes = $routeCollector->getRoutes();
        $this->assertEquals(count($expected), count($routes));
        $names = [];
        $patterns = [];
        foreach ($routes as $route) {
            $names[] = $route->getName();
            $patterns[] = $route->getPattern();
        }
        // path is now shifted to index 3
        foreach ($expected as $name => $routeInfo) {
            $this->assertEquals(true, in_array($name, $names));
            $this->assertEquals(true, in_array($routeInfo[3], $patterns));
        }
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppBootstrap(): void
    {
        $app = require dirname(__DIR__, 2) . '/config/bootstrap.php';
        $this->assertEquals(\Slim\App::class, $app::class);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testAppBootstrap')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppMainRequest(): void
    {
        $app = TestHelper::getApp();
        $this->assertEquals(\Slim\App::class, $app::class);

        $expected = '<title>BicBucStriim :: Most recent</title>';
        $request = RequestUtil::getServerRequest('GET', '/');
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testAppBootstrap')]
    #[\PHPUnit\Framework\Attributes\DataProvider('getExpectedRoutes')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetRequest($input, $output, $methods, $pattern, ...$args): void
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
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testMainAuthorNotes(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/authors/5/notes/');

        $expected = '<title>BicBucStriim :: Notes</title>';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testMainKindle(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
        $_POST['email'] = 'kindle@example.org';
        $uri = '/titles/7/kindle/The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin.epub';

        $app = TestHelper::getApp();
        $app->getContainer()->set(Mailer::class, new Mailer(Mailer::MOCK));

        $expected = 'The book has been sent!';
        $request = RequestUtil::getServerRequest('POST', $uri);
        $response = $app->handle($request);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['CONTENT_TYPE']);
        unset($_POST['email']);
        $this->assertStringContainsString($expected, (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
