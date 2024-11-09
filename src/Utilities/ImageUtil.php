<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Utilities;

class ImageUtil
{
    # Thumbnail dimension (they are square)
    public const THUMB_RES = 160;

    /**
     * Create a square thumbnail
     * @param  string 	$cover      path to input image
     * @param  bool 	$png      	true if the input is a PNG file, false = JPEG
     * @param  string 	$thumb_path path for thumbnail storage
     * @param  bool 	$clipped    true if the image is clipped, false if the image is stuffed
     * @return bool             	true = thumbnail created, else false
     */
    public static function createThumbnail($cover, $png, $thumb_path, $clipped)
    {
        if ($clipped) {
            $created = ImageUtil::thumbnailClipped($cover, $png, self::THUMB_RES, self::THUMB_RES, $thumb_path);
        } else {
            $created = ImageUtil::thumbnailStuffed($cover, $png, self::THUMB_RES, self::THUMB_RES, $thumb_path);
        }
        return $created;
    }

    /**
     * Create a square thumbnail by clipping the largest possible square from the cover
     * @param  string 	$cover      path to input image
     * @param  bool 	$png      	true if the input is a PNG file, false = JPEG
     * @param  int 	 	$newwidth   required thumbnail width
     * @param  int 		$newheight  required thumbnail height
     * @param  string 	$thumb_path path for thumbnail storage
     * @return bool             	true = thumbnail created, else false
     */
    public static function thumbnailClipped($cover, $png, $newwidth, $newheight, $thumb_path)
    {
        [$width, $height] = getimagesize($cover);
        $thumb = imagecreatetruecolor($newwidth, $newheight);
        if ($png) {
            $source = imagecreatefrompng($cover);
        } else {
            $source = imagecreatefromjpeg($cover);
        }
        $minwh = min([$width, $height]);
        $newx = ($width / 2) - ($minwh / 2);
        $newy = ($height / 2) - ($minwh / 2);
        $inbetween = imagecreatetruecolor($minwh, $minwh);
        imagecopy($inbetween, $source, 0, 0, $newx, $newy, $minwh, $minwh);
        imagecopyresized($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $minwh, $minwh);
        $created = imagepng($thumb, $thumb_path);
        return $created;
    }

    /**
     * Create a square thumbnail by stuffing the cover at the edges
     * @param  string 	$cover      path to input image
     * @param  bool 	$png      	true if the input is a PNG file, false = JPEG
     * @param  int 	 	$newwidth   required thumbnail width
     * @param  int 		$newheight  required thumbnail height
     * @param  string 	$thumb_path path for thumbnail storage
     * @return bool             	true = thumbnail created, else false
     */
    public static function thumbnailStuffed($cover, $png, $newwidth, $newheight, $thumb_path)
    {
        [$width, $height] = getimagesize($cover);
        $thumb = self::transparentImage($newwidth, $newheight);
        if ($png) {
            $source = imagecreatefrompng($cover);
        } else {
            $source = imagecreatefromjpeg($cover);
        }
        $dstx = 0;
        $dsty = 0;
        $maxwh = max([$width, $height]);
        if ($height > $width) {
            $diff = $maxwh - $width;
            $dstx = (int) $diff / 2;
        } else {
            $diff = $maxwh - $height;
            $dsty = (int) $diff / 2;
        }
        $inbetween = self::transparentImage($maxwh, $maxwh);
        imagecopy($inbetween, $source, $dstx, $dsty, 0, 0, $width, $height);
        imagecopyresampled($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $maxwh, $maxwh);
        $created = imagepng($thumb, $thumb_path);
        imagedestroy($thumb);
        imagedestroy($inbetween);
        imagedestroy($source);
        return $created;
    }

    /**
     * Create an image with transparent background.
     *
     * see http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php#279310
     *
     * @param  int 	$width
     * @param  int 	$height
     * @return object|resource image
     */
    public static function transparentImage($width, $height)
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $backgr = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $backgr);
        return $img;
    }

    /**
     * Download image, save to tmpFile and get mimeType
     * @param string $imageUrl
     * @return array{0: string, 1: string}
     */
    public static function downloadImage($imageUrl)
    {
        // @see https://foundation.wikimedia.org/wiki/Policy:User-Agent_policy
        $context = stream_context_create([
            'http' => [
                'follow_location' => true,
                'timeout' => 20,
                'user_agent' => 'EPubLoader/3.5 (https://github.com/mikespub-org/epub-loader)',
            ],
        ]);
        $imageData = file_get_contents($imageUrl, false, $context);
        if ($imageData === false) {
            //var_dump($http_response_header);
            return ['', ''];
        }
        $tmpFile = tempnam(sys_get_temp_dir(), 'BBS');
        if (!file_put_contents($tmpFile, $imageData)) {
            return ['', ''];
        }

        $mimeType = '';
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($tmpFile);
        } elseif (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimeType = finfo_file($finfo, $tmpFile);
            finfo_close($finfo);
        }
        if (empty($mimeType) || !str_starts_with($mimeType, 'image/')) {
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $mimeType = 'image/' . $extension;
            }
        }

        return [$tmpFile, $mimeType];
    }
}
