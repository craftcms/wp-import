<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\EntryTypes;

use CraftCms\Ckeditor\Field;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\WpImport\Console\ImportCommand;
use CraftCms\WpImport\Generators\Fields\PostContent;
use RuntimeException;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseEntryTypeGenerator
{
    private static array $entryTypes = [];

    public static function get(): EntryType
    {
        if (! isset(self::$entryTypes[static::class])) {
            $entryType = EntryTypes::getEntryTypeByUid(static::uid());
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
        $entryType = new EntryType;
        $entryType->uid = static::uid();
        self::populateAndSave($entryType);

        return $entryType;
    }

    private static function populateAndSave(EntryType $entryType): void
    {
        static::populate($entryType);

        $existingEntryType = EntryTypes::getEntryTypeByHandle($entryType->handle);
        if ($existingEntryType && $existingEntryType->id !== $entryType->id) {
            $entryType->handle .= '_'.Str::random(5);
        }

        /** @var ImportCommand $command */
        $command = app(ImportCommand::class);
        $message = sprintf('%s the `%s` entry type', $entryType->id ? 'Saving' : 'Creating', $entryType->name);
        $command->do($message, function () use ($entryType) {
            if (! EntryTypes::saveEntryType($entryType)) {
                throw new RuntimeException(implode(', ', $entryType->getFirstErrors()));
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

        /** @var ImportCommand $command */
        $command = app(ImportCommand::class);
        $command->do("Updating `$field->name` field", function () use ($field) {
            if (! Fields::saveField($field)) {
                throw new RuntimeException(implode(', ', $field->getFirstErrors()));
            }
        });
    }
}
