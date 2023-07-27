<?php

namespace Tests\Domain\Calibre;

use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreFilter;
use PHPUnit\Framework\TestCase;

class CustomColumnsTest extends TestCase
{
    public const CDB1 = __DIR__ . '/../../fixtures/metadata_empty.db';
    public const CDB2 = __DIR__ . '/../../fixtures/lib2/metadata.db';
    public const CDB3 = __DIR__ . '/../../fixtures/lib3/metadata.db';

    public const DB2 = __DIR__ . '/../../fixtures/data2.db';

    public const DATA = __DIR__ . '/../../data';
    public const DATADB = __DIR__ . '/../../data/data.db';

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
    public function testCustomColumns()
    {
        $ccs = $this->calibre->customColumns(7);
        #print_r($ccs);
        $this->assertEquals(9, sizeof($ccs));
        $this->assertEquals('col2a, col2b', $ccs['Col2']['value']);
    }

    # Ignore series ccs for now
    public function testCustomColumnsIgnoreSeries()
    {
        $ccs = $this->calibre->customColumns(5);
        #print_r($ccs);
        $this->assertEquals(0, sizeof($ccs));
    }

    # Only one cc
    public function testCustomColumnsJustOneCC()
    {
        $ccs = $this->calibre->customColumns(1);
        $this->assertEquals(1, sizeof($ccs));
    }
}
