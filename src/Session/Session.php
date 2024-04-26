<?php

namespace BicBucStriim\Session;

use Aura\Auth\Session\SessionInterface;
use Aura\Session\Session as AuraSession;
use Aura\Auth\Auth as AuraAuth;

/**
 *
 * Session that integrates Aura Auth and Session.
 *
 */
class Session extends AuraSession implements SessionInterface
{
    /**
     * Returns the value of a key in the session cookies instead of auth segment - $_COOKIE[$key]
     * @param string $key The key in the session cookies.
     * @param mixed $alt An alternative value to return if the key is not set.
     * @return mixed
     */
    public function getCookie($key, $alt = null)
    {
        return $this->cookies[$key] ?? $alt;
    }

    /**
     * Sets the value of a key in the session cookies instead of auth segment - $_COOKIE[$key] = $val
     * @param string $key The key to set.
     * @param mixed $val The value to set it to.
     */
    public function setCookie($key, $val)
    {
        $this->cookies[$key] = $val;
    }

    /**
     * Get the session segment used by Aura\Auth
     * @see \Aura\Auth\AuthFactory
     * @see \Aura\Auth\Session\Segment
     */
    public function getAuthSegment()
    {
        return $this->getSegment(AuraAuth::class);
    }
}
