<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;
use craft\wpimport\generators\entrytypes\PullQuote as PullQuoteEntryType;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PullQuote extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/pullquote';
    }

    public function render(array $data, Entry $entry): string
    {
        $crawler = new Crawler($data['innerHTML']);
        $paragraphNodes = $crawler->filter('p');
        $citeNodes = $crawler->filter('cite');

        if (!$paragraphNodes->count() && !$citeNodes->count()) {
            return '';
        }

        $html = '';
        $paragraphNodes->each(function(Crawler $node) use (&$html) {
            $html .= $node->outerHtml();
        });

        $cite = $citeNodes->text();

        return $this->createNestedEntry($entry, function(Entry $nestedEntry) use ($html, $cite) {
            $nestedEntry->setTypeId(PullQuoteEntryType::get()->id);
            $nestedEntry->setFieldValue('pullQuote', $html);
            $nestedEntry->setFieldvalue('citation', $cite);
        });
    }
}
