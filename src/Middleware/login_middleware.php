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

class LoginMiddleware extends DefaultMiddleware
{
    protected $realm;
    protected $static_resource_paths;

    /**
     * Initialize the PDO connection and merge user
     * config with defaults.
     *
     * @param \BicBucStriim\App $app
     * @param string $realm
     * @param array $statics
     */
    public function __construct($app, $realm, $statics)
    {
        parent::__construct($app);
        $this->realm = $realm;
        $this->static_resource_paths = $statics;
    }


    public function call()
    {
        // Slim 3+ framework does not support hooks anymore
        $this->app()->hook('slim.before.dispatch', [$this, 'authBeforeDispatch']);
        /**
        try {
            $this->authBeforeDispatch();
        } catch (\Slim\Exception\Stop $e) {
            $this->response()->write(ob_get_clean());
            return;
        }
         */
        $this->next->call();
    }

    public function authBeforeDispatch()
    {
        $globalSettings = $this->settings();
        $request = $this->request();
        $resource = $request->getResourceUri();
        $accept = $request->headers('ACCEPT');
        $this->log()->debug('login resource: ' . $resource);
        if ($globalSettings[LOGIN_REQUIRED] == 1) {
            if (!$this->is_static_resource($resource) && !$this->is_authorized()) {
                if ($resource === '/login/') {
                    // special case login page
                    $this->log()->debug('login: login page authorized');
                    return;
                } elseif (stripos($resource, '/opds') === 0) {
                    $this->log()->debug('login: unauthorized OPDS request');
                    $this->mkAuthenticate($this->realm);
                } elseif ($request->getMethod() != 'GET' && ($request->isXhr() || $request->isAjax())) {
                    $this->log()->debug('login: unauthorized JSON request');
                    $this->mkAuthenticate($this->realm);
                } else {
                    $this->log()->debug('login: redirecting to login');
                    // now we can also use the native app->redirect method!
                    $this->mkRedirect($this->getRootUrl() . '/login/');
                    // app->redirect not useable in middleware
                    //$this->mkRedirect($this->getRootUrl() . '/login/', 302, false);
                }
            }
        } else {
            if ($resource === '/login/') {
                $this->is_authorized();
                // special case login page
                $this->log()->debug('login: login page authorized');
                return;
            } elseif (stripos($resource, '/admin') === 0 && !$this->is_static_resource($resource) && !$this->is_authorized()) {
                $this->log()->debug('login: redirecting to login');
                $this->mkRedirect($this->getRootUrl() . '/login/');
            }
        }
    }

    /**
     * Static resources must not be protected. Return true id the requested resource
     * belongs to a static resource path, else false.
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
        // @todo replace $_COOKIE with $request->cookies() once fixed?
        $request = $this->request();
        $session_factory = new \BicBucStriim\Session\SessionFactory();
        $session = $session_factory->newInstance($_COOKIE);
        $session->setCookieParams(['path' => $request->getRootUri() . '/']);
        $auth_factory = new \Aura\Auth\AuthFactory($_COOKIE, $session);
        $this->auth($auth_factory->newInstance());
        $hash = new \Aura\Auth\Verifier\PasswordVerifier(PASSWORD_BCRYPT);
        $cols = ['username', 'password', 'id', 'email', 'role', 'languages', 'tags'];
        $pdo_adapter = $auth_factory->newPdoAdapter($this->bbs()->mydb, $hash, $cols, 'user');
        $this->container('login_service', $auth_factory->newLoginService($pdo_adapter));
        $this->container('logout_service', $auth_factory->newLogoutService($pdo_adapter));
        $resume_service = $auth_factory->newResumeService($pdo_adapter);
        try {
            $resume_service->resume($this->auth());
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
            } else {
                $this->log()->warning("bad cookie contents: killing session");
                // bad cookie contents, kill it
                $session->destroy();
                return false;
            }
        } else {
            // not logged in - check for login info
            $auth = $this->checkPhpAuth($request);
            if (is_null($auth)) {
                $auth = $this->checkHttpAuth($request);
            }
            $this->log()->debug('login auth: ' . var_export($auth, true));
            // if auth info found check the database
            if (is_null($auth)) {
                return false;
            } else {
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
        }
    }

    /**
     * Look for PHP authorization headers
     * @param $request HTTP request
     * @return ?array with username and pasword, or null
     */
    protected function checkPhpAuth($request)
    {
        $authUser = $request->headers('PHP_AUTH_USER');
        $authPass = $request->headers('PHP_AUTH_PW');
        if (!empty($authUser) && !empty($authPass)) {
            return [$authUser, $authPass];
        } else {
            return null;
        }
    }

    /**
     * Look for a HTTP Authorization header and decode it
     * @param $request HTTP request
     * @return ?array with username and pasword, or null
     */
    protected function checkHttpAuth($request)
    {
        $b64auth = $request->headers('Authorization');
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
}
