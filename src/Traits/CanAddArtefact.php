<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\AppData\DataConstants;
use BicBucStriim\Models\Artefact;
use BicBucStriim\Models\Calibrething;
use BicBucStriim\Models\R;
use BicBucStriim\Utilities\ImageUtil;
use BicBucStriim\Utilities\Thumbnails;
use Throwable;

trait CanAddArtefact
{
    # dir for bbs db
    public $dataDir = '';

    /**
     * Get the file name of a Calibre entity's thumbnail image.
     * @param ?Calibrething $calibreThing
     * @return ?Artefact file name of the thumbnail image, or null
     */
    public function getCalibreThumbnail($calibreThing)
    {
        if (is_null($calibreThing)) {
            return null;
        }
        return $calibreThing->getThumbnail();
    }

    /**
     * Change the thumbnail image for a Calibre entity.
     *
     * @param ?Calibrething $calibreThing
     * @param bool|int 	$clipped 	true = image should be clipped, else stuffed
     * @param string 	$file 		File name of the input image
     * @param string 	$mime 		Mime type of the image
     * @return ?Artefact artefact with file name of the thumbnail image, or null
     */
    public function editCalibreThumbnail($calibreThing, $clipped, $file, $mime)
    {
        if (is_null($calibreThing)) {
            return null;
        }
        if (($mime == 'image/jpeg')
        || ($mime == "image/jpg")
        || ($mime == "image/pjpeg")) {
            $png = false;
        } else {
            $png = true;
        }

        if (empty($this->dataDir)) {
            $this->dataDir = realpath('data');
        }
        // change directory & prefix depending on $calibreType
        [$thumbsDir, $prefix] = Thumbnails::getConfig($calibreThing->ctype);
        if ($calibreThing->ctype == DataConstants::BOOK_TYPE) {
            // @see Thumbnails::titleThumbnail() - using calibre id + different format
            $fname = $this->dataDir . '/' . $thumbsDir . '/' . $prefix . $calibreThing->cid . '.png';
        } else {
            $fname = $this->dataDir . '/' . $thumbsDir . '/' . $prefix . $calibreThing->id . '_thm.png';
        }
        if (file_exists($fname)) {
            unlink($fname);
        }

        try {
            $created = ImageUtil::createThumbnail($file, $png, $fname, $clipped);
        } catch (Throwable $e) {
            copy($file, $fname . '.err');
            echo $e->getMessage();
            $created = false;
        }
        if (!$created) {
            return null;
        }

        // @todo use relative url for thumbnails
        $baseDir = dirname(__DIR__, 2);
        if (str_starts_with($fname, $baseDir)) {
            $fname = '.' . substr($fname, strlen($baseDir));
        }
        $artefact = $calibreThing->getThumbnail();
        if (is_null($artefact)) {
            // Unless/until we support different types of artefacts per entity, the default is the Calibre type
            $artefact = Artefact::build($calibreThing->ctype, $fname);
            $calibreThing->addArtefact($artefact);
            $calibreThing->refctr += 1;
            R::store($calibreThing);
        } else {
            $artefact->url = $fname;
            R::store($artefact);
        }
        return $artefact;
    }

    /**
     * Delete a Calibre entity's thumbnail image.
     * @param ?Calibrething $calibreThing
     * @return bool
     */
    public function deleteCalibreThumbnail($calibreThing)
    {
        if (is_null($calibreThing)) {
            return false;
        }
        $artefact = $calibreThing->getThumbnail();
        if (is_null($artefact)) {
            return false;
        }
        $ret = unlink($artefact->url);
        $calibreThing->deleteArtefact($artefact->id);
        $calibreThing->refctr -= 1;
        R::trash($artefact);
        if ($calibreThing->refctr == 0) {
            R::trash($calibreThing);
        } else {
            R::store($calibreThing);
        }
        return $ret;
    }
}
