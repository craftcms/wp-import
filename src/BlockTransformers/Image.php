<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\WpImport\Importers\Media;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Image extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/image';
    }

    public function render(array $data, Entry $entry): string
    {
        $id = $data['attrs']['id'] ?? null;
        if (! $id) {
            return '';
        }

        try {
            $assetId = $this->command->import(Media::SLUG, $id);
        } catch (Throwable) {
            return '';
        }

        // See if there's a caption
        $node = (new Crawler($data['innerHTML']))->filter('figcaption');
        if ($node->count()) {
            $caption = $node->html();
        } else {
            $caption = null;
        }

        return $this->command->createNestedMediaEntry($entry, $assetId, $caption);
    }
}
