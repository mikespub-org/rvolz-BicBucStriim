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

/*********************************************************************
 * App utility interface (documentation only) - use AppTrait in class
 ********************************************************************/
interface AppInterface
{
    /**
     * Get BicBucStriim app data
     * @return \BicBucStriim\AppData\BicBucStriim
     */
    public function bbs();

    /**
     * Get Calibre data
     * @return \BicBucStriim\Calibre\Calibre
     */
    public function calibre();

    /**
     * Get application log
     * @return \Psr\Log\LoggerInterface
     */
    public function log();

    /**
     * Get mailer instance
     * @return \BicBucStriim\Utilities\Mailer
     */
    public function mailer();

    /**
     * Set global app settings
     * @param array<string, mixed>|\BicBucStriim\AppData\Settings $settings
     * @return \BicBucStriim\AppData\Settings
     */
    public function setSettings($settings);

    /**
     * Get global app settings
     * @return \BicBucStriim\AppData\Settings
     */
    public function settings();

    /**
     * Get Twig environment
     * @return \Twig\Environment
     */
    public function twig();

    /**
     * Get container key
     * @param ?string $key
     * @param mixed $value
     * @return mixed
     */
    public function container($key = null, $value = null);

    /**
     * Get response factory
     * @return \Psr\Http\Message\ResponseFactoryInterface
     */
    public function getResponseFactory();
}
