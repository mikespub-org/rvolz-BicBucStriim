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
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Psr\Http\Message\ResponseInterface as Response;

/*********************************************************************
 * Extra actions
 ********************************************************************/
class ExtraActions extends DefaultActions
{
    public const PREFIX = '/extra';

    /**
     * Add routes for extra actions
     */
    public static function addRoutes($app, $prefix = self::PREFIX, $gatekeeper = null)
    {
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        // use $gatekeeper for all actions in this group
        $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
            RouteUtil::mapRoutes($group, $routes);
        })->add($gatekeeper);
    }

    /**
     * Get routes for extra actions
     * @param self|class-string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // name => method(s), path, ...middleware(s), callable
            'extra-loader-path' => ['GET', '/loader/{path:.*}', [$self, 'loader']],
            'extra-loader' => ['GET', '/loader', [$self, 'loader']],
            'extra' => ['GET', '/', [$self, 'extra']],
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
            'page' => $this->buildPage('extra', 0, 2),
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

        /**
         * Define callbacks to update information here
         */
        $gConfig['callbacks'] = [
            'setAuthorInfo' => $this->setAuthorInfo(...),
            'setSeriesInfo' => $this->setSeriesInfo(...),
            'setBookInfo' => $this->setBookInfo(...),
        ];

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

        // update requester with query params
        $this->requester->setParams($urlParams);

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

    /**
     * Callback function for Loader to set author info here
     * @param int $authorId
     * @param AuthorInfo $authorInfo
     * @return bool
     */
    public function setAuthorInfo($authorId, $authorInfo)
    {
        $author = $this->calibre()->author($authorId);
        $appentity = $this->bbs()->author($authorId, $author->name);
        $settings = $this->settings();
        $clipped = $settings->thumb_gen_clipped;

        $result = true;
        if (!empty($authorInfo->image) && str_starts_with($authorInfo->image, 'http')) {
            $image = $appentity->saveThumbnail($authorInfo->image, $clipped);
            $result = $result && ($image ? true : false);
        }
        if (!empty($authorInfo->link) && str_starts_with($authorInfo->link, 'http')) {
            // check for duplicate links
            $links = array_filter($appentity->getLinks(), function ($link) use ($authorInfo) {
                return $link->url == $authorInfo->link;
            });
            if (empty($links)) {
                $label = $authorInfo->source . ' Link';
                $link = $appentity->addLink($label, $authorInfo->link);
            } else {
                $link = true;
            }
            $result = $result && ($link ? true : false);
        }
        if (!empty($authorInfo->note) && !empty($authorInfo->note->doc)) {
            $root = $this->requester->getRootUrl();
            $dbNum = $this->requester->get('dbNum');
            $urlPrefix = $root . '/extra/loader/resource/' . $dbNum;
            $content = $authorInfo->note->parseHtml($urlPrefix);
            $mimeType = 'text/html';
            $note = $appentity->editNote($mimeType, $content);
            $result = $result && ($note ? true : false);
        }
        if (!empty($authorInfo->books)) {
            // ...
        }
        if (!empty($authorInfo->series)) {
            // ...
        }
        return $result;
    }

    /**
     * Callback function for Loader to set series info here
     * @param int $seriesId
     * @param SeriesInfo $seriesInfo
     * @return bool
     */
    public function setSeriesInfo($seriesId, $seriesInfo)
    {
        $series = $this->calibre()->series($seriesId);
        $appentity = $this->bbs()->series($seriesId, $series->name);
        $settings = $this->settings();
        $clipped = $settings->thumb_gen_clipped;

        $result = true;
        if (!empty($seriesInfo->image) && str_starts_with($seriesInfo->image, 'http')) {
            $image = $appentity->saveThumbnail($seriesInfo->image, $clipped);
            $result = $result && ($image ? true : false);
        }
        if (!empty($seriesInfo->link) && str_starts_with($seriesInfo->link, 'http')) {
            // check for duplicate links
            $links = array_filter($appentity->getLinks(), function ($link) use ($seriesInfo) {
                return $link->url == $seriesInfo->link;
            });
            if (empty($links)) {
                $label = $seriesInfo->source . ' Link';
                $link = $appentity->addLink($label, $seriesInfo->link);
            } else {
                $link = true;
            }
            $result = $result && ($link ? true : false);
        }
        if (!empty($seriesInfo->note) && !empty($seriesInfo->note->doc)) {
            $root = $this->requester->getRootUrl();
            $dbNum = $this->requester->get('dbNum');
            $urlPrefix = $root . '/extra/loader/resource/' . $dbNum;
            $content = $seriesInfo->note->parseHtml($urlPrefix);
            $mimeType = 'text/html';
            $note = $appentity->editNote($mimeType, $content);
            $result = $result && ($note ? true : false);
        }
        if (!empty($seriesInfo->books)) {
            // ...
        }
        if (!empty($seriesInfo->authors)) {
            // ...
        }
        return $result;
    }

    /**
     * Callback function for Loader to set book info here
     * @param int $bookId
     * @param BookInfo $bookInfo
     * @return bool
     */
    public function setBookInfo($bookId, $bookInfo)
    {
        $book = $this->calibre()->title($bookId);
        $appentity = $this->bbs()->book($bookId, $book->title);
        $settings = $this->settings();
        $clipped = $settings->thumb_gen_clipped;

        $result = true;
        if (!empty($bookInfo->cover) && str_starts_with($bookInfo->cover, 'http')) {
            $image = $appentity->saveThumbnail($bookInfo->cover, $clipped);
            $result = $result && ($image ? true : false);
        }
        if (!empty($bookInfo->uri) && str_starts_with($bookInfo->uri, 'http')) {
            // check for duplicate links
            $links = array_filter($appentity->getLinks(), function ($link) use ($bookInfo) {
                return $link->url == $bookInfo->uri;
            });
            if (empty($links)) {
                $label = $bookInfo->source . ' Link';
                $link = $appentity->addLink($label, $bookInfo->uri);
            } else {
                $link = true;
            }
            $result = $result && ($link ? true : false);
        }
        if (!empty($bookInfo->description)) {
            $content = $bookInfo->description;
            $mimeType = 'text/html';
            $note = $appentity->editNote($mimeType, $content);
            $result = $result && ($note ? true : false);
        }
        if (!empty($bookInfo->identifiers)) {
            // ...
        }
        if (!empty($bookInfo->authors)) {
            // ...
        }
        if (!empty($bookInfo->series)) {
            // ...
        }
        return $result;
    }
}
