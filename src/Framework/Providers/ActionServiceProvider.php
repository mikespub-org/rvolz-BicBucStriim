<?php

namespace BicBucStriim\Framework\Providers;

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\AdminActions;
use BicBucStriim\Actions\ApiActions;
use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Actions\ExtraActions;
use BicBucStriim\Actions\MainActions;
use BicBucStriim\Actions\MetadataActions;
use BicBucStriim\Actions\OpdsActions;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @var ActionRegistry $registry */
        $registry = $this->app->make(ActionRegistry::class);

        // Populate the registry with all our action classes.
        $actions = [
            MainActions::class,
            AdminActions::class,
            MetadataActions::class,
            OpdsActions::class,
            ExtraActions::class,
            ApiActions::class,
            DefaultActions::class,
        ];
        foreach ($actions as $class) {
            $registry->register($class);
        }

        // Now, map the routes.
        $routeMap = $registry->getRouteMap();

        foreach ($routeMap as $name => [$methods, $path, $callable]) {
            // Laravel uses ':' for parameters, while the app uses '{}'.
            $laravelPath = str_replace(['{', '}'], [':', ''], $path);

            Route::match($methods, $laravelPath, [ActionAdapterController::class, 'handle'])
                ->name($name)
                ->defaults('action_callable', $callable);
        }
    }
}
