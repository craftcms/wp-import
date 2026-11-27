<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Block extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/block';
    }

    public function render(array $data, Entry $entry): string
    {
        $this->command->warn('`core/block` blocks aren’t supported');

        return '';
    }
}
