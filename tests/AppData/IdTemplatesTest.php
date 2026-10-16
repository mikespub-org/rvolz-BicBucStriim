<?php

if (!defined('REDBEAN_MODEL_PREFIX')) {
    define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\Models\\');
}

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Models\Idtemplate;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BicBucStriim::class)]
#[CoversClass(Idtemplate::class)]
class IdTemplatesTest extends PHPUnit\Framework\TestCase
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
