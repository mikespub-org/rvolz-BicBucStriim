<?php

namespace BicBucStriim\Utilities;

class InputUtil
{
    /**
     * Returns the user language, priority:
     * 1. Language in $_GET['lang']
     * 2. Language in $_SESSION['lang']
     * 3. HTTP_ACCEPT_LANGUAGE
     * 4. Fallback language
     *
     * @todo adapt InputUtil::getUserLang() to use $request and possibly $session from Login middleware
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
