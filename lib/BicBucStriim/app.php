<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim;

class App extends \Slim\Slim
{
    /** @var \Aura\Auth\Auth */
    public $auth;
    /** @var \BicBucStriim\AppData\BicBucStriim */
    public $bbs;
    /** @var \BicBucStriim\Calibre\Calibre */
    public $calibre;
    /** @var \Aura\Auth\Service\LoginService */
    public $login_service;
    /** @var \Aura\Auth\Service\LogoutService */
    public $logout_service;
    /** @var bool */
    public $must_login;
    ///** @var \BicBucStriim\TwigView set in container as singleton by Slim\Slim constructor */
    //public $view;
}