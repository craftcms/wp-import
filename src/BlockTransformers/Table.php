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
        if (! $node->count()) {
            return '';
        }

        return Html::tag('figure', $node->outerHtml(), ['class' => 'table']);
    }
}
