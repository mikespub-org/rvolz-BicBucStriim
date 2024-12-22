<?php

/**
 * Test our workaround to search for items with non-ascii names
 */

use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Calibre\CalibreFilter;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Calibre::class)]
#[CoversClass(CalibreFilter::class)]
class CalibreIcuTest extends PHPUnit\Framework\TestCase
{
    public static $cdb4;

    /** @var ?Calibre */
    public $calibre;

    public function setUp(): void
    {
        self::$cdb4 = dirname(__DIR__, 2) . '/tests/fixtures/lib4/metadata.db';
        $this->calibre = new Calibre(self::$cdb4);
    }

    public function tearDown(): void
    {
        $this->calibre = null;
    }

    public function testAuthorsSliceSearch(): void
    {
        $result0 = $this->calibre->authorsSlice(0, 2, 'Асприн');
        $this->assertEquals(1, count($result0['entries']));
        $result0 = $this->calibre->authorsSlice(0, 2, 'lôr');
        $this->assertEquals(1, count($result0['entries']));
    }

    public function testSeriesSliceSearch(): void
    {
        $result0 = $this->calibre->seriesSlice(0, 2, 'ü');
        $this->assertEquals(1, count($result0['entries']));
    }

    public function testTagsSliceSearch(): void
    {
        $result0 = $this->calibre->tagsSlice(0, 2, 'I');
        $this->assertEquals(2, count($result0['entries']));
        $result0 = $this->calibre->tagsSlice(0, 2, 'Ét');
        $this->assertEquals(1, count($result0['entries']));
        $result0 = $this->calibre->tagsSlice(0, 2, 'Ü');
        $this->assertEquals(2, count($result0['entries']));
    }

    public function testTitlesSliceSearch(): void
    {
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'ü');
        $this->assertEquals(1, count($result0['entries']));
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'ä');
        $this->assertEquals(1, count($result0['entries']));
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'ß');
        $this->assertEquals(1, count($result0['entries']));
        $result0 = $this->calibre->titlesSlice('fr', 0, 2, new CalibreFilter(), 'é');
        $this->assertEquals(1, count($result0['entries']));
        $result0 = $this->calibre->titlesSlice('fr', 0, 2, new CalibreFilter(), 'ò');
        $this->assertEquals(1, count($result0['entries']));
    }
}
