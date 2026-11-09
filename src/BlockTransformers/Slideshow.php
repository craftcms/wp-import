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
class Slideshow extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'jetpack/slideshow';
    }

    public function render(array $data, Entry $entry): string
    {
        $assetIds = Collection::make($data['attrs']['ids'])
            ->map(function (int $id) {
                try {
                    return $this->command->import(Media::SLUG, $id);
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
