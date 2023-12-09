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

/*********************************************************************
 * App utility interface (documentation only) - use AppTrait in class
 ********************************************************************/
interface AppInterface
{
    /**
     * Get BicBucStriim app
     * @param \BicBucStriim\App|null $app
     * @return \BicBucStriim\App
     */
    public function app($app = null);

    /**
     * Get authentication tracker
     * @param ?\Aura\Auth\Auth $auth
     * @return \Aura\Auth\Auth
     */
    public function auth($auth = null);

    /**
     * Get BicBucStriim app data
     * @param ?\BicBucStriim\AppData\BicBucStriim $bbs
     * @return \BicBucStriim\AppData\BicBucStriim
     */
    public function bbs($bbs = null);

    /**
     * Get Calibre data
     * @param ?\BicBucStriim\Calibre\Calibre $calibre
     * @return \BicBucStriim\Calibre\Calibre
     */
    public function calibre($calibre = null);

    /**
     * Set flash message for subsequent request
     * @param  string   $key
     * @param  mixed    $value
     * @return void
     */
    public function flash($key, $value);

    /**
     * Get application log
     * @param ?\Slim\Log $logger
     * @return \Slim\Log
     */
    public function log($logger = null);

    /**
     * Get the Request object
     * @return \Slim\Http\Request
     */
    public function request();

    /**
     * Get the Response object
     * @return \Slim\Http\Response
     */
    public function response();

    /**
     * Get global app settings
     * @param ?array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function settings($settings = null);

    /**
     * Get Twig environment
     * @param ?\Twig\Environment $twig
     * @return \Twig\Environment
     */
    public function twig($twig = null);

    /**
     * Get container key
     * @param ?string $key
     * @param mixed $value
     * @return mixed
     */
    public function container($key = null, $value = null);

    /**
     * Get root url
     * @return string root url
     */
    public function getRootUrl();

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
     * @param  bool     $halt       Invoke response->halt() or not (optional for middleware)
     */
    public function mkRedirect($url, $status = 302, $halt = true);

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
}
