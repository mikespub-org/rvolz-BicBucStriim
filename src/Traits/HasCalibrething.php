<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\Models\Calibrething;
use BicBucStriim\Models\R;

trait HasCalibrething
{
    /**
     * Find a Calibre item.
     * @param int 	$calibreType
     * @param int 	$calibreId
     * @return ?Calibrething the Calibre item
     */
    public function getCalibreThing($calibreType, $calibreId)
    {
        $calibreThing = R::findOne(
            'calibrething',
            ' ctype = :type and cid = :id',
            [
                ':type' => $calibreType,
                'id' => $calibreId,
            ]
        );
        if (!is_null($calibreThing)) {
            $calibreThing = Calibrething::cast($calibreThing);
        }
        return $calibreThing;
    }

    /**
     * Add a new reference to a Calibre item.
     *
     * Calibre items are identified by type, ID and name. ID and name
     * are used to find items that can be renamed, like authors.
     *
     * @param int 		$calibreType
     * @param int 		$calibreId
     * @param string 	$calibreName
     * @return Calibrething the Calibre item
     */
    public function addCalibreThing($calibreType, $calibreId, $calibreName)
    {
        $calibreThing = Calibrething::build($calibreType, $calibreId, $calibreName);
        $id = R::store($calibreThing);
        return $calibreThing;
    }
}
