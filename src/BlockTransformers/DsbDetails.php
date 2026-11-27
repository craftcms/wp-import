<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\WpImport\Generators\EntryTypes\Details as DetailsEntryType;
use CraftCms\WpImport\Generators\Fields\Summary;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class DsbDetails extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'dsb/details-summary-block';
    }

    public function render(array $data, Entry $entry): string
    {
        return $this->command->createNestedEntry($entry, function (Entry $nestedEntry) use ($data) {
            $nestedEntry->setTypeId(DetailsEntryType::get()->id);

            $node = (new Crawler($data['innerHTML']))->filter('summary');
            if ($node->count()) {
                $nestedEntry->setFieldValue(Summary::get()->handle, $node->html());
            }

            // save it so we get an ID, before parsing the nested blocks
            $this->command->saveNestedEntry($nestedEntry);

            $nestedEntry->setFieldValue(
                'postContent',
                $this->command->renderBlocks($data['innerBlocks'], $nestedEntry),
            );
        });
    }
}
