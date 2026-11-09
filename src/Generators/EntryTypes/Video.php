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
class Video extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return 'c34c148c-8e45-4867-b6c2-1ad69ba00f5e';
    }

    protected static function create(): EntryType
    {
        $entryType = parent::create();
        static::addToPostContentField($entryType);
        return $entryType;
    }

    protected static function populate(EntryType $entryType): void
    {
        $entryType->name = 'Video';
        $entryType->handle = 'video';
        $entryType->icon = 'youtube';
        $entryType->color = Color::Red;
        $entryType->showStatusField = false;
        $entryType->showSlugField = false;

        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [
                    new CustomField(LinkUrl::get(), [
                        'handle' => 'videoUrl',
                        'includeInCards' => true,
                    ]),
                ],
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);
    }
}
