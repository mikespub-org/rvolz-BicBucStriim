<?php

use BicBucStriim\Framework\ActionLoader;
use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Actions\AdminActions;
use BicBucStriim\Actions\ApiActions;
use BicBucStriim\Actions\DefaultActions;
use BicBucStriim\Actions\ExtraActions;
use BicBucStriim\Actions\MainActions;
use BicBucStriim\Actions\MetadataActions;
use BicBucStriim\Actions\OpdsActions;
use BicBucStriim\AppData\Settings;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the ActionLoader: the single source of truth for which action
 * classes are used and how they are mounted (honoring the 'hasapi' setting).
 */
#[CoversClass(ActionLoader::class)]
#[CoversClass(ActionRegistry::class)]
#[CoversClass(Settings::class)]
class ActionLoaderTest extends PHPUnit\Framework\TestCase
{
    public function testDescriptorsWithoutApiSlot(): void
    {
        $settings = new Settings(); // hasapi falsy by default
        $settings['hasapi'] = false;
        $descriptors = ActionLoader::getActionDescriptors($settings);

        // The /api slot falls back to the DefaultActions "hello world" stub,
        // mounted under /api (regardless of its own null PREFIX).
        $this->assertSame(
            [
                [MainActions::class, MainActions::PREFIX],
                [AdminActions::class, AdminActions::PREFIX],
                [MetadataActions::class, MetadataActions::PREFIX],
                [OpdsActions::class, OpdsActions::PREFIX],
                [ExtraActions::class, ExtraActions::PREFIX],
                [DefaultActions::class, '/api'],
            ],
            $descriptors
        );
    }

    public function testDescriptorsWithApiSlot(): void
    {
        $settings = new Settings();
        $settings['hasapi'] = true;
        $descriptors = ActionLoader::getActionDescriptors($settings);

        // The /api slot is served by the real ApiActions.
        $this->assertSame([ApiActions::class, ApiActions::PREFIX], $descriptors[5]);
    }

    public function testDescriptorsDefaultToApiOffWhenNoSettings(): void
    {
        // No settings -> hasapi falsy -> DefaultActions stub in the /api slot.
        $descriptors = ActionLoader::getActionDescriptors();
        $this->assertSame([DefaultActions::class, '/api'], $descriptors[5]);
    }

    public function testGetActionClassesReturnsOnlyClassNames(): void
    {
        $classes = ActionLoader::getActionClasses();
        $this->assertSame(
            [
                MainActions::class,
                AdminActions::class,
                MetadataActions::class,
                OpdsActions::class,
                ExtraActions::class,
                DefaultActions::class,
            ],
            $classes
        );
    }

    public function testLoadPopulatesRegistry(): void
    {
        $registry = new ActionRegistry();
        $loader = new ActionLoader();
        $loader->load($registry);

        $this->assertSame(
            [
                MainActions::class,
                AdminActions::class,
                MetadataActions::class,
                OpdsActions::class,
                ExtraActions::class,
                DefaultActions::class,
            ],
            $registry->getActionClasses()
        );
    }

    public function testLoadUsesApiActionsWhenHasApi(): void
    {
        $settings = new Settings();
        $settings['hasapi'] = true;

        $registry = new ActionRegistry();
        new ActionLoader()->load($registry, $settings);

        $classes = $registry->getActionClasses();
        $this->assertContains(ApiActions::class, $classes);
        $this->assertNotContains(DefaultActions::class, $classes);
    }
}
