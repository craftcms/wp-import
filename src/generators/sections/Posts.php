<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\sections;

use Craft;
use craft\elements\Entry;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\wpimport\BaseSectionGenerator;
use craft\wpimport\generators\entrytypes\Post;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Posts extends BaseSectionGenerator
{
    protected static function uid(): string
    {
        return '3358a830-9ada-487d-a170-01751e921710';
    }

    protected static function populate(Section $section): void
    {
        $section->name = 'Posts';
        $section->handle = 'posts';
        $section->type = Section::TYPE_CHANNEL;
        $section->setEntryTypes([Post::get()]);
        $section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => Craft::$app->sites->getPrimarySite()->id,
                'uriFormat' => '{slug}',
            ]),
        ]);
        $section->previewTargets = [
            [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => Entry::lowerDisplayName(),
                ]),
                'urlFormat' => '{url}',
            ],
        ];
    }
}
