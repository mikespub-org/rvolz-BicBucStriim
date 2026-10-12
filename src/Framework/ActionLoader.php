<?php

declare(strict_types=1);

namespace BicBucStriim\Framework;

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\AdminActions;
use BicBucStriim\Actions\ApiActions;
use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Actions\ExtraActions;
use BicBucStriim\Actions\MainActions;
use BicBucStriim\Actions\MetadataActions;
use BicBucStriim\Actions\OpdsActions;
use BicBucStriim\AppData\Settings;

/**
 * Responsible for loading and registering all application actions.
 */
class ActionLoader
{
    /**
     * Get the list of all core Action classes for the application.
     *
     * Kept for backward compatibility: returns the action classes for the
     * current settings (see getActionDescriptors()).
     *
     * @return array<class-string>
     */
    public static function getActionClasses(?Settings $settings = null): array
    {
        $classes = [];
        foreach (self::getActionDescriptors($settings) as [$class, $prefix]) {
            $classes[] = $class;
        }
        return $classes;
    }

    /**
     * Get the action classes to load, together with the URL prefix to mount
     * them under, honoring the 'hasapi' setting.
     *
     * This is the single source of truth for which action classes are used and
     * how they are mounted, shared by the Slim bootstrap (config/routes.php),
     * the Laravel service provider and the framework-agnostic ActionRegistry.
     *
     * When the JSON API is enabled (hasapi) the /api slot is served by
     * ApiActions, otherwise it falls back to the DefaultActions "hello world"
     * stub. In both cases the API slot is mounted under /api, regardless of the
     * class' own PREFIX constant.
     *
     * @param ?Settings $settings
     * @return array<array{0: class-string, 1: string|null}>
     */
    public static function getActionDescriptors(?Settings $settings = null): array
    {
        $hasapi = !empty($settings['hasapi']);
        $api = $hasapi
            ? [ApiActions::class, ApiActions::PREFIX]
            : [DefaultActions::class, '/api'];

        return [
            // classname, prefix
            [MainActions::class, MainActions::PREFIX],
            [AdminActions::class, AdminActions::PREFIX],
            [MetadataActions::class, MetadataActions::PREFIX],
            [OpdsActions::class, OpdsActions::PREFIX],
            [ExtraActions::class, ExtraActions::PREFIX],
            $api,
        ];
    }

    /**
     * Populate the ActionRegistry with all application actions.
     *
     * @param ?Settings $settings used to decide whether the JSON API is enabled
     */
    public function load(ActionRegistry $registry, ?Settings $settings = null): void
    {
        foreach (self::getActionDescriptors($settings) as [$class, $prefix]) {
            $registry->register($class, $prefix);
        }
    }
}
