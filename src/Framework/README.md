# Framework Integration Architecture

This document outlines the architecture used to make the application's core logic (found in `src/Actions`) reusable across different PHP frameworks like Slim, Symfony, Laravel, and a minimal custom framework.

## Core Concepts

The primary goal is to keep the action classes completely independent of any specific framework. This is achieved through several key abstractions:

- **Action Classes (`src/Actions`)**: These classes contain the application logic (e.g., `MainActions`, `AdminActions`). They expose only data and policy — `getRoutes()` / `getExtraRoutes()` route tuples, plus `PREFIX` and `ROLES` — with no framework-specific code.

- **`AppTrait` (`src/Traits/AppTrait.php`)**: This trait provides a consistent API for action classes to access application services (like settings, database connections, and the logger) from a dependency injection (DI) container. It abstracts away the specific implementation of the container.

- **`ContainerAdapter` (`src/Framework/ContainerAdapter.php`)**: This class wraps any PSR-11 container to provide a consistent interface for the `Action` classes, including a non-standard `set()` method for testability and dynamic service replacement.

- **`RendererInterface` (`src/Framework/RendererInterface.php`)**: This interface defines a contract for rendering templates. By depending on this interface instead of a concrete implementation, action classes can render templates without knowing whether the underlying engine is Twig, Blade, or something else.

- **Framework-Specific Bridges**: For each supported framework, a set of "bridge" classes and configuration files are created within the `src/Framework` directory. These are responsible for integrating the shared action classes into the host framework's lifecycle.

---

## Integration Strategies by Framework

### 1. Slim

- **Entry Point**: The application is bootstrapped via `config/bootstrap.php`, which creates and configures a `Slim\App` instance.
- **Dependency Injection**: The DI container is configured in `config/container.php` using `PHP-DI`. This file is responsible for defining how services are created.
- **Middleware**: Middleware is defined and applied in `config/middleware.php`.
- **Routing**: Routes are loaded from `config/routes.php` via `ActionLoader::getActionDescriptors($settings)` (the single source of truth, honoring `hasapi`), populating the `ActionRegistry` and registering all routes from its map with `RouteUtil::addRoutesFromMap($app, $registry->getRouteMap(), $gatekeeper)`.
- **Rendering**: The `config/container.php` file binds the `Framework/TwigRenderer` to the `RendererInterface`. This renderer is a straightforward wrapper around a `\Twig\Environment` instance.
- **Execution**: Slim's router dispatches to the action callable. The `BicBucStriim\Utilities\ActionsWrapperStrategy` is set as the default invocation strategy. This strategy is responsible for instantiating the action class (if needed), calling its `initialize()` method, and then executing the action method.

### 2. Custom Framework

This is a minimal, self-contained implementation that serves as a reference for the core components, independent of a larger framework like Slim.

- **Entry Point**: `Framework/App.php` acts as the application runner. It implements PSR-15 `RequestHandlerInterface` to handle an incoming request, run it through a middleware pipeline, and dispatch it to the correct action.
- **Dependency Injection**: It receives a PSR-11 container in its constructor.
- **Routing**: `App::handle()` uses `FastRoute` to map the request URL to a specific method on an action class, using the structured route map from the `ActionRegistry`.
- **Rendering**: It relies on the DI container to provide an implementation for `RendererInterface`.
- **Execution**: `Framework/ActionResolver` is used within `App::handle()` to get an instance of the action class from the `ActionRegistry`, initialize it, and execute the target method.
- **Access control**: When a matched route is `gated` (action class `ROLES = ['admin']`), `App::handle()` runs the framework-agnostic `Framework/RoleGuard` (`RequestUtil::isAdmin()`, plain `403`, no Slim/Twig) before dispatching.

### 3. Symfony

Symfony integration leverages its powerful service container and controller-based architecture.

- **Dependency Injection**: Services are configured in `config/services.yaml`. This is where the framework-agnostic services are wired into Symfony's container.
- **Rendering**: `Framework/SymfonyRenderer` is used. It implements our `RendererInterface` as a thin wrapper around an injected `\Twig\Environment` (same constructor-injection shape as `TwigRenderer`/`LaravelRenderer`), so it is testable without booting `framework-bundle`.
- **Integration**: In `config/services.yaml`, the `SymfonyRenderer` is bound to the `RendererInterface`. When an action class asks for a renderer via `AppTrait::renderer()`, Symfony's container provides the `SymfonyRenderer` instance.
- **Execution**: The integration is seamless and event-driven, making the shared action methods behave like native Symfony controllers.
    1.  **Routing**: A custom `Routing\ActionRouteLoader` is registered as a service. It loads the structured route map from our `ActionRegistry` and adds all routes to Symfony's router, with `gated`/`middleware` attached as route defaults (`_bbs_gated` / `_bbs_middleware`).
    2.  **Execution & Access Control**: The `Event\ActionInitializeListener` listens for Symfony's `kernel.controller` event and converts the request to PSR-7. It resolves the route's middleware via the shared `MiddlewareResolver::resolveForRoute($gated, $middleware)` (gated flag from `_bbs_gated`, entries from `_bbs_middleware`) and, when middleware is present, runs the action inside the shared `MiddlewareDispatcher` — then sets the result on the event via `setResponse()`, which short-circuits Symfony's own controller invocation. `RoleGuard` produces the 403 inside this pipeline. When a route has no middleware, the listener only calls `initialize()` and lets Symfony invoke the controller as usual (handled below).
    3.  **Response Handling**: The `Event\PsrResponseListener` listens for Symfony's `kernel.view` event. After the action method runs and returns a PSR-7 `ResponseInterface`, this listener intercepts it and converts it into a native Symfony `Response` object, which the kernel can then send to the client.

### 4. Laravel

- **Dependency Injection & Bootstrapping**: `Framework/Providers/ActionServiceProvider.php` is the central integration point. This Laravel Service Provider is responsible for:
    1.  **Registering Services**: It binds all necessary services, including the PSR-7 factories, the Twig environment, the renderer, and the shared `MiddlewareResolver`.
    2.  **Registering Routes**: In its `boot()` method, it registers each route from the `ActionRegistry` structured map (honoring `hasapi` via `ActionLoader`), attaching `gated`/`middleware` as route defaults.
- **Rendering**: `Framework/LaravelRenderer` is used. It implements `RendererInterface` and, like the `TwigRenderer`, acts as a wrapper around a `\Twig\Environment` instance that has been configured within the `ActionServiceProvider`.
- **Execution**: All registered routes point to a single controller method: `Framework/Http/Controllers/ActionAdapterController::handle`. This adapter controller does the following:
    1.  Converts the incoming Laravel `Request` to a PSR-7 `Request`.
    2.  Resolves the route's middleware via the shared `MiddlewareResolver::resolveForRoute($gated, $middleware)` (`action_gated` flag + `action_middleware` entries), and runs the action inside the shared `MiddlewareDispatcher` — `RoleGuard` produces the 403 inside this pipeline when the request is not an admin.
    3.  Uses the `ActionResolver` (invoked by the pipeline's terminal handler) to execute the correct method on the shared action class (the callable is passed in by the router).
    4.  Converts the resulting PSR-7 `Response` back into a Laravel `Response` to be sent to the client.

This adapter approach allows us to use the shared action classes in Laravel without modification.

---

*This document was generated with assistance from Google's Gemini Code Assist.*