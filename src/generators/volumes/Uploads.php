<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\volumes;

use Craft;
use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\Volume;
use craft\wpimport\Command;
use craft\wpimport\generators\fields\Caption;
use craft\wpimport\generators\fields\Description;
use craft\wpimport\generators\fields\WpId;
use craft\wpimport\generators\fields\WpTitle;
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
        $volume->name = 'Uploads';
        $volume->handle = 'uploads';
        $volume->setFsHandle(UploadsFs::get()->handle);
    }

    protected static function updateFieldLayout(FieldLayout $fieldLayout): void
    {
        /** @var Command $command */
        $command = Craft::$app->controller;
        $command->addElementsToLayout($fieldLayout, 'Content', [
            new AltField(),
            new CustomField(Caption::get()),
            new CustomField(Description::get()),
        ], true, true);
        $command->addElementsToLayout($fieldLayout, 'Meta', [
            new CustomField(WpId::get()),
            new CustomField(WpTitle::get()),
        ]);
    }
}
