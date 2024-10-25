<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\categorygroups;

use Craft;
use craft\fieldlayoutelements\CustomField;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Site;
use craft\wpimport\BaseCategoryGroupGenerator;
use craft\wpimport\generators\fields\WpId;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Categories extends BaseCategoryGroupGenerator
{
    protected static function uid(): string
    {
        return '89d90c8f-05eb-4b35-970f-a1568dd6713b';
    }

    protected static function populate(CategoryGroup $group): void
    {
        $group->name = 'Categories';
        $group->handle = 'categories';

        $group->setSiteSettings(array_map(fn(Site $site) => new CategoryGroup_SiteSettings([
            'siteId' => $site->id,
        ]), Craft::$app->sites->getAllSites(true)));

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
