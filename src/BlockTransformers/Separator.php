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
class Separator extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/separator';
    }

    public function render(array $data, Entry $entry): string
    {
        return Html::tag('hr');
    }
}
