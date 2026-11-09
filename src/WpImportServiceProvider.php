<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport;

use CraftCms\WpImport\Console\ImportCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the `wp-import` command.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class WpImportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCommand::class,
            ]);
        }
    }
}
