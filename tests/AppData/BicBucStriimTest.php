<?php

if (!defined('REDBEAN_MODEL_PREFIX')) {
    define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\Models\\');
}

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Models\Calibrething;
use BicBucStriim\Models\Config;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BicBucStriim::class)]
#[CoversClass(Calibrething::class)]
#[CoversClass(Config::class)]
class BicBucStriimTest extends PHPUnit\Framework\TestCase
{
    public static $data;
    public static $datadb;

    /** @var ?BicBucStriim */
    public $bbs;

    public function setUp(): void
    {
        self::$datadb = TestHelper::copyFixtureDatabase();
        self::$data = dirname(self::$datadb);
        $this->bbs = new BicBucStriim(self::$datadb, false);
    }

    public function tearDown(): void
    {
        // Must destruct to clear caches etc.
        $this->bbs = null;
        TestHelper::removeFixtureDatabase(self::$datadb);
    }

    public function testDbOk(): void
    {
        $this->assertTrue($this->bbs->dbOk());
        $this->bbs = new BicBucStriim(self::$data . '/nodata.db');
        $this->assertFalse($this->bbs->dbOk());
    }

    public function testCreateDb(): void
    {
        $this->bbs = new BicBucStriim(self::$data . '/nodata.db');
        $this->assertFalse($this->bbs->dbOk());
        $this->bbs->createDataDB(self::$data . '/newdata.db');
        $this->assertTrue(file_exists(self::$data . '/newdata.db'));
        $this->bbs = new BicBucStriim(self::$data . '/newdata.db');
        $this->assertTrue($this->bbs->dbOk());
    }

    public function testConfigs(): void
    {
        $configs = $this->bbs->configs();
        if (count($configs) > 1) {
            var_dump($this->bbs);
            var_dump($configs);
        }
        $this->assertEquals(1, count($configs));

        $configA = ['propa' => 'vala', 'propb' => 1];
        $this->bbs->saveConfigs($configA);
        $configs = $this->bbs->configs();
        $this->assertEquals(3, count($configs));
        $this->assertEquals('propa', $configs[2]->name);
        $this->assertEquals('vala', $configs[2]->val);
        $this->assertEquals('propb', $configs[3]->name);
        $this->assertEquals(1, $configs[3]->val);

        $configB = ['propa' => 'vala', 'propb' => 2];
        $this->bbs->saveConfigs($configB);
        $configs = $this->bbs->configs();
        $this->assertEquals(3, count($configs));
        $this->assertEquals('propa', $configs[2]->name);
        $this->assertEquals('vala', $configs[2]->val);
        $this->assertEquals('propb', $configs[3]->name);
        $this->assertEquals(2, $configs[3]->val);
    }
}
