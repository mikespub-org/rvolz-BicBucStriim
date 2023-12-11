<?php

use BicBucStriim\Utilities\UrlInfo;

/**
 * @covers \BicBucStriim\Utilities\UrlInfo
 * @covers \Utilities
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
            Utilities::bookPath('tests/fixtures/lib2', 'Gotthold Ephraim Lessing/Lob der Faulheit (1)', 'Lob der Faulheit - Gotthold Ephraim Lessing.epub')
        );
    }

    public function testTitleMimeType()
    {
        $this->assertEquals('application/epub+zip', Utilities::titleMimeType('x/y/test.epub'));
        $this->assertEquals('application/x-mobi8-ebook', Utilities::titleMimeType('test.azw3'));
        $this->assertEquals('application/x-mobipocket-ebook', Utilities::titleMimeType('test.mobi'));
        $this->assertEquals('application/x-mobipocket-ebook', Utilities::titleMimeType('test.azw'));
        $this->assertEquals('application/vnd.amazon.ebook', Utilities::titleMimeType('test.azw1'));
        $this->assertEquals('application/vnd.amazon.ebook', Utilities::titleMimeType('test.azw2'));
        $this->assertEquals('text/plain', Utilities::titleMimeType(self::FIXT . '/test.unknown-format'));
        $this->assertEquals('text/xml', Utilities::titleMimeType(self::FIXT . '/atom.rng'));
    }
}
