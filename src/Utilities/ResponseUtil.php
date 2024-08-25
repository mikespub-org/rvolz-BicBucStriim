<?php

namespace BicBucStriim\Utilities;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Class ResponseUtil provides utilities for the response
 */
class ResponseUtil
{
    /** @var Response */
    public $response;

    /**
     * @param Response $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Slim.php#L865
     * Set HTTP cookie to be sent with the HTTP response
     *
     * @param string     $name      The cookie name
     * @param string     $value     The cookie value
     * @param int|string $time      The duration of the cookie;
     *                                  If integer, should be UNIX timestamp;
     *                                  If string, converted to UNIX timestamp with `strtotime`;
     * @param string     $path      The path on the server in which the cookie will be available on
     * @param string     $domain    The domain that the cookie is available to
     * @param bool       $secure    Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection to/from the client
     * @param bool       $httponly  When TRUE the cookie will be made accessible only through the HTTP protocol
     * @return Response updated response
     */
    public function setCookie($name, $value, $time = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        /**
        $settings = array(
            'value' => $value,
            'expires' => is_null($time) ? $this->config('cookies.lifetime') : $time,
            'path' => is_null($path) ? $this->config('cookies.path') : $path,
            'domain' => is_null($domain) ? $this->config('cookies.domain') : $domain,
            'secure' => is_null($secure) ? $this->config('cookies.secure') : $secure,
            'httponly' => is_null($httponly) ? $this->config('cookies.httponly') : $httponly
        );
        $this->response->cookies->set($name, $settings);
         */
        $setCookie = SetCookie::create($name)
            ->withValue($value)
            ->withPath($path ?? '/')
            ->withExpires($time);
        $this->response = FigResponseCookies::set($this->response, $setCookie);
        return $this->response;
    }

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Slim.php#L970
     * Delete HTTP cookie (encrypted or unencrypted)
     *
     * Remove a Cookie from the client. This method will overwrite an existing Cookie
     * with a new, empty, auto-expiring Cookie. This method's arguments must match
     * the original Cookie's respective arguments for the original Cookie to be
     * removed. If any of this method's arguments are omitted or set to NULL, the
     * default Cookie setting values (set during Slim::init) will be used instead.
     *
     * @param string    $name       The cookie name
     * @param string    $path       The path on the server in which the cookie will be available on
     * @param string    $domain     The domain that the cookie is available to
     * @param bool      $secure     Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection from the client
     * @param  bool     $httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
     * @return Response updated response
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        /**
        $settings = array(
            'domain' => is_null($domain) ? $this->config('cookies.domain') : $domain,
            'path' => is_null($path) ? $this->config('cookies.path') : $path,
            'secure' => is_null($secure) ? $this->config('cookies.secure') : $secure,
            'httponly' => is_null($httponly) ? $this->config('cookies.httponly') : $httponly
        );
        $this->response->cookies->remove($name, $settings);
         */
        $setCookie = SetCookie::create($name)
            ->withValue('')
            ->withPath($path ?? '/');

        $this->response = FigResponseCookies::set($this->response, $setCookie->expire());
        return $this->response;
    }

    /**
     * Create response from app response factory or Nyholm PSR-17 factory
     * @param ?\Slim\App $app
     * @return Response
     */
    public static function getResponse($app = null)
    {
        // create response from app response factory
        if (!empty($app)) {
            $response = $app->getResponseFactory()->createResponse();
            return $response;
        }
        // create response from Nyholm PSR-17 factory
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        //$responseBody = $psr17Factory->createStream('Hello world');
        //$response = $psr17Factory->createResponse(200)->withBody($responseBody);
        $response = $psr17Factory->createResponse();
        return $response;
    }
}
