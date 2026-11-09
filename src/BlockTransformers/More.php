<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class More extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/more';
    }

    public function render(array $data, Entry $entry): string
    {
        return '<div class="page-break" style="page-break-after:always;"><span style="display:none;">&nbsp;</span></div>';
    }
}
