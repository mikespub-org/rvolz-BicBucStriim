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

use Aura\Auth\Auth;
use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Calibre\Calibre;
use Psr\Log\LoggerInterface;

/*********************************************************************
 * App utility trait
 ********************************************************************/
trait AppTrait
{
    ///** @var \BicBucStriim\App|\Slim\App|object */
    //protected $app;

    /** @var ?\Psr\Http\Message\ServerRequestInterface */
    protected $request;
    /** @var ?\Psr\Http\Message\ResponseInterface */
    protected $response;

    /**
     * Get BicBucStriim app
     * @param \BicBucStriim\App|\Slim\App|object|null $app
     * @return \BicBucStriim\App|\Slim\App|object
     */
    public function app($app = null)
    {
        if (!empty($app)) {
            $this->app = $app;
        }
        return $this->app;
    }

    /**
     * Get authentication tracker
     * @param ?Auth $auth
     * @return Auth
     */
    public function auth($auth = null)
    {
        return $this->container(Auth::class, $auth);
    }

    /**
     * Get BicBucStriim app data
     * @param ?BicBucStriim $bbs
     * @return BicBucStriim
     */
    public function bbs($bbs = null)
    {
        return $this->container(BicBucStriim::class, $bbs);
    }

    /**
     * Get Calibre data
     * @param ?Calibre $calibre
     * @return Calibre
     */
    public function calibre($calibre = null)
    {
        return $this->container(Calibre::class, $calibre);
    }

    /**
     * Set flash message for subsequent request
     * @param  string   $key
     * @param  mixed    $value
     * @return void
     */
    public function flash($key, $value)
    {
        if ($this->app->getContainer()->has('flash')) {
            $this->app->getContainer()->get('flash')->set($key, $value);
        }
    }

    /**
     * Get application log
     * @param ?\Psr\Log\LoggerInterface $logger
     * @return \Psr\Log\LoggerInterface
     */
    public function log($logger = null)
    {
        return $this->container(LoggerInterface::class, $logger);
    }

    /**
     * Get the Request object
     * @param ?\Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function request($request = null)
    {
        if (!empty($request)) {
            $this->request = $request;
        }
        return $this->request;
    }

    /**
     * Get the Response object or create one if needed
     * @param ?\Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function response($response = null)
    {
        if (!empty($response)) {
            $this->response = $response;
        }
        if (empty($this->response)) {
            // Slim\App contains responseFactory as mandatory first param in constructor
            $this->response = $this->app->getResponseFactory()->createResponse();
            //$this->response = new \Slim\Psr7\Response();
        }
        return $this->response;
    }

    /**
     * Get global app settings
     * @param ?array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function settings($settings = null)
    {
        return $this->container('globalSettings', $settings);
    }

    /**
     * Get Twig environment
     * @param ?\Twig\Environment $twig
     * @return \Twig\Environment
     */
    public function twig($twig = null)
    {
        return $this->container(\Twig\Environment::class, $twig);
    }

    /**
     * Get container key
     * @param ?string $key
     * @param mixed $value
     * @return mixed
     */
    public function container($key = null, $value = null)
    {
        if (empty($key)) {
            return $this->app->getContainer();
        }
        if (!is_null($value)) {
            $this->app->getContainer()->set($key, $value);
        }
        if ($this->app->getContainer()->has($key)) {
            return $this->app->getContainer()->get($key);
        }
        return null;
    }

