<?php

namespace BicBucStriim\Utilities;

/**
 * Logger with replacement for \Slim\Logger\DateTimeFileWriter - see config/config.php
 */
class Logger extends \Apix\Log\Logger
{
    /** @var bool */
    public $enabled;

    /**
     * See https://github.com/slimphp/Slim/blob/2.x/Slim/Log.php#L126
     * Enable or disable logging
     * @param  bool $enabled
     */
    public function setEnabled($enabled)
    {
        if ($enabled) {
            $this->enabled = true;
        } else {
            $this->enabled = false;
            $this->buckets = [];
            $this->setMinLevel(\Psr\Log\LogLevel::EMERGENCY, false);
        }
    }
}
