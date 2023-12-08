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

use BicBucStriim\Utilities\UrlInfo;

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
     * Get root url
     * @return string root url
     */
    public function getRootUrl()
    {
        $globalSettings = $this->settings();

        if ($globalSettings[RELATIVE_URLS] == '1') {
            $root = rtrim($this->request()->getRootUri(), "/");
        } else {
            // Get forwarding information, if available
            $info = UrlInfo::getForwardingInfo($this->request()->headers);
            if (is_null($info) || !$info->is_valid()) {
                // No forwarding info available
                $root = rtrim($this->request()->getUrl() . $this->request()->getRootUri(), "/");
            } else {
                // Use forwarding info
                $this->log()->debug("getRootUrl: Using forwarding information " . $info);
                $root = $info->protocol . '://' . $info->host . $this->request()->getRootUri();
            }
        }
        $this->log()->debug("getRootUrl: Using root url " . $root);
        return $root;
    }

    /**
     * Create and send an error to authenticate (401)
     * @param  string   $realm      The realm
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function mkAuthenticate($realm, $status = 401, $message = 'Please authenticate')
    {
        $this->response()->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', $realm));
        $this->mkError($status, $message);
    }

    /**
     * Create and send an error response (halt)
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function mkError($status, $message = '')
    {
        $this->app->halt($status, $message);
    }

    /**
     * Create and send a redirect response (redirect)
     * @param  string   $url        The destination URL
     * @param  int      $status     The HTTP redirect status code (optional)
     * @param  bool     $halt       Invoke response->halt() or not (optional for middleware)
     */
    public function mkRedirect($url, $status = 302, $halt = true)
    {
        if ($halt) {
            $this->app->redirect($url, $status);
        } else {
            $this->response()->redirect($url, $status);
        }
    }

    /**
     * Create and send a normal response
     * @param string $content
     * @param string $type
     * @param int $status
     * @return void
     */
    public function mkResponse($content, $type, $status = 200)
    {
        // Slim 2 framework will finalize response after slim call() and echo output in run()
        $resp = $this->response();
        $resp->setStatus($status);
        $resp->headers->set('Content-type', $type);
        $resp->headers->set('Content-Length', strlen($content));
        $resp->setBody($content);
    }

    /**
     * Create and send a file response
     * @param string $filepath
     * @param string $type
     * @param int $status
     * @return void
     */
    public function mkSendFile($filepath, $type, $status = 200)
    {
        $resp = $this->response();
        $resp->setStatus($status);
        $resp->headers->set('Content-type', $type);
        $resp->headers->set('Content-Length', filesize($filepath));
        readfile($filepath);
    }
}
