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
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class GBHeadline extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'generateblocks/headline';
    }

    public function render(array $data, Entry $entry): string
    {
        $node = (new Crawler($data['innerHTML']))->filter('h1,h2,h3,h4,h5,h6,p,div');
        if (! $node->count()) {
            return '';
        }

        $tag = $node->nodeName();
        if ($tag === 'div') {
            $tag = 'p';
        }

        return HtmlHelper::tag($tag, $node->html());
    }
}