    /**
     * Get root url
     * @return string root url
     */
    public function getRootUrl()
    {
        $globalSettings = $this->settings();

        if ($globalSettings[RELATIVE_URLS] == '1') {
            $root = rtrim($this->getRootUri(), "/");
        } else {
            // Get forwarding information, if available
            $util = new \BicBucStriim\Utilities\RequestUtil($this->request());
            $info = $util->getForwardingInfo();
            if (is_null($info) || !$info->is_valid()) {
                // No forwarding info available
                $root = rtrim($this->getUrl() . $this->getRootUri(), "/");
            } else {
                // Use forwarding info
                $this->log()->debug("getRootUrl: Using forwarding information " . $info);
                $root = $info->protocol . '://' . $info->host . $this->getRootUri();
            }
        }
        $this->log()->debug("getRootUrl: Using root url " . $root);
        return $root;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L569
     */
    public function getUrl()
    {
        $uri = $this->request->getUri();
        $url = $uri->getScheme() . '://' . $uri->getHost();
        if (($uri->getScheme() === 'https' && $uri->getPort() !== 443) || ($uri->getScheme() === 'http' && $uri->getPort() !== 80)) {
            $url .= sprintf(':%s', $uri->getPort());
        }
        return $url;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L533
     * See https://www.slimframework.com/docs/v4/objects/request.html#obtain-base-path-from-within-route
     * See https://discourse.slimframework.com/t/slim-4-get-base-url/3406
     */
    public function getRootUri()
    {
        $basedir = dirname($this->request->getServerParams()['SCRIPT_NAME'] ?? '');
        $basepath = $this->app->getBasePath();
        //echo "$basepath ?= $basedir ?= N/A\n";
        //$routeContext = \Slim\Routing\RouteContext::fromRequest($this->request());
        //$baseroute = $routeContext->getBasePath();
        //echo "$basepath ?= $basedir ?= $baseroute\n";
        return rtrim($basedir, '/');
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L560
     */
    public function getResourceUri()
    {
        $resource = $this->request->getRequestTarget();
        $basepath = $this->app->getBasePath();
        //echo "Basepath: '$basepath' - Resource: '$resource'\n";
        if (!empty($basepath) && str_starts_with($resource, $basepath . '/')) {
            $resource = substr($resource, strlen($basepath));
        }
        return $resource;
        /**
        $pathinfo = $this->request->getServerParams()['PATH_INFO'] ?? null;
        if (isset($pathinfo)) {
            return $pathinfo;
        }
        $requesturi = explode('?', $this->request->getServerParams()['REQUEST_URI'] ?? '')[0];
        if (empty($requesturi)) {
            return '';
        }
        $scriptname = str_replace('\\', '/', $this->request->getServerParams()['SCRIPT_NAME'] ?? '');
        if (empty($scriptname)) {
            return $requesturi;
        }
        if (str_starts_with($requesturi, $scriptname)) {
            return substr($requesturi, strlen($scriptname));
        }
        $scriptname = dirname($scriptname);
        if (str_starts_with($requesturi, $scriptname)) {
            return substr($requesturi, strlen($scriptname));
        }
        return $requesturi;
         */
    }

    /**
     * Create and send an error to authenticate (401)
     * @param  string   $realm      The realm
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function mkAuthenticate($realm, $status = 401, $message = 'Please authenticate')
    {
        $this->response = $this->response()->withHeader('WWW-Authenticate', sprintf('Basic realm="%s"', $realm));
        $this->mkError($status, $message);
    }

    /**
     * Create and send an error response (halt)
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function mkError($status, $message = '')
    {
        //$this->app->halt($status, $message);
        $emptyBody = $this->app->getResponseFactory()->createResponse()->getBody();
        $emptyBody->write($message);
        $this->response = $this->response->withStatus($status)->withBody($emptyBody);
    }

    /**
     * Create and send a redirect response (redirect)
     * @param  string   $url        The destination URL
     * @param  int      $status     The HTTP redirect status code (optional)
     * @param  bool     $halt       Invoke response->halt() or not (optional for middleware)
     */
    public function mkRedirect($url, $status = 302, $halt = true)
    {
        $this->response = $this->response()->withStatus($status)->withHeader('Location', $url);
        if ($halt) {
            //throw new \Slim\Exception\HttpException($this->request, 'redirected');
            //$this->app->redirect($url, $status);
            // @todo throw Exception?
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
        $this->response = $this->response()->withStatus($status)->withHeader('Content-type', $type)->withHeader('Content-Length', (string) strlen($content));
        $this->response->getBody()->write($content);
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
        $etag = '"' . md5((string) filemtime($filepath) . '-' . $filepath) . '"';
        $resp = $this->response()->withStatus($status)->withHeader('Content-type', $type)->withHeader('Content-Length', (string) filesize($filepath))->withHeader('ETag', $etag);
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $this->response = $resp->withBody($psr17Factory->createStreamFromFile($filepath));
    }

    /**
     * Create and send a file response as attachment
     * @param string $filepath
     * @param string $type
     * @param string $filename
     * @param int $status
     * @return void
     */
    public function mkSendFileAsAttachment($filepath, $type, $filename, $status = 200)
    {
        //header("Content-Description: File Transfer");
        //header("Content-Transfer-Encoding: binary");
        $resp = $this->response()->withStatus($status)->withHeader('Content-type', $type)->withHeader('Content-Length', (string) filesize($filepath))->withHeader('Content-Disposition', "attachment; filename=\"" . $filename . "\"");
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $this->response = $resp->withBody($psr17Factory->createStreamFromFile($filepath));
    }
}
