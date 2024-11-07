<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\AppData\AppSeries;
use BicBucStriim\Models\Calibrething;

trait HasSeries
{
    use HasCalibrething;
    use HasArtefacts;
    use HasLinks;
    use HasNotes;

    public const SERIES_TYPE = 6;

    /**
     * Find Calibre series or add reference to it
     * @param int $seriesId
     * @param ?string $seriesName
     * @return AppSeries
     */
    public function series($seriesId, $seriesName = null)
    {
        return new AppSeries($seriesId, $seriesName, $this->dataDir);
    }

    /**
     * Find Calibre series
     * @param int $seriesId
     * @return Calibrething|null
     */
    public function getCalibreSeries($seriesId)
    {
        return $this->getCalibreThing(self::SERIES_TYPE, $seriesId);
    }

    /**
     * Add reference to Calibre series
     * @param int $seriesId
     * @param string $seriesName
     * @return Calibrething
     */
    public function addCalibreSeries($seriesId, $seriesName)
    {
        return $this->addCalibreThing(self::SERIES_TYPE, $seriesId, $seriesName);
    }
}
