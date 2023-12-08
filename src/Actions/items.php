<?php
/**
 * Utility items
 */

namespace BicBucStriim\Actions;

# A database item class
class Item {}

# Configuration utilities for BBS
class Encryption extends Item
{
    public $key;
    public $text;
}
class ConfigMailer extends Item
{
    public $key;
    public $text;
}
class ConfigTtsOption extends Item
{
    public $key;
    public $text;
}
class IdUrlTemplate extends Item
{
    public $name;
    public $val;
    public $label;
}
