<?php

namespace BicBucStriim\Framework\Providers;

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\ActionResolver;
use BicBucStriim\AppData\Settings;
use BicBucStriim\Framework\ActionLoader;
use BicBucStriim\Framework\LaravelRenderer;
use BicBucStriim\Framework\MiddlewareResolver;
use BicBucStriim\Framework\RendererInterface;
use BicBucStriim\Framework\Http\Controllers\ActionAdapterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\PsrHttpFactoryInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

/**
 * @suppress PHP0413
 */
class ActionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the ActionRegistry as a singleton, so it's created only once.
        $this->app->singleton(ActionRegistry::class, function ($app) {
            // The registry needs the container to instantiate actions.
            return new ActionRegistry($app);
        });

        // Register the ActionResolver as a singleton too, so the adapter
        // controller can share the same registry (and its singleton action
        // instances) rather than re-inlining the action lifecycle.
        $this->app->singleton(ActionResolver::class, function ($app) {
            return new ActionResolver($app->make(ActionRegistry::class));
        });

        // Register the shared, framework-agnostic MiddlewareResolver so the
        // adapter controller can turn a route's middleware class-strings into
        // PSR-15 instances. The Laravel container ($app) is passed as the
        // optional PSR-11 container so container-provided middleware wins over
        // the no-arg fallback, matching Slim's CallableResolver behaviour.
        $this->app->singleton(MiddlewareResolver::class, function ($app) {
            return new MiddlewareResolver($app, $app->make(ResponseFactoryInterface::class));
        });

        // Bind PSR-7/PSR-17 factories and the Symfony bridge to the container.
        // This allows them to be injected automatically wherever they are needed.
        $this->app->singleton(Psr17Factory::class, fn($app) => new Psr17Factory());
        $this->app->singleton(RequestFactoryInterface::class, fn($app) => $app->make(Psr17Factory::class));
        $this->app->singleton(ResponseFactoryInterface::class, fn($app) => $app->make(Psr17Factory::class));
        $this->app->singleton(ServerRequestFactoryInterface::class, fn($app) => $app->make(Psr17Factory::class));
        $this->app->singleton(StreamFactoryInterface::class, fn($app) => $app->make(Psr17Factory::class));
        $this->app->singleton(UploadedFileFactoryInterface::class, fn($app) => $app->make(Psr17Factory::class));
        $this->app->singleton(UriFactoryInterface::class, fn($app) => $app->make(Psr17Factory::class));

        $this->app->singleton(HttpFoundationFactoryInterface::class, fn($app) => new HttpFoundationFactory());
        $this->app->singleton(PsrHttpFactoryInterface::class, function ($app) {
            return new PsrHttpFactory(
                $app->make(ServerRequestFactoryInterface::class),
                $app->make(StreamFactoryInterface::class),
                $app->make(UploadedFileFactoryInterface::class),
                $app->make(ResponseFactoryInterface::class),
            );
        });

        // Bind the Twig Environment to the container as a singleton.
        $this->app->singleton(TwigEnvironment::class, function ($app) {
            // Point the loader to the existing 'templates' directory at the project root.
            $loader = new FilesystemLoader($app->basePath('templates'));

            // For production, you would enable Twig's cache.
            // $options = ['cache' => $app->storagePath('framework/views/twig')];
            $options = [];

            return new TwigEnvironment($loader, $options);
        });

        // Bind our RendererInterface to the LaravelRenderer implementation.
        $this->app->bind(RendererInterface::class, LaravelRenderer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @var ActionRegistry $registry */
        $registry = $this->app->make(ActionRegistry::class);

        // Single source of truth for which action classes to load and their
        // URL prefixes, honoring the 'hasapi' setting.
        /** @var ?Settings $settings */
        $settings = $this->app->bound(Settings::class) ? $this->app->make(Settings::class) : null;
        foreach (ActionLoader::getActionDescriptors($settings) as [$class, $prefix]) {
            $registry->register($class, $prefix);
        }

        // Now, map the routes.
        $routeMap = $registry->getRouteMap();

        foreach ($routeMap as $name => $route) {
            // Structured route entry from the ActionRegistry:
            // methods, path, callable, middleware, gated.
            // Laravel uses ':' for parameters, while the app uses '{}'.
            $laravelPath = str_replace(['{', '}'], [':', ''], $route->path);

            $laravelRoute = Route::match($route->methods, $laravelPath, [ActionAdapterController::class, 'handle'])
                ->name($name)
                ->defaults('action_callable', $route->callable);

            // Our route definitions use PSR-15 middleware, which Laravel's
            // route pipeline does not consume directly. Carry the route-level
            // middleware and the group gatekeeper flag as route defaults so the
            // adapter controller can run them around the action.
            $laravelRoute->defaults('action_middleware', $route->middleware);
            $laravelRoute->defaults('action_gated', $route->gated);
        }
    }
}
