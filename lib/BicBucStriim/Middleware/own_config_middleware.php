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

use BicBucStriim\AppData\BicBucStriim;

class OwnConfigMiddleware extends DefaultMiddleware
{
    protected $knownConfigs;

    /**
     * Initialize the configuration
     *
     * @param array $knownConfigs
     */
    public function __construct($knownConfigs)
    {
        $this->knownConfigs = $knownConfigs;
    }

    public function call()
    {
        $config_status = $this->check_config_db();
        if ($config_status == 0) {
            $this->halt(500, 'No or bad configuration database. Please use <a href="' .
                $this->request()->getRootUri() .
                '/installcheck.php">installcheck.php</a> to check for errors.');
        } elseif ($config_status == 2) {
            // TODO Redirect to an update script in the future
            $this->halt(500, 'Old configuration database detected. Please refer to the <a href="http://projekte.textmulch.de/bicbucstriim/#upgrading">upgrade documentation</a> for more information.');
        } else {
            $this->next->call();
        }
    }

    protected function check_config_db()
    {
        $we_have_config = 0;
        $globalSettings = $this->settings();
        if ($this->bbs()->dbOk()) {
            $we_have_config = 1;
            $css = $this->bbs()->configs();
            foreach ($css as $config) {
                if (in_array($config->name, $this->knownConfigs)) {
                    $globalSettings[$config->name] = $config->val;
                } else {
                    $this->log()->warn(join(
                        'own_config_middleware: ',
                        ['Unknown configuration, name: ', $config->name,', value: ',$config->val]
                    ));
                }
            }
            $this->settings($globalSettings);

            if ($globalSettings[DB_VERSION] != DB_SCHEMA_VERSION) {
                $this->log()->warn('own_config_middleware: old db schema detected. please run update');
                return 2;
            }

            if ($globalSettings[LOGIN_REQUIRED] == 1) {
                $this->app()->must_login = true;
                $this->log()->info('multi user mode: login required');
            } else {
                $this->app()->must_login = false;
                $this->log()->debug('easy mode: login not required');
            }
            $this->log()->debug("own_config_middleware: config loaded");
        } else {
            $this->log()->info("own_config_middleware: no config db found - creating a new one with default values");
            $this->bbs()->createDataDb();
            $this->bbs(new BicBucStriim('data/data.db', true));
            $this->bbs()->saveConfigs($this->knownConfigs);
            $we_have_config = 1;
        }
        return $we_have_config;
    }
}
