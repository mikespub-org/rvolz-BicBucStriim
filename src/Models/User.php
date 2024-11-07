<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Models;

/**
 * RedBeanPHP FUSE model for 'user' bean
 * @property mixed $id
 * @property mixed $username
 * @property mixed $password
 * @property mixed $tags
 * @property mixed $languages - to filter books, not user lang
 * @property mixed $role
 */
class User extends Model
{
    protected $_filterProps = ['email', 'password'];

    /**
     * Summary of build
     * @param mixed $username
     * @param mixed $password
     * @return self
     */
    public static function build($username, $password)
    {
        $user = self::cast(R::dispense('user'));
        $user->username = $username;
        $user->password = $password;
        $user->tags = null;
        $user->languages = null;
        $user->role = 0;
        return $user;
    }
}
