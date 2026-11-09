<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\ckeconfigs;

use Craft;
use craft\base\FieldInterface;
use craft\ckeditor\CkeConfig;
use craft\ckeditor\Field;
use craft\ckeditor\Plugin as Ckeditor;
use craft\wpimport\Command;
use CraftCms\Ckeditor\Field as NativeField;
use yii\base\InvalidArgumentException;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseCkeConfigGenerator
{
    /**
     * Creates a CKEditor field with this config’s toolbar (but doesn’t save it).
     *
     * @return FieldInterface
     */
    public static function createField(array $config = [])
    {
        $field = self::newField($config);
        if (self::usesConfigs()) {
            $field->ckeConfig = static::get()->uid;
        } else {
            $field->toolbar = static::toolbar();
        }
        return $field;
    }

    /**
     * Creates a CKEditor field (but doesn’t save it).
     *
     * @return FieldInterface
     */
    public static function newField(array $config = [])
    {
        // CKEditor 6 (for Craft 6) has a new namespace
        $class = class_exists(NativeField::class) ? NativeField::class : Field::class;
        return new $class($config);
    }

    /**
     * Returns whether toolbars are stored in CKEditor configs (CKEditor 4), rather than on the fields themselves.
     */
    public static function usesConfigs(): bool
    {
        return class_exists(CkeConfig::class);
    }

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
     * Returns the toolbar.
     */
    abstract protected static function toolbar(): array;

    /**
     * Configures the CKEditor config (but doesn’t save it).
     */
    abstract protected static function populate(CkeConfig $config): void;
}
