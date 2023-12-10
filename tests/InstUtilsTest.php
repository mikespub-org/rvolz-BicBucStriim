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
require 'src/inst_utils.php';

/**
 * @covers ::find_gd_version
 */
class InstUtilsTest extends PHPUnit\Framework\TestCase
{
    public function setUp(): void {}

    public function tearDown(): void {}

    public function testFindGdVersion()
    {
        $this->assertEquals("2.1", find_gd_version("gd version 2.1"));
        $this->assertEquals("2.1.0", find_gd_version("gd version 2.1.0"));
        $this->assertEquals("2.1", find_gd_version("gd headers version 2.1"));
        $this->assertEquals("2.1.0", find_gd_version("gd headers version 2.1.0"));
        $this->assertEquals("2.1.0-alpha", find_gd_version("GD headers Version 2.1.0-alpha "));
    }
}
