<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\EntryTypes;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\WpImport\Console\ImportCommand;
use CraftCms\WpImport\Generators\Fields\Media as MediaField;
use CraftCms\WpImport\Generators\Fields\RichCaption;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Media extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return 'd2356f61-430b-423e-a52e-a167fc52ba47';
    }

    protected static function create(): EntryType
    {
        $entryType = parent::create();
        static::addToPostContentField($entryType);

        return $entryType;
    }

    protected static function populate(EntryType $entryType): void
    {
        $entryType->name = 'Media';
        $entryType->handle = 'media';
        $entryType->icon = 'photo-film-music';
        $entryType->color = Color::Amber;
        $entryType->showStatusField = false;
        $entryType->showSlugField = false;

        $fieldLayout = $entryType->getFieldLayout();

        /** @var ImportCommand $command */
        $command = app(ImportCommand::class);
        $command->addElementsToLayout($fieldLayout, 'Content', [
            new CustomField(MediaField::get(), [
                'providesThumbs' => true,
                'includeInCards' => true,
            ]),
            new CustomField(RichCaption::get(), [
                'label' => 'Caption',
                'includeInCards' => true,
            ]),
        ], true);
        $entryType->setFieldLayout($fieldLayout);
    }
}
