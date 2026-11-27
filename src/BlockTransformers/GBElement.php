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
class GBElement extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'generateblocks/element';
    }

    public function render(array $data, Entry $entry): string
    {
        if (empty($data['innerBlocks'])) {
            return '';
        }

        return $this->command->renderBlocks($data['innerBlocks'], $entry);
    }
}
