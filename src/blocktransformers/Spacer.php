<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\helpers\Html as HtmlHelper;
use craft\wpimport\BaseBlockTransformer;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Spacer extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/spacer';
    }

    public function render(array $data, Entry $entry): string
    {
        // replace with empty paragraphs
        $paragraphs = (int)round((int)($data['attrs']['height'] ?? 0) / 22);
        return str_repeat(HtmlHelper::tag('p', '&nbsp;'), $paragraphs);
    }
}
