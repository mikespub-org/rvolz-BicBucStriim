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
     * @return \BicBucStriim\App
     */
    public function app();

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
     * Get application log
     * @return \Slim\Log
     */
    public function log();

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
     * Halt
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function halt($status, $message = '');
}
