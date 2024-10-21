<?php

use BicBucStriim\Utilities\L10n;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(L10n::class)]
class L10nTest extends PHPUnit\Framework\TestCase
{
    public function setUp(): void {}

    public function tearDown(): void {}

    ##
    # Test array functionality
    #
    public function testArrayGet(): void
    {
        $langde = L10n::loadMessages('de');

        $l10n = new L10n('de');
        $this->assertEquals($langde['admin'], $l10n->message('admin'));
        $this->assertEquals(true, isset($l10n['admin']));
        $this->assertEquals($langde['admin'], $l10n['admin']);
        $this->assertEquals('Undefined message!', $l10n['bla bla']);
    }

    public function testDefaultLanguage(): void
    {
        $langen = L10n::loadMessages('en');

        $l10n = new L10n('en');
        $this->assertEquals($langen['admin'], $l10n->message('admin'));
    }

    public function testInvalidLanguage(): void
    {
        $langen = L10n::loadMessages('en');

        $l10n = new L10n('na');
        $this->assertEquals($langen['admin'], $l10n->message('admin'));
    }

    public function testEmptyMessage(): void
    {
        $l10n = new L10n('en');
        $this->assertEquals('', $l10n->message(null));
        $this->assertEquals('', $l10n->message(''));
    }
}
