# Framework Integration Architecture

This document outlines the architecture used to make the application's core logic (found in `src/Actions`) reusable across different PHP frameworks like Slim, Symfony, Laravel, and a minimal custom framework.

## Core Concepts

The primary goal is to keep the action classes completely independent of any specific framework. This is achieved through several key abstractions:

- **Action Classes (`src/Actions`)**: These classes contain the actual application logic for handling requests (e.g., `MainActions`, `AdminActions`). They are simple PHP classes that do not extend any framework-specific base class.

- **`AppTrait` (`src/Traits/AppTrait.php`)**: This trait provides a consistent API for action classes to access application services (like settings, database connections, and the logger) from a dependency injection (DI) container. It abstracts away the specific implementation of the container.

- **`ContainerAdapter` (`src/Framework/ContainerAdapter.php`)**: This class wraps any PSR-11 container to provide a consistent interface for the `Action` classes, including a non-standard `set()` method for testability and dynamic service replacement.

- **`RendererInterface` (`src/Framework/RendererInterface.php`)**: This interface defines a contract for rendering templates. By depending on this interface instead of a concrete implementation, action classes can render templates without knowing whether the underlying engine is Twig, Blade, or something else.

- **Framework-Specific Bridges**: For each supported framework, a set of "bridge" classes and configuration files are created within the `src/Framework` directory. These are responsible for integrating the shared action classes into the host framework's lifecycle.

---

## Integration Strategies

### 1. Custom Framework / Slim

This is the most direct implementation and serves as a reference for the core components.

- **Entry Point**: `Framework/App.php` is a minimal, self-contained application runner. It implements PSR-15 `RequestHandlerInterface` to handle an incoming request, run it through a middleware pipeline, and dispatch it to the correct action.
- **Dependency Injection**: The DI container is configured in `config/container.php` using `PHP-DI`. This file is responsible for defining how services are created.
- **Routing**: `App::handle()` uses `FastRoute` to map the request URL to a specific method on an action class (e.g., `[MainActions::class, 'index']`).
- **Rendering**: The `config/container.php` file binds the `Framework/TwigRenderer` to the `RendererInterface`. This renderer is a straightforward wrapper around a `\Twig\Environment` instance.
- **Execution**: `Framework/ActionResolver` receives the target class and method from the router. It gets a singleton instance of the action class from the `ActionRegistry`, initializes it with the current request and response, and executes the method.

### 2. Symfony

Symfony integration leverages its powerful service container and controller-based architecture.

- **Dependency Injection**: Services are configured in `config/services.yaml`. This is where the framework-agnostic services are wired into Symfony's container.
- **Rendering**: `Framework/SymfonyRenderer` is used. This class is unique because it implements our `RendererInterface` while also extending Symfony's `AbstractController`. This allows it to use the controller's built-in `render()` method and access to the container, acting as a perfect bridge.
- **Integration**: In `config/services.yaml`, the `SymfonyRenderer` is bound to the `RendererInterface`. When an action class asks for a renderer via `AppTrait::renderer()`, Symfony's container provides the `SymfonyRenderer` instance.
- **Execution**: A standard Symfony controller would be used to call the shared action methods. The controller would be responsible for creating the PSR-7 request and handling the PSR-7 response returned by the action.
- **Execution**: The integration is seamless and event-driven, making the shared action methods behave like native Symfony controllers.
    1.  **Routing**: A custom `Routing\ActionRouteLoader` is registered as a service. It loads the route map from our `ActionRegistry` and adds all routes to Symfony's router. The controller for each route is set to the action's callable (e.g., `[MainActions::class, 'index']`).
    2.  **Initialization**: The `Event\ActionInitializeListener` listens for Symfony's `kernel.controller` event. Before the action method is called, this listener converts the Symfony request to a PSR-7 request and calls the `initialize()` method on the action instance. This prepares the action with the necessary context.
    3.  **Response Handling**: The `Event\PsrResponseListener` listens for Symfony's `kernel.view` event. After the action method runs and returns a PSR-7 `ResponseInterface`, this listener intercepts it and converts it into a native Symfony `Response` object, which the kernel can then send to the client.

### 3. Laravel

- **Dependency Injection & Bootstrapping**: `Framework/Providers/ActionServiceProvider.php` is the central integration point. This Laravel Service Provider is responsible for:
    1.  **Registering Services**: It binds all necessary services, including the PSR-7 factories, the Twig environment, and the renderer.
    2.  **Registering Routes**: In its `boot()` method, it gets the complete route map from the `ActionRegistry` and registers each route with Laravel's router.
- **Rendering**: `Framework/LaravelRenderer` is used. It implements `RendererInterface` and, like the `TwigRenderer`, acts as a wrapper around a `\Twig\Environment` instance that has been configured within the `ActionServiceProvider`.
- **Execution**: All registered routes point to a single controller method: `Framework/Http/Controllers/ActionAdapterController::handle`. This adapter controller does the following:
    1.  Converts the incoming Laravel `Request` to a PSR-7 `Request`.
    2.  Uses the `ActionResolver` to execute the correct method on the shared action class (the callable is passed in by the router).
    3.  Converts the resulting PSR-7 `Response` back into a Laravel `Response` to be sent to the client.

This adapter approach allows us to use the shared action classes in Laravel without modification.

---

*This document was generated with assistance from Google's Gemini Code Assist.*