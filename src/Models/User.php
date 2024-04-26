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
}
