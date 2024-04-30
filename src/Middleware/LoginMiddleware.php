<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Middleware;

use BicBucStriim\Session\Session;
use BicBucStriim\Utilities\InputUtil;
use BicBucStriim\Utilities\L10n;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class LoginMiddleware extends DefaultMiddleware
{
    protected $realm;
    protected $static_resource_paths;

    /**
     * Initialize the PDO connection and merge user
     * config with defaults.
     *
     * @param string $realm
     * @param array $statics
     */
    public function __construct(ContainerInterface $container, $realm, $statics)
    {
        parent::__construct($container);
        $this->realm = $realm;
        $this->static_resource_paths = $statics;
    }

    /**
     * @param Request $request The request
     * @param RequestHandler $handler The handler
     * @return Response The response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->request = $request;
        //$response = $this->response();
        try {
            // true if we have a response ready (= need to authenticate), false otherwise
            if ($this->authBeforeDispatch()) {
                return $this->response;
            }
        } catch (\Exception $e) {
            if (!empty($this->response)) {
                return $this->response;
            }
            //$response = $this->response();
            //$this->response()->write(ob_get_clean());
            echo ob_get_clean();
            echo "Done: " . $e->getMessage();
            exit;
        }
        $this->setCurrentLanguage($this->request);
        return $handler->handle($this->request);
    }

    /**
     * Check if we need to authenticate before dispatching the request further
     * @return bool true if we have a response ready (= need to authenticate), false otherwise
     */
    public function authBeforeDispatch()
    {
        $settings = $this->settings();
        $request = $this->request();
        $resource = $this->getPathInfo();
        $this->log()->debug('login resource: ' . $resource);
        if ($settings->must_login == 1) {
            if ($this->is_static_resource($resource)) {
                return false;
            }
            // we need to initialize $this->auth() here to identify the user
            if ($this->is_authorized()) {
                return false;
            }
            if ($resource === '/login/') {
                // special case login page
                $this->log()->debug('login: login page authorized');
                return false;
            }
            if (stripos($resource, '/opds') === 0) {
                $this->log()->debug('login: unauthorized OPDS request');
                $this->mkAuthenticate($this->realm);
                return true;
            }
            $util = new \BicBucStriim\Utilities\RequestUtil($request);
            if ($request->getMethod() != 'GET' && ($util->isXhr() || $util->isAjax())) {
                $this->log()->debug('login: unauthorized JSON request');
                $this->mkAuthenticate($this->realm);
                return true;
            }
            $this->log()->debug('login: redirecting to login');
            // app->redirect not useable in middleware
            $this->mkRedirect($this->getRootUrl() . '/login/');
            return true;
        }
        if ($resource === '/login/') {
            // we need to initialize $this->auth() if we want to login in MainActions
            $this->is_authorized();
            // special case login page
            $this->log()->debug('login: login page authorized');
            return false;
        }
        if ($resource === '/logout/') {
            // we need to initialize $this->auth() if we want to logout in MainActions
            $this->is_authorized();
            // special case logout page
            $this->log()->debug('login: logout page authorized');
            return false;
        }
        if (stripos($resource, '/admin') === 0) {
            if (!$this->is_static_resource($resource) && !$this->is_authorized()) {
                $this->log()->debug('login: redirecting to login');
                $this->mkRedirect($this->getRootUrl() . '/login/');
                return true;
            }
            return false;
        }
        // we do not want/need to initialize $this->auth() here = anonymous user without session
        return false;
    }

    /**
     * Static resources must not be protected. Return true if the requested resource
     * belongs to a static resource path, else false.
     * @return bool
     */
    protected function is_static_resource($resource)
    {
        $path_parts = preg_split('/\//', $resource);
        if (!empty($path_parts)) {
            # Some OPDS clients like Aldiko don't send auth information for image resources so we have to handle them here
            # FIXME better solution for resources
            if (sizeof($path_parts) == 5 && ($path_parts[3] == 'cover' || $path_parts[3] == 'thumbnail')) {
                return true;
            }
            foreach ($this->static_resource_paths as $static_resource_path) {
                if (strcasecmp($static_resource_path, $path_parts[1]) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if the access request is authorized by a user. A request must either contain session data from
     * a previous login or contain a HTTP Basic authorization info, which is then used to
     * perform a login against the users table in the database.
     * @return bool true if authorized else false
     */
    protected function is_authorized()
    {
        $request = $this->request();
        $session = $this->getSession($request, $this->getBasePath());
        $this->auth($this->getAuthTracker($request, $session, $this->bbs()->mydb));
        try {
            $resume_service = $this->container('resume_service');
            $resume_service->resume($this->auth());
            $this->session($session);
        } catch(\ErrorException $e) {
            $this->log()->warning('login error: bad cookie data ' . var_export(get_class($e), true));
        }
        $this->log()->debug("after resume: " . $this->auth()->getStatus());
        if ($this->auth()->isValid()) {
            // already logged in -- check for bad cookie contents
            $ud = $this->auth()->getUserData();
            if (is_array($ud) && array_key_exists('role', $ud) && array_key_exists('id', $ud)) {
                // contents seems ok
                return true;
            }
            $this->log()->warning("bad cookie contents: killing session");
            // bad cookie contents, kill it
            $session->destroy();
            return false;
        }
        // not logged in - check for login info
        $auth = $this->checkPhpAuth($request);
        if (is_null($auth)) {
            $auth = $this->checkHttpAuth($request);
        }
        $this->log()->debug('login auth: ' . var_export($auth, true));
        // if auth info found check the database
        if (is_null($auth)) {
            return false;
        }
        try {
            $this->container('login_service')->login($this->auth(), [
                'username' => $auth[0],
                'password' => $auth[1]]);
            $this->log()->debug('login status: ' . var_export($this->auth()->getStatus(), true));
        } catch (\Aura\Auth\Exception $e) {
            $this->log()->debug('login error: ' . var_export(get_class($e), true));
        }
        return $this->auth()->isValid();
    }

    /**
     * Get session manager for current request
     * @param Request $request HTTP request
     * @param string $basePath
     * @return Session
     */
    protected function getSession($request, $basePath = '')
    {
        $sessionFactory = new \BicBucStriim\Session\SessionFactory();
        $session = $sessionFactory->newInstance($request->getCookieParams());
        $session->setCookieParams(['path' => $basePath . '/']);
        return $session;
    }

    /**
     * Get authentication tracker for current request and session manager
     * @param Request $request HTTP request
     * @param Session $session
     * @param \PDO $pdo
     * @return \Aura\Auth\Auth
     */
    protected function getAuthTracker($request, $session, $pdo)
    {
        $authFactory = new \Aura\Auth\AuthFactory($request->getCookieParams(), $session);
        $hash = new \Aura\Auth\Verifier\PasswordVerifier(PASSWORD_BCRYPT);
        $cols = ['username', 'password', 'id', 'email', 'role', 'languages', 'tags'];
        $pdoAdapter = $authFactory->newPdoAdapter($pdo, $hash, $cols, 'user');
        $this->container('login_service', fn() => $authFactory->newLoginService($pdoAdapter));
        $this->container('logout_service', fn() => $authFactory->newLogoutService($pdoAdapter));
        $this->container('resume_service', fn() => $authFactory->newResumeService($pdoAdapter));
        return $authFactory->newInstance();
    }

    /**
     * Look for PHP authorization headers
     * @param Request $request HTTP request
     * @return ?array with username and pasword, or null
     */
    protected function checkPhpAuth($request)
    {
        $authUser = $request->getHeaderLine('PHP_AUTH_USER');
        $authPass = $request->getHeaderLine('PHP_AUTH_PW');
        if (!empty($authUser) && !empty($authPass)) {
            return [$authUser, $authPass];
        } else {
            return null;
        }
    }

    /**
     * Look for a HTTP Authorization header and decode it
     * @param Request $request HTTP request
     * @return ?array with username and pasword, or null
     */
    protected function checkHttpAuth($request)
    {
        $b64auth = $request->getHeaderLine('Authorization');
        if (!empty($b64auth)) {
            $auth_array1 = preg_split('/ /', $b64auth);
            if (empty($auth_array1) || strcasecmp('Basic', $auth_array1[0]) != 0) {
                return null;
            }
            if (sizeof($auth_array1) != 2 || !isset($auth_array1[1])) {
                return null;
            }
            $auth = base64_decode($auth_array1[1]);
            return preg_split('/:/', $auth);
        } else {
            return null;
        }
    }

    /**
     * Set current language and load L10n messages
     * @param Request $request HTTP request
     * @return void
     */
    protected function setCurrentLanguage($request)
    {
        $settings = $this->settings();
        # Find the user language, either one of the allowed languages or
        # English as a fallback.
        $settings['lang'] = InputUtil::getUserLang($request);
        $settings['l10n'] = new L10n($settings['lang']);
        $settings['langa'] = $settings['l10n']->langa;
        $settings['langb'] = $settings['l10n']->langb;
        $this->settings($settings);
    }
}
