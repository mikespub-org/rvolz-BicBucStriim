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
 * App utility trait
 ********************************************************************/
trait AppTrait
{
    ///** @var \BicBucStriim\App */
    //protected $app;

    /**
     * Get BicBucStriim app
     * @return \BicBucStriim\App
     */
    public function app()
    {
        return $this->app;
    }

    /**
     * Get authentication tracker
     * @param ?\Aura\Auth\Auth $auth
     * @return \Aura\Auth\Auth
     */
    public function auth($auth = null)
    {
        if (!empty($auth)) {
            $this->app->auth = $auth;
        }
        return $this->app->auth;
    }

    /**
     * Get BicBucStriim app data
     * @param ?\BicBucStriim\AppData\BicBucStriim $bbs
     * @return \BicBucStriim\AppData\BicBucStriim
     */
    public function bbs($bbs = null)
    {
        if (!empty($bbs)) {
            $this->app->bbs = $bbs;
        }
        return $this->app->bbs;
    }

    /**
     * Get Calibre data
     * @param ?\BicBucStriim\Calibre\Calibre $calibre
     * @return \BicBucStriim\Calibre\Calibre
     */
    public function calibre($calibre = null)
    {
        if (!empty($calibre)) {
            $this->app->calibre = $calibre;
        }
        return $this->app->calibre;
    }

    /**
     * Get application log
     * @return \Slim\Log
     */
    public function log()
    {
        return $this->app->getLog();
    }

    /**
     * Get the Request object
     * @return \Slim\Http\Request
     */
    public function request()
    {
        return $this->app->request();
    }

    /**
     * Get the Response object
     * @return \Slim\Http\Response
     */
    public function response()
    {
        return $this->app->response();
    }

    /**
     * Get global app settings
     * @param ?array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function settings($settings = null)
    {
        if (!empty($settings)) {
            $this->app->config('globalSettings', $settings);
        }
        return $this->app->config('globalSettings');
    }

    /**
     * Halt
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function halt($status, $message = '')
    {
        $this->app->halt($status, $message);
    }
}
