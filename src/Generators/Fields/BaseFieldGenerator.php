<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use Craft;
use craft\base\FieldInterface;
use craft\helpers\StringHelper;
use craft\wpimport\Command;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseFieldGenerator
{
    public static function get(): FieldInterface
    {
        $field = Craft::$app->fields->getFieldByUid(static::uid());

        if (!$field) {
            $field = static::create();
            $field->uid = static::uid();

            if (Craft::$app->fields->getFieldByHandle($field->handle) !== null) {
                $field->handle .= '_' . StringHelper::randomString(5);
            }

            /** @var Command $command */
            $command = Craft::$app->controller;
            $command->do("Creating `$field->name` field", function() use ($field) {
                if (!Craft::$app->fields->saveField($field)) {
                    throw new Exception(implode(', ', $field->getFirstErrors()));
                }
            });
        }

        return $field;
    }

    /**
     * Returns the field’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Creates and configures the field (but doesn’t save it).
     */
    abstract protected static function create(): FieldInterface;
}
