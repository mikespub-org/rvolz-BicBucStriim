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
 * RedBeanPHP FUSE model for 'link' bean
 * @property mixed $id
 * @property mixed $ltype
 * @property mixed $label
 * @property mixed $url
 */
class Link extends Model
{
    /**
     * Summary of build
     * @param mixed $ltype
     * @param mixed $label
     * @param mixed $url
     * @return self
     */
    public static function build($ltype, $label, $url)
    {
        $link = self::cast(R::dispense('link'));
        $link->ltype = $ltype;
        $link->label = $label;
        $link->url = $url;
        return $link;
    }
}
