<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\entrytypes;

use craft\enums\Color;
use craft\fieldlayoutelements\CustomField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\wpimport\generators\fields\LinkUrl;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class CodepenEmbed extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return 'ff0c2e4b-8cfb-4e47-9216-751983be5496';
    }

    protected static function create(): EntryType
    {
        $entryType = parent::create();
        static::addToPostContentField($entryType);
        return $entryType;
    }

    protected static function populate(EntryType $entryType): void
    {
        $entryType->name = 'CodePen Embed';
        $entryType->handle = 'codepenEmbed';
        $entryType->icon = 'codepen';
        $entryType->color = Color::Emerald;
        $entryType->showStatusField = false;
        $entryType->showSlugField = false;

        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [
                    new CustomField(LinkUrl::get(), [
                        'label' => 'Pen URL',
                        'handle' => 'penUrl',
                        'includeInCards' => true,
                    ]),
                ],
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);
    }
}
