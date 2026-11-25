<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Utilities;

use BicBucStriim\AppData\DataConstants;

class Thumbnails
{
    public const CONFIG = [
        DataConstants::BOOK_TYPE => ['titles', 'thumb_'],
        DataConstants::AUTHOR_TYPE => ['authors', 'author_'],
        DataConstants::SERIES_TYPE => ['series', 'series_'],
    ];

    # dir for bbs db (relative to root)
    public $dataUri = '';
    # dir for bbs db (real path)
    public $dataDir = '';
    # dir for generated title thumbs (real path)
    public $titlesDir = '';
    # dir for generated author thumbs (real path)
    public $authorsDir = '';
    # dir for generated series thumbs (real path) - @todo
    public $seriesDir = '';

    /**
     * Get thumbnail configuration for a Calibre entity type
     * @param int $type
     * @return array{0: string, 1: string}
     */
    public static function getConfig($type)
    {
        if (!isset(self::CONFIG[$type])) {
            return self::CONFIG[DataConstants::BOOK_TYPE];
        }
        return self::CONFIG[$type];
    }

    /**
     * Summary of __construct
     * @param string $dataDir (relative to root)
     */
    public function __construct($dataDir)
    {
        // use relative to root for uri
        $this->dataUri = $dataDir;
        // set data dir for thumbnails (real path)
        $this->dataDir = realpath($dataDir);
        [$thumbsDir, ] = self::getConfig(DataConstants::BOOK_TYPE);
        $this->titlesDir = $this->dataDir . '/' . $thumbsDir;
        if (!file_exists($this->titlesDir)) {
            mkdir($this->titlesDir);
        }
        [$thumbsDir, ] = self::getConfig(DataConstants::AUTHOR_TYPE);
        $this->authorsDir = $this->dataDir . '/' . $thumbsDir;
        if (!file_exists($this->authorsDir)) {
            mkdir($this->authorsDir);
        }
    }

    /**
     * Checks if the thumbnail for a book was already generated.
     * @param int 	$id 	Calibre book ID
     * @return 	bool	true if the thumbnail file exists, else false
     */
    public function isTitleThumbnailAvailable($id)
    {
        $thumb_name = 'thumb_' . $id . '.png';
        $thumb_path = $this->titlesDir . '/' . $thumb_name;
        return file_exists($thumb_path);
    }

    /**
     * Returns the path to a thumbnail of a book's cover image or NULL.
     *
     * If a thumbnail doesn't exist the function tries to make one from the cover.
     * The thumbnail dimension generated is 160*160, which is more than what
     * jQuery Mobile requires (80*80). However, if we send the 80*80 resolution the
     * thumbnails look very pixely.
     *
     * The function expects the input file to be a JPEG.
     *
     * @param  int 		$id 		book id
     * @param  ?string 	$cover 	    path to cover image
     * @param  bool|int $clipped	true = clip the thumbnail, else stuff it
     * @return ?string real thumbnail path or NULL
     */
    public function titleThumbnail($id, $cover, $clipped)
    {
        $thumb_name = 'thumb_' . $id . '.png';
        $thumb_path = $this->titlesDir . '/' . $thumb_name;
        if (file_exists($thumb_path)) {
            return $thumb_path;
        }
        if (is_null($cover)) {
            return null;
        }
        $created = ImageUtil::createThumbnail($cover, false, $thumb_path, $clipped);
        if (!$created) {
            return null;
        }
        return $thumb_path;
    }

    /**
     * Delete existing thumbnail files
     * @return bool false if there was an error
     */
    public function clearThumbnails()
    {
        $cleared1 = $this->clearDirectory($this->titlesDir, 'thumb_');
        $cleared2 = $this->clearDirectory($this->authorsDir, 'author_');
        return $cleared1 && $cleared2;
    }

    /**
     * Summary of clearDirectory
     * @param string $dirPath
     * @param string $prefix
     * @return bool
     */
    public function clearDirectory($dirPath, $prefix)
    {
        $dh = opendir($dirPath);
        if (!$dh) {
            return false;
        }
        $cleared = true;
        while (($file = readdir($dh)) !== false) {
            $fn = $dirPath . '/' . $file;
            if (preg_match("/^$prefix.*\\.png$/", $file) && file_exists($fn)) {
                if (!@unlink($fn)) {
                    $cleared = false;
                    break;
                }
            }
        }
        closedir($dh);
        return $cleared;
    }
}
