<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\taggroups;

use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\TagGroup;
use craft\wpimport\BaseTagGroupGenerator;
use craft\wpimport\generators\fields\WpId;

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

        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'WordPress',
                'elements' => [
                    new CustomField(WpId::get()),
                ],
            ]),
        ]);
        $group->setFieldLayout($fieldLayout);
    }
}
