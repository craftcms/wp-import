<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use Craft;
use craft\ckeditor\Field;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\wpimport\generators\fields\PostContent;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseEntryTypeGenerator
{
    public static function get(): EntryType
    {
        return Craft::$app->entries->getEntryTypeByUid(static::uid()) ?? static::create();
    }

    /**
     * Returns the entry type’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Creates and saves the entry type.
     */
    protected static function create(): EntryType
    {
        $entryType = new EntryType();
        $entryType->uid = static::uid();
        static::populate($entryType);

        if (Craft::$app->entries->getEntryTypeByHandle($entryType->handle) !== null) {
            $entryType->handle .= '_' . StringHelper::randomString(5);
        }

        /** @var Command $command */
        $command = Craft::$app->controller;
        $command->do("Creating `$entryType->name` entry type", function() use ($entryType) {
            if (!Craft::$app->entries->saveEntryType($entryType)) {
                throw new Exception(implode(', ', $entryType->getFirstErrors()));
            }
        });

        return $entryType;
    }

    /**
     * Creates and configures the entry type (but doesn’t save it).
     */
    abstract protected static function populate(EntryType $entryType): void;

    /**
     * Adds the entry type to the Post Content CKEditor field
     */
    protected static function addToPostContentField(EntryType $entryType): void
    {
        // add to the Post Content field
        /** @var Field $field */
        $field = PostContent::get();
        $entryTypes = $field->getEntryTypes();
        $entryTypes[] = $entryType;
        $field->setEntryTypes($entryTypes);

        /** @var Command $command */
        $command = Craft::$app->controller;
        $command->do("Updating `$field->name` field", function() use ($field) {
            if (!Craft::$app->fields->saveField($field)) {
                throw new Exception(implode(', ', $field->getFirstErrors()));
            }
        });
    }
}
