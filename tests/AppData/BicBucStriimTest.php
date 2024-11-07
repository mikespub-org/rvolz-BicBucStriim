<?php

if (!defined('REDBEAN_MODEL_PREFIX')) {
    define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\Models\\');
}

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Models\Calibrething;
use BicBucStriim\Models\Config;
use BicBucStriim\Models\R;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BicBucStriim::class)]
#[CoversClass(Calibrething::class)]
#[CoversClass(Config::class)]
class BicBucStriimTest extends PHPUnit\Framework\TestCase
{
    public static $schema;
    public static $testschema;
    public static $db2;

    public static $data;
    public static $datadb;

    /** @var ?BicBucStriim */
    public $bbs;

    public function setUp(): void
    {
        self::$schema = dirname(__DIR__, 2) . '/data/schema.sql';
        self::$testschema = dirname(__DIR__, 2) . '/tests/data/schema.sql';
        self::$db2 = dirname(__DIR__, 2) . '/tests/fixtures/data2.db';

        self::$data = dirname(__DIR__, 2) . '/tests/data';
        self::$datadb = dirname(__DIR__, 2) . '/tests/data/data.db';
        if (file_exists(self::$data)) {
            system("rm -rf " . self::$data);
        }
        mkdir(self::$data);
        chmod(self::$data, 0o777);
        copy(self::$db2, self::$datadb);
        copy(self::$schema, self::$testschema);
        $this->bbs = new BicBucStriim(self::$datadb, false);
    }

    public function tearDown(): void
    {
        // Must use nuke() to clear caches etc.
        R::nuke();
        R::close();
        $this->bbs = null;
        system("rm -rf " . self::$data);
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
