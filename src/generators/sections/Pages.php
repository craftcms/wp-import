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
use craft\wpimport\generators\entrytypes\Page;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Pages extends BaseSectionGenerator
{
    protected static function uid(): string
    {
        return 'b5e87e4c-f83a-40a6-bf98-9db86b6383fb';
    }

    protected static function populate(Section $section): void
    {
        $section->name = 'Pages';
        $section->handle = 'pages';
        $section->type = Section::TYPE_STRUCTURE;
        $section->setEntryTypes([Page::get()]);
        $section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => Craft::$app->sites->getPrimarySite()->id,
                'hasUrls' => true,
                'uriFormat' => '{slug}',
                'template' => '_page.twig',
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
