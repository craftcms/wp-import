<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\Volumes;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\WpImport\Console\ImportCommand;
use CraftCms\WpImport\Generators\Fields\Caption;
use CraftCms\WpImport\Generators\Fields\Description;
use CraftCms\WpImport\Generators\Fields\WpId;
use CraftCms\WpImport\Generators\Fields\WpTitle;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class WpContent extends BaseVolumeGenerator
{
    protected static function uid(): string
    {
        return 'ba83893c-c85d-4d01-81a2-0dfc8723fb5d';
    }

    protected static function populate(Volume $volume): void
    {
        $volume->name = 'WordPress Content';
        $volume->handle = 'wpContent';
        $volume->subpath = 'wp-content';
        $volume->setFsHandle(app(ImportCommand::class)->disk);
        $volume->hasUrls = true;
    }

    protected static function updateFieldLayout(FieldLayout $fieldLayout): void
    {
        /** @var ImportCommand $command */
        $command = app(ImportCommand::class);
        $command->addElementsToLayout($fieldLayout, 'Content', [
            new AltField,
            new CustomField(Caption::get()),
            new CustomField(Description::get()),
        ], true, true);
        $command->addElementsToLayout($fieldLayout, 'Meta', [
            new CustomField(WpId::get()),
            new CustomField(WpTitle::get()),
        ]);
    }
}
