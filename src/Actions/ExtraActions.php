<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Actions;

use BicBucStriim\Utilities\RouteUtil;
use Marsender\EPubLoader\RequestHandler;
use Marsender\EPubLoader\App\ExtraActions as LoaderActions;
use Psr\Http\Message\ResponseInterface as Response;

/*********************************************************************
 * Extra actions
 ********************************************************************/
class ExtraActions extends DefaultActions
{
    /**
     * Add routes for extra actions
     */
    public static function addRoutes($app, $prefix = '/extra', $gatekeeper = null)
    {
        //$self = new self($app);
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        // use $gatekeeper for all actions in this group
        $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
            RouteUtil::mapRoutes($group, $routes);
        })->add($gatekeeper);
    }

    /**
     * Get routes for extra actions
     * @param self|string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // method(s), path, ...middleware(s), callable
            ['GET', '/loader/{path:.*}', [$self, 'loader']],
            ['GET', '/loader', [$self, 'loader']],
            ['GET', '/', [$self, 'extra']],
        ];
    }

    public function extra()
    {
        $options = [];
        if (class_exists('\Marsender\EPubLoader\RequestHandler')) {
            $options[] = [
                'id' => 'loader',
                'label' => 'BBS Loader',
                'description' => 'Look up metadata about authors, books and series',
                'external' => true,
            ];
        }
        $version = $this->calibre()->getUserVersion();
        $required = $this->calibre()::USER_VERSION;
        $flash = [];
        if (!empty($version) && $version < $required) {
            $flash['error'] = $this->getMessageString('database_upgrade') . ' ';
            $flash['error'] .= sprintf($this->getMessageString('admin_new_version'), $required, $version);
        }
        return $this->render('extra.twig', [
            'page' => $this->mkPage('extra', 0, 2),
            'options' => $options,
            'flash' => $flash,
        ]);
    }

    /**
     * EPub Loader -> GET /metadata/loader/{path:.*} (dev only)
     * @return Response
     */
    public function loader($path = '')
    {
        if (!class_exists('\Marsender\EPubLoader\RequestHandler')) {
            $this->log()->warning('loader: class does not exist');
            $message = 'This action is available in developer mode only (without --no-dev option):' . "<br/>\n";
            $message .= '$ composer install -o';
            return $this->responder->error(400, $message);
        }
        $settings = $this->settings();
        $root = $this->requester->getRootUrl();

        // get the global config for epub-loader from config/loader.php
        $gConfig = require dirname(__DIR__, 2) . '/config/loader.php';
        // adapt for use with BBS
        $gConfig['endpoint'] = $root . '/extra/loader';
        $gConfig['app_name'] = 'BBS Loader';
        $gConfig['version'] = $settings['version'];
        $gConfig['admin_email'] = '';
        $gConfig['create_db'] = false;

        // specify a cache directory for any Google or Wikidata lookup
        $cacheDir = $gConfig['cache_dir'] ?? dirname(__DIR__, 2) . '/data/cache';
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0o777, true)) {
            $message = 'Please make sure the cache directory can be created';
            $this->log()->warning('loader: ' . $message);
            return $this->responder->error(400, $message);
        }
        if (!is_writable($cacheDir)) {
            $message = 'Please make sure the cache directory is writeable';
            $this->log()->warning('loader: ' . $message);
            return $this->responder->error(400, $message);
        }

        /**
        //$gConfig['databases'] = [];
        // get the current BBS calibre directories
        $calibreDir = Config::get('calibre_directory');
        if (!is_array($calibreDir)) {
            $calibreDir = ['BBS Database' => $calibreDir];
        }
        foreach ($calibreDir as $name => $path) {
            $gConfig['databases'][] = ['name' => $name, 'db_path' => rtrim((string) $path, '/'), 'epub_path' => '.'];
        }
        */

        // add optional query param for path in loader for Swagger UI
        if ($this->requester->isJsonApi() && empty($path) && !empty($this->requester->get('path'))) {
            $path = $this->requester->get('path');
        }

        // Format: {action}/{dbNum:\\d+}/{authorId:\\w+}/{urlPath:.*}
        $path .= '///';
        [$action, $dbNum, $authorId, $urlPath] = explode('/', $path, 4);

        // Set path params in urlParams for request handler
        $urlParams = $this->requester->get();
        if (empty($action)) {
            $action = null;
        }
        $urlParams['action'] ??= $action;
        if (!is_numeric($dbNum)) {
            $dbNum = null;
        }
        $urlParams['dbNum'] ??= $dbNum;
        if (empty($authorId)) {
            $authorId = null;
        }
        $urlParams['authorId'] ??= $authorId;
        $urlPath = trim($urlPath, '/');
        $urlParams['urlPath'] ??= $urlPath;

        // you can define extra actions for your app - see example.php
        $handler = new RequestHandler($gConfig, LoaderActions::class, $cacheDir);
        $result = $handler->request($action, $dbNum, $urlParams, $urlPath);

        if (method_exists($handler, 'isDone')) {
            if ($handler->isDone()) {
                return $this->responder->done();
            }
        }

        // render a json response if hasapi with Accept header - see DefaultActions::render()
        if ($this->requester->isJsonApi()) {
            return $this->renderJson($result);
        }

        // handle the result yourself or let epub-loader generate the output
        $result = array_merge($gConfig, $result);
        //$templateDir = 'templates/loader';  // if you want to use custom templates
        $templateDir = $gConfig['template_dir'] ?? null;
        $template = null;

        $output = $handler->output($result, $templateDir, $template);
        return $this->responder->html($output);
    }
}
