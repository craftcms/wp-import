<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\helpers\Console;
use craft\wpimport\BaseBlockTransformer;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Block extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/block';
    }

    public function render(array $data, Entry $entry): string
    {
        $this->command->output('`core/block` blocks aren’t supported ', Console::FG_YELLOW);
        return '';
    }
}
