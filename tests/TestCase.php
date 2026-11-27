<?php

declare(strict_types=1);

namespace CraftCms\WpImport\Tests;

use CraftCms\Cms\Plugin\Testing\PluginTestCase;
use CraftCms\WpImport\WpImportServiceProvider;
use Override;

abstract class TestCase extends PluginTestCase
{
    /**
     * wp-import is a package rather than a Craft plugin, so there’s nothing to install.
     */
    public function setupInstallsPlugin(): void {}

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            WpImportServiceProvider::class,
        ];
    }
}
