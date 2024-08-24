<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\Actions\DefaultActions;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\CallableResolver;

/**
 * Override default callable resolver for actions
 * @deprecated 3.4.1 instantiate actions with container instead of app
 */
class ActionsCallableResolver implements AdvancedCallableResolverInterface
{
    private ?ContainerInterface $container;
    private ?CallableResolver $resolver;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->resolver = new CallableResolver($container);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($toResolve): callable
    {
        if (is_array($toResolve)) {
            [$class, $method] = $toResolve;
            // when using [static::class, 'method'] - doesn't help with middleware
            // @todo instantiate actions with container instead of app and drop this
            if (is_string($class) && is_a($class, DefaultActions::class, true)) {
                $instance = new $class($this->container);
                return [$instance, $method];
            }
        }
        return $this->resolver->resolve($toResolve);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute($toResolve): callable
    {
        if (is_array($toResolve)) {
            [$class, $method] = $toResolve;
            // when using [static::class, 'method'] - doesn't help with middleware
            // @todo instantiate actions with container instead of app and drop this
            if (is_string($class) && is_a($class, DefaultActions::class, true)) {
                $instance = new $class($this->container);
                return [$instance, $method];
            }
        }
        return $this->resolver->resolveRoute($toResolve);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware($toResolve): callable
    {
        return $this->resolver->resolveMiddleware($toResolve);
    }
}
