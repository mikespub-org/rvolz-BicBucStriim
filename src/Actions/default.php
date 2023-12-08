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

use Utilities;

/*********************************************************************
 * Default actions
 ********************************************************************/
class DefaultActions implements \BicBucStriim\Traits\AppInterface
{
    use \BicBucStriim\Traits\AppTrait;

    /** @var \BicBucStriim\App */
    protected $app;

    /**
     * Add routes for default actions
     * @param \BicBucStriim\App $app
     * @param ?string $prefix
     * @return void
     */
    public static function addRoutes($app, $prefix = null)
    {
        // Slim 2 framework uses callable - we need $app instance
        $self = new self($app);
        static::mapRoutes($app, $self);
    }

    /**
     * Map routes for default actions
     * @param \BicBucStriim\App $app
     * @param self $self
     * @return void
     */
    public static function mapRoutes($app, $self)
    {
        $routes = static::getRoutes($self);
        foreach ($routes as $route) {
            $method = array_shift($route);
            $path = array_shift($route);
            if (is_string($method) && $method === 'GET') {
                $method = ['GET', 'HEAD'];
            }
            $app->map($path, ...$route)->via($method);
        }
    }

    /**
     * Get routes for default actions
     * @param self $self
     * @return array<mixed> list of [method(s), path, callable(s)] for each action
     */
    public static function getRoutes($self)
    {
        return [
            // method(s), path, callable(s)
            ['GET', '/', [$self, 'hello']],
            ['GET', '/:name', [$self, 'hello']],
        ];
    }

    /**
     * @param \BicBucStriim\App $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Hello function (example)
     * @param ?string $name
     */
    public function hello($name = null)
    {
        $name ??= 'world';
        $answer = 'Hello, ' . $name . '!';
        $this->mkResponse($answer, 'text/plain');
    }

    /**
     * Check admin rights and redirect if necessary
     */
    public function check_admin()
    {
        if (!$this->is_admin()) {
            $this->render('error.html', [
                'page' => $this->mkPage('error', 0, 0),
                'error' => $this->getMessageString('error_no_access')]);
        }
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
     * Render a template
     * @param  string $template The name of the template passed into the view's render() method
     * @param  array  $data     Associative array of data made available to the view
     * @param  ?int    $status   The HTTP response status code to use (optional)
     * @return void
     */
    public function render($template, $data = [], $status = null)
    {
        // Slim 2 framework will replace data, render template and echo output via slim view display()
        $this->app()->render($template, $data, $status);
    }

    /**
     * Utility function to fill the page array
     */
    public function mkPage($messageId = '', $menu = 0, $level = 0)
    {
        $globalSettings = $this->settings();

        $subtitle = $this->getMessageString($messageId);
        if ($subtitle == '') {
            $title = $globalSettings[DISPLAY_APP_NAME];
        } else {
            $title = $globalSettings[DISPLAY_APP_NAME] . $globalSettings['sep'] . $subtitle;
        }
        $rot = $this->getRootUrl();
        $auth = $this->is_authenticated();
        if ($globalSettings[LOGIN_REQUIRED]) {
            $adm = $this->is_admin();
        } else {
            $adm = true;
        }    # the admin button should be always visible if no login is required
        $page = ['title' => $title,
            'rot' => $rot,
            'h1' => $subtitle,
            'version' => $globalSettings['version'],
            'glob' => $globalSettings,
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
        $globalSettings = $this->settings();
        $msg = $globalSettings['l10n']->message($id);
        return $msg;
    }
}
