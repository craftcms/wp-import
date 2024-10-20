<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\helpers\Html;
use craft\wpimport\BaseBlockTransformer;
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
        $nodes = (new Crawler($data['innerHTML']))->filter('pre');
        if (!$nodes->count()) {
            return '';
        }
        return Html::beginTag('pre') .
            Html::tag('code', $nodes->html(), ['class' => 'language-plaintext']) .
            Html::endTag('pre');
    }
}
