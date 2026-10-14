<?php

/**
 * BicBucStriim
 *
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Actions;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

/*********************************************************************
 * JSON API actions
 ********************************************************************/
class ApiActions extends DefaultActions
{
    public const PREFIX = '/api';

    /** @var ActionRegistry */
    protected $registry;

    /**
     * Get the extra route(s) for API actions to be registered at the
     * application root: the CORS preflight route for any API route(s).
     *
     * @param self|class-string $self
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each extra route
     */
    public static function getExtraRoutes($self)
    {
        return [
            // name => method(s), path, ...middleware(s), callable
            // return CORS options for any route(s)
            'cors-options' => ['OPTIONS', '/{routes:.*}', [$self, 'corsOptions']],
        ];
    }

    /**
     * Get routes for API actions
     * @param self|class-string $self
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self)
    {
        return [
            // name => method(s), path, ...middleware(s), callable
            'api-home' => ['GET', '/', [$self, 'home']],
            'api-routes' => ['GET', '/routes', [$self, 'routes']],
            'api-openapi' => ['GET', '/openapi.json', [$self, 'openapi']],
        ];
    }

    /**
     * This will be instantiated by callable route resolver with dependency injection.
     *
     * Depends on the framework-agnostic ActionRegistry (not Slim's
     * RouteCollectorInterface) so API introspection (routes()/openapi()) works
     * identically under every supported framework. The registry is a shared
     * singleton that already carries the fully-populated route map.
     */
    public function __construct(ContainerInterface $container, ActionRegistry $registry)
    {
        $this->registry = $registry;
        parent::__construct($container);
    }

    /**
     * Generate the API home page
     * @return Response
     */
    public function home()
    {
        $settings = $this->settings();
        $title = $settings->display_app_name;
        $root = $this->requester->getRootUrl();
        $link = $root . '/api/openapi.json';
        return $this->render('api_home.twig', [
            'title' => $title,
            'link' => $link]);
    }

    /**
     * Get the list of routes
     * @return Response
     */
    public function routes()
    {
        $settings = $this->settings();
        $title = $settings->display_app_name;
        $root = $this->requester->getRootUrl();
        $patterns = [];
        foreach ($this->registry->getRouteMap() as $route) {
            $methods = is_string($route->methods) ? [$route->methods] : $route->methods;
            $link = $root . $route->path;
            $patterns[$link] ??= [];
            $patterns[$link] = array_merge($patterns[$link], array_filter($methods, function ($method) {
                return $method !== 'HEAD';
            }));
        }
        $data = [
            'title' => $title,
            'routes' => $patterns,
        ];
        return $this->responder->json($data);
    }

    /**
     * Get minimal OpenAPI specification for the routes
     * @return Response
     */
    public function openapi()
    {
        $data = $this->getOpenApi();
        return $this->responder->json($data);
    }

    /**
     * Send CORS options
     * @return Response
     */
    public function corsOptions($routes = '')
    {
        $origin = $this->requester->getCorsOrigin();
        return $this->responder->cors($origin);
    }

    /**
     * Summary of getOpenApi
     * @return array<string, mixed>
     */
    public function getOpenApi()
    {
        $root = $this->requester->getRootUrl();
        $settings = $this->settings();
        $result = [
            "openapi" => "3.0.3",
            "info" => [
                "title" => $settings['appname'] . " API",
                "version" => $settings['version'],
            ],
        ];
        $result["servers"] = [
            ["url" => $root, "description" => $settings['appname'] . " API Endpoint"],
        ];
        $result["components"] = [
            "securitySchemes" => [
                "cookieAuth" => [
                    "type" => "apiKey",
                    "in" => "cookie",
                    "name" => "PHPSESSID",
                ],
            ],
            "parameters" => [],
        ];
        $result["paths"] = [];
        foreach ($this->registry->getRouteMap() as $name => $route) {
            $path = $route->path;
            $methods = is_string($route->methods) ? [$route->methods] : $route->methods;
            $methods = array_filter($methods, function ($method) {
                return $method !== 'HEAD';
            });
            // Use the stable route-map name as both the display name and the
            // operationId base (replaces Slim's volatile auto-generated id).
            $operationId = $name;
            // @todo there should be only one here
            if (count($methods) > 1) {
                $operationId = null;
            }
            $params = [];
            $found = [];
            // support custom pattern for route placeholders - see nikic/fast-route
            if (preg_match_all("~\{(\w+(|:[^}]+))\}~", $path, $found)) {
                foreach ($found[1] as $param) {
                    $schema = [
                        "type" => "string",
                    ];
                    $required = true;
                    if (str_contains($param, ':')) {
                        [$param, $pattern] = explode(':', $param);
                        $schema["pattern"] = '^' . $pattern . '$';
                        $path = str_replace(':' . $pattern, '', $path);
                        if ($pattern == '.*') {
                            $required = false;
                        }
                    }
                    array_push($params, [
                        "name" => $param,
                        "in" => "path",
                        "required" => $required,
                        "schema" => $schema,
                    ]);
                }
            }
            // add optional query param for path in loader for Swagger UI
            if ($path == '/extra/loader') {
                $param = 'path';
                $schema = [
                    "type" => "string",
                ];
                $required = false;
                array_push($params, [
                    "name" => $param,
                    "in" => "query",
                    "required" => $required,
                    "schema" => $schema,
                ]);
            }
            $result["paths"][$path] ??= [];
            foreach ($methods as $method) {
                $method = strtolower($method);
                $result["paths"][$path][$method] = [
                    "summary" => "Route to $path ($name)",
                    "operationId" => $operationId ?? ($method . $name),
                    "responses" => [
                        "200" => [
                            "description" => "Result of " . $path,
                            "content" => [
                                "application/json" => [
                                    "schema" => [
                                        "type" => "object",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
                if (!empty($params)) {
                    $result["paths"][$path][$method]["parameters"] = $params;
                }
                if (str_starts_with($path, '/admin/') || str_starts_with($path, '/metadata/')) {
                    $result["paths"][$path][$method]["summary"] .= " - with cookie api key";
                    $result["paths"][$path][$method]["security"] = [
                        ["cookieAuth" => []],
                    ];
                }
            }
        }
        return $result;
    }
}
