<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\ckeconfigs;

use Craft;
use craft\ckeditor\CkeConfig;
use craft\ckeditor\Plugin as Ckeditor;
use craft\wpimport\Command;
use yii\base\InvalidArgumentException;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseCkeConfigGenerator
{
    public static function get(): CkeConfig
    {
        $configService = Ckeditor::getInstance()->getCkeConfigs();
        try {
            return $configService->getByUid(static::uid());
        } catch (InvalidArgumentException) {
            $config = new CkeConfig();
            $config->uid = static::uid();
            static::populate($config);

            /** @var Command $command */
            $command = Craft::$app->controller;
            $command->do("Creating `$config->name` CKEditor config", function() use ($configService, $config) {
                if (!$configService->save($config)) {
                    throw new Exception(implode(', ', $config->getFirstErrors()));
                }
            });

            return $config;
        }
    }

    /**
     * Returns the CKEditor config’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Configures the CKEditor config (but doesn’t save it).
     */
    abstract protected static function populate(CkeConfig $config): void;
}
