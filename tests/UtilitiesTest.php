<?php

use BicBucStriim\Utilities\InputUtil;
use BicBucStriim\Utilities\L10n;
use BicBucStriim\Utilities\UrlInfo;
use BicBucStriim\Utilities\CalibreUtil;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Session\SessionFactory;

/**
 * @covers \BicBucStriim\Utilities\UrlInfo
 * @covers \BicBucStriim\Utilities\CalibreUtil
 * @covers \BicBucStriim\Utilities\InputUtil
 */
class UtilitiesTest extends PHPUnit\Framework\TestCase
{
    public const FIXT = './tests/fixtures';

    public function testConstructUrlInfoSimple(): void
    {
        $gen = new UrlInfo('host.org', null);
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('host.org', $gen->host);
        $this->assertEquals('http', $gen->protocol);

        $gen = new UrlInfo('host.org', 'https');
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('host.org', $gen->host);
        $this->assertEquals('https', $gen->protocol);
    }

    public function testConstructUrlInfoForwarded(): void
    {
        $input1 = "for=192.0.2.60;proto=http;by=203.0.113.43";
        $input2 = "for=192.0.2.60;proto=https;by=203.0.113.43";
        $input3 = "for=192.0.2.60;by=203.0.113.43;proto=https";

        $gen = new UrlInfo($input1);
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('203.0.113.43', $gen->host);
        $this->assertEquals('http', $gen->protocol);

        $gen = new UrlInfo($input2);
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('203.0.113.43', $gen->host);
        $this->assertEquals('https', $gen->protocol);

        $gen = new UrlInfo($input3);
        $this->assertTrue($gen->is_valid());
        $this->assertEquals('https', $gen->protocol);
    }

    public function testUrlInfoGetForwardingInfo(): void
    {
        $headers = [];
        $gen = UrlInfo::getForwardingInfo($headers);
        $this->assertEquals(null, $gen);

        $headers = ['Forwarded' => 'for=192.0.2.60;proto=https;by=203.0.113.43'];
        $gen = UrlInfo::getForwardingInfo($headers);
        $this->assertEquals('203.0.113.43', $gen->host);
        $this->assertEquals('https', $gen->protocol);

        $headers = ['X-Forwarded-Host' => '203.0.113.43', 'X-Forwarded-Proto' => 'https'];
        $gen = UrlInfo::getForwardingInfo($headers);
        $this->assertEquals('203.0.113.43', $gen->host);
        $this->assertEquals('https', $gen->protocol);
    }

    public function testBookPath(): void
    {
        $this->assertEquals(
            'tests/fixtures/lib2/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub',
            CalibreUtil::bookPath('tests/fixtures/lib2', 'Gotthold Ephraim Lessing/Lob der Faulheit (1)', 'Lob der Faulheit - Gotthold Ephraim Lessing.epub')
        );
    }

    public function testTitleMimeType(): void
    {
        $this->assertEquals('application/epub+zip', CalibreUtil::titleMimeType('x/y/test.epub'));
        $this->assertEquals('application/x-mobi8-ebook', CalibreUtil::titleMimeType('test.azw3'));
        $this->assertEquals('application/x-mobipocket-ebook', CalibreUtil::titleMimeType('test.mobi'));
        $this->assertEquals('application/x-mobipocket-ebook', CalibreUtil::titleMimeType('test.azw'));
        $this->assertEquals('application/vnd.amazon.ebook', CalibreUtil::titleMimeType('test.azw1'));
        $this->assertEquals('application/vnd.amazon.ebook', CalibreUtil::titleMimeType('test.azw2'));
        $this->assertEquals('text/plain', CalibreUtil::titleMimeType(self::FIXT . '/test.unknown-format'));
        $this->assertEquals('text/xml', CalibreUtil::titleMimeType(self::FIXT . '/atom.rng'));
    }

    public function testGetUserLang(): void
    {
        $expected = 'pl';
        $_GET['lang'] = $expected;
        $request = RequestUtil::getServerRequest();
        $this->assertEquals($expected, InputUtil::getUserLang($request));
        unset($_GET['lang']);

        $expected = 'es';
        $_COOKIE['lang'] = $expected;
        $request = RequestUtil::getServerRequest();
        // @see LoginMiddleware::is_authorized()
        $session_factory = new SessionFactory();
        $session = $session_factory->newInstance($request->getCookieParams());
        $request = $request->withAttribute('session', $session);
        $this->assertEquals($expected, InputUtil::getUserLang($request));
        unset($_COOKIE['lang']);

        $expected = 'it';
        $_COOKIE['lang'] = $expected;
        $request = RequestUtil::getServerRequest();
        $this->assertEquals($expected, InputUtil::getUserLang($request));
        unset($_COOKIE['lang']);

        $expected = 'fr';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5';
        $request = RequestUtil::getServerRequest();
        $this->assertEquals($expected, InputUtil::getUserLang($request));
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        # Fallback language if the browser prefers another than the allowed languages
        $expected = L10n::$fallbackLang;
        $_GET['lang'] = 'na';
        $request = RequestUtil::getServerRequest();
        $this->assertEquals($expected, InputUtil::getUserLang($request));
        unset($_GET['lang']);

        $expected = L10n::$fallbackLang;
        $request = RequestUtil::getServerRequest();
        $this->assertEquals($expected, InputUtil::getUserLang($request));
    }

    public function testIsEMailValid(): void
    {
        $this->assertFalse(InputUtil::isEMailValid('a'));
        $this->assertFalse(InputUtil::isEMailValid('@b'));
        $this->assertFalse(InputUtil::isEMailValid('a@b'));
        $this->assertTrue(InputUtil::isEMailValid('a@b.c'));
        $this->assertFalse(InputUtil::isEMailValid('a.b@c'));
        $this->assertTrue(InputUtil::isEMailValid('a.b@c.d'));
    }
}
