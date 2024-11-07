<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\Models\Config;
use BicBucStriim\Models\R;

trait HasConfigs
{
    /**
     * Find all configuration values in the settings DB
     * @return array configuration values
     */
    public function configs()
    {
        return R::findAll('config');
    }

    /**
     * Find a specific configuration value by name
     * @param string 	$name 	configuration parameter name
     * @return ?Config	config paramter or null
     */
    public function config($name)
    {
        $config = R::findOne('config', ' name = :name', [':name' => $name]);
        if (!is_null($config)) {
            $config = Config::cast($config);
        }
        return $config;
    }

    /**
     * Save all configuration values in the settings DB
     * @param  array 	$configs 	array of configuration values
     */
    public function saveConfigs($configs)
    {
        foreach ($configs as $name => $val) {
            $config = $this->config($name);
            if (is_null($config)) {
                $config = Config::build($name, $val);
            } else {
                $config->val = $val;
            }
            if ($config->unbox()->getMeta('tainted')) {
                R::store($config);
            }
        }
    }
}
