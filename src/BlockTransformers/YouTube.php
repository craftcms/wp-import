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

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class YouTube extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core-embed/youtube';
    }

    public function render(array $data, Entry $entry): string
    {
        return Html::beginTag('figure', ['class' => 'media']) .
            Html::tag('oembed', '', ['url' => $data['attrs']['url']]) .
            Html::endTag('figure');
    }
}
