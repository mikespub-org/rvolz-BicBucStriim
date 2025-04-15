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
 * RedBeanPHP FUSE model for 'note' bean
 * @property mixed $id
 * @property mixed $ntype
 * @property mixed $mime
 * @property mixed $ntext
 */
class Note extends Model
{
    /**
     * Summary of build
     * @param mixed $ntype
     * @param mixed $mime
     * @param mixed $ntext
     * @return self
     */
    public static function build($ntype, $mime, $ntext)
    {
        $note = self::cast(R::dispense('note'));
        $note->ntype = $ntype;
        $note->mime = $mime;
        $note->ntext = $ntext;
        return $note;
    }
}
