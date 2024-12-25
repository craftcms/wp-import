<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\volumes;

use Craft;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\Volume;
use craft\wpimport\Command;
use yii\console\Exception;

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

        $volume = Craft::$app->volumes->getVolumeByUid(static::uid());
        $newVolume = !$volume;

        if ($newVolume) {
            $volume = new Volume();
            $volume->uid = static::uid();
            static::populate($volume);

            if (Craft::$app->volumes->getVolumeByHandle($volume->handle) !== null) {
                $volume->handle .= '_' . StringHelper::randomString(5);
            }
        }

        static::updateFieldLayout($volume->getFieldLayout());

        /** @var Command $command */
        $command = Craft::$app->controller;
        $message = sprintf('%s `%s` volume', $newVolume ? 'Creating' : 'Saving', $volume->name);
        $command->do($message, function() use ($volume) {
            if (!Craft::$app->volumes->saveVolume($volume)) {
                throw new Exception(implode(', ', $volume->getFirstErrors()));
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
    protected static function updateFieldLayout(FieldLayout $fieldLayout): void
    {
    }
}
