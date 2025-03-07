<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\entrytypes;

use Craft;
use craft\enums\Color;
use craft\fieldlayoutelements\CustomField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\wpimport\Command;
use craft\wpimport\generators\fields\Caption;
use craft\wpimport\generators\fields\Media as MediaField;
use craft\wpimport\generators\fields\RichCaption;

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

        /** @var Command $command */
        $command = Craft::$app->controller;
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
