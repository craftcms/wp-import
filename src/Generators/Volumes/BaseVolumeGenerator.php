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
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Str;
use CraftCms\WpImport\Console\ImportCommand;
use RuntimeException;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseVolumeGenerator
{
    private static array $volumes = [];

    public static function get(): Volume
    {
        if (isset(self::$volumes[static::uid()])) {
            return self::$volumes[static::uid()];
        }

        $volume = Volumes::getVolumeByUid(static::uid());
        $newVolume = ! $volume;

        if ($newVolume) {
            $volume = new Volume;
            $volume->uid = static::uid();
            static::populate($volume);

            if (Volumes::getVolumeByHandle($volume->handle) !== null) {
                $volume->handle .= '_'.Str::random(5);
            }
        }

        static::updateFieldLayout($volume->getFieldLayout());

        /** @var ImportCommand $command */
        $command = app(ImportCommand::class);
        $message = sprintf('%s the `%s` volume', $newVolume ? 'Creating' : 'Updating', $volume->name);
        $command->do($message, function () use ($volume) {
            if (! Volumes::saveVolume($volume)) {
                throw new RuntimeException(implode(', ', $volume->getFirstErrors()));
            }
        });

        return self::$volumes[static::uid()] = $volume;
    }

    /**
     * Returns the volume’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Creates and configures the volume (but doesn’t save it).
     */
    abstract protected static function populate(Volume $volume): void;

    /**
     * Updates the tag group’s field layout.
     */
    protected static function updateFieldLayout(FieldLayout $fieldLayout): void {}
}
