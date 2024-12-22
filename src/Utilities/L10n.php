<?php

/**
* Localization helper
*/

namespace BicBucStriim\Utilities;

use ArrayObject;

class L10n extends ArrayObject
{
    # Allowed languages, i.e. languages with translations
    public static $allowedLangs = ['de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'];
    # Fallback language if the browser prefers another than the allowed languages
    public static $fallbackLang = 'en';

    /**
     * User language
     * @var string
     */
    public $user_lang;
    /**
     * Messages of primary localization language
     * @var array
     */
    public $langa;
    /**
     * Messages of fallback localization language
     * @var array
     */
    public $langb;

    /**
     * Find the user language, either one of the allowed languages or
     * English as a fallback. Store the English messages as an alternative
     * for incomplete translations.
     *
     * @param string $lang  user language (according to client)
     */
    public function __construct($lang)
    {
        if (!in_array($lang, static::$allowedLangs)) {
            $lang = static::$fallbackLang;
        }
        $this->langa = static::loadMessages($lang);
        if ($lang == static::$fallbackLang) {
            $this->langb = $this->langa;
        } else {
            $this->langb = static::loadMessages(static::$fallbackLang);
        }
        $this->user_lang = $lang;
    }

    public static function loadMessages($lang)
    {
        $messages = require dirname(__DIR__, 2) . "/lang/messages.{$lang}.php";
        return $messages;
    }

    /**
     * Implement this part of the Array interface for easier
     * access by the templates.
     *
     * Always return true, because then ::message will return an
     * error string for undefined IDs.
     */
    public function offsetExists($id): bool
    {
        return true;
    }

    /**
     * Implement this part of the Array interface for easier
     * access by the templates.
     *
     * Just call ::message.
     */
    public function offsetGet($id): mixed
    {
        return $this->message($id);
    }

    /**
     * Return a localized message string for $id.
     *
     * If there is no defined message for $id in the current language the function
     * looks for an alterantive in English. If that also fails an error message
     * is returned.
     *
     * If $id is NULL or '' the empty string will be returned.
     *
     * @param  ?string $id message id
     * @return string     localized message string
     */
    public function message($id)
    {
        if (empty($id)) {
            return '';
        }
        if (array_key_exists($id, $this->langa)) {
            return $this->langa[$id];
        } else {
            if (array_key_exists($id, $this->langb)) {
                return $this->langb[$id];
            } else {
                return 'Undefined message!';
            }
        }
    }
}
