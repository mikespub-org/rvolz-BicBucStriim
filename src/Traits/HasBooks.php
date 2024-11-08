<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\AppData\AppBook;
use BicBucStriim\AppData\DataConstants;
use BicBucStriim\Models\Calibrething;

trait HasBooks
{
    use HasCalibrething;

    public const BOOK_TYPE = DataConstants::BOOK_TYPE;

    /**
     * Find Calibre book or add reference to it
     * @param int $bookId
     * @param ?string $bookTitle
     * @return AppBook
     */
    public function book($bookId, $bookTitle = null)
    {
        return new AppBook($bookId, $bookTitle, $this->dataDir);
    }

    /**
     * Find Calibre book
     * @param int $bookId
     * @return Calibrething|null
     */
    public function getCalibreBook($bookId)
    {
        return $this->getCalibreThing(self::BOOK_TYPE, $bookId);
    }

    /**
     * Add reference to Calibre book
     * @param int $bookId
     * @param string $bookTitle
     * @return Calibrething
     */
    public function addCalibreBook($bookId, $bookTitle)
    {
        return $this->addCalibreThing(self::BOOK_TYPE, $bookId, $bookTitle);
    }
}
