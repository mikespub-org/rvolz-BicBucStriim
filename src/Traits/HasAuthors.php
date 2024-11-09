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
     * @return 		?Artefact file name of the thumbnail image, or null
     */
    public function authorThumbnail($authorId)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        return $this->getCalibreThumbnail($calibreThing);
    }

    /**
     * Return all links defined for an author.
     * @param int 	$authorId 	Calibre ID for the author
     * @return array<Link> author links
     */
    public function authorLinks($authorId)
    {
        $calibreThing = $this->getCalibreThing(self::AUTHOR_TYPE, $authorId);
        return $this->getCalibreLinks($calibreThing);
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
}
