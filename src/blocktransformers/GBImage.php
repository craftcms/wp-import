<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;
use craft\wpimport\importers\Media;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class GBImage extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'generateblocks/image';
    }

    public function render(array $data, Entry $entry): string
    {
        $id = $data['attrs']['mediaId'] ?? null;
        if (!$id) {
            return '';
        }

        $img = (new Crawler($data['innerHTML']))->filter('img');
        $src = $img->attr('src');

        try {
            if ($src) {
                $assetId = $this->command->import(Media::SLUG, [
                    'id' => $id,
                    'source_url' => $src,
                    'title' => $img->attr('title'),
                    'alt_text' => $img->attr('alt'),
                ]);
            } else {
                $assetId = $this->command->import(Media::SLUG, $id);
            }
        } catch (Throwable) {
            return '';
        }

        return $this->command->createNestedMediaEntry($entry, $assetId);
    }
}
