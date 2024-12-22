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

use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\ResponseUtil;
use BicBucStriim\Utilities\RouteUtil;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/*********************************************************************
 * Default actions
 ********************************************************************/
class DefaultActions implements \BicBucStriim\Traits\AppInterface
{
    use \BicBucStriim\Traits\AppTrait;

    public const PREFIX = null;

    /** @var RequestUtil|null */
    protected $requester = null;
    /** @var ResponseUtil|null */
    protected $responder = null;

    /** @var string|null */
    protected $templatesDir = null;

    /**
     * Add routes for default actions
     * @param \Slim\App|object $app
     * @param ?string $prefix
     * @param ?object $gatekeeper (optional)
     * @return void
     */
    public static function addRoutes($app, $prefix = self::PREFIX, $gatekeeper = null)
    {
        // Slim 4 framework uses its own CallableResolver if this is a class string, *before* invocation strategy
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        if (!empty($prefix)) {
            $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
                RouteUtil::mapRoutes($group, $routes);
            });
        } else {
            RouteUtil::mapRoutes($app, $routes);
        }
    }

    /**
     * Get routes for default actions
     * @param self|class-string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // name => method(s), path, ...middleware(s), callable
            'hello' => ['GET', '/', [$self, 'hello']],
            'hello-name' => ['GET', '/{name}', [$self, 'hello']],
        ];
    }

    /**
     * This will be instantiated by callable route resolver with dependency injection
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Initialize actions with ActionsWrapperStrategy
     * @param ?Request $request
     * @param ?Response $response
     * @return void
     */
    public function initialize($request, $response)
    {
        $this->requester = new RequestUtil($request, $this->settings());
        // Slim\App contains responseFactory as mandatory first param in constructor
        $response ??= $this->getResponseFactory()->createResponse();
        //$response ??= new \Slim\Psr7\Response();
        $this->responder = new ResponseUtil($response);
    }

    /**
     * Hello function (example)
     * @param ?string $name
     * @return Response
     */
    public function hello($name = null)
    {
        return $this->helloResponse($name);
    }

    /**
     * Hello function (example) - new-style = returning response
     * @param ?string $name
     * @return Response
     */
    public function helloResponse($name = null)
    {
        $name ??= 'world';
        $answer = 'Hello, ' . $name . '!';
        return $this->responder->respond($answer, 'text/plain');
    }

    /**
     * Get filter based on user languages and tags
     * @return \BicBucStriim\Calibre\CalibreFilter
     */
    public function getFilter()
    {
        $lang = null;
        $tag = null;
        if ($this->requester->isAuthenticated()) {
            $user = $this->requester->getAuth()->getUserData();
            $this->log()->debug('getFilter: ' . var_export($user, true));
            if (!empty($user['languages'])) {
                $lang = $this->calibre()->getLanguageId($user['languages']);
            }
            if (!empty($user['tags'])) {
                $tag = $this->calibre()->getTagId($user['tags']);
            }
            $this->log()->debug('getFilter: Using language ' . $lang . ', tag ' . $tag);
        }
        return new \BicBucStriim\Calibre\CalibreFilter($lang, $tag);
    }

    /**
     * Create json response for template data (if hasapi with Accept header)
     * @param  array  $data     Associative array of data made available to the view
     * @param  ?int    $status   The HTTP response status code to use (optional)
     * @return Response
     */
    public function renderJson($data = [], $status = null)
    {
        if (array_key_exists('page', $data) && is_array($data['page'])) {
            unset($data['page']['glob']);
            unset($data['page']['admin']);
        }
        if (array_key_exists('users', $data) && is_array($data['users'])) {
            foreach (array_keys($data['users']) as $id) {
                unset($data['users'][$id]['email']);
                unset($data['users'][$id]['password']);
            }
        }
        // Add Allow-Origin + Allow-Credentials to response for non-preflighted requests
        $origin = $this->requester->getCorsOrigin();
        return $this->responder->json($data, $origin);
    }

    /**
     * Render a template (or json response if hasapi with Accept header)
     * @param  string $template The name of the template passed into the view's render() method
     * @param  array  $data     Associative array of data made available to the view
     * @param  ?int    $status   The HTTP response status code to use (optional)
     * @return Response
     */
    public function render($template, $data = [], $status = null)
    {
        if ($this->requester->isJsonApi()) {
            return $this->renderJson($data, $status);
        }
        // Slim 2 framework will replace data, render template and echo output via slim view display()
        $this->setTemplatesDir();
        $content = $this->twig()->render($template, $data);
        return $this->responder->html($content);
    }

    /**
     * Set custom templates directory (once)
     * @return void
     */
    public function setTemplatesDir()
    {
        if (is_null($this->templatesDir)) {
            $this->templatesDir = '';
            $settings = $this->settings();
            // convert to real path here
            if (!empty($settings->templates_dir)) {
                $this->templatesDir = realpath($settings->templates_dir);
            }
            // override default templates if available
            if (!empty($this->templatesDir)) {
                $this->twig()->getLoader()->prependPath($this->templatesDir);
            }
        }
    }

    /**
     * Utility function to fill the page array
     */
    public function buildPage($messageId = '', $menu = 0, $level = 0)
    {
        $settings = $this->settings();

        $subtitle = $this->getMessageString($messageId);
        if ($subtitle == '') {
            $title = $settings->display_app_name;
        } else {
            $title = $settings->display_app_name . $settings['sep'] . $subtitle;
        }
        $templatesDirName = '';
        if (!empty($settings->templates_dir)) {
            $templatesDirName = basename($settings->templates_dir);
        }
        $root = $this->requester->getRootUrl();
        $auth = $this->requester->isAuthenticated();
        if ($settings->must_login) {
            $adm = $this->requester->isAdmin();
        } else {
            $adm = true;
        }    # the admin button should be always visible if no login is required
        $page = ['title' => $title,
            'rot' => $root,
            'h1' => $subtitle,
            'version' => $settings['version'],
            'custom' => $templatesDirName,
            'glob' => $settings,
            'menu' => $menu,
            'level' => $level,
            'auth' => $auth,
            'admin' => $adm];
        return $page;
    }

    /**
     * Return a localized message string for $id.
     *
     * If there is no defined message for $id in the current language the function
     * looks for an alterantive in English. If that also fails an error message
     * is returned.
     *
     * @param  string $id message id
     * @return string     localized message string
     */
    public function getMessageString($id)
    {
        $settings = $this->settings();
        $msg = $settings['l10n']->message($id);
        return $msg;
    }

    /**
     * Set flash message for next request
     * @param  string   $key
     * @param  mixed    $value
     * @return void
     */
    public function setFlash($key, $value)
    {
        $session = $this->requester->getSession();
        if (empty($session)) {
            return;
        }
        $session->getLocalSegment()->setFlash($key, $value);
    }

    /**
     * Get flash from previous request
     * @param  string   $key
     * @return void|mixed
     */
    public function getFlash($key)
    {
        $session = $this->requester->getSession();
        if (empty($session)) {
            return;
        }
        return $session->getLocalSegment()->getFlash($key);
    }
}
