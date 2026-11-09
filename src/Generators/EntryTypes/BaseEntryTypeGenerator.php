<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\entrytypes;

use Craft;
use craft\ckeditor\Field;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\wpimport\Command;
use craft\wpimport\generators\fields\PostContent;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseEntryTypeGenerator
{
    private static array $entryTypes = [];

    public static function get(): EntryType
    {
        if (!isset(self::$entryTypes[static::class])) {
            $entryType = Craft::$app->entries->getEntryTypeByUid(static::uid());
            if ($entryType) {
                self::populateAndSave($entryType);
            } else {
                $entryType = static::create();
            }

            self::$entryTypes[static::class] = $entryType;
        }

        return self::$entryTypes[static::class];
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
        self::populateAndSave($entryType);
        return $entryType;
    }

    private static function populateAndSave(EntryType $entryType): void
    {
        static::populate($entryType);

        $existingEntryType = Craft::$app->entries->getEntryTypeByHandle($entryType->handle);
        if ($existingEntryType && $existingEntryType->id !== $entryType->id) {
            $entryType->handle .= '_' . StringHelper::randomString(5);
        }

        /** @var Command $command */
        $command = Craft::$app->controller;
        $message = sprintf('%s the `%s` entry type', $entryType->id ? 'Saving' : 'Creating', $entryType->name);
        $command->do($message, function() use ($entryType) {
            if (!Craft::$app->entries->saveEntryType($entryType)) {
                throw new Exception(implode(', ', $entryType->getFirstErrors()));
            }
        });
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
