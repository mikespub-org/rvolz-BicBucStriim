<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\AppData;

/**
 * RedBeanPHP FUSE model for 'user' bean
 * @property mixed $id
 * @property mixed $username
 * @property mixed $password
 * @property mixed $tags
 * @property mixed $languages
 * @property mixed $role
 */
class Model_User extends \RedBeanPHP\TypedModel
{
    public function to_json()
    {
        $props = $this->unbox()->getProperties();
        print "to_json";
        print_r($props);
        return json_encode($props);
    }
}
