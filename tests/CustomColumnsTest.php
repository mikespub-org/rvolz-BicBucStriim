<?php

use BicBucStriim\Calibre\Bic;
use BicBucStriim\Calibre\Calibre;

/**
 * @covers \BicBucStriim\Calibre\Calibre
 */
class CustomColumnsTest extends PHPUnit\Framework\TestCase
{
    public const CDB1 = './tests/fixtures/metadata_empty.db';
    public const CDB2 = './tests/fixtures/lib2/metadata.db';
    public const CDB3 = './tests/fixtures/lib3/metadata.db';

    public const DB2 = './tests/fixtures/data2.db';

    public const DATA = './tests/data';
    public const DATADB = './tests/data/data.db';

    /** @var ?Calibre */
    public $calibre;

    public function setUp(): void
    {
        $this->calibre = new Calibre(self::CDB2);
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
