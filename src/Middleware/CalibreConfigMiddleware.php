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

use BicBucStriim\Utilities\RequestUtil;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Exception;

class CalibreConfigMiddleware extends DefaultMiddleware
{
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
        $requestUtil = new RequestUtil($request);
        $resource = $requestUtil->getPathInfo();
        if ($resource == '/login/') {
            return $handler->handle($request);
        }
        if (!$this->check_calibre() && $resource != '/admin/configuration/') {
            // app->redirect not useable in middleware
            return $this->mkRedirect($requestUtil->getBasePath() . '/admin/configuration/');
        }
        return $handler->handle($request);
    }

    /**
     * Check calibre database
     * @return bool
     */
    protected function check_calibre()
    {
        $settings = $this->settings();
        # 'After installation' scenario: here is a config DB but no valid connection to Calibre
        if (empty($settings->calibre_dir)) {
            $this->log()->warning('check_config: Calibre library path not configured.');
            return false;
        }
        # Setup the connection to the Calibre metadata db
        try {
            $calibre = $this->calibre();
        } catch (Exception $e) {
            $this->log()->error('check_config: Exception while opening metadata db in ' . $settings->calibre_dir);
            return false;
        }
        if (!$calibre->libraryOk()) {
            $clp = $settings->calibre_dir . '/metadata.db';
            $this->log()->error('check_config: Exception while opening metadata db ' . $clp . '. Showing admin page.');
            return false;
        }
        return true;
    }
}
