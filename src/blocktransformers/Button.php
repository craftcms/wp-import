<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;
use craft\wpimport\generators\entrytypes\Button as ButtonEntryType;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Button extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/button';
    }

    public function render(array $data, Entry $entry): string
    {
        $node = (new Crawler($data['innerHTML']))->filter('a');
        if (!$node->count()) {
            return '';
        }
        $label = $node->text();
        $url = $node->attr('href');

        return $this->command->createNestedEntry($entry, function(Entry $nestedEntry) use ($label, $url) {
            $nestedEntry->setTypeId(ButtonEntryType::get()->id);
            $nestedEntry->title = $label;
            $nestedEntry->setFieldValue('buttonUrl', $url);
        });
    }
}
