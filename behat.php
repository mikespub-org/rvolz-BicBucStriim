<?php

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Filter\TagFilter;
use Behat\Config\GherkinOptions;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\MinkExtension\ServiceContainer\MinkExtension;

/** @suppress PHP0413 */
return new Config()
    ->withProfile(new Profile('default')
        ->withGherkinOptions(new GherkinOptions()
            ->withFilter(new TagFilter('~@wip')))
        ->withExtension(new Extension(MinkExtension::class, [
            'base_url' => getenv('BBS_BEHAT_BASE_URL') ?: 'http://localhost/',
            'sessions' => [
                'default' => [
                    'browserkit_http' => null,
                ],
            ],
        ]))
        ->withSuite(new Suite('admin_features')
            ->withContexts(
                'AdminContext',
                'FeatureSetupContext'
            )
            ->withPaths('%paths.base%/features/admin'))
        ->withSuite(new Suite('author_features')
            ->withContexts(
                'AuthorContext',
                'FeatureSetupContext'
            )
            ->withPaths('%paths.base%/features/author'))
        ->withSuite(new Suite('initial_features')
            ->withContexts('InitialContext')
            ->withPaths('%paths.base%/features/initial'))
        ->withSuite(new Suite('opds_features')
            ->withContexts(
                'OpdsContext',
                'FeatureSetupContext'
            )
            ->withPaths('%paths.base%/features/opds'))
        ->withSuite(new Suite('general_features')
            ->withContexts(
                'GeneralContext',
                'FeatureSetupContext'
            )
            ->withPaths('%paths.base%/features/general')));
