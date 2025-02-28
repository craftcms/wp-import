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
class GBElement extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'generateblocks/element';
    }

    public function render(array $data, Entry $entry): string
    {
        if (empty($data['innerBlocks'])) {
            return '';
        }

        return $this->command->renderBlocks($data['innerBlocks'], $entry);
    }
}
