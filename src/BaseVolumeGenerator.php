<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use Craft;
use craft\helpers\StringHelper;
use craft\models\Volume;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseVolumeGenerator
{
    public static function get(): Volume
    {
        $volume = Craft::$app->volumes->getVolumeByUid(static::uid());

        if (!$volume) {
            $volume = new Volume();
            $volume->uid = static::uid();
            static::populate($volume);

            if (Craft::$app->volumes->getVolumeByHandle($volume->handle) !== null) {
                $volume->handle .= '_' . StringHelper::randomString(5);
            }

            /** @var Command $command */
            $command = Craft::$app->controller;
            $command->do("Creating `$volume->name` volume", function() use ($volume) {
                if (!Craft::$app->volumes->saveVolume($volume)) {
                    throw new Exception(implode(', ', $volume->getFirstErrors()));
                }
            });
        }

        return $volume;
    }

    /**
     * Returns the volume’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Creates and configures the volume (but doesn’t save it).
     */
    abstract protected static function populate(Volume $volume): void;
}
