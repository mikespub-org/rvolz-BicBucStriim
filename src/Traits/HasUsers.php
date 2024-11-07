<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\Models\User;
use BicBucStriim\Models\R;
use Exception;

trait HasUsers
{
    /**
     * Find all user records in the settings DB
     * @return array user data
     */
    public function users()
    {
        return R::findAll('user');
    }

    /**
     * Find a specific user in the settings DB
     * @return ?User user data or NULL if not found
     */
    public function user($userid)
    {
        $user = R::load('user', $userid);
        if (empty($user->id)) {
            return null;
        } else {
            return User::cast($user);
        }
    }

    /**
     * Add a new user account.
     * The username must be unique. Name and password must not be empty.
     * @param $username string login name for the account, must be unique
     * @param $password string clear text password
     * @return ?User user account or null if the user exists or one of the parameters is empty
     * @throws Exception if the DB operation failed
     */
    public function addUser($username, $password)
    {
        if (empty($username) || empty($password)) {
            return null;
        }
        $other = R::findOne('user', ' username = :name', [':name' > $username]);
        if (!is_null($other)) {
            return null;
        }
        $mdp = password_hash($password, PASSWORD_BCRYPT);
        $user = User::build($username, $mdp);
        $id = R::store($user);
        return $user;
    }

    /**
     * Delete a user account from the database.
     * The admin account (ID 1) can't be deleted.
     * @param $userid integer
     * @return bool true if a user was deleted else false
     */
    public function deleteUser($userid)
    {
        if ($userid == 1) {
            return false;
        } else {
            /** @var User $user */
            $user = R::load('user', $userid);
            if (!$user->id) {
                return false;
            } else {
                R::trash($user);
                return true;
            }
        }
    }

    /**
     * Update an existing user account.
     * The username cannot be changed and the password must not be empty.
     * @param integer 	$userid 	integer
     * @param string 	$password 	new clear text password or old encrypted password
     * @param string 	$languages 	comma-delimited set of language identifiers
     * @param string 	$tags 		string comma-delimited set of tags
     * @param string 	$role       "1" for admin "0" for normal user
     * @return ?User updated user account or null if there was an error
     */
    public function changeUser($userid, $password, $languages, $tags, $role)
    {
        $user = $this->user($userid);
        if (is_null($user)) {
            return null;
        }
        if (empty($password)) {
            return null;
        } else {
            $mdp = password_hash($password, PASSWORD_BCRYPT);
            if ($password != $user->password) {
                $user->password = $mdp;
            }
            $user->languages = $languages;
            $user->tags = $tags;
            if (strcasecmp($role, "admin") == 0) {
                $user->role = "1";
            } else {
                $user->role = "0";
            }
            try {
                $id = R::store($user);
                return $user;
            } catch (Exception $e) {
                return null;
            }
        }
    }
}
