<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\AppData\Settings;

/*********************************************************************
 * App utility interface (documentation only) - use AppTrait in class
 ********************************************************************/
interface AppInterface
{
    /**
     * Get BicBucStriim app data
     * @return \BicBucStriim\AppData\BicBucStriim
     */
    public function bbs();

    /**
     * Get Calibre data
     * @return \BicBucStriim\Calibre\Calibre
     */
    public function calibre();

    /**
     * Get application log
     * @return \Psr\Log\LoggerInterface
     */
    public function log();

    /**
     * Get global app settings
     * @param array<string, mixed>|Settings|null $settings
     * @return Settings
     */
    public function settings($settings = null);

    /**
     * Get Twig environment
     * @return \Twig\Environment
     */
    public function twig();

    /**
     * Get container key
     * @param ?string $key
     * @param mixed $value
     * @return mixed
     */
    public function container($key = null, $value = null);

    /**
     * @return \Psr\Http\Message\ResponseFactoryInterface
     */
    public function getResponseFactory();

    /**
     * Get the Request object
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function request();

    /**
     * Get the Response object
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function response();

    /**
     * Get session - depends on request
     * @param ?\Aura\Session\Session $session
     * @deprecated 3.4.3 use DefaultActions::getSession() instead
     * @return \Aura\Session\Session|null
     */
    public function session($session = null);

    /**
     * Get authentication tracker - depends on request
     * @param ?\Aura\Auth\Auth $auth
     * @deprecated 3.4.3 use DefaultActions::getAuth() instead
     * @return \Aura\Auth\Auth|null
     */
    public function auth($auth = null);

    /**
     * Set flash message for subsequent request
     * @param  string   $key
     * @param  mixed    $value
     * @deprecated 3.4.3 use DefaultActions::getFlash() or DefaultActions::setFlash() instead
     */
    public function flash($key, $value);

    /**
     * Get root url
     * @todo deprecated 3.4.3 moved to \BicBucStriim\Utilities\RequestUtil
     * @return string root url
     */
    public function getRootUrl();

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L569
     * @deprecated 3.4.3 moved to \BicBucStriim\Utilities\RequestUtil
     */
    public function getSchemeAndHttpHost();

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L533
     * @deprecated 3.4.3 moved to \BicBucStriim\Utilities\RequestUtil
     */
    public function getBasePath();

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L560
     * @deprecated 3.4.3 moved to \BicBucStriim\Utilities\RequestUtil
     */
    public function getPathInfo();

    /**
     * Create and send an error to authenticate (401)
     * @param  string   $realm      The realm
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function mkAuthenticate($realm, $status = 401, $message = 'Please authenticate');

    /**
     * Create and send an error response (halt)
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function mkError($status, $message = '');

    /**
     * Create and send a redirect response (redirect)
     * @param  string   $url        The destination URL
     * @param  int      $status     The HTTP redirect status code (optional)
     */
    public function mkRedirect($url, $status = 302);

    /**
     * Create and send a JSON response
     * @param mixed $data array or object
     * @param string $type (optional)
     * @param int $status (optional)
     * @return void
     */
    public function mkJsonResponse($data, $type = 'application/json', $status = 200);

    /**
     * Create and send a normal response
     * @param string $content
     * @param string $type
     * @param int $status
     * @return void
     */
    public function mkResponse($content, $type, $status = 200);

    /**
     * Create and send a file response
     * @param string $filepath
     * @param string $type
     * @param int $status
     * @return void
     */
    public function mkSendFile($filepath, $type, $status = 200);

    /**
     * Create and send a file response as attachment
     * @param string $filepath
     * @param string $type
     * @param string $filename
     * @param int $status
     * @return void
     */
    public function mkSendFileAsAttachment($filepath, $type, $filename, $status = 200);
}
