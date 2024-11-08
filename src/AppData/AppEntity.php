<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\AppData;

use BicBucStriim\Models\Artefact;
use BicBucStriim\Models\Calibrething;
use BicBucStriim\Models\Link;
use BicBucStriim\Models\Note;
use BicBucStriim\Traits\HasCalibrething;
use BicBucStriim\Traits\CanAddArtefact;
use BicBucStriim\Traits\CanAddLink;
use BicBucStriim\Traits\CanAddNote;
use BicBucStriim\Utilities\ImageUtil;

/**
 * Add thumbnail, links & note to Calibre entities in BBS DB
 */
class AppEntity
{
    use HasCalibrething;
    use CanAddArtefact;
    use CanAddLink;
    use CanAddNote;

    public const CALIBRE_TYPE = DataConstants::ENTITY_TYPE;

    # dir for bbs db
    public $dataDir = '';
    public ?Calibrething $calibreThing = null;

    public function __construct($calibreId, $calibreName = null, $dataDir = 'data')
    {
        $this->calibreThing = $this->getCalibreThing(static::CALIBRE_TYPE, $calibreId);
        if (!isset($this->calibreThing) && isset($calibreName)) {
            $this->calibreThing = $this->addCalibreThing(static::CALIBRE_TYPE, $calibreId, $calibreName);
        }
        // set data dir for thumbnails
        $this->dataDir = realpath($dataDir);
    }

    /**
     * Get the Calibre entity (RedBeanPHP FUSE model)
     * @return Calibrething|null
     */
    public function getEntity()
    {
        return $this->calibreThing;
    }

    /**
     * Get the file name of a Calibre entity's thumbnail image.
     * @return ?Artefact file name of the thumbnail image, or null
     */
    public function getThumbnail()
    {
        return $this->getCalibreThumbnail($this->calibreThing);
    }

    /**
     * Change the thumbnail image for a Calibre entity.
     *
     * @param bool|int 	$clipped 	true = image should be clipped, else stuffed
     * @param string 	$file 		File name of the input image
     * @param string 	$mime 		Mime type of the image
     * @return 			bool file name of the thumbnail image, or null
     */
    public function editThumbnail($clipped, $file, $mime)
    {
        return $this->editCalibreThumbnail($this->calibreThing, $clipped, $file, $mime);
    }

    /**
     * Download image and set thumbnail for this Calibre entity
     * @param string    $imageUrl   image to download
     * @param bool|int 	$clipped 	true = image should be clipped, else stuffed
     * @return bool
     */
    public function setImage($imageUrl, $clipped)
    {
        [$file, $mime] = ImageUtil::downloadImage($imageUrl);
        return $this->editThumbnail($clipped, $file, $mime);
    }

    /**
     * Delete a Calibre entity's thumbnail image.
     *
     * Deletes the thumbnail artefact, and then the CalibreThing if that
     * has no further references.
     *
     * @return 	bool	true if deleted, else false
     */
    public function deleteThumbnail()
    {
        return $this->deleteCalibreThumbnail($this->calibreThing);
    }

    /**
     * Return all links defined for a Calibre entity.
     * @return array<Link> entity links
     */
    public function getLinks()
    {
        return $this->getCalibreLinks($this->calibreThing);
    }

    /**
     * Add a link for a Calibre entity.
     * @param string 	$label 	link label
     * @param string 	$url 	link url
     * @return Link 	created author link
     */
    public function addLink($label, $url)
    {
        return $this->addCalibreLink($this->calibreThing, $label, $url);
    }

    /**
     * Delete a link from the collection defined for a Calibre entity.
     * @param int 	$linkId 	ID of the link
     * @return boolean 			true if the link was deleted, else false
     */
    public function deleteLink($linkId)
    {
        return $this->deleteCalibreLink($this->calibreThing, $linkId);
    }

    /**
     * Get the note text for a Calibre entity.
     * @return ?Note note text or null
     */
    public function getNote()
    {
        return $this->getCalibreNote($this->calibreThing);
    }

    /**
     * Set the note text for a Calibre entity.
     * @param string 	$mime 		mime type for the note's content
     * @param string 	$noteText	note content
     * @return Note 	created/edited note
     */
    public function editNote($mime, $noteText)
    {
        return $this->editCalibreNote($this->calibreThing, $mime, $noteText);
    }

    /**
     * Delete the note for a Calibre entity
     * @return boolean true if the note was deleted, else false
     */
    public function deleteNote()
    {
        return $this->deleteCalibreNote($this->calibreThing);
    }
}
