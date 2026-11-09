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
class Paragraph extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/paragraph';
    }

    public function render(array $data, Entry $entry): string
    {
        // Convert double <br>s into multiple paragraphs
        return str_replace('<br><br>', '</p><p>', $data['innerHTML']);
    }
}
