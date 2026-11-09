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
use Illuminate\Support\Collection;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Gallery extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/gallery';
    }

    public function render(array $data, Entry $entry): string
    {
        $assetIds = Collection::make($data['innerBlocks'])
            ->filter(fn (array $block) => $block['blockName'] === 'core/image')
            ->map(function (array $block) {
                try {
                    return $this->command->import(Media::SLUG, $block['attrs']['id']);
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->all();

        if (empty($assetIds)) {
            return '';
        }

        return $this->command->createNestedMediaEntry($entry, $assetIds);
    }
}
