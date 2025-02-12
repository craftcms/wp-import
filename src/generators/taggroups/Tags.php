<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\taggroups;

use Craft;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\TagGroup;
use craft\wpimport\Command;
use craft\wpimport\generators\fields\WpId;
use craft\wpimport\generators\fields\WpTitle;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Tags extends BaseTagGroupGenerator
{
    protected static function uid(): string
    {
        return '677c2b2b-14a7-4692-8bb8-b4dc667e1a12';
    }

    protected static function populate(TagGroup $group): void
    {
        $group->name = 'Tags';
        $group->handle = 'tags';
    }

    protected static function updateFieldLayout(FieldLayout $fieldLayout): void
    {
        /** @var Command $command */
        $command = Craft::$app->controller;
        $command->addElementsToLayout($fieldLayout, 'Meta', [
            new CustomField(WpId::get()),
            new CustomField(WpTitle::get()),
        ]);
    }
}
