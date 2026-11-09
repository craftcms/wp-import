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
class Vimeo extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core-embed/vimeo';
    }

    public function render(array $data, Entry $entry): string
    {
        return Html::beginTag('figure', ['class' => 'media']).
            Html::tag('oembed', '', ['url' => $data['attrs']['url']]).
            Html::endTag('figure');
    }
}
