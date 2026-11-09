<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\filesystems;

use Craft;
use craft\base\FsInterface;
use craft\wpimport\Command;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseFsGenerator
{
    public static function get(): FsInterface
    {
        $fs = Craft::$app->fs->getFilesystemByHandle(static::handle());

        if (!$fs) {
            $fs = static::create();
            $fs->handle = static::handle();

            /** @var Command $command */
            $command = Craft::$app->controller;
            $command->do("Creating `$fs->name` filesystem", function() use ($fs) {
                if (!Craft::$app->fs->saveFilesystem($fs)) {
                    throw new Exception(implode(', ', $fs->getFirstErrors()));
                }
            });
        }

        return $fs;
    }

    /**
     * Returns the filesystem’s UUID.
     */
    abstract protected static function handle(): string;

    /**
     * Creates and configures the filesystem (but doesn’t save it).
     */
    abstract protected static function create(): FsInterface;
}
