<?php

use BicBucStriim\Utilities\L10n;

/**
 * @covers \BicBucStriim\Utilities\L10n
 */
class L10nTest extends PHPUnit\Framework\TestCase
{
    public function setUp(): void {}

    public function tearDown(): void {}

    ##
    # Test array functionality
    #
    public function testArrayGet()
    {
        $langde = L10n::loadMessages('de');

        $l10n = new L10n('de');
        $this->assertEquals($langde['admin'], $l10n->message('admin'));
        $this->assertEquals($langde['admin'], $l10n['admin']);
        $this->assertEquals('Undefined message!', $l10n['bla bla']);
    }
}
