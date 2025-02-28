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
class Heading extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/heading';
    }

    public function render(array $data, Entry $entry): string
    {
        // render a new heading tag without `class="wp-block-heading"`
        // (can't rely on `$data['attrs']['level']` unfortunately)
        $node = (new Crawler($data['innerHTML']))->filter('h1,h2,h3,h4,h5,h6');
        if (!$node->count()) {
            return '';
        }
        return Html::tag($node->nodeName(), $node->html());
    }
}
