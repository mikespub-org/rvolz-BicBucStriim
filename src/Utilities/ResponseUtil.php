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
     * @param ?Response $response
     */
    public function __construct($response)
    {
        // @todo get app responsefactory from container
        $this->response = $response ?? static::getResponse();
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
     * Create and send an error to authenticate (401)
     * @param  string   $realm      The realm
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     * @return Response
     */
    public function mkAuthenticate($realm, $status = 401, $message = 'Please authenticate')
    {
        $this->response = $this->response->withHeader('WWW-Authenticate', sprintf('Basic realm="%s"', $realm));
        return $this->mkError($status, $message);
    }

    /**
     * Create and send an error response (halt)
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     * @return Response
     */
    public function mkError($status, $message = '')
    {
        $emptyBody = static::getResponse()->getBody();
        $emptyBody->write($message);
        $this->response = $this->response->withStatus($status)->withBody($emptyBody);
        return $this->response;
    }

    /**
     * Create and send a redirect response (redirect)
     * @param  string   $url        The destination URL
     * @param  int      $status     The HTTP redirect status code (optional)
     * @return Response
     */
    public function mkRedirect($url, $status = 302)
    {
        $this->response = $this->response->withStatus($status)->withHeader('Location', $url);
        return $this->response;
    }

    /**
     * Create and send a normal response
     * @param string $content
     * @param string $type
     * @param int $status
     * @return Response
     */
    public function mkResponse($content, $type, $status = 200)
    {
        // Slim 2 framework will finalize response after slim call() and echo output in run()
        $this->response = $this->response->withStatus($status)->withHeader('Content-type', $type)->withHeader('Content-Length', (string) strlen($content));
        $this->response->getBody()->write($content);
        return $this->response;
    }

    /**
     * Create and send a JSON response
     * @param mixed $data array or object
     * @param mixed $origin (optional)
     * @param string $type (optional)
     * @param int $status (optional)
     * @return Response
     */
    public function mkJsonResponse($data, $origin = null, $type = 'application/json', $status = 200)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $response = $this->mkResponse($content, $type, $status);
        // Add Allow-Origin + Allow-Credentials to response for non-preflighted requests
        if (empty($origin)) {
            return $response;
        }
        // @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#requests_with_credentials
        $this->response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Vary', 'Origin');
        return $this->response;
    }

    /**
     * Create and send CORS options
     * @param string|false $origin
     * @return Response
     */
    public function mkCorsOptions($origin)
    {
        if (empty($origin)) {
            return $this->response;
        }
        $this->response = $this->response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')  // PUT, DELETE, PATCH
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Vary', 'Origin');
        return $this->response;
    }

    /**
     * Create and send the typical OPDS response
     * @return Response
     */
    public function mkOpdsResponse($content, $type, $status = 200)
    {
        return $this->mkResponse($content, $type, $status);
    }

    /**
     * Create and send a file response
     * @param string $filepath
     * @param string $type
     * @param int $status
     * @return Response
     */
    public function mkSendFile($filepath, $type, $status = 200)
    {
        $etag = '"' . md5((string) filemtime($filepath) . '-' . $filepath) . '"';
        $resp = $this->response->withStatus($status)->withHeader('Content-type', $type)->withHeader('Content-Length', (string) filesize($filepath))->withHeader('ETag', $etag);
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $this->response = $resp->withBody($psr17Factory->createStreamFromFile($filepath));
        return $this->response;
    }

    /**
     * Create and send a file response as attachment
     * @param string $filepath
     * @param string $type
     * @param string $filename
     * @param int $status
     * @return Response
     */
    public function mkSendFileAsAttachment($filepath, $type, $filename, $status = 200)
    {
        //header("Content-Description: File Transfer");
        //header("Content-Transfer-Encoding: binary");
        $resp = $this->response->withStatus($status)->withHeader('Content-type', $type)->withHeader('Content-Length', (string) filesize($filepath))->withHeader('Content-Disposition', "attachment; filename=\"" . $filename . "\"");
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $this->response = $resp->withBody($psr17Factory->createStreamFromFile($filepath));
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
