<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\AppData\AppTag;
use BicBucStriim\Models\Calibrething;

trait HasTags
{
    use HasCalibrething;
    use HasArtefacts;
    use HasLinks;
    use HasNotes;

    public const TAG_TYPE = 7;

    /**
     * Find Calibre tag or add reference to it
     * @param int $tagId
     * @param ?string $tagName
     * @return AppTag
     */
    public function tag($tagId, $tagName = null)
    {
        return new AppTag($tagId, $tagName, $this->dataDir);
    }

    /**
     * Find Calibre tag
     * @param int $tagId
     * @return Calibrething|null
     */
    public function getCalibreTag($tagId)
    {
        return $this->getCalibreThing(self::TAG_TYPE, $tagId);
    }

    /**
     * Add reference to Calibre tag
     * @param int $tagId
     * @param string $tagName
     * @return Calibrething
     */
    public function addCalibreTag($tagId, $tagName)
    {
        return $this->addCalibreThing(self::TAG_TYPE, $tagId, $tagName);
    }
}
