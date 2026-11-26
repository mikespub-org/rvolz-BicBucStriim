<?php

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\MainActions;
use BicBucStriim\Calibre\Book;
use BicBucStriim\Calibre\Data;
use BicBucStriim\Utilities\LinkGenerator;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LinkGenerator::class)]
class LinkGeneratorTest extends PHPUnit\Framework\TestCase
{
    protected LinkGenerator $generator;

    public function setUp(): void
    {
        // Use the real test container so the adapter wraps a PSR-11 container.
        $container = require dirname(__DIR__, 2) . '/config/container.php';
        $registry = $container->get(ActionRegistry::class);
        $registry->register(MainActions::class);

        $this->generator = $container->get(LinkGenerator::class);
        $this->assertInstanceOf(LinkGenerator::class, $this->generator);
    }

    public function tearDown(): void
    {
        // ...
    }

    public function testGetPathForMainBook()
    {
        $routeName = 'main-book';
        include_once dirname(__DIR__) . '/Actions/MainActionsTest.php';
        $routes = MainActionsTest::getExpectedRoutes();
        [$input, $output, $methods, $pattern] = $routes[$routeName];
        $expected = $pattern;
        foreach ($input as $name => $value) {
            $expected = str_replace('{' . $name . '}', (string) $value, $expected);
            // let getPathFromRouteMap() encode values if needed
            if (in_array($name, ['file', 'path', 'fname']) && str_contains($value, '%')) {
                $input[$name] = rawurldecode($value);
            }
        }
        $path = $this->generator->getPath($routeName, $input);
        $this->assertEquals($expected, $path);
    }

    public function testGetLinkForData()
    {
        $data = new Data();
        $data->book = 7;
        $data->name = "The Stones of Venice, Volume II - John Ruskin";
        $data->format = "EPUB";

        $expected = "/titles/7/file/The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin.epub";
        $path = $this->generator->getLink($data);
        $this->assertEquals($expected, $path);
    }

    public function testGetLinkForUnknown()
    {
        $object = new \stdClass();
        $object->id = 123;

        $expected = "TODO get link for stdClass";
        $path = $this->generator->getLink($object);
        $this->assertEquals($expected, $path);
    }

    public function testGetDataLink()
    {
        $data = new Data();
        $data->book = 7;
        $data->name = "The Stones of Venice, Volume II - John Ruskin";
        $data->format = "EPUB";

        $expected = "/titles/7/file/The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin.epub";
        $path = $this->generator->getDataLink($data);
        $this->assertEquals($expected, $path);

        $expected = "/static/files/7/epub/The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin/";
        $path = $this->generator->getDataLink($data, 2);
        $this->assertEquals($expected, $path);
    }

    public function testGetDataKindle()
    {
        $data = new Data();
        $data->book = 7;
        $data->name = "The Stones of Venice, Volume II - John Ruskin";
        $data->format = "EPUB";

        $expected = "/titles/7/kindle/The%20Stones%20of%20Venice%2C%20Volume%20II%20-%20John%20Ruskin.epub";
        $path = $this->generator->getDataKindle($data);
        $this->assertEquals($expected, $path);
    }

    public function testGetBookLink()
    {
        $book = new Book();
        $book->id = 7;

        $expected = "/titles/7/";
        $path = $this->generator->getBookLink($book);
        $this->assertEquals($expected, $path);
    }

    public function testGetBookCover()
    {
        $book = new Book();
        $book->id = 7;

        $expected = "/titles/7/cover/";
        $path = $this->generator->getBookCover($book);
        $this->assertEquals($expected, $path);

        $expected = "/static/covers/7/";
        $path = $this->generator->getBookCover($book, 2);
        $this->assertEquals($expected, $path);
    }

    public function testGetBookThumb()
    {
        $book = new Book();
        $book->id = 7;

        $expected = "/titles/7/thumbnail/";
        $path = $this->generator->getBookThumb($book);
        $this->assertEquals($expected, $path);

        $expected = "/static/titlethumbs/7/";
        $path = $this->generator->getBookThumb($book, 2);
        $this->assertEquals($expected, $path);
    }
}
