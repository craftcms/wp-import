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
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Audio extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/audio';
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

        return $this->command->createNestedMediaEntry($entry, $assetId);
    }
}
