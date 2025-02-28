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
class Table extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/table';
    }

    public function render(array $data, Entry $entry): string
    {
        // render a new <figure> with `class="table"`
        $node = (new Crawler($data['innerHTML']))->filter('table');
        if (!$node->count()) {
            return '';
        }
        return Html::tag('figure', $node->outerHtml(), ['class' => 'table']);
    }
}
