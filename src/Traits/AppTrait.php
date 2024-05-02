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
use BicBucStriim\AppData\Settings;
use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Session\Session;
use BicBucStriim\Utilities\Mailer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Routing\RouteContext;

/*********************************************************************
 * App utility trait
 ********************************************************************/
trait AppTrait
{
    /** @var ?\Psr\Container\ContainerInterface */
    protected $container;
    /** @var ?\Psr\Http\Message\ServerRequestInterface */
    protected $request;
    /** @var ?\Psr\Http\Message\ResponseInterface */
    protected $response;

    /**
     * Get BicBucStriim app data
     * @return BicBucStriim
     */
    public function bbs()
    {
        return $this->container(BicBucStriim::class);
    }

    /**
     * Get Calibre data
     * @return Calibre
     */
    public function calibre()
    {
        return $this->container(Calibre::class);
    }

    /**
     * Get application log
     * @return \Psr\Log\LoggerInterface
     */
    public function log()
    {
        return $this->container(LoggerInterface::class);
    }

    /**
     * Get mailer instance
     * @return \BicBucStriim\Utilities\Mailer
     */
    public function mailer()
    {
        return $this->container(Mailer::class);
    }

    /**
     * Get global app settings
     * @param array<string, mixed>|Settings|null $settings
     * @return Settings
     */
    public function settings($settings = null)
    {
        if (is_array($settings)) {
            $settings = new Settings($settings);
        }
        return $this->container(Settings::class, $settings);
    }

    /**
     * Get Twig environment
     * @return \Twig\Environment
     */
    public function twig()
    {
        return $this->container(\Twig\Environment::class);
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
            return $this->container;
        }
        if (!is_null($value)) {
            $this->container->set($key, $value);
        }
        if ($this->container->has($key)) {
            return $this->container->get($key);
        }
        return null;
    }

    /**
     * @return \Psr\Http\Message\ResponseFactoryInterface
     */
    public function getResponseFactory()
    {
        return $this->container(ResponseFactoryInterface::class);
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
            $this->response = $this->getResponseFactory()->createResponse();
            //$this->response = new \Slim\Psr7\Response();
        }
        return $this->response;
    }

    /**
     * Get session - depends on request
     * @param ?Session $session
     * @return Session|null
     */
    public function session($session = null)
    {
        if (!empty($session)) {
            $this->request = $this->request->withAttribute('session', $session);
        }
        return $this->request?->getAttribute('session');
    }

    /**
     * Get authentication tracker - depends on request
     * @param ?Auth $auth
     * @return Auth|null
     */
    public function auth($auth = null)
    {
        if (!empty($auth)) {
            $this->request = $this->request->withAttribute('auth', $auth);
        }
        return $this->request?->getAttribute('auth');
    }

    /**
     * Set flash message for next request or get flash from previous request
     * @param  string   $key
     * @param  mixed    $value
     * @return void|mixed
     */
    public function flash($key, $value = null)
    {
        $session = $this->session();
        if (empty($session)) {
            return;
        }
        if (isset($value)) {
            $session->getLocalSegment()->setFlash($key, $value);
            return;
        }
        return $session->getLocalSegment()->getFlash($key);
    }

    /**
     * Get root url
     * @param bool $absolute override relative_urls settings
     * @return string root url
     */
    public function getRootUrl($absolute = false)
    {
        $settings = $this->settings();

        if ($settings->relative_urls == '1' && !$absolute) {
            $root = rtrim($this->getBasePath(), "/");
        } else {
            // Get forwarding information, if available
            $util = new \BicBucStriim\Utilities\RequestUtil($this->request());
            $info = $util->getForwardingInfo();
            if (is_null($info) || !$info->is_valid()) {
                // No forwarding info available
                $root = rtrim($this->getSchemeAndHttpHost() . $this->getBasePath(), "/");
            } else {
                // Use forwarding info
                $this->log()->debug("getRootUrl: Using forwarding information " . $info);
                $root = $info->protocol . '://' . $info->host . $this->getBasePath();
            }
        }
        $this->log()->debug("getRootUrl: Using root url " . $root);
        return $root;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L569
     */
    public function getSchemeAndHttpHost()
    {
        $uri = $this->request->getUri();
        $url = $uri->getScheme() . '://' . $uri->getHost();
        if (empty($uri->getPort())) {
            return $url;
        }
        if (($uri->getScheme() === 'https' && $uri->getPort() !== 443) || ($uri->getScheme() === 'http' && $uri->getPort() !== 80)) {
            $url .= sprintf(':%s', $uri->getPort());
        }
        return $url;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L533
     * See https://www.slimframework.com/docs/v4/objects/request.html#obtain-base-path-from-within-route
     * See https://discourse.slimframework.com/t/slim-4-get-base-url/3406
     * @todo align with request basepath
     */
    public function getBasePath()
    {
        $basedir = dirname($this->request->getServerParams()['SCRIPT_NAME'] ?? '');
        return rtrim($basedir, '/');
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L560
     */
    public function getPathInfo()
    {
        $resource = $this->request->getRequestTarget();
        $basepath = $this->request->getAttribute(RouteContext::BASE_PATH);
        //$routeContext = RouteContext::fromRequest($this->request);
        //$basepath = $routeContext->getBasePath();
        //echo "Basepath: '$basepath' - Resource: '$resource'\n";
        if (!empty($basepath) && str_starts_with($resource, $basepath . '/')) {
            $resource = substr($resource, strlen($basepath));
        }
        // with trafex/php-nginx, request target becomes "/login/?q=/login/&" for "/login/"
        if (str_contains($resource, '?')) {
            $resource = explode('?', $resource)[0];
        }
        return $resource;
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
        $emptyBody = $this->getResponseFactory()->createResponse()->getBody();
        $emptyBody->write($message);
        $this->response = $this->response()->withStatus($status)->withBody($emptyBody);
    }

    /**
     * Create and send a redirect response (redirect)
     * @param  string   $url        The destination URL
     * @param  int      $status     The HTTP redirect status code (optional)
     */
    public function mkRedirect($url, $status = 302)
    {
        $this->response = $this->response()->withStatus($status)->withHeader('Location', $url);
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
     * Create and send a JSON response
     * @param mixed $data array or object
     * @param string $type (optional)
     * @param int $status (optional)
     * @return void
     */
    public function mkJsonResponse($data, $type = 'application/json', $status = 200)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $this->mkResponse($content, $type, $status);
        // Add Allow-Origin + Allow-Credentials to response for non-preflighted requests
        $origin = $this->getCorsOrigin();
        if (!$origin) {
            return;
        }
        // @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#requests_with_credentials
        $this->response = $this->response()
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Vary', 'Origin');
    }

    /**
     * Check if CORS origin is allowed
     * @return string|false
     */
    public function getCorsOrigin()
    {
        $settings = $this->settings();
        $allowed = $settings['cors_origin'] ?? '*';
        // Check if origin is allowed or undefined
        $origin = $this->request()->getHeaderLine('Origin') ?: '*';
        if (is_array($allowed)) {
            if (!in_array($origin, $allowed)) {
                return false;
            }
        } elseif ($allowed !== '*' && $origin !== $allowed) {
            return false;
        }
        return $origin;
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
