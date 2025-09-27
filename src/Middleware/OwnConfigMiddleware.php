<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Middleware;

use BicBucStriim\Utilities\ResponseUtil;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Exception;

class OwnConfigMiddleware extends DefaultMiddleware
{
    protected const STATUS_OOPS = 0;
    protected const STATUS_OK = 1;
    protected const STATUS_OLD = 2;

    protected $knownConfigs;

    /**
     * Initialize the configuration
     *
     * @param array $knownConfigs
     */
    public function __construct(ContainerInterface $container, $knownConfigs)
    {
        parent::__construct($container);
        $this->knownConfigs = $knownConfigs;
    }

    /**
     * Check if own configuration is valid
     * @param Request $request The request
     * @param RequestHandler $handler The handler
     * @return Response The response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $requester = $this->setRequester($request);
        $config_status = $this->check_config_db();
        if ($config_status == static::STATUS_OOPS) {
            $responder = new ResponseUtil(null);
            return $responder->error(500, 'No or bad configuration database. Please use <a href="'
                . $requester->getBasePath()
                . '/installcheck.php">installcheck.php</a> to check for errors.');
        } elseif ($config_status == static::STATUS_OLD) {
            $responder = new ResponseUtil(null);
            return $responder->error(500, 'Old configuration database detected. Please refer to the <a href="https://github.com/mikespub-org/rvolz-BicBucStriim#upgrading">upgrade documentation</a> for more information.');
        } else {
            return $handler->handle($request);
        }
    }

    protected function check_config_db()
    {
        $we_have_config = static::STATUS_OOPS;
        try {
            $bbs = $this->bbs();
        } catch (Exception $e) {
            $this->log()->error("own_config_middleware: " . $e->getMessage());
            return $we_have_config;
        }
        if (!$bbs->dbOk()) {
            $this->log()->info("own_config_middleware: no config db found");
            return $we_have_config;
        }
        $we_have_config = static::STATUS_OK;
        # Update global settings from known configs in bbs db
        $settings = $this->settings();
        $css = $bbs->configs();
        foreach ($css as $config) {
            if (in_array($config->name, $this->knownConfigs)) {
                $settings[$config->name] = $config->val;
            } else {
                $this->log()->warning(join(
                    'own_config_middleware: ',
                    ['Unknown configuration, name: ', $config->name,', value: ',$config->val]
                ));
            }
        }
        $this->setSettings($settings);

        if ($settings->db_version != $settings::DB_SCHEMA_VERSION) {
            $this->log()->warning('own_config_middleware: old db schema detected. please run update');
            $we_have_config = static::STATUS_OLD;
            return $we_have_config;
        }

        if ($settings->must_login == 1) {
            $this->container('must_login', true);
            $this->log()->info('multi user mode: login required');
        } else {
            $this->container('must_login', false);
            $this->log()->debug('easy mode: login not required');
        }
        $this->log()->debug("own_config_middleware: config loaded");
        return $we_have_config;
    }
}
