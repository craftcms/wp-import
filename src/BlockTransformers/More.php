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
class More extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/more';
    }

    public function render(array $data, Entry $entry): string
    {
        return '<div class="page-break" style="page-break-after:always;"><span style="display:none;">&nbsp;</span></div>';
    }
}
