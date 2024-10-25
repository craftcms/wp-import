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
use craft\wpimport\BaseEntryTypeGenerator;
use craft\wpimport\generators\fields\PostContent;
use craft\wpimport\generators\fields\Summary;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Details extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return '354a145a-dc30-40c6-abe4-35c0fb93abeb';
    }

    protected static function create(): EntryType
    {
        $entryType = parent::create();
        static::addToPostContentField($entryType);
        return $entryType;
    }

    protected static function populate(EntryType $entryType): void
    {
        $entryType->name = 'Details';
        $entryType->handle = 'details';
        $entryType->icon = 'chevron-down';
        $entryType->color = Color::Fuchsia;
        $entryType->showStatusField = false;
        $entryType->showSlugField = false;

        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [
                    new CustomField(Summary::get(), [
                        'includeInCards' => true,
                    ]),
                    new CustomField(PostContent::get()),
                ],
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);
    }
}
