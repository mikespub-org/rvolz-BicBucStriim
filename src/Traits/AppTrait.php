<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\AppData\Settings;
use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Session\AuthServices;
use BicBucStriim\Utilities\Mailer;
use BicBucStriim\Utilities\Thumbnails;
use BicBucStriim\Framework\ContainerAdapter;
use BicBucStriim\Framework\RendererInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/*********************************************************************
 * App utility trait
 ********************************************************************/
trait AppTrait
{
    /** @var \Psr\Container\ContainerInterface|ContainerAdapter|null */
    protected $container;

    /**
     * Get BicBucStriim app data
     * @return BicBucStriim
     */
    public function bbs()
    {
        return $this->container(BicBucStriim::class);
    }

    /**
     * Get Calibre data
     * @return Calibre
     */
    public function calibre()
    {
        return $this->container(Calibre::class);
    }

    /**
     * Get application log
     * @return \Psr\Log\LoggerInterface
     */
    public function log()
    {
        return $this->container(LoggerInterface::class);
    }

    /**
     * Get mailer instance
     * @return Mailer
     */
    public function mailer()
    {
        return $this->container(Mailer::class);
    }

    /**
     * Set global app settings
     * @param array<string, mixed>|Settings $settings
     * @return Settings
     */
    public function setSettings($settings)
    {
        if (is_array($settings)) {
            $settings = new Settings($settings);
        }
        // Ensure the container is always our adapter, which provides the non-standard set() method.
        if (!method_exists($this->container, 'set')) {
            $this->container = new ContainerAdapter($this->container);
        }
        // @todo avoid re-setting container value here
        $this->container->set(Settings::class, $settings);
        return $this->container->get(Settings::class);
    }

    /**
     * Get global app settings
     * @return Settings
     */
    public function settings()
    {
        return $this->container(Settings::class);
    }

    /**
     * Get thumbnails
     * @return Thumbnails
     */
    public function thumbs()
    {
        return $this->container(Thumbnails::class);
    }

    /**
     * Get renderer from container
     * @return RendererInterface
     */
    public function renderer()
    {
        return $this->container(RendererInterface::class);
    }

    /**
     * Get container key
     * @param ?string $key
     * @return mixed
     */
    public function container($key = null)
    {
        if (empty($key)) {
            return $this->container;
        }
        if ($this->container->has($key)) {
            return $this->container->get($key);
        }
        return null;
    }

    /**
     * Get response factory
     * @return \Psr\Http\Message\ResponseFactoryInterface
     */
    public function getResponseFactory()
    {
        return $this->container(ResponseFactoryInterface::class);
    }

    /**
     * Get auth services
     * @return AuthServices
     */
    public function getAuthService()
    {
        return $this->container(AuthServices::class);
    }
}
