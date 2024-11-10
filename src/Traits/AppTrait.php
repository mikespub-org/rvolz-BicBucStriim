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
use BicBucStriim\Utilities\Mailer;
use BicBucStriim\Utilities\Thumbnails;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/*********************************************************************
 * App utility trait
 ********************************************************************/
trait AppTrait
{
    /** @var ?\Psr\Container\ContainerInterface */
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
        return $this->container(Settings::class, $settings);
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
     * Get Twig environment
     * @return \Twig\Environment
     */
    public function twig()
    {
        return $this->container(\Twig\Environment::class);
    }

    /**
     * Get container key
     * @param ?string $key
     * @param mixed $value
     * @return mixed
     */
    public function container($key = null, $value = null)
    {
        if (empty($key)) {
            return $this->container;
        }
        if (!is_null($value)) {
            // @todo let phpstan know we're dealing with a container that can set()
            assert($this->container instanceof \DI\Container);
            $this->container->set($key, $value);
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
}
