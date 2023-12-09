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

class CalibreConfigMiddleware extends DefaultMiddleware
{
    protected $calibreDir;

    /**
     * Initialize the configuration
     *
     * @param \BicBucStriim\App $app
     * @param string $calibreDir
     */
    public function __construct($app, $calibreDir)
    {
        parent::__construct($app);
        $this->calibreDir = $calibreDir;
    }

    /**
     * Check if the Calibre configuration is valid:
     * - If Calibre dir is undefined -> goto admin page
     * - If Calibre cannot be opened -> goto admin page
     */
    public function call()
    {
        $globalSettings = $this->settings();
        $request = $this->request();

        if ($request->getResourceUri() != '/login/') {
            # 'After installation' scenario: here is a config DB but no valid connection to Calibre
            if (empty($globalSettings[$this->calibreDir])) {
                $this->log()->warning('check_config: Calibre library path not configured.');
                if ($request->getResourceUri() != '/admin/configuration/') {
                    // app->redirect not useable in middleware
                    $this->mkRedirect($request->getRootUri() . '/admin/configuration/', 302, false);
                } else {
                    $this->next->call();
                }
            } else {
                # Setup the connection to the Calibre metadata db
                $clp = $globalSettings[$this->calibreDir] . '/metadata.db';
                $this->calibre(new \BicBucStriim\Calibre\Calibre($clp));
                if (!$this->calibre()->libraryOk() && $request->getResourceUri() != '/admin/configuration/') {
                    $this->log()->error('check_config: Exception while opening metadata db ' . $clp . '. Showing admin page.');
                    // app->redirect not useable in middleware
                    $this->mkRedirect($request->getRootUri() . '/admin/configuration/', 302, false);
                } else {
                    $this->next->call();
                }
            }
        } else {
            $this->next->call();
        }
    }
}
