<?php

use BicBucStriim\Utilities\ImageUtil;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ImageUtil::class)]
class ImageUtilTest extends PHPUnit\Framework\TestCase
{
    /** @var string path to a small portrait JPEG fixture (600x900) */
    protected string $jpegFixture;

    protected function setUp(): void
    {
        $this->jpegFixture = dirname(__DIR__) . '/fixtures/author1.jpg';
    }

    /**
     * Skip the GD-dependent tests when the GD extension isn't available.
     */
    protected function requireGd(): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            $this->markTestSkipped('GD extension not available');
        }
    }

    /**
     * createThumbnail() with $clipped = true -> thumbnailClipped path.
     */
    public function testCreateThumbnailClipped(): void
    {
        $this->requireGd();
        $thumbPath = tempnam(sys_get_temp_dir(), 'BBS') . '.png';
        $created = ImageUtil::createThumbnail($this->jpegFixture, false, $thumbPath, true);
        $this->assertTrue($created);
        $this->assertFileExists($thumbPath);
        [$width, $height] = getimagesize($thumbPath);
        $this->assertEquals(ImageUtil::THUMB_RES, $width);
        $this->assertEquals(ImageUtil::THUMB_RES, $height);
        @unlink($thumbPath);
    }

    /**
     * createThumbnail() with $clipped = false -> the else branch that calls
     * thumbnailStuffed() (otherwise never exercised by the existing suite).
     */
    public function testCreateThumbnailStuffed(): void
    {
        $this->requireGd();
        $thumbPath = tempnam(sys_get_temp_dir(), 'BBS') . '.png';
        $created = ImageUtil::createThumbnail($this->jpegFixture, false, $thumbPath, false);
        $this->assertTrue($created);
        $this->assertFileExists($thumbPath);
        [$width, $height] = getimagesize($thumbPath);
        $this->assertEquals(ImageUtil::THUMB_RES, $width);
        $this->assertEquals(ImageUtil::THUMB_RES, $height);
        @unlink($thumbPath);
    }

    /**
     * thumbnailStuffed() directly, from a JPEG source.
     */
    public function testThumbnailStuffedFromJpeg(): void
    {
        $this->requireGd();
        $thumbPath = tempnam(sys_get_temp_dir(), 'BBS') . '.png';
        $created = ImageUtil::thumbnailStuffed($this->jpegFixture, false, 100, 120, $thumbPath);
        $this->assertTrue($created);
        [$width, $height] = getimagesize($thumbPath);
        $this->assertEquals(100, $width);
        $this->assertEquals(120, $height);
        @unlink($thumbPath);
    }

    /**
     * thumbnailStuffed() with $png = true -> the imagecreatefrompng() branch.
     * Builds a tiny PNG on the fly so we don't depend on a large PNG fixture.
     */
    public function testThumbnailStuffedFromPng(): void
    {
        $this->requireGd();
        $srcPath = tempnam(sys_get_temp_dir(), 'BBS') . '.png';
        $im = imagecreatetruecolor(120, 80);
        imagepng($im, $srcPath);

        $thumbPath = tempnam(sys_get_temp_dir(), 'BBS') . '.png';
        $created = ImageUtil::thumbnailStuffed($srcPath, true, 100, 100, $thumbPath);
        $this->assertTrue($created);
        [$width, $height] = getimagesize($thumbPath);
        $this->assertEquals(100, $width);
        $this->assertEquals(100, $height);
        @unlink($srcPath);
        @unlink($thumbPath);
    }

    /**
     * thumbnailClipped() with $png = true -> the imagecreatefrompng() branch
     * (line 50). Builds a tiny PNG on the fly so we don't depend on a large
     * PNG fixture.
     */
    public function testThumbnailClippedFromPng(): void
    {
        $this->requireGd();
        $srcPath = tempnam(sys_get_temp_dir(), 'BBS') . '.png';
        $im = imagecreatetruecolor(90, 90);
        imagepng($im, $srcPath);

        $thumbPath = tempnam(sys_get_temp_dir(), 'BBS') . '.png';
        $created = ImageUtil::thumbnailClipped($srcPath, true, 100, 100, $thumbPath);
        $this->assertTrue($created);
        [$width, $height] = getimagesize($thumbPath);
        $this->assertEquals(100, $width);
        $this->assertEquals(100, $height);
        @unlink($srcPath);
        @unlink($thumbPath);
    }

    /**
     * transparentImage() produces a fully-transparent image of the given size.
     */
    public function testTransparentImage(): void
    {
        $this->requireGd();
        $img = ImageUtil::transparentImage(33, 44);
        $this->assertEquals(33, imagesx($img));
        $this->assertEquals(44, imagesy($img));
        // A pixel anywhere should be fully transparent (alpha channel = 127).
        $rgba = imagecolorat($img, 16, 22);
        $alpha = ($rgba >> 24) & 0xFF;
        $this->assertEquals(127, $alpha);
    }

    /**
     * downloadImage() returns ['', ''] when the fetch fails. Uses a missing
     * local file:// URL so the failure is deterministic and needs no network.
     * A local error handler suppresses the file_get_contents() warning.
     */
    public function testDownloadImageFailure(): void
    {
        set_error_handler(function () {
            return true;
        });
        try {
            [$file, $mime] = ImageUtil::downloadImage('file:///nonexistent/bbs/image.jpg');
        } finally {
            restore_error_handler();
        }
        $this->assertSame('', $file);
        $this->assertSame('', $mime);
    }
}
