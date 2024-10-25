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
use craft\wpimport\generators\fields\Color as ColorField;
use craft\wpimport\generators\fields\Comments;
use craft\wpimport\generators\fields\Description;
use craft\wpimport\generators\fields\Media as MediaField;
use craft\wpimport\generators\fields\PostContent;
use craft\wpimport\generators\fields\Template;
use craft\wpimport\generators\fields\WpId;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Page extends BaseEntryTypeGenerator
{
    protected static function uid(): string
    {
        return '9ca3795a-ec88-4bec-a4e0-9839113a6c48';
    }

    protected static function populate(EntryType $entryType): void
    {
        /** @var Command $command */
        $command = Craft::$app->controller;

        $entryType->name = 'Page';
        $entryType->handle = 'page';
        $entryType->icon = 'page';
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
                    new HorizontalRule(),
                    $command->importComments ? new CustomField(Comments::get()) : null,
                ]),
            ]),
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'WordPress',
                'elements' => [
                    new CustomField(WpId::get()),
                    new CustomField(Template::get()),
                ],
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);
    }
}
