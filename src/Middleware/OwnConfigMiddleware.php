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

use BicBucStriim\AppData\BicBucStriim;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class OwnConfigMiddleware extends DefaultMiddleware
{
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
        $this->request = $request;
        //$response = $this->response();
        $config_status = $this->check_config_db();
        if ($config_status == 0) {
            $this->mkError(500, 'No or bad configuration database. Please use <a href="' .
                $this->getBasePath() .
                '/installcheck.php">installcheck.php</a> to check for errors.');
            return $this->response();
        } elseif ($config_status == 2) {
            // TODO Redirect to an update script in the future
            $this->mkError(500, 'Old configuration database detected. Please refer to the <a href="http://projekte.textmulch.de/bicbucstriim/#upgrading">upgrade documentation</a> for more information.');
            return $this->response();
        } else {
            return $handler->handle($request);
        }
    }

    protected function check_config_db()
    {
        $we_have_config = 0;
        $settings = $this->settings();
        if ($this->bbs()->dbOk()) {
            $we_have_config = 1;
            $css = $this->bbs()->configs();
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
            $this->settings($settings);

            if ($settings->db_version != $settings::DB_SCHEMA_VERSION) {
                $this->log()->warning('own_config_middleware: old db schema detected. please run update');
                return 2;
            }

            if ($settings->must_login == 1) {
                $this->container('must_login', true);
                $this->log()->info('multi user mode: login required');
            } else {
                $this->container('must_login', false);
                $this->log()->debug('easy mode: login not required');
            }
            $this->log()->debug("own_config_middleware: config loaded");
        } else {
            $this->log()->info("own_config_middleware: no config db found - creating a new one with default values");
            $this->bbs()->createDataDb();
            $this->bbs(new BicBucStriim('data/data.db', true));
            $this->bbs()->saveConfigs($this->knownConfigs);
            $we_have_config = 1;
        }
        return $we_have_config;
    }
}
