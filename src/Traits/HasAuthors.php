<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\AppData\AppAuthor;
use BicBucStriim\AppData\DataConstants;
use BicBucStriim\Models\Artefact;
use BicBucStriim\Models\Calibrething;
use BicBucStriim\Models\Link;
use BicBucStriim\Models\Note;

trait HasAuthors
{
    use HasCalibrething;
    use CanAddArtefact;
    use CanAddLink;
    use CanAddNote;

    public const AUTHOR_TYPE = DataConstants::AUTHOR_TYPE;

    /**
     * Find Calibre author or add reference to it
     * @param int $authorId
     * @param ?string $authorName
     * @return AppAuthor
     */
    public function author($authorId, $authorName = null)
    {
        return new AppAuthor($authorId, $authorName, $this->dataDir);
    }

    /**
     * Find Calibre author
     * @param int $authorId
     * @return Calibrething|null
     */
    public function getCalibreAuthor($authorId)
    {
        return $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
    }

    /**
     * Add reference to Calibre author
     * @param int $authorId
     * @param string $authorName
     * @return Calibrething
     */
    public function addCalibreAuthor($authorId, $authorName)
    {
        return $this->addCalibreThing(self::AUTHOR_TYPE, $authorId, $authorName);
    }

    /**
     * Get the file name of an author's thumbnail image.
     * @param int 	$authorId 	Calibre ID of the author
     * @deprecated 3.5.5 use bbs()->author($authorId)->getThumbnail()
     * @return 		?Artefact file name of the thumbnail image, or null
     */
    public function getAuthorThumbnail($authorId)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        return $this->getCalibreThumbnail($calibreThing);
    }

    /**
     * Change the thumbnail image for an author.
     *
     * @param int 		$authorId 	Calibre ID of the author
     * @param string 	$authorName Calibre name of the author
     * @param bool|int 	$clipped 	true = image should be clipped, else stuffed
     * @param string 	$file 		File name of the input image
     * @param string 	$mime 		Mime type of the image
     * @deprecated 3.5.5 use bbs()->author($authorId, $authorName)->editThumbnail($clipped, $file, $mime)
     * @return ?Artefact artefact with file name of the thumbnail image, or null
     */
    public function editAuthorThumbnail($authorId, $authorName, $clipped, $file, $mime)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            $calibreThing = $this->addCalibreThing(self::AUTHOR_TYPE, $authorId, $authorName);
        }

        return $this->editCalibreThumbnail($calibreThing, $clipped, $file, $mime);
    }

    /**
     * Delete an author's thumbnail image.
     *
     * Deletes the thumbnail artefact, and then the CalibreThing if that
     * has no further references.
     *
     * @param int 	$authorId 	Calibre ID of the author
     * @deprecated 3.5.5 use bbs()->author($authorId)->deleteThumbnail()
     * @return 	bool	true if deleted, else false
     */
    public function deleteAuthorThumbnail($authorId)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        return $this->deleteCalibreThumbnail($calibreThing);
    }

    /**
     * Return all links defined for an author.
     * @param int 	$authorId 	Calibre ID for the author
     * @deprecated 3.5.5 use bbs()->author($authorId)->getLinks()
     * @return array<Link> author links
     */
    public function authorLinks($authorId)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        return $this->getCalibreLinks($calibreThing);
    }

    /**
     * Add a link for an author.
     * @param int 		$authorId 	Calibre ID for author
     * @param string 	$authorName 	Calibre name for author
     * @param string 	$label 		link label
     * @param string 	$url 		link url
     * @deprecated 3.5.5 use bbs()->author($authorId, $authorName)->addLink($label, $url)
     * @return Link 	created author link
     */
    public function addAuthorLink($authorId, $authorName, $label, $url)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            $calibreThing = $this->addCalibreThing(self::AUTHOR_TYPE, $authorId, $authorName);
        }
        return $this->addCalibreLink($calibreThing, $label, $url);
    }

    /**
     * Delete a link from the collection defined for an author.
     * @param int 	$authorId 	Calibre ID for author
     * @param int 	$linkId 		ID of the author link
     * @deprecated 3.5.5 use bbs()->author($authorId)->deleteLink($linkId)
     * @return boolean 			true if the link was deleted, else false
     */
    public function deleteAuthorLink($authorId, $linkId)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        return $this->deleteCalibreLink($calibreThing, $linkId);
    }

    /**
     * Get the note text for an author.
     * @param int 	$authorId 	Calibre ID of the author
     * @return 		?Note 		note text or null
     */
    public function authorNote($authorId)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        return $this->getCalibreNote($calibreThing);
    }

    /**
     * Set the note text for an author.
     * @param int 		$authorId 	Calibre ID for author
     * @param string 	$authorName 	Calibre name for author
     * @param string 	$mime 		mime type for the note's content
     * @param string 	$noteText	note content
     * @deprecated 3.5.5 use bbs()->author($authorId, $authorName)->editNote($mime, $noteText)
     * @return Note 	created/edited note
     */
    public function editAuthorNote($authorId, $authorName, $mime, $noteText)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            $calibreThing = $this->addCalibreThing(self::AUTHOR_TYPE, $authorId, $authorName);
        }
        return $this->editCalibreNote($calibreThing, $mime, $noteText);
    }

    /**
     * Delete the note for an author
     * @param int 	$authorId 	Calibre ID for author
     * @deprecated 3.5.5 use bbs()->author($authorId)->deleteNote()
     * @return boolean 			true if the note was deleted, else false
     */
    public function deleteAuthorNote($authorId)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        return $this->deleteCalibreNote($calibreThing);
    }
}
