<?php
/**
 * Utility items
 */

# A database item class
class Item {}

# Configuration utilities for BBS
class Encryption extends Item
{
    public $key;
    public $text;
}
class ConfigMailer extends Item
{
    public $key;
    public $text;
}
class ConfigTtsOption extends Item
{
    public $key;
    public $text;
}
class IdUrlTemplate extends Item
{
    public $name;
    public $val;
    public $label;
}

/**
 * Class UrlInfo contains information on how to construct URLs
 */
class UrlInfo
{
    /**
     * @var string $protocol - protocol used for access, default 'http'
     */
    public $protocol = 'http';
    /**
     * @var string $host - hostname or ip address used for access
     */
    public $host;

    public function __construct()
    {
        $na = func_num_args();
        if ($na == 2) {
            $fhost = func_get_arg(0);
            if (!is_null($fhost) && $fhost != 'unknown') {
                $this->host = $fhost;
            }
            $fproto = func_get_arg(1);
            if (!is_null($fproto)) {
                $this->protocol = $fproto;
            }
        } else {
            $ffw = func_get_arg(0);
            $ffws = preg_split('/;/', $ffw, -1, PREG_SPLIT_NO_EMPTY);
            $opts = [];
            foreach ($ffws as $ffwi) {
                $ffwis = preg_split('/=/', $ffwi, -1);
                $opts[$ffwis[0]] = $ffwis[1];
            }
            if (isset($opts['by'])) {
                $this->host = $opts['by'];
            }
            if (isset($opts['proto'])) {
                $this->protocol = $opts['proto'];
            }
        }
    }

    public function __toString()
    {
        return "UrlInfo{ protocol: $this->protocol, host: $this->host}";
    }

    public function is_valid()
    {
        return (!empty($this->host));
    }
}

class Utilities
{
    /**
     * Return the true path of a book.
     *
     * Works around a strange feature of Calibre where middle components of names are capitalized,
     * eg "Aliette de Bodard" -> "Aliette De Bodard".
     * The directory name uses the capitalized form, the book path stored in the DB uses the original
     * form.
     * @param  string $cd   Calibre library directory
     * @param  string $bp   book path, as stored in the DB
     * @param  string $file file name
     * @return string       the filesystem path to the book file
     */
    public static function bookPath($cd, $bp, $file)
    {
        try {
            $path = $cd . '/' . $bp . '/' . $file;
            stat($path);
        } catch (Exception $e) {
            $p = explode("/", $bp);
            $path = $cd . '/' . ucwords($p[0]) . '/' . $p[1] . '/' . $file;
        }
        return $path;
    }

    public const MIME_EPUB = 'application/epub+zip';

    /**
     * Check if a string starts with a substring.
     *
     * Works around a strange feature of Calibre where middle components of names are capitalized,
     * eg "Aliette de Bodard" -> "Aliette De Bodard".
     * The directory name uses the capitalized form, the book path stored in the DB uses the original
     * form.
     * @param  string $haystack String to be searched
     * @param  string $needle   String to search for
     * @return boolean          true if $haystack starts with $needle, case insensitive
     */
    public static function stringStartsWith($haystack, $needle)
    {
        return (stripos($haystack, $needle) === 0);
    }

    /**
     * Return the MIME type for an ebook file.
     *
     * To reduce search time the function checks first wether the file
     * has a well known extension. If not two functions are tried. If all fails
     * 'application/force-download' is returned to force the download of the
     * unknown format.
     *
     * @param  string $file_path path to ebook file
     * @return string            MIME type
     */
    public static function titleMimeType($file_path)
    {
        $mtype = '';

        if (preg_match('/epub$/', $file_path) == 1) {
            return Utilities::MIME_EPUB;
        } elseif (preg_match('/(mobi|azw)$/', $file_path) == 1) {
            return 'application/x-mobipocket-ebook';
        } elseif (preg_match('/azw(1|2)$/', $file_path) == 1) {
            return 'application/vnd.amazon.ebook';
        } elseif (preg_match('/azw3$/', $file_path) == 1) {
            return 'application/x-mobi8-ebook';
        } elseif (preg_match('/pdf$/', $file_path) == 1) {
            return 'application/pdf';
        } elseif (preg_match('/txt$/', $file_path) == 1) {
            return 'text/plain';
        } elseif (preg_match('/html$/', $file_path) == 1) {
            return 'text/html';
        } elseif (preg_match('/zip$/', $file_path) == 1) {
            return 'application/zip';
        }

        if (function_exists('mime_content_type')) {
            $mtype = mime_content_type($file_path);
        } elseif (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mtype = finfo_file($finfo, $file_path);
            finfo_close($finfo);
        }

        if ($mtype == '') {
            $mtype = 'application/force-download';
        }

        return $mtype;
    }

