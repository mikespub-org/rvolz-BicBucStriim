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
 * RedBeanPHP FUSE model for 'artefact' bean
 * @property mixed $id
 * @property mixed $atype
 * @property mixed $url
 */
class Artefact extends Model
{
    /**
     * Summary of build
     * @param mixed $atype
     * @param mixed $url
     * @return self
     */
    public static function build($atype, $url)
    {
        $artefact = self::cast(R::dispense('artefact'));
        $artefact->atype = $atype;
        $artefact->url = $url;
        return $artefact;
    }
}
