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
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\WpImport\Generators\Fields\Citation;
use CraftCms\WpImport\Generators\Fields\PullQuote as PullQuoteField;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PullQuote extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return '186ec936-68fb-4e76-94e6-031af83f7b25';
    }

    protected static function create(): EntryType
    {
        $entryType = parent::create();
        static::addToPostContentField($entryType);

        return $entryType;
    }

    protected static function populate(EntryType $entryType): void
    {
        $entryType->name = 'Pull Quote';
        $entryType->handle = 'pullQuote';
        $entryType->icon = 'quote-left';
        $entryType->color = Color::Teal;
        $entryType->showStatusField = false;
        $entryType->showSlugField = false;

        $fieldLayout = new FieldLayout;
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [
                    new CustomField(PullQuoteField::get(), [
                        'includeInCards' => true,
                    ]),
                    new CustomField(Citation::get(), [
                        'includeInCards' => true,
                    ]),
                ],
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);
    }
}
