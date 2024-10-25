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
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fieldlayoutelements\HorizontalRule;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\wpimport\BaseEntryTypeGenerator;
use craft\wpimport\Command;
use craft\wpimport\generators\fields\Caption;
use craft\wpimport\generators\fields\Categories;
use craft\wpimport\generators\fields\Color as ColorField;
use craft\wpimport\generators\fields\Comments;
use craft\wpimport\generators\fields\Description;
use craft\wpimport\generators\fields\Format;
use craft\wpimport\generators\fields\Media as MediaField;
use craft\wpimport\generators\fields\PostContent;
use craft\wpimport\generators\fields\Sticky;
use craft\wpimport\generators\fields\Tags;
use craft\wpimport\generators\fields\WpId;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Post extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return 'b701559d-352a-4a55-80af-30d65b624292';
    }

    protected static function populate(EntryType $entryType): void
    {
        /** @var Command $command */
        $command = Craft::$app->controller;

        $entryType->name = 'Post';
        $entryType->handle = 'post';
        $entryType->icon = 'pen-nib';
        $entryType->color = Color::Blue;

        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [
                    new EntryTitleField([
                        'required' => false,
                    ]),
                    new CustomField(PostContent::get()),
                ],
            ]),
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Cover Photo',
                'elements' => [
                    new CustomField(Description::get(), [
                        'label' => 'Cover Text',
                        'handle' => 'coverText',
                    ]),
                    new CustomField(MediaField::get(), [
                        'label' => 'Cover Photo',
                        'handle' => 'coverPhoto',
                    ]),
                    new CustomField(ColorField::get(), [
                        'label' => 'Cover Overlay Color',
                        'handle' => 'coverOverlayColor',
                    ]),
                ],
            ]),
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Meta',
                'elements' => array_filter([
                    new CustomField(MediaField::get(), [
                        'label' => 'Featured Image',
                        'handle' => 'featuredImage',
                    ]),
                    new Customfield(Caption::get(), [
                        'label' => 'Excerpt',
                        'handle' => 'excerpt',
                    ]),
                    new HorizontalRule(),
                    $command->importComments ? new CustomField(Comments::get()) : null,
                    new CustomField(Format::get()),
                    new CustomField(Sticky::get()),
                    new HorizontalRule(),
                    new CustomField(Categories::get()),
                    new CustomField(Tags::get()),
                ]),
            ]),
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'WordPress',
                'elements' => [
                    new CustomField(WpId::get()),
                ],
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);
    }
}
