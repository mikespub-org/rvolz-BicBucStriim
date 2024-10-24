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
                'GET', '/authors/{id}/notes/', '$gatekeeper', ['$self', 'authorNotes'],
            ],
            //'authorNotesEdit' => [
            //    ['id' => 5], 'hello',
            //    'POST', '/authors/{id}/notes/', '$gatekeeper', ['$self', 'authorNotesEdit']
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
        $patterns = [];
        foreach ($routes as $route) {
            $patterns[] = $route->getPattern();
        }
        // path is now shifted to index 3
        foreach ($expected as $routeInfo) {
            $this->assertEquals(true, in_array($routeInfo[3], $patterns));
        }
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppBootstrap(): void
    {
        $app = require(dirname(__DIR__) . '/config/bootstrap.php');
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
        $request = RequestUtil::getServerRequest('GET', '/authors/5/notes/');
        $userData = [
            'role' => 1,
        ];
        $auth = TestHelper::getAuth($request, $userData);
        $request = $request->withAttribute('auth', $auth);

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
