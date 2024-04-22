<?php
/**
 * BicBucStriim
 *
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Actions;

use BicBucStriim\Utilities\RouteUtil;

/*********************************************************************
 * JSON API actions
 ********************************************************************/
class ApiActions extends DefaultActions
{
    /**
     * Add routes for API actions
     * @return void
     */
    public static function addRoutes($app, $prefix = '/api')
    {
        $self = new self($app);
        $routes = static::getRoutes($self);
        $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
            RouteUtil::mapRoutes($group, $routes);
        });
    }

    /**
     * Get routes for API actions
     * @param self $self
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self)
    {
        return [
            // method(s), path, ...middleware(s), callable
            ['GET', '/', [$self, 'home']],
            ['GET', '/routes', [$self, 'routes']],
            ['GET', '/openapi.json', [$self, 'openapi']],
        ];
    }

    /**
     * Generate the API home page
     * @return void
     */
    public function home()
    {
        $globalSettings = $this->settings();
        $title = $globalSettings[DISPLAY_APP_NAME];
        $rot = $this->getRootUrl();
        $link = $rot . '/api/openapi.json';
        $this->render('api_home.twig', [
            'title' => $title,
            'link' => $link]);
    }

    /**
     * Get the list of routes
     * @return void
     */
    public function routes()
    {
        $globalSettings = $this->settings();
        $title = $globalSettings[DISPLAY_APP_NAME];
        $rot = $this->getRootUrl();
        $routes = $this->app()->getRouteCollector()->getRoutes();
        foreach ($routes as $route) {
            $link = $rot . $route->getPattern();
            $patterns[$link] ??= [];
            $patterns[$link] = array_merge($patterns[$link], array_filter($route->getMethods(), function ($method) {
                return $method !== 'HEAD';
            }));
        }
        $data = [
            'title' => $title,
            'routes' => $patterns,
        ];
        $this->mkJsonResponse($data);
    }

    /**
     * Get minimal OpenAPI specification for the routes
     * @return void
     */
    public function openapi()
    {
        $data = $this->getOpenApi();
        $this->mkJsonResponse($data);
    }

    /**
     * Summary of getOpenApi
     * @return array<string, mixed>
     */
    public function getOpenApi()
    {
        $root = $this->getRootUrl();
        $globalSettings = $this->settings();
        $result = [
            "openapi" => "3.0.3",
            "info" => [
                "title" => $globalSettings['appname'] . " API",
                "version" => $globalSettings['version'],
            ],
        ];
        $result["servers"] = [
            ["url" => $root, "description" => $globalSettings['appname'] . " API Endpoint"],
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
        $routes = $this->app()->getRouteCollector()->getRoutes();
        foreach ($routes as $route) {
            $path = $route->getPattern();
            $methods = array_filter($route->getMethods(), function ($method) {
                return $method !== 'HEAD';
            });
            $args = $route->getArguments();
            $operationId = $route->getIdentifier();
            $name = $route->getName() ?? $operationId;
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
                    if (str_contains($param, ':')) {
                        [$param, $pattern] = explode(':', $param);
                        $schema["pattern"] = '^' . $pattern . '$';
                        $route = str_replace(':' . $pattern, '', $route);
                    }
                    array_push($params, [
                        "name" => $param,
                        "in" => "path",
                        "required" => true,
                        "schema" => $schema,
                    ]);
                }
            }
            $result["paths"][$path] ??= [];
            foreach ($methods as $method) {
                $method = strtolower($method);
                $result["paths"][$path][$method] = [
                    "summary" => "Route to $path ($name)",
                    "operationId" => $operationId ?? ($method . $route->getIdentifier()),
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
                        ["CookieAuth" => []],
                    ];
                }
            }
        }
        return $result;
    }
}
