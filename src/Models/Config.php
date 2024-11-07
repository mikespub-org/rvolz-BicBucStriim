<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Models;

/**
 * RedBeanPHP FUSE model for 'config' bean
 * @property mixed $id
 * @property mixed $name
 * @property mixed $val
 */
class Config extends Model
{
    /**
     * Summary of build
     * @param mixed $name
     * @param mixed $val
     * @return self
     */
    public static function build($name, $val)
    {
        $config = self::cast(R::dispense('config'));
        $config->name = $name;
        $config->val = $val;
        return $config;
    }
}
