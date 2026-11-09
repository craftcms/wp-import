<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\EntryTypes;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Shared\Enums\Color as ColorEnum;
use CraftCms\WpImport\Generators\Fields\Color;
use CraftCms\WpImport\Generators\Fields\PostContent;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Group extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return '36ca6464-18ad-4f2a-9ae0-e8da4e00c94a';
    }

    protected static function create(): EntryType
    {
        $entryType = parent::create();
        static::addToPostContentField($entryType);

        return $entryType;
    }

    protected static function populate(EntryType $entryType): void
    {
        $entryType->name = 'Group';
        $entryType->handle = 'group';
        $entryType->icon = 'layer-group';
        $entryType->color = ColorEnum::Indigo;
        $entryType->showStatusField = false;
        $entryType->showSlugField = false;

        $fieldLayout = new FieldLayout;
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [
                    new CustomField(Color::get(), [
                        'label' => 'Background Color',
                        'handle' => 'backgroundColor',
                        'includeInCards' => true,
                    ]),
                    new CustomField(Color::get(), [
                        'label' => 'Text Color',
                        'handle' => 'textColor',
                        'includeInCards' => true,
                    ]),
                    new CustomField(PostContent::get()),
                ],
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);
    }
}
