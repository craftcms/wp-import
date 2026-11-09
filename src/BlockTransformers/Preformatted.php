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
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Preformatted extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/preformatted';
    }

    public function render(array $data, Entry $entry): string
    {
        // `<pre class="wp-block-preformatted">` → `<pre><code>`
        $node = (new Crawler($data['innerHTML']))->filter('pre');
        if (! $node->count()) {
            return '';
        }

        return Html::beginTag('pre').
            Html::tag('code', $node->html(), ['class' => 'language-plaintext']).
            Html::endTag('pre');
    }
}
