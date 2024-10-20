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
class Html extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/html';
    }

    public function render(array $data, Entry $entry): string
    {
        return HtmlHelper::tag('div', $data['innerHTML'], [
            'class' => 'raw-html-embed',
        ]);
    }
}
