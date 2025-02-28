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
class Blockquote extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/quote';
    }

    public function render(array $data, Entry $entry): string
    {
        $content = $this->command->renderBlocks($data['innerBlocks'], $entry);

        // is there a <cite> tag in innerHTML?
        $node = (new Crawler($data['innerHTML']))->filter('cite');
        if ($node->count()) {
            $content .= $node->outerHtml();
        }

        return Html::tag('blockquote', $content);
    }
}
