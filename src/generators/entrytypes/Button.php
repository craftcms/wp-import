<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\entrytypes;

use craft\enums\Color;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\wpimport\generators\fields\LinkUrl;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Button extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return 'fc8630bf-cc94-4830-8e52-40246afab5e5';
    }

    protected static function create(): EntryType
    {
        $entryType = parent::create();
        static::addToPostContentField($entryType);
        return $entryType;
    }

    protected static function populate(EntryType $entryType): void
    {
        $entryType->name = 'Button';
        $entryType->handle = 'button';
        $entryType->icon = 'link';
        $entryType->color = Color::Cyan;
        $entryType->showStatusField = false;
        $entryType->showSlugField = false;

        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [
                    new EntryTitleField(),
                    new CustomField(LinkUrl::get(), [
                        'handle' => 'buttonUrl',
                        'includeInCards' => true,
                    ]),
                ],
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);
    }
}
