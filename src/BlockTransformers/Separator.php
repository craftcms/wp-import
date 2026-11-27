<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Html;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Separator extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/separator';
    }

    public function render(array $data, Entry $entry): string
    {
        return Html::tag('hr');
    }
}
