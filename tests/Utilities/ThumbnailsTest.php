<?php

use BicBucStriim\Utilities\Thumbnails;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Thumbnails::class)]
class ThumbnailsTest extends PHPUnit\Framework\TestCase
{
    public static $data;

    /** @var ?Thumbnails */
    public $thumbs;

    public function setUp(): void
    {
        self::$data = dirname(__DIR__, 2) . '/tests/data';
        if (file_exists(self::$data)) {
            system("rm -rf " . self::$data);
        }
        mkdir(self::$data);
        chmod(self::$data, 0o777);
        $this->thumbs = new Thumbnails(self::$data);
    }

    public function tearDown(): void
    {
        $this->thumbs = null;
        system("rm -rf " . self::$data);
    }

    public function testIsTitleThumbnailAvailable(): void
    {
        $this->assertNotNull($this->thumbs->titleThumbnail(1, 'tests/fixtures/author1.jpg', true));
        $this->assertTrue($this->thumbs->isTitleThumbnailAvailable(1));
        $this->assertFalse($this->thumbs->isTitleThumbnailAvailable(2));
    }

    public function testClearThumbnail(): void
    {
        $result = $this->thumbs->titleThumbnail(3, 'tests/fixtures/author1.jpg', true);
        $this->assertNotNull($result);
        $this->assertTrue($this->thumbs->isTitleThumbnailAvailable(3));
        $this->assertTrue($this->thumbs->clearThumbnails());
        clearstatcache(true);
        $this->assertFalse(file_exists($result));
    }
}
