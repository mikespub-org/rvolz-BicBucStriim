<?php

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
     * Returns the user language, priority:
     * 1. Language in $_GET['lang']
     * 2. Language in $_SESSION['lang']
     * 3. HTTP_ACCEPT_LANGUAGE
     * 4. Fallback language
     *
     * @todo move this later in the request handling when we have $request available
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
