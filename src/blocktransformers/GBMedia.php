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
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class GBMedia extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'generateblocks/media';
    }

    public function render(array $data, Entry $entry): string
    {
        $id = $data['attrs']['mediaId'] ?? null;
        if (!$id) {
            return '';
        }

        try {
            if (!empty($data['attrs']['htmlAttributes']['src'])) {
                $assetId = $this->command->import(Media::SLUG, [
                    'id' => $id,
                    'source_url' => $data['attrs']['htmlAttributes']['src'],
                    'title' => ['raw' => $data['attrs']['htmlAttributes']['title'] ?? null],
                    'alt_text' => $data['attrs']['htmlAttributes']['alt'] ?? null,
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
