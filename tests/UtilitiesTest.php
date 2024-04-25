<?php

use BicBucStriim\Utilities\InputUtil;
use BicBucStriim\Utilities\L10n;
use BicBucStriim\Utilities\UrlInfo;
use BicBucStriim\Utilities\CalibreUtil;

/**
 * @covers \BicBucStriim\Utilities\UrlInfo
 * @covers \BicBucStriim\Utilities\CalibreUtil
 * @covers \BicBucStriim\Utilities\InputUtil
 */
class UtilitiesTest extends PHPUnit\Framework\TestCase
{
    public const FIXT = './tests/fixtures';

    public function testConstructUrlInfoSimple()
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

    public function testConstructUrlInfoForwarded()
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

    public function testBookPath()
    {
        $this->assertEquals(
            'tests/fixtures/lib2/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub',
            CalibreUtil::bookPath('tests/fixtures/lib2', 'Gotthold Ephraim Lessing/Lob der Faulheit (1)', 'Lob der Faulheit - Gotthold Ephraim Lessing.epub')
        );
    }

    public function testTitleMimeType()
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

    public function testGetUserLang()
    {
        # Allowed languages, i.e. languages with translations
        $allowedLangs = L10n::$allowedLangs;
        # Fallback language if the browser prefers another than the allowed languages
        $fallbackLang = L10n::$fallbackLang;

        $expected = 'pl';
        $_GET['lang'] = $expected;
        $this->assertEquals($expected, InputUtil::getUserLang($allowedLangs, $fallbackLang));
        unset($_GET['lang']);

        $expected = 'es';
        $_SESSION['lang'] = $expected;
        $this->assertEquals($expected, InputUtil::getUserLang($allowedLangs, $fallbackLang));
        unset($_SESSION['lang']);

        $expected = 'fr';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5';
        $this->assertEquals($expected, InputUtil::getUserLang($allowedLangs, $fallbackLang));
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $expected = $fallbackLang;
        $this->assertEquals($expected, InputUtil::getUserLang($allowedLangs, $fallbackLang));

        $expected = 'na';
        $this->assertEquals($expected, InputUtil::getUserLang($allowedLangs, 'na'));
    }

    public function testIsEMailValid()
    {
        $this->assertFalse(InputUtil::isEMailValid('a'));
        $this->assertFalse(InputUtil::isEMailValid('@b'));
        $this->assertFalse(InputUtil::isEMailValid('a@b'));
        $this->assertTrue(InputUtil::isEMailValid('a@b.c'));
        $this->assertFalse(InputUtil::isEMailValid('a.b@c'));
        $this->assertTrue(InputUtil::isEMailValid('a.b@c.d'));
    }
}
