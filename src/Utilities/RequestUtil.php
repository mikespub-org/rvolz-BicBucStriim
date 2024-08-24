<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\AppData\Settings;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

/**
 * Class RequestUtil provides utilities for the request
 */
class RequestUtil
{
    /** @var Request */
    public $request;
    /** @var ?Settings */
    public $settings;

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
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L166
     * Is this an AJAX request?
     * @return bool
     */
    public function isAjax()
    {
        $params = $this->request->getQueryParams();
        $body = $this->request->getParsedBody();
        if (is_array($body)) {
            $params = array_merge($params, $body);
        }
        $requestedWith = $this->request->getHeaderLine('X_REQUESTED_WITH');
        if (!empty($params['isajax'])) {
            return true;
        } elseif (!empty($requestedWith) && $requestedWith === 'XMLHttpRequest') {
            return true;
        }
        return false;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Http/Request.php#L181
     */
    public function isXhr()
    {
        return $this->isAjax();
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
     * Create server request from Nyholm PSR-17 factory
     * @param ?string $method
     * @param ?string $uri
     * @return Request
     */
    public static function getServerRequest($method = null, $uri = null)
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
        return $serverRequest;
    }
}
