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
use BicBucStriim\Models\Link;
use BicBucStriim\Models\R;
use Exception;

trait CanAddLink
{
    /**
     * Return all links defined for a Calibre entity.
     * @param ?Calibrething $calibreThing
     * @return array<Link> entity links
     */
    public function getCalibreLinks($calibreThing)
    {
        if (is_null($calibreThing)) {
            return [];
        }
        return $calibreThing->getLinks();
    }

    /**
     * Add a link for a Calibre entity.
     * @param Calibrething $calibreThing
     * @param string 	$label 		link label
     * @param string 	$url 		link url
     * @return Link     created link
     */
    public function addCalibreLink($calibreThing, $label, $url)
    {
        // Unless/until we support different types of links per entity, the default is the Calibre type
        $link = Link::build($calibreThing->ctype, $label, $url);
        $calibreThing->addLink($link);
        $calibreThing->refctr += 1;
        R::store($calibreThing);
        return $link;
    }

    /**
     * Delete a link from the collection defined for a Calibre entity.
     * @param ?Calibrething $calibreThing
     * @param int   $linkId ID of the link
     * @return bool
     */
    public function deleteCalibreLink($calibreThing, $linkId)
    {
        if (is_null($calibreThing)) {
            return false;
        }
        try {
            $link = $calibreThing->ownLinkList[$linkId];
        } catch (Exception $e) {
            $link = null;
        }
        if (is_null($link)) {
            return false;
        }
        /** @var ?Link $link */
        $calibreThing->deleteLink($link->id);
        R::trash($link);
        $calibreThing->refctr -= 1;
        if ($calibreThing->refctr == 0) {
            R::trash($calibreThing);
        } else {
            R::store($calibreThing);
        }
        return true;
    }
}
