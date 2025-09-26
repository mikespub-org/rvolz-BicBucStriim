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

/**
 * Responsible for loading and registering all application actions.
 */
class ActionLoader
{
    /**
     * Get the list of all core Action classes for the application.
     *
     * @return array<class-string>
     */
    public static function getActionClasses(): array
    {
        return [
            MainActions::class,
            AdminActions::class,
            MetadataActions::class,
            OpdsActions::class,
            ExtraActions::class,
            ApiActions::class,
            //DefaultActions::class,
        ];
    }

    /**
     * Populate the ActionRegistry with all application actions.
     */
    public function load(ActionRegistry $registry): void
    {
        foreach (self::getActionClasses() as $class) {
            $registry->register($class);
        }
    }
}
