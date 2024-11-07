<?php

use BicBucStriim\Calibre\Calibre;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Calibre::class)]
class CustomColumnsTest extends PHPUnit\Framework\TestCase
{
    public static $cdb1;
    public static $cdb2;
    public static $cdb4;

    public static $db2;

    public static $data;
    public static $datadb;

    /** @var ?Calibre */
    public $calibre;

    public function setUp(): void
    {
        self::$cdb1 = dirname(__DIR__, 2) . '/tests/fixtures/metadata_empty.db';
        self::$cdb2 = dirname(__DIR__, 2) . '/tests/fixtures/lib2/metadata.db';
        self::$cdb4 = dirname(__DIR__, 2) . '/tests/fixtures/lib4/metadata.db';

        self::$db2 = dirname(__DIR__, 2) . '/tests/fixtures/data2.db';

        self::$data = dirname(__DIR__, 2) . '/tests/data';
        self::$datadb = dirname(__DIR__, 2) . '/tests/data/data.db';
        $this->calibre = new Calibre(self::$cdb2);
    }

    public function tearDown(): void
    {
        $this->calibre = null;
    }

    # Lots of ccs -- one with multiple values
    public function testCustomColumns(): void
    {
        $ccs = $this->calibre->customColumns(7);
        #print_r($ccs);
        $this->assertEquals(9, sizeof($ccs));
        $this->assertEquals('col2a, col2b', $ccs['Col2']['value']);
    }

    # Ignore series ccs for now
    public function testCustomColumnsIgnoreSeries(): void
    {
        $ccs = $this->calibre->customColumns(5);
        #print_r($ccs);
        $this->assertEquals(0, sizeof($ccs));
    }

    # Only one cc
    public function testCustomColumnsJustOneCC(): void
    {
        $ccs = $this->calibre->customColumns(1);
        $this->assertEquals(1, sizeof($ccs));
    }
}
