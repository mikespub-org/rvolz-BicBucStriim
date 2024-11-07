<?php

if (!defined('REDBEAN_MODEL_PREFIX')) {
    define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\Models\\');
}

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Models\Idtemplate;
use BicBucStriim\Models\R;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BicBucStriim::class)]
#[CoversClass(Idtemplate::class)]
class IdTemplatesTest extends PHPUnit\Framework\TestCase
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

    public function testIdTemplates(): void
    {
        $this->assertEquals(0, count($this->bbs->idTemplates()));
        $this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
        $this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
        $this->assertEquals(2, count($this->bbs->idTemplates()));
        $template = $this->bbs->idTemplate('amazon');
        $this->assertEquals('amazon', $template->name);
        $this->assertEquals('http://amazon.com/%id%', $template->val);
        $this->assertEquals('Amazon search', $template->label);
    }

    public function testDeleteIdTemplates(): void
    {
        $this->assertEquals(0, count($this->bbs->idTemplates()));
        $this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
        $this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
        $this->assertEquals(2, count($this->bbs->idTemplates()));
        $this->bbs->deleteIdTemplate('amazon123');
        $this->assertEquals(2, count($this->bbs->idTemplates()));
        $this->bbs->deleteIdTemplate('amazon');
        $this->assertEquals(1, count($this->bbs->idTemplates()));
    }

    public function testChangeIdTemplate(): void
    {
        $this->assertEquals(0, count($this->bbs->idTemplates()));
        $this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
        $this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
        $this->assertEquals(2, count($this->bbs->idTemplates()));
        $template = $this->bbs->idTemplate('amazon');
        $this->assertEquals('amazon', $template->name);
        $this->assertEquals('http://amazon.com/%id%', $template->val);
        $this->assertEquals('Amazon search', $template->label);
        $template = $this->bbs->changeIdTemplate('amazon', 'http://amazon.de/%id%', 'Amazon DE search');
        $this->assertEquals('amazon', $template->name);
        $this->assertEquals('http://amazon.de/%id%', $template->val);
        $this->assertEquals('Amazon DE search', $template->label);
    }
}
