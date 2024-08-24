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
use BicBucStriim\Utilities\RequestUtil;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/*********************************************************************
 * App utility trait
 ********************************************************************/
trait AppTrait
{
    /** @var ?\Psr\Container\ContainerInterface */
    protected $container;
    /** @var ?Request */
    protected $request;
    /** @var ?Response */
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
     * @param ?Request $request
     * @return Request
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
     * @param ?Response $response
     * @return Response
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
     * @deprecated 3.4.3 use DefaultActions::getSession() instead
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
     * @deprecated 3.4.3 use DefaultActions::getAuth() instead
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
     * @deprecated 3.4.3 use DefaultActions::getFlash() or DefaultActions::setFlash() instead
     * @return void|mixed
     */
    public function flash($key, $value = null)
    {
        // @todo pass along $request or $session here?
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
     * @todo deprecated 3.4.3 moved to \BicBucStriim\Utilities\RequestUtil
     * @return string root url
     */
    public function getRootUrl($absolute = false)
    {
        $requestUtil = new RequestUtil($this->request, $this->settings());
        return $requestUtil->getRootUrl($absolute);
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L569
     * @deprecated 3.4.3 moved to \BicBucStriim\Utilities\RequestUtil
     */
    public function getSchemeAndHttpHost()
    {
        $requestUtil = new RequestUtil($this->request);
        return $requestUtil->getSchemeAndHttpHost();
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L533
     * See https://www.slimframework.com/docs/v4/objects/request.html#obtain-base-path-from-within-route
     * See https://discourse.slimframework.com/t/slim-4-get-base-url/3406
     * @deprecated 3.4.3 moved to \BicBucStriim\Utilities\RequestUtil
     * @todo align with request basepath
     */
    public function getBasePath()
    {
        $requestUtil = new RequestUtil($this->request);
        return $requestUtil->getBasePath();
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L560
     * @deprecated 3.4.3 moved to \BicBucStriim\Utilities\RequestUtil
     */
    public function getPathInfo()
    {
        $requestUtil = new RequestUtil($this->request);
        return $requestUtil->getPathInfo();
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
