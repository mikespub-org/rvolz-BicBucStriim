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
 * RedBeanPHP FUSE model for 'idtemplate' bean
 * @property mixed $id
 * @property mixed $name
 * @property mixed $val
 * @property mixed $label
 */
class Idtemplate extends Model
{
    /**
     * Summary of build
     * @param mixed $name
     * @param mixed $val
     * @param mixed $label
     * @return self
     */
    public static function build($name, $val, $label)
    {
        $template = self::cast(R::dispense('idtemplate'));
        $template->name = $name;
        $template->val = $val;
        $template->label = $label;
        return $template;
    }
}
