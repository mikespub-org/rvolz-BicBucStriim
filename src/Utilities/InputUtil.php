<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\Session\Session;
use Psr\Http\Message\ServerRequestInterface as Request;

class InputUtil
{
    /**
     * Returns the user language, priority:
     * 1. Language in $_GET['lang']
     * 2. Language in $_COOKIE['lang'] - not $_SESSION['lang] or Auth::getUserData()
     * 3. HTTP_ACCEPT_LANGUAGE
     * 4. Fallback language
     *
     * @param Request $request The request
     * @return string the user language, like 'de' or 'en'
     */
    public static function getUserLang($request)
    {
        // reset user_lang array
        $userLangs = [];
        // 1st highest priority: GET parameter 'lang'
        $query = $request->getQueryParams();
        if (isset($query['lang']) && is_string($query['lang'])) {
            $userLangs[] = $query['lang'];
        }
        // 2nd highest priority: SESSION parameter 'lang' - from COOKIE, not SESSION or Auth::getUserData()
        $session = $request->getAttribute('session');
        if (isset($session)) {
            /** @var Session $session */
            $sessionLanguage = $session->getCookie('lang');
            if (isset($sessionLanguage) && is_string($sessionLanguage)) {
                $userLangs[] = $sessionLanguage;
            }
        } else {
            // or COOKIE parameter 'lang' in case we don't have a session
            $cookie = $request->getCookieParams();
            if (isset($cookie['lang']) && is_string($cookie['lang'])) {
                $userLangs[] = $cookie['lang'];
            }
        }
        // 3rd highest priority: HTTP_ACCEPT_LANGUAGE
        $acceptLanguage = $request->getHeaderLine('Accept-Language');
        if (!empty($acceptLanguage)) {
            foreach (explode(',', $acceptLanguage) as $part) {
                $userLangs[] = strtolower(substr($part, 0, 2));
            }
        }
        // Lowest priority: fallback
        $userLangs[] = L10n::$fallbackLang;
        foreach (L10n::$allowedLangs as $al) {
            if ($userLangs[0] == $al) {
                return $al;
            }
        }
        return L10n::$fallbackLang;
    }

    /**
     * Check for valid email address format
     */
    public static function isEMailValid($mail)
    {
        return (filter_var($mail, FILTER_VALIDATE_EMAIL) !== false);
    }
}
