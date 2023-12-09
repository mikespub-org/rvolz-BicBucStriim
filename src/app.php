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

/*********************************************************************
 * BicBucStriim App (documentation only) - use AppFactory in bootstrap
 ********************************************************************/
class App extends \Slim\App
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
    /** @var \BicBucStriim\Utilities\Logger */
    public $logger;
    /** @var \Twig\Environment */
    public $twig;
    ///** @var \Psr\Container\ContainerInterface|null */
    //public $container;

    public function getContainer(): ?\Psr\Container\ContainerInterface
    {
        return $this->container;
    }
}
