<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\volumes;

use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Volume;
use craft\wpimport\generators\fields\Caption;
use craft\wpimport\generators\fields\Description;
use craft\wpimport\generators\fields\WpId;
use craft\wpimport\generators\filesystems\Uploads as UploadsFs;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Uploads extends BaseVolumeGenerator
{
    protected static function uid(): string
    {
        return 'ba83893c-c85d-4d01-81a2-0dfc8723fb5d';
    }

    protected static function populate(Volume $volume): void
    {
        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [
                    new AltField(),
                    new CustomField(Caption::get()),
                    new CustomField(Description::get()),
                ],
            ]),
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Meta',
                'elements' => [
                    new CustomField(WpId::get()),
                ],
            ]),
        ]);

        $volume->name = 'Uploads';
        $volume->handle = 'uploads';
        $volume->setFsHandle(UploadsFs::get()->handle);
        $volume->setFieldLayout($fieldLayout);
    }
}
