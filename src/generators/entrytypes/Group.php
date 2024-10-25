<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\entrytypes;

use craft\enums\Color as ColorEnum;
use craft\fieldlayoutelements\CustomField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\wpimport\BaseEntryTypeGenerator;
use craft\wpimport\generators\fields\Color;
use craft\wpimport\generators\fields\PostContent;

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

        $fieldLayout = new FieldLayout();
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