    /**
     * Create an image with transparent background.
     *
     * see http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php#279310
     *
     * @param  int  $width
     * @param  int  $height
     * @return object image
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
     * Get root url
     * @param \BicBucStriim\App $app
     * @return string root url
     */
    public static function getRootUrl($app)
    {
        $globalSettings = $app->config('globalSettings');

        if ($globalSettings[RELATIVE_URLS] == '1') {
            $root = rtrim($app->request()->getRootUri(), "/");
        } else {
            // Get forwarding information, if available
            $info = static::getForwardingInfo($app->request->headers);
            if (is_null($info) || !$info->is_valid()) {
                // No forwarding info available
                $root = rtrim($app->request()->getUrl() . $app->request()->getRootUri(), "/");
            } else {
                // Use forwarding info
                $app->getLog()->debug("getRootUrl: Using forwarding information " . $info);
                $root = $info->protocol . '://' . $info->host . $app->request()->getRootUri();
            }
        }
        $app->getLog()->debug("getRootUrl: Using root url " . $root);
        return $root;
    }

    /**
     * Return a UrlInfo instance if the request contains forwarding information, or null if not.
     *
     * First we look for the standard 'Forwarded' header from RFC 7239, then for the non-standard X-Forwarded-... headers.
     *
     * @param \Slim\Http\Headers $headers
     * @return null|\UrlInfo
     */
    public static function getForwardingInfo($headers)
    {
        $info = null;
        $forwarded = $headers->get('Forwarded');
        if (!is_null($forwarded)) {
            $info = new \UrlInfo($forwarded);
        } else {
            $fhost = $headers->get('X-Forwarded-Host');
            $fproto = $headers->get('X-Forwarded-Proto');
            if (!is_null($fhost)) {
                $info = new \UrlInfo($fhost, $fproto);
            }
        }
        return $info;
    }

    /**
     * Returns the user language, priority:
     * 1. Language in $_GET['lang']
     * 2. Language in $_SESSION['lang']
     * 3. HTTP_ACCEPT_LANGUAGE
     * 4. Fallback language
     *
     * @param array $allowedLangs list of existing languages
     * @param string $fallbackLang id of the fallback language if nothing helps
     * @return string the user language, like 'de' or 'en'
     */
    public static function getUserLang($allowedLangs, $fallbackLang)
    {
        // reset user_lang array
        $userLangs = [];
        // 2nd highest priority: GET parameter 'lang'
        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            $userLangs[] = $_GET['lang'];
        }
        // 3rd highest priority: SESSION parameter 'lang'
        if (isset($_SESSION['lang']) && is_string($_SESSION['lang'])) {
            $userLangs[] = $_SESSION['lang'];
        }
        // 4th highest priority: HTTP_ACCEPT_LANGUAGE
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
                $userLangs[] = strtolower(substr($part, 0, 2));
            }
        }
        // Lowest priority: fallback
        $userLangs[] = $fallbackLang;
        foreach ($allowedLangs as $al) {
            if ($userLangs[0] == $al) {
                return $al;
            }
        }
        return $fallbackLang;
    }

    /**
     * Check for valid email address format
     */
    public static function isEMailValid($mail)
    {
        return (filter_var($mail, FILTER_VALIDATE_EMAIL) !== false);
    }
}
