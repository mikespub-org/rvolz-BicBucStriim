<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Actions;

use BicBucStriim\Utilities\RouteUtil;

/*********************************************************************
 * Default actions
 ********************************************************************/
class DefaultActions implements \BicBucStriim\Traits\AppInterface
{
    use \BicBucStriim\Traits\AppTrait;

    /** @var \BicBucStriim\App|\Slim\App|object */
    protected $app;
    /** @var string|null */
    protected $templatesDir = null;

    /**
     * Add routes for default actions
     * @param \BicBucStriim\App|\Slim\App|object $app
     * @param ?string $prefix
     * @param ?object $gatekeeper (optional)
     * @return void
     */
    public static function addRoutes($app, $prefix = null, $gatekeeper = null)
    {
        // Slim 2 framework uses callable - we need $app instance
        $self = new self($app);
        $routes = static::getRoutes($self, $gatekeeper);
        RouteUtil::mapRoutes($app, $routes);
    }

    /**
     * Get routes for default actions
     * @param self $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // method(s), path, ...middleware(s), callable
            ['GET', '/', [$self, 'hello']],
            ['GET', '/{name}', [$self, 'hello']],
        ];
    }

    /**
     * @param \BicBucStriim\App|\Slim\App|object $app
     */
    public function __construct($app)
    {
        $this->app($app);
    }

    /**
     * Hello function (example)
     * @param ?string $name
     * @return void
     */
    public function hello($name = null)
    {
        $name ??= 'world';
        $answer = 'Hello, ' . $name . '!';
        $this->mkResponse($answer, 'text/plain');
    }

    /**
     * Get param(s)
     * @param ?string $name
     */
    public function get($name = null)
    {
        $params = $this->request()->getQueryParams();
        if (empty($name)) {
            return $params;
        }
        return $params[$name] ?? null;
    }

    /**
     * Post param(s)
     * @param ?string $name
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
     * Check admin rights and redirect if necessary
     * @see \BicBucStriim\Middleware\GatekeeperMiddleware
     * @return bool true if we have a response ready (= no access), false otherwise
     */
    public function check_admin()
    {
        if (!$this->is_admin()) {
            $this->render('error.twig', [
                'page' => $this->mkPage('error', 0, 0),
                'error' => $this->getMessageString('error_no_access')]);
            return true;
        }
        return false;
    }

    /**
     * Check if the current user was authenticated
     * @return boolean  true if authenticated, else false
     */
    public function is_authenticated()
    {
        return (is_object($this->auth()) && $this->auth()->isValid());
    }

    /**
     * Check for admin permissions. Currently this is only the user
     * <em>admin</em>, ID 1.
     * @return boolean  true if admin user, else false
     */
    public function is_admin()
    {
        if ($this->is_authenticated()) {
            $user = $this->auth()->getUserData();
            return (intval($user['role']) === 1);
        } else {
            return false;
        }
    }

    public function getFilter()
    {
        $lang = null;
        $tag = null;
        if ($this->is_authenticated()) {
            $user = $this->auth()->getUserData();
            $this->log()->debug('getFilter: ' . var_export($user, true));
            if (!empty($user['languages'])) {
                $lang = $this->calibre()->getLanguageId($user['languages']);
            }
            if (!empty($user['tags'])) {
                $tag = $this->calibre()->getTagId($user['tags']);
            }
            $this->log()->debug('getFilter: Using language ' . $lang . ', tag ' . $tag);
        }
        return new \BicBucStriim\Calibre\CalibreFilter($lang, $tag);
    }

    /**
     * Create json response for template data (if hasapi with Accept header)
     * @param  array  $data     Associative array of data made available to the view
     * @param  ?int    $status   The HTTP response status code to use (optional)
     * @return void
     */
    public function renderJson($data = [], $status = null)
    {
        if (array_key_exists('page', $data) && is_array($data['page'])) {
            unset($data['page']['glob']);
            unset($data['page']['admin']);
        }
        if (array_key_exists('users', $data) && is_array($data['users'])) {
            foreach (array_keys($data['users']) as $id) {
                unset($data['users'][$id]['email']);
                unset($data['users'][$id]['password']);
            }
        }
        $this->mkJsonResponse($data);
        return;
    }

    /**
     * Render a template (or json response if hasapi with Accept header)
     * @param  string $template The name of the template passed into the view's render() method
     * @param  array  $data     Associative array of data made available to the view
     * @param  ?int    $status   The HTTP response status code to use (optional)
     * @return void
     */
    public function render($template, $data = [], $status = null)
    {
        $settings = $this->settings();
        if (!empty($settings['hasapi']) && $this->request()->hasHeader('Accept') && in_array('application/json', $this->request()->getHeader('Accept'))) {
            $this->renderJson($data, $status);
            return;
        }
        // Slim 2 framework will replace data, render template and echo output via slim view display()
        //$this->app()->render($template, $data, $status);
        $this->setTemplatesDir();
        $content = $this->twig()->render($template, $data);
        $this->mkResponse($content, 'text/html');
    }

    /**
     * Set custom templates directory (once)
     * @return void
     */
    public function setTemplatesDir()
    {
        if (is_null($this->templatesDir)) {
            $this->templatesDir = '';
            $settings = $this->settings();
            // convert to real path here
            if (!empty($settings->templates_dir)) {
                $this->templatesDir = realpath($settings->templates_dir);
            }
            // override default templates if available
            if (!empty($this->templatesDir)) {
                $this->twig()->getLoader()->prependPath($this->templatesDir);
            }
        }
    }

    /**
     * Utility function to fill the page array
     */
    public function mkPage($messageId = '', $menu = 0, $level = 0)
    {
        $settings = $this->settings();

        $subtitle = $this->getMessageString($messageId);
        if ($subtitle == '') {
            $title = $settings->display_app_name;
        } else {
            $title = $settings->display_app_name . $settings['sep'] . $subtitle;
        }
        $templatesDirName = '';
        if (!empty($settings->templates_dir)) {
            $templatesDirName = basename($settings->templates_dir);
        }
        $rot = $this->getRootUrl();
        $auth = $this->is_authenticated();
        if ($settings->must_login) {
            $adm = $this->is_admin();
        } else {
            $adm = true;
        }    # the admin button should be always visible if no login is required
        $page = ['title' => $title,
            'rot' => $rot,
            'h1' => $subtitle,
            'version' => $settings['version'],
            'custom' => $templatesDirName,
            'glob' => $settings,
            'menu' => $menu,
            'level' => $level,
            'auth' => $auth,
            'admin' => $adm];
        return $page;
    }

    /**
     * Return a localized message string for $id.
     *
     * If there is no defined message for $id in the current language the function
     * looks for an alterantive in English. If that also fails an error message
     * is returned.
     *
     * @param  string $id message id
     * @return string     localized message string
     */
    public function getMessageString($id)
    {
        $settings = $this->settings();
        $msg = $settings['l10n']->message($id);
        return $msg;
    }
}
