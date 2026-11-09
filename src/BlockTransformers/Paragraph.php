<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;

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
