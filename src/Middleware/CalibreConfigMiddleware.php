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

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CalibreConfigMiddleware extends DefaultMiddleware
{
    /**
     * Initialize the configuration
     *
     * @param \BicBucStriim\App|\Slim\App|object $app The app
     */
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Check if the Calibre configuration is valid:
     * - If Calibre dir is undefined -> goto admin page
     * - If Calibre cannot be opened -> goto admin page
     * @param Request $request The request
     * @param RequestHandler $handler The handler
     * @return Response The response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->request = $request;
        //$response = $this->response();
        $settings = $this->settings();

        $resource = $this->getResourceUri();
        if ($resource != '/login/') {
            # 'After installation' scenario: here is a config DB but no valid connection to Calibre
            if (empty($settings->calibre_dir)) {
                $this->log()->warning('check_config: Calibre library path not configured.');
                if ($resource != '/admin/configuration/') {
                    // app->redirect not useable in middleware
                    $this->mkRedirect($this->getRootUri() . '/admin/configuration/', 302, false);
                    return $this->response();
                } else {
                    return $handler->handle($request);
                }
            } else {
                # Setup the connection to the Calibre metadata db
                $clp = $settings->calibre_dir . '/metadata.db';
                $this->calibre(new \BicBucStriim\Calibre\Calibre($clp));
                if (!$this->calibre()->libraryOk() && $resource != '/admin/configuration/') {
                    $this->log()->error('check_config: Exception while opening metadata db ' . $clp . '. Showing admin page.');
                    // app->redirect not useable in middleware
                    $this->mkRedirect($this->getRootUri() . '/admin/configuration/', 302, false);
                    return $this->response();
                } else {
                    return $handler->handle($request);
                }
            }
        } else {
            return $handler->handle($request);
        }
    }
}
