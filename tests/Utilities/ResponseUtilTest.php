<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2016 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 * Unit tests for the response utilities.
 *
 * ResponseUtil is a framework-agnostic helper shared by middleware and action
 * classes. These tests exercise each response helper against real Nyholm PSR-7
 * objects (Nyholm is the PSR-17 library used throughout the app) and verify that
 * the PSR-17 factory is created lazily and shared per instance.
 */

use BicBucStriim\Utilities\ResponseUtil;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResponseUtil::class)]
class ResponseUtilTest extends PHPUnit\Framework\TestCase
{
    /**
     * Read a protected property of a ResponseUtil instance via reflection.
     *
     * @return mixed
     */
    private function readProtected(ResponseUtil $util, string $prop)
    {
        $ref = new \ReflectionProperty(ResponseUtil::class, $prop);
        $ref->setAccessible(true);
        return $ref->getValue($util);
    }

    /** Invoke a protected method of a ResponseUtil instance via reflection. */
    private function callProtected(ResponseUtil $util, string $method)
    {
        $ref = new \ReflectionMethod(ResponseUtil::class, $method);
        $ref->setAccessible(true);
        return $ref->invoke($util);
    }

    public function testConstructorUsesSuppliedResponse(): void
    {
        $response = new Response(418);
        $util = new ResponseUtil($response);
        $this->assertSame($response, $util->value());
        $this->assertEquals(418, $util->value()->getStatusCode());
    }

    public function testConstructorCreatesResponseWhenNull(): void
    {
        $util = new ResponseUtil(null);
        $this->assertEquals(200, $util->value()->getStatusCode());
    }

    public function testFactoryIsLazyWhenResponseSupplied(): void
    {
        // When a response is passed in, the factory must never be allocated.
        $util = new ResponseUtil(new Response());
        $this->assertNull($this->readProtected($util, 'psr17Factory'));
    }

    public function testFactoryAllocatedWhenResponseNull(): void
    {
        // When no response is supplied, a response must be created via the factory.
        $util = new ResponseUtil(null);
        $this->assertNotNull($this->readProtected($util, 'psr17Factory'));
    }

    public function testFactoryIsSharedPerInstance(): void
    {
        $util = new ResponseUtil(null);
        $first = $this->callProtected($util, 'factory');
        $second = $this->callProtected($util, 'factory');
        $this->assertSame($first, $second, 'factory() should return the same shared instance');
    }

    public function testValueGetSet(): void
    {
        $util = new ResponseUtil(new Response());
        $this->assertEquals(200, $util->value()->getStatusCode());

        $other = new Response(201);
        $this->assertSame($other, $util->value($other));
        $this->assertEquals(201, $util->value()->getStatusCode());
    }

    public function testRespond(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->respond('hello', 'text/plain', 201);

        $this->assertEquals(201, $resp->getStatusCode());
        $this->assertEquals('text/plain', $resp->getHeaderLine('Content-type'));
        $this->assertEquals('5', $resp->getHeaderLine('Content-Length'));
        $this->assertEquals('hello', (string) $resp->getBody());
    }

    public function testHtml(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->html('<p>hi</p>');
        $this->assertEquals('text/html', $resp->getHeaderLine('Content-type'));
        $this->assertEquals('<p>hi</p>', (string) $resp->getBody());
    }

    public function testPlain(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->plain('just text');
        $this->assertEquals('text/plain', $resp->getHeaderLine('Content-type'));
        $this->assertEquals('just text', (string) $resp->getBody());
    }

    public function testJsonWithoutOrigin(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->json(['a' => 1]);
        $this->assertEquals('application/json', $resp->getHeaderLine('Content-type'));
        // json() encodes with JSON_PRETTY_PRINT, so the body is multi-line.
        $this->assertEquals("{\n    \"a\": 1\n}", (string) $resp->getBody());
        $this->assertEquals('', $resp->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testJsonWithOriginAddsCorsHeaders(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->json(['ok' => true], 'https://example.org');
        $this->assertEquals('https://example.org', $resp->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('true', $resp->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertEquals('Origin', $resp->getHeaderLine('Vary'));
    }

    public function testError(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->error(404, 'not found');
        $this->assertEquals(404, $resp->getStatusCode());
        $this->assertEquals('not found', (string) $resp->getBody());
    }

    public function testErrorUsesFactoryAndIsShared(): void
    {
        // error() must obtain its empty body via the shared factory.
        $util = new ResponseUtil(new Response());
        $this->assertNull($this->readProtected($util, 'psr17Factory'));
        $resp = $util->error(500, 'boom');
        $factory = $this->readProtected($util, 'psr17Factory');
        $this->assertInstanceOf(Psr17Factory::class, $factory);
        $this->assertEquals(500, $resp->getStatusCode());
        $this->assertEquals('boom', (string) $resp->getBody());
    }

    public function testRedirect(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->redirect('/target', 301);
        $this->assertEquals(301, $resp->getStatusCode());
        $this->assertEquals('/target', $resp->getHeaderLine('Location'));
    }

    public function testCorsEmptyOriginIsNoop(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->cors('');
        $this->assertEquals('', $resp->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testCorsSetsHeaders(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->cors('https://example.org');
        $this->assertEquals('https://example.org', $resp->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST, OPTIONS', $resp->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals('86400', $resp->getHeaderLine('Access-Control-Max-Age'));
    }

    public function testSetCookie(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->setCookie('session', 'abc123', time() + 3600);
        $this->assertStringContainsString('session', $resp->getHeaderLine('Set-Cookie'));
    }

    public function testAuthenticate(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->authenticate('MyRealm');
        $this->assertEquals(401, $resp->getStatusCode());
        $this->assertStringContainsString('Basic realm="MyRealm"', $resp->getHeaderLine('WWW-Authenticate'));
    }

    public function testFile(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'ru');
        file_put_contents($file, 'file-body-contents');

        $util = new ResponseUtil(new Response());
        $resp = $util->file($file, 'image/jpeg', 200);

        $this->assertEquals('image/jpeg', $resp->getHeaderLine('Content-type'));
        $this->assertEquals((string) filesize($file), $resp->getHeaderLine('Content-Length'));
        $this->assertEquals('file-body-contents', (string) $resp->getBody());
        $this->assertNotEquals('', $resp->getHeaderLine('ETag'));

        unlink($file);
    }

    public function testDownload(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'ru');
        file_put_contents($file, 'download-body');

        $util = new ResponseUtil(new Response());
        $resp = $util->download($file, 'application/epub+zip', 'book.epub');

        $this->assertEquals('application/epub+zip', $resp->getHeaderLine('Content-type'));
        $this->assertEquals('attachment; filename="book.epub"', $resp->getHeaderLine('Content-Disposition'));
        $this->assertEquals('download-body', (string) $resp->getBody());

        unlink($file);
    }

    public function testOpds(): void
    {
        $util = new ResponseUtil(new Response());
        $resp = $util->opds('<feed/>', 'application/atom+xml');
        $this->assertEquals('application/atom+xml', $resp->getHeaderLine('Content-type'));
        $this->assertEquals('<feed/>', (string) $resp->getBody());
    }

    public function testDone(): void
    {
        $response = new Response();
        $util = new ResponseUtil($response);
        $this->assertSame($response, $util->done());
    }
}
