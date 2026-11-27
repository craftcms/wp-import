<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Html as HtmlHelper;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Html extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/html';
    }

    public function render(array $data, Entry $entry): string
    {
        return HtmlHelper::tag('div', $data['innerHTML'], [
            'class' => 'raw-html-embed',
        ]);
    }
}
