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
class DefaultActions
{
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
        $self = new self($app);
    }

    /**
     * @param \BicBucStriim\App $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Check admin rights and redirect if necessary
     */
    public function check_admin()
    {
        $app = $this->app;

        if (!$this->is_admin()) {
            $app->render('error.html', [
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
        $app = $this->app;
        return (is_object($app->auth) && $app->auth->isValid());
    }

    /**
     * Check for admin permissions. Currently this is only the user
     * <em>admin</em>, ID 1.
     * @return boolean  true if admin user, else false
     */
    public function is_admin()
    {
        $app = $this->app;
        if ($this->is_authenticated()) {
            $user = $app->auth->getUserData();
            return (intval($user['role']) === 1);
        } else {
            return false;
        }
    }

    public function getFilter()
    {
        $app = $this->app;

        $lang = null;
        $tag = null;
        if ($this->is_authenticated()) {
            $user = $app->auth->getUserData();
            $app->getLog()->debug('getFilter: ' . var_export($user, true));
            if (!empty($user['languages'])) {
                $lang = $app->calibre->getLanguageId($user['languages']);
            }
            if (!empty($user['tags'])) {
                $tag = $app->calibre->getTagId($user['tags']);
            }
            $app->getLog()->debug('getFilter: Using language ' . $lang . ', tag ' . $tag);
        }
        return new \BicBucStriim\Calibre\CalibreFilter($lang, $tag);
    }

    /**
     * Utility function to fill the page array
     */
    public function mkPage($messageId = '', $menu = 0, $level = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        $subtitle = $this->getMessageString($messageId);
        if ($subtitle == '') {
            $title = $globalSettings[DISPLAY_APP_NAME];
        } else {
            $title = $globalSettings[DISPLAY_APP_NAME] . $globalSettings['sep'] . $subtitle;
        }
        $rot = Utilities::getRootUrl($app);
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
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');
        $msg = $globalSettings['l10n']->message($id);
        return $msg;
    }
}
