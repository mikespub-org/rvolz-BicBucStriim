<?php

namespace BicBucStriim\Utilities;

use Aura\Auth\Auth;
use BicBucStriim\Session\Session;
use BicBucStriim\AppData\Settings;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

/**
 * Class RequestUtil provides utilities for the request
 */
class RequestUtil
{
    public const ADMIN_ROLE = 1;

    /** @var Request */
    protected $request;
    /** @var ?Settings */
    protected $settings;

    /**
     * @param Request $request
     * @param ?Settings $settings
     */
    public function __construct($request, $settings = null)
    {
        $this->request = $request;
        $this->settings = $settings;
    }

    /**
     * Get or set current request value in requester
     * @param ?Request $request
     * @return Request
     */
    public function value($request = null)
    {
        if (!empty($request)) {
            $this->request = $request;
        }
        return $this->request;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L166
     * Is this an AJAX request?
     * @deprecated 3.5.0 use isXhr() instead - see slim/http
     * @return bool
     */
    public function isAjax()
    {
        return $this->isXhr();
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L181
     * See https://github.com/slimphp/Slim-Http/blob/master/src/ServerRequest.php#L765
     */
    public function isXhr()
    {
        return $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Is this an API request expecting a JSON response?
     * @return bool
     */
    public function isJsonApi()
    {
        if (!empty($this->settings['hasapi']) && $this->request->hasHeader('Accept') && in_array('application/json', $this->request->getHeader('Accept'))) {
            return true;
        }
        return false;
    }

    /**
     * Get param(s)
     * @param ?string $name
     * @return mixed
     */
    public function get($name = null)
    {
        $params = $this->request->getQueryParams();
        if (empty($name)) {
            return $params;
        }
        return $params[$name] ?? null;
    }

    /**
     * Post param(s)
     * @param ?string $name
     * @return mixed
     */
    public function post($name = null)
    {
        $params = $this->request->getParsedBody();
        if (empty($name)) {
            return $params;
        }
        return $params[$name] ?? null;
    }

    /**
     * Files param(s)
     * @param ?string $name
     * @return array<mixed>|null
     */
    public function files($name = null)
    {
        $files = $this->request->getUploadedFiles();
        if (empty($name)) {
            return $files;
        }
        return $files[$name] ?? null;
    }

    /**
     * Get session
     * @return Session|null
     */
    public function getSession()
    {
        return $this->request->getAttribute('session');
    }

    /**
     * Get authentication tracker
     * @return Auth|null
     */
    public function getAuth()
    {
        return $this->request->getAttribute('auth');
    }

    /**
     * Get authenticated username or null
     * @return string|null
     */
    public function getUserName()
    {
        return $this->getAuth()?->getUserName();
    }

    /**
     * Check if the current user was authenticated
     * @return bool  true if authenticated, else false
     */
    public function isAuthenticated()
    {
        return is_object($this->getAuth()) && $this->getAuth()->isValid();
    }

    /**
     * Check for admin permissions. Currently this is only the user
     * <em>admin</em>, ID 1.
     * @return bool  true if admin user, else false
     */
    public function isAdmin()
    {
        if ($this->isAuthenticated()) {
            $user = $this->getAuth()->getUserData();
            return (intval($user['role']) === self::ADMIN_ROLE);
        } else {
            return false;
        }
    }

    /**
     * Set session
     * @param Session $session
     * @return Request
     */
    public function setSession($session)
    {
        $this->request = $this->request->withAttribute('session', $session);
        return $this->request;
    }

    /**
     * Set authentication tracker
     * @param Auth $auth
     * @return Request
     */
    public function setAuth($auth)
    {
        $this->request = $this->request->withAttribute('auth', $auth);
        return $this->request;
    }

    /**
     * Get root url
     * @param bool $absolute override relative_urls settings
     * @return string root url
     */
    public function getRootUrl($absolute = false)
    {
        if ($this->settings?->relative_urls == '1' && !$absolute) {
            $root = rtrim($this->getBasePath(), "/");
        } else {
            // Get forwarding information, if available
            $info = $this->getForwardingInfo();
            if (is_null($info) || !$info->is_valid()) {
                // No forwarding info available
                $root = rtrim($this->getSchemeAndHttpHost() . $this->getBasePath(), "/");
            } else {
                // Use forwarding info
                $root = $info->protocol . '://' . $info->host . $this->getBasePath();
            }
        }
        return $root;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L569
     * @return string
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
     * @return string
     */
    public function getBasePath()
    {
        $basedir = dirname($this->request->getServerParams()['SCRIPT_NAME'] ?? '');
        return rtrim($basedir, '/');
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L560
     * @see \BicBucStriim\Middleware\BasePathMiddleware
     * @return string
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
     * Return a UrlInfo instance if the request contains forwarding information, or null if not.
     *
     * First we look for the standard 'Forwarded' header from RFC 7239, then for the non-standard X-Forwarded-... headers.
     *
     * @return null|UrlInfo
     */
    public function getForwardingInfo()
    {
        $headers = $this->request->getHeaders();
        return UrlInfo::getForwardingInfo($headers);
    }

    /**
     * Check if CORS origin is allowed
     * @return string|false
     */
    public function getCorsOrigin()
    {
        $allowed = $this->settings['cors_origin'] ?? '*';
        // Check if origin is allowed or undefined
        $origin = $this->request->getHeaderLine('Origin') ?: '*';
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
     * Create server request from Nyholm PSR-17 factory
     * @param ?string $method
     * @param ?string $uri
     * @param ?array<mixed> $params
     * @return Request
     */
    public static function getServerRequest($method = null, $uri = null, $params = null)
    {
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $serverRequest = $creator->fromGlobals();
        if (!empty($method)) {
            $serverRequest = $serverRequest->withMethod($method);
        }
        if (!empty($uri)) {
            $uri = new \Nyholm\Psr7\Uri($uri);
            $serverRequest = $serverRequest->withUri($uri);
        }
        if (!empty($params)) {
            if ($method === 'GET') {
                $serverRequest = $serverRequest->withQueryParams($params);
            } elseif ($method === 'POST') {
                $serverRequest = $serverRequest->withParsedBody($params);
            } else {
                $serverRequest = $serverRequest->withAttribute('body', $params);
            }
        }
        return $serverRequest;
    }
}
