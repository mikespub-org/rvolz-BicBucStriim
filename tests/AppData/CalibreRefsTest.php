<?php

declare(strict_types=1);

if (!defined('REDBEAN_MODEL_PREFIX')) {
    define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\Models\\');
}

use BicBucStriim\AppData\AppBook;
use BicBucStriim\AppData\AppSeries;
use BicBucStriim\AppData\AppTag;
use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\AppData\DataConstants;
use BicBucStriim\Models\Calibrething;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the HasBooks/HasSeries/HasTags delegating traits.
 *
 * Each trait is a thin wrapper around HasCalibrething plus an App* value
 * object factory. Covers the factory methods and the add/get round-trip
 * against the fixture BBS DB.
 */
#[CoversClass(BicBucStriim::class)]
#[CoversClass(AppBook::class)]
#[CoversClass(AppSeries::class)]
#[CoversClass(AppTag::class)]
#[CoversClass(Calibrething::class)]
class CalibreRefsTest extends PHPUnit\Framework\TestCase
{
    public static $data;
    public static $datadb;

    /** @var ?BicBucStriim */
    public $bbs;

    protected function setUp(): void
    {
        self::$datadb = TestHelper::copyFixtureDatabase();
        self::$data = dirname(self::$datadb);
        $this->bbs = new BicBucStriim(self::$datadb, false);
    }

    protected function tearDown(): void
    {
        // Remove the test DB so nothing is left behind.
        $this->bbs = null;
        TestHelper::removeFixtureDatabase(self::$datadb);
    }

    public function testBookFactoryAndRoundTrip(): void
    {
        // Factory creates the App value object (which auto-registers the
        // entity via AppEntity::__construct), then get/add round-trip it.
        $book = $this->bbs->book(701, 'Stones of Venice');
        $this->assertInstanceOf(AppBook::class, $book);

        $found = $this->bbs->getCalibreBook(701);
        $this->assertNotNull($found);
        $this->assertSame('Stones of Venice', $found->cname);
        $this->assertSame(DataConstants::BOOK_TYPE, (int) $found->ctype);
    }

    public function testSeriesFactoryAndRoundTrip(): void
    {
        $series = $this->bbs->series(702, 'My Series');
        $this->assertInstanceOf(AppSeries::class, $series);

        $found = $this->bbs->getCalibreSeries(702);
        $this->assertNotNull($found);
        $this->assertSame('My Series', $found->cname);
        $this->assertSame(DataConstants::SERIES_TYPE, (int) $found->ctype);
    }

    public function testTagFactoryAndRoundTrip(): void
    {
        $tag = $this->bbs->tag(703, 'poetry');
        $this->assertInstanceOf(AppTag::class, $tag);

        $found = $this->bbs->getCalibreTag(703);
        $this->assertNotNull($found);
        $this->assertSame('poetry', $found->cname);
        $this->assertSame(DataConstants::TAG_TYPE, (int) $found->ctype);
    }

    public function testAddGetRoundTripWithoutFactory(): void
    {
        // addCalibre* + getCalibre* directly (no App* factory involved).
        $this->assertNull($this->bbs->getCalibreBook(704));
        $added = $this->bbs->addCalibreBook(704, 'Direct Book');
        $this->assertInstanceOf(Calibrething::class, $added);

        $found = $this->bbs->getCalibreBook(704);
        $this->assertNotNull($found);
        $this->assertSame('Direct Book', $found->cname);
    }
}
