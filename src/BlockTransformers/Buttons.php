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
class Buttons extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/buttons';
    }

    public function render(array $data, Entry $entry): string
    {
        return $this->command->renderBlocks($data['innerBlocks'], $entry);
    }
}
