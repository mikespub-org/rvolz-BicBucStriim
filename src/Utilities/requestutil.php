<?php

namespace BicBucStriim\Utilities;

/**
 * Class RequestUtil provides utilities for the request
 */
class RequestUtil
{
    /** @var \Psr\Http\Message\ServerRequestInterface */
    public $request;
    /** @var ?\Psr\Container\ContainerInterface */
    public $container;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param ?\Psr\Container\ContainerInterface $container
     */
    public function __construct($request, $container = null)
    {
        $this->request = $request;
        $this->container = $container;
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
     * Return a UrlInfo instance if the request contains forwarding information, or null if not.
     *
     * First we look for the standard 'Forwarded' header from RFC 7239, then for the non-standard X-Forwarded-... headers.
     *
     * @return null|UrlInfo
     */
    public function getForwardingInfo()
    {
        $headers = $this->request->getHeaders();
        $info = null;
        $forwarded = $headers['Forwarded'] ?? null;
        if (!is_null($forwarded)) {
            $info = new UrlInfo($forwarded);
        } else {
            $fhost = $headers['X-Forwarded-Host'] ?? null;
            $fproto = $headers['X-Forwarded-Proto'] ?? null;
            if (!is_null($fhost)) {
                $info = new UrlInfo($fhost, $fproto);
            }
        }
        return $info;
    }

    /**
     * Create server request from Nyholm PSR-17 factory
     * @param ?string $method
     * @param ?string $uri
     * @return \Psr\Http\Message\ServerRequestInterface
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
