<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\WpImport\Console\ImportCommand;
use RuntimeException;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseFieldGenerator
{
    public static function get(): FieldInterface
    {
        $field = Fields::getFieldByUid(static::uid());

        if (! $field) {
            $field = static::create();
            $field->uid = static::uid();

            if (Fields::getFieldByHandle($field->handle) !== null) {
                $field->handle .= '_'.Str::random(5);
            }

            /** @var ImportCommand $command */
            $command = app(ImportCommand::class);
            $command->do("Creating `$field->name` field", function () use ($field) {
                if (! Fields::saveField($field)) {
                    throw new RuntimeException(implode(', ', $field->getFirstErrors()));
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
