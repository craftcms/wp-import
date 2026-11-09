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
class Video extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/video';
    }

    public function render(array $data, Entry $entry): string
    {
        $id = $data['attrs']['id'] ?? null;
        if (!$id) {
            return '';
        }

        try {
            $assetId = $this->command->import(Media::SLUG, $id);
        } catch (Throwable) {
            return '';
        }

        return $this->command->createNestedMediaEntry($entry, $assetId);
    }
}
