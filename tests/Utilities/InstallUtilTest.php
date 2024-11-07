<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2016 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 * Test installation check utilities.
 *
 */

use BicBucStriim\Utilities\InstallUtil;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InstallUtil::class)]
class InstallUtilTest extends PHPUnit\Framework\TestCase
{
    public function setUp(): void {}

    public function tearDown(): void {}

    public function testFindGdVersion(): void
    {
        $this->assertEquals("2.1", InstallUtil::find_gd_version("gd version 2.1"));
        $this->assertEquals("2.1.0", InstallUtil::find_gd_version("gd version 2.1.0"));
        $this->assertEquals("2.1", InstallUtil::find_gd_version("gd headers version 2.1"));
        $this->assertEquals("2.1.0", InstallUtil::find_gd_version("gd headers version 2.1.0"));
        $this->assertEquals("2.1.0-alpha", InstallUtil::find_gd_version("GD headers Version 2.1.0-alpha "));
    }
}
